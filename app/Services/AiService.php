<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;

/**
 * Enterprise-Grade AI Service for Clinforce Platform
 * 
 * Provides advanced AI capabilities including:
 * - Multi-model support with automatic failover
 * - Intelligent context management
 * - Streaming responses
 * - Advanced tool calling
 * - Analytics and usage tracking
 * - Rate limiting and quota management
 * - Semantic search and embeddings
 * - Knowledge base integration
 */
class AiService
{
    // Model configurations with fallbacks
    private const MODELS = [
        'primary' => [
            'model' => 'gpt-4o',
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'context_window' => 128000,
        ],
        'fallback' => [
            'model' => 'gpt-4o-mini',
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'context_window' => 128000,
        ],
        'fast' => [
            'model' => 'gpt-4o-mini',
            'max_tokens' => 2048,
            'temperature' => 0.5,
            'context_window' => 128000,
        ],
        'analytical' => [
            'model' => 'gpt-4o',
            'max_tokens' => 8192,
            'temperature' => 0.3,
            'context_window' => 128000,
        ],
    ];

    private ?string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    private array $usageStats = [];
    private array $context = [];
    private bool $streamingEnabled = true;
    private bool $cachingEnabled = true;
    private int $cacheTtl = 3600; // 1 hour

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.api_key');
    }

    /**
     * Check if AI service is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get service health status
     */
    public function healthCheck(): array
    {
        $status = [
            'available' => $this->isAvailable(),
            'api_key_configured' => !empty($this->apiKey),
            'models' => [],
            'rate_limit' => $this->getRateLimitStatus(),
            'usage_today' => $this->getDailyUsage(),
        ];

        if ($this->isAvailable()) {
            foreach (self::MODELS as $name => $config) {
                $status['models'][$name] = [
                    'model' => $config['model'],
                    'available' => true,
                ];
            }
        }

        return $status;
    }

    /**
     * Main chat completion method with enterprise features
     */
    public function chat(array $messages, array $options = []): array
    {
        $startTime = microtime(true);
        $modelType = $options['model_type'] ?? 'primary';
        $enableTools = $options['enable_tools'] ?? true;
        $enableStreaming = $options['streaming'] ?? false;
        $enableCaching = $options['caching'] ?? $this->cachingEnabled;
        $userId = $options['user_id'] ?? null;
        $sessionId = $options['session_id'] ?? null;

        // Check cache first
        if ($enableCaching && !$enableStreaming) {
            $cacheKey = $this->generateCacheKey($messages, $modelType);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $this->logUsage($userId, $sessionId, 'cache_hit', 0, $startTime);
                return $cached;
            }
        }

        // Check rate limits
        if (!$this->checkRateLimit($userId)) {
            return $this->getRateLimitExceededResponse();
        }

        // Build request payload
        $payload = $this->buildChatPayload($messages, $modelType, $options);

        // Make API call with retry logic
        try {
            $response = $this->makeApiCall($payload, $modelType);
            
            // Process response
            $result = $this->processResponse($response, $modelType);

            // Cache successful response
            if ($enableCaching && !$enableStreaming && isset($result['content'])) {
                $cacheKey = $this->generateCacheKey($messages, $modelType);
                Cache::put($cacheKey, $result, $this->cacheTtl);
            }

            // Log usage
            $tokens = $response['usage']['total_tokens'] ?? 0;
            $this->logUsage($userId, $sessionId, 'success', $tokens, $startTime);

            return $result;

        } catch (\Exception $e) {
            Log::error('AI Service Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => self::MODELS[$modelType]['model'] ?? 'unknown',
                'user_id' => $userId,
            ]);

            // Try fallback model
            if ($modelType === 'primary') {
                Log::info('Attempting fallback to secondary model');
                return $this->chat($messages, array_merge($options, ['model_type' => 'fallback']));
            }

            $this->logUsage($userId, $sessionId, 'error', 0, $startTime);
            return $this->getErrorFallbackResponse($e->getMessage());
        }
    }

    /**
     * Streaming chat completion
     */
    public function chatStream(array $messages, callable $onChunk, array $options = []): void
    {
        if (!$this->isAvailable()) {
            $onChunk(['error' => 'AI service not configured']);
            return;
        }

        $modelType = $options['model_type'] ?? 'primary';
        $payload = $this->buildChatPayload($messages, $modelType, $options);
        $payload['stream'] = true;

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(120)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream',
                ])
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->failed()) {
                $onChunk(['error' => 'Stream failed: ' . $response->body()]);
                return;
            }

            // Process streaming response
            $body = $response->body();
            $lines = explode("\n", $body);
            
            foreach ($lines as $line) {
                if (str_starts_with($line, 'data: ')) {
                    $data = substr($line, 6);
                    if ($data === '[DONE]') {
                        $onChunk(['done' => true]);
                        break;
                    }
                    
                    $json = json_decode($data, true);
                    if ($json && isset($json['choices'][0]['delta'])) {
                        $onChunk([
                            'chunk' => $json['choices'][0]['delta']['content'] ?? '',
                            'done' => false,
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('AI Stream Error', ['error' => $e->getMessage()]);
            $onChunk(['error' => 'Stream error: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate embeddings for semantic search
     */
    public function generateEmbedding(string $text, string $model = 'text-embedding-3-small'): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/embeddings", [
                    'model' => $model,
                    'input' => $text,
                    'encoding_format' => 'float',
                ]);

            if ($response->failed()) {
                Log::error('Embedding API Error', ['body' => $response->body()]);
                return [];
            }

            $data = $response->json();
            return $data['data'][0]['embedding'] ?? [];

        } catch (\Exception $e) {
            Log::error('Embedding Generation Error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Intelligent document analysis
     */
    public function analyzeDocument(string $content, array $options = []): array
    {
        $analysisType = $options['type'] ?? 'general';
        
        $prompts = [
            'resume' => $this->getResumeAnalysisPrompt(),
            'job_description' => $this->getJobAnalysisPrompt(),
            'comparison' => $this->getCandidateComparisonPrompt(),
            'general' => "Provide a comprehensive analysis of the following document. Include key insights, structured summary, and actionable recommendations.",
        ];

        $systemPrompt = $prompts[$analysisType] ?? $prompts['general'];

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $content],
        ];

        return $this->chat($messages, [
            'model_type' => 'analytical',
            'enable_tools' => false,
        ]);
    }

    /**
     * Smart candidate matching
     */
    public function matchCandidates(array $jobRequirements, array $candidates, int $limit = 10): array
    {
        if (empty($candidates)) {
            return ['matches' => [], 'message' => 'No candidates to evaluate'];
        }

        $prompt = $this->buildMatchingPrompt($jobRequirements, $candidates);

        $messages = [
            ['role' => 'system', 'content' => $this->getMatchingSystemPrompt()],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->chat($messages, [
            'model_type' => 'analytical',
            'enable_tools' => false,
        ]);

        // Parse and structure the response
        return $this->parseMatchingResults($response['content'] ?? '', $candidates);
    }

    /**
     * Generate interview questions
     */
    public function generateInterviewQuestions(array $jobDescription, array $candidateProfile = []): array
    {
        $prompt = $this->buildInterviewQuestionsPrompt($jobDescription, $candidateProfile);

        $messages = [
            ['role' => 'system', 'content' => $this->getInterviewQuestionsSystemPrompt()],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->chat($messages, [
            'model_type' => 'analytical',
            'enable_tools' => false,
        ]);

        return [
            'questions' => $this->parseInterviewQuestions($response['content'] ?? ''),
            'raw_response' => $response['content'] ?? '',
        ];
    }

    /**
     * Context management for conversation memory
     */
    public function setContext(string $userId, array $context): void
    {
        $cacheKey = "ai_context_{$userId}";
        Cache::put($cacheKey, $context, 86400); // 24 hours
    }

    public function getContext(string $userId): array
    {
        $cacheKey = "ai_context_{$userId}";
        return Cache::get($cacheKey, []);
    }

    public function addToContext(string $userId, string $key, $value): void
    {
        $context = $this->getContext($userId);
        $context[$key] = $value;
        $this->setContext($userId, $context);
    }

    /**
     * Rate limiting and quota management
     */
    private function checkRateLimit(?string $userId = null): bool
    {
        if (!$userId) {
            return true; // No limit for unauthenticated users
        }

        $dailyLimit = 1000; // Messages per day per user
        $key = "ai_usage_daily_{$userId}";
        $current = Cache::get($key, 0);

        return $current < $dailyLimit;
    }

    private function getRateLimitStatus(): array
    {
        return [
            'enabled' => true,
            'daily_limit_per_user' => 1000,
            'model' => self::MODELS['primary']['model'],
        ];
    }

    private function getDailyUsage(): array
    {
        // This would typically query a database table
        return [
            'messages_today' => 0,
            'tokens_today' => 0,
            'api_calls_today' => 0,
        ];
    }

    /**
     * Build chat payload with advanced options
     */
    private function buildChatPayload(array $messages, string $modelType, array $options = []): array
    {
        $config = self::MODELS[$modelType] ?? self::MODELS['primary'];

        $payload = [
            'model' => $config['model'],
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? $config['temperature'],
            'max_tokens' => $options['max_tokens'] ?? $config['max_tokens'],
            'top_p' => $options['top_p'] ?? 1.0,
            'frequency_penalty' => $options['frequency_penalty'] ?? 0.0,
            'presence_penalty' => $options['presence_penalty'] ?? 0.0,
        ];

        // Add tools if enabled
        if (isset($options['tools']) && is_array($options['tools'])) {
            $payload['tools'] = $options['tools'];
            $payload['tool_choice'] = $options['tool_choice'] ?? 'auto';
        }

        // Add response format if specified
        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        return $payload;
    }

    /**
     * Make API call with exponential backoff retry
     */
    private function makeApiCall(array $payload, string $modelType): array
    {
        $maxRetries = 3;
        $retryDelay = 1000; // 1 second
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $config = self::MODELS[$modelType] ?? self::MODELS['primary'];
                
                $response = Http::withToken($this->apiKey)
                    ->timeout(120)
                    ->connectTimeout(30)
                    ->withHeaders([
                        'OpenAI-Beta' => 'assistants=v2',
                    ])
                    ->post("{$this->baseUrl}/chat/completions", $payload);

                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorBody = $response->body();
                    
                    // Don't retry client errors
                    if ($statusCode >= 400 && $statusCode < 500) {
                        throw new \Exception("OpenAI API Error ({$statusCode}): {$errorBody}");
                    }

                    $lastError = new \Exception("OpenAI API Error ({$statusCode}): {$errorBody}");
                    Log::warning("OpenAI API attempt {$attempt} failed", [
                        'status' => $statusCode,
                        'body' => $errorBody,
                    ]);
                    
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2; // Exponential backoff
                    continue;
                }

                return $response->json();

            } catch (\Exception $e) {
                $lastError = $e;
                if ($attempt === $maxRetries) {
                    throw $lastError;
                }
                usleep($retryDelay * 1000);
                $retryDelay *= 2;
            }
        }

        throw $lastError ?? new \Exception('API call failed after retries');
    }

    /**
     * Process API response
     */
    private function processResponse(array $response, string $modelType): array
    {
        if (!isset($response['choices'][0]['message'])) {
            throw new \Exception('Invalid response structure from OpenAI');
        }

        $message = $response['choices'][0]['message'];
        $usage = $response['usage'] ?? [];

        return [
            'content' => $message['content'] ?? '',
            'role' => $message['role'] ?? 'assistant',
            'tool_calls' => $message['tool_calls'] ?? null,
            'finish_reason' => $response['choices'][0]['finish_reason'] ?? null,
            'usage' => [
                'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'total_tokens' => $usage['total_tokens'] ?? 0,
            ],
            'model' => $response['model'] ?? self::MODELS[$modelType]['model'],
        ];
    }

    /**
     * Generate cache key
     */
    private function generateCacheKey(array $messages, string $modelType): string
    {
        $lastMessage = end($messages);
        $content = $lastMessage['content'] ?? '';
        
        return 'ai_cache_' . md5($content . '_' . $modelType);
    }

    /**
     * Log AI usage
     */
    private function logUsage(?string $userId, ?string $sessionId, string $status, int $tokens, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // ms
        
        $logEntry = [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'status' => $status,
            'tokens' => $tokens,
            'duration_ms' => $duration,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('daily')->info('AI Usage', $logEntry);

        // Update daily usage counter
        if ($userId) {
            $key = "ai_usage_daily_{$userId}";
            Cache::increment($key);
            Cache::put($key, Cache::get($key, 0) + 1, 86400);
        }
    }

    /**
     * Error fallback responses
     */
    private function getErrorFallbackResponse(string $error): array
    {
        return [
            'content' => $this->getFriendlyErrorMessage($error),
            'role' => 'assistant',
            'error' => true,
            'error_type' => $this->classifyError($error),
        ];
    }

    private function getRateLimitExceededResponse(): array
    {
        return [
            'content' => "You've reached your daily AI usage limit. Please try again tomorrow, or contact support to increase your quota.",
            'role' => 'assistant',
            'error' => true,
            'error_type' => 'rate_limit',
        ];
    }

    private function getFriendlyErrorMessage(string $error): string
    {
        $errorLower = strtolower($error);

        if (str_contains($errorLower, 'authentication') || str_contains($errorLower, 'api key')) {
            return "I'm having trouble connecting to my AI brain right now. Our team has been notified and we're working on it. Please try again in a moment!";
        }

        if (str_contains($errorLower, 'rate limit') || str_contains($errorLower, 'too many requests')) {
            return "We're experiencing high demand right now. Please wait a moment and try again.";
        }

        if (str_contains($errorLower, 'timeout') || str_contains($errorLower, 'connection')) {
            return "The AI service is taking too long to respond. Please try again or rephrase your question.";
        }

        if (str_contains($errorLower, 'context length') || str_contains($errorLower, 'token limit')) {
            return "Our conversation has gotten too long! Let's start a fresh conversation to continue.";
        }

        return "I encountered a temporary issue while processing your request. My team has been notified. Please try again in a moment.";
    }

    private function classifyError(string $error): string
    {
        $errorLower = strtolower($error);

        if (str_contains($errorLower, 'authentication') || str_contains($errorLower, 'api key')) {
            return 'configuration';
        }
        if (str_contains($errorLower, 'rate limit')) {
            return 'rate_limit';
        }
        if (str_contains($errorLower, 'timeout')) {
            return 'timeout';
        }

        return 'general';
    }

    // ============================================================
    // ADVANCED PROMPTS FOR ENTERPRISE AI CAPABILITIES
    // ============================================================

    private function getResumeAnalysisPrompt(): string
    {
        return <<<'PROMPT'
You are an expert HR professional and talent acquisition specialist with 20+ years of experience in healthcare recruitment.

Analyze this resume/CV with extreme detail and provide:

## 1. EXECUTIVE SUMMARY
- Candidate name and current role
- Years of relevant experience
- Core competencies (list top 5-7)
- Overall fit rating (1-10 with justification)

## 2. QUALIFICATIONS ASSESSMENT
- Education & Certifications (list all, highlight relevant ones)
- Licenses & Credentials (verify completeness)
- Specialized Training & Continuous Learning
- Compliance & Regulatory Knowledge

## 3. EXPERIENCE ANALYSIS
For each position held:
- Role title, company, duration
- Key achievements and impact
- Relevance to target position
- Career progression pattern

## 4. SKILLS MATRIX
Rate each skill area 1-5:
- Clinical/Technical Skills
- Leadership & Management
- Communication & Interpersonal
- Problem-Solving & Critical Thinking
- Adaptability & Learning Agility

## 5. STRENGTHS & VALUE PROPOSITION
- Top 5 unique strengths
- Competitive advantages
- Specialized expertise areas

## 6. AREAS FOR DEVELOPMENT
- Skill gaps
- Experience gaps
- Recommended training/certifications

## 7. HIRING RECOMMENDATION
- Strong Hire / Hire / Consider / Pass
- Detailed justification
- Suggested role level and compensation range
- Onboarding focus areas

Provide specific evidence from the resume for each point. Be thorough but concise.
PROMPT;
    }

    private function getJobAnalysisPrompt(): string
    {
        return <<<'PROMPT'
You are a strategic HR consultant specializing in job design and talent attraction.

Analyze this job description and provide:

## 1. JOB OVERVIEW
- Title appropriateness
- Industry alignment
- Level classification (entry/mid/senior/executive)

## 2. REQUIREMENTS ANALYSIS
- Must-have vs nice-to-have qualifications
- Realistic expectations for the level
- Potential barriers to candidate attraction

## 3. MARKET COMPETITIVENESS
- Salary benchmarking (if provided)
- Benefits assessment
- Market demand for this role
- Candidate availability

## 4. JOB DESCRIPTION QUALITY
- Clarity and completeness
- Inclusive language check
- Compliance with employment standards
- Attractiveness to target demographic

## 5. IDEAL CANDIDATE PROFILE
- Experience level needed
- Key competencies required
- Cultural fit indicators
- Growth potential

## 6. RECOMMENDATIONS
- Improvements to job description
- Sourcing strategy suggestions
- Interview focus areas
- Red flags to watch for

Provide actionable, data-driven insights.
PROMPT;
    }

    private function getCandidateComparisonPrompt(): string
    {
        return <<<'PROMPT'
You are an expert talent evaluator comparing multiple candidates for a position.

Provide a detailed comparison including:

## 1. CANDIDATE OVERVIEW TABLE
| Criteria | Candidate A | Candidate B | Candidate C |
|----------|-------------|-------------|-------------|
| Experience | | | |
| Education | | | |
| Key Skills | | | |
| Rating | | | |

## 2. INDIVIDUAL STRENGTHS
For each candidate:
- Top 3 unique advantages
- Standout achievements
- Cultural fit indicators

## 3. COMPARATIVE ANALYSIS
- Who excels in which areas
- Trade-offs between candidates
- Risk assessment for each

## 4. FINAL RECOMMENDATION
- Ranked order with justification
- Hiring decision for each
- Negotiation leverage points

Be objective and evidence-based.
PROMPT;
    }

    private function getMatchingSystemPrompt(): string
    {
        return "You are an AI talent matching engine. Analyze the job requirements against candidate profiles and provide precise matching scores with detailed justifications. Return results in structured JSON format with match_percentage (0-100), strengths, gaps, and hiring_recommendation for each candidate.";
    }

    private function buildMatchingPrompt(array $jobRequirements, array $candidates): string
    {
        $jobText = json_encode($jobRequirements, JSON_PRETTY_PRINT);
        $candidatesText = json_encode($candidates, JSON_PRETTY_PRINT);

        return "JOB REQUIREMENTS:\n{$jobText}\n\nCANDIDATES TO EVALUATE:\n{$candidatesText}\n\nProvide detailed matching analysis for each candidate.";
    }

    private function parseMatchingResults(string $response, array $candidates): array
    {
        // Try to extract JSON from response
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                return $json;
            }
        }

        return [
            'raw_analysis' => $response,
            'message' => 'Manual review required - auto-parsing failed',
        ];
    }

    private function getInterviewQuestionsSystemPrompt(): string
    {
        return "You are a master interviewer coach with expertise in behavioral, technical, and cultural fit interviewing. Generate targeted, high-impact interview questions that reveal candidate capabilities, potential red flags, and growth trajectory.";
    }

    private function buildInterviewQuestionsPrompt(array $jobDescription, array $candidateProfile = []): string
    {
        $jobText = json_encode($jobDescription, JSON_PRETTY_PRINT);
        $candidateText = $candidateProfile ? json_encode($candidateProfile, JSON_PRETTY_PRINT) : 'Not provided';

        return "JOB DESCRIPTION:\n{$jobText}\n\nCANDIDATE PROFILE:\n{$candidateText}\n\nGenerate 15-20 targeted interview questions organized by category:\n1. Role-Specific Technical Questions (5-7)\n2. Behavioral & Situational Questions (5-7)\n3. Cultural Fit Questions (3-5)\n4. Leadership & Growth Questions (2-3)\n\nFor each question include:\n- The question itself\n- What it reveals (purpose)\n- What a great answer looks like\n- Red flags to watch for\n\nFocus on uncovering gaps between the job requirements and candidate profile.";
    }

    private function parseInterviewQuestions(string $response): array
    {
        // Extract structured questions from response
        $questions = [];
        $lines = explode("\n", $response);
        $currentCategory = null;
        $currentQuestion = [];

        foreach ($lines as $line) {
            if (preg_match('/^\d+\.\s+(.+)/', $line, $matches)) {
                if ($currentQuestion) {
                    $questions[$currentCategory][] = $currentQuestion;
                }
                $currentQuestion = ['question' => trim($matches[1]), 'details' => []];
            } elseif (preg_match('/^#+\s+(.+)/', $line, $matches)) {
                if ($currentQuestion) {
                    $questions[$currentCategory][] = $currentQuestion;
                    $currentQuestion = [];
                }
                $currentCategory = $matches[1];
            } elseif ($currentQuestion && trim($line)) {
                $currentQuestion['details'][] = trim($line);
            }
        }

        if ($currentQuestion) {
            $questions[$currentCategory][] = $currentQuestion;
        }

        return $questions ?: ['raw_questions' => explode("\n\n", $response)];
    }

    // ============================================================
    // AI RESUME GENERATION
    // ============================================================

    /**
     * Generate professional resume from candidate profile data
     */
    public function generateResume(array $profileData): array
    {
        if (!$this->isAvailable()) {
            return [
                'error' => 'AI service unavailable',
                'message' => 'Cannot generate resume at this time',
            ];
        }

        $prompt = $this->buildResumePrompt($profileData);

        $messages = [
            ['role' => 'system', 'content' => $this->getResumeGenerationSystemPrompt()],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->chat($messages, [
            'model_type' => 'analytical',
            'enable_tools' => false,
            'temperature' => 0.5,
            'max_tokens' => 4096,
        ]);

        // Parse and structure the resume
        return $this->parseGeneratedResume($response['content'] ?? '', $profileData);
    }

    private function getResumeGenerationSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert resume writer and career coach with 15+ years of experience creating ATS-optimized, professional resumes for healthcare and life sciences professionals.

Your task is to transform raw candidate profile data into a polished, structured resume that:
1. Highlights key achievements and quantifiable impact
2. Uses strong action verbs and industry-specific language
3. Is optimized for applicant tracking systems (ATS)
4. Follows modern resume best practices (concise, scannable, results-focused)
5. Tailors content to healthcare/clinical research roles

Return the resume in structured JSON format with these sections:
- header (name, title, contact info)
- summary (2-3 sentence professional summary)
- core_competencies (6-8 key skills as bullet points)
- professional_experience (each role with 3-5 achievement bullets)
- education (degrees, certifications)
- certifications (licenses, credentials)
- additional_sections (volunteer work, publications, etc. if applicable)

Rules:
- Use past tense for past roles, present tense for current role
- Quantify achievements where possible (%, $, time saved, people managed)
- Avoid first-person pronouns (I, my, me)
- Keep each bullet point to 1-2 lines
- Use industry-standard terminology
PROMPT;
    }

    private function buildResumePrompt(array $profileData): string
    {
        $json = json_encode($profileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "Transform this candidate profile data into a professional, ATS-optimized resume:\n\n{$json}\n\nGenerate a complete, polished resume in structured JSON format. Focus on healthcare/clinical research industry standards.";
    }

    private function parseGeneratedResume(string $response, array $profileData): array
    {
        // Try to extract JSON
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $resume = json_decode($matches[0], true);
            if ($resume) {
                return [
                    'success' => true,
                    'resume' => $resume,
                    'generated_at' => now()->toIso8601String(),
                    'version' => '1.0',
                ];
            }
        }

        // Fallback: return structured text
        return [
            'success' => false,
            'raw_text' => $response,
            'message' => 'Could not parse structured resume. Raw text provided.',
        ];
    }

    // ============================================================
    // AI JOB DESCRIPTION WRITER
    // ============================================================

    /**
     * Generate professional job description from template parameters
     */
    public function generateJobDescription(array $params): array
    {
        if (!$this->isAvailable()) {
            return [
                'error' => 'AI service unavailable',
                'message' => 'Cannot generate job description at this time',
            ];
        }

        $prompt = $this->buildJobDescriptionPrompt($params);

        $messages = [
            ['role' => 'system', 'content' => $this->getJobDescriptionSystemPrompt()],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->chat($messages, [
            'model_type' => 'analytical',
            'enable_tools' => false,
            'temperature' => 0.7,
            'max_tokens' => 4096,
        ]);

        return $this->parseGeneratedJobDescription($response['content'] ?? '', $params);
    }

    /**
     * Generate multiple A/B test variants of a job description
     */
    public function generateJobDescriptionVariants(array $params, int $count = 2): array
    {
        if (!$this->isAvailable()) {
            return [
                'error' => 'AI service unavailable',
                'message' => 'Cannot generate job description variants at this time',
            ];
        }

        $variants = [];
        $styles = [
            'professional' => 'Professional and formal tone, focusing on credentials and requirements',
            'engaging' => 'Engaging and enthusiastic tone, highlighting benefits and culture',
            'concise' => 'Direct and concise, focusing on key requirements and benefits',
            'storytelling' => 'Narrative style, painting a picture of the role and its impact',
        ];

        for ($i = 0; $i < min($count, 4); $i++) {
            $style = array_values($styles)[$i];
            $variantParams = array_merge($params, ['style' => $style, 'variant' => chr(65 + $i)]);
            
            $prompt = $this->buildJobDescriptionPrompt($variantParams);

            $messages = [
                ['role' => 'system', 'content' => $this->getJobDescriptionSystemPrompt()],
                ['role' => 'user', 'content' => $prompt],
            ];

            $response = $this->chat($messages, [
                'model_type' => 'analytical',
                'enable_tools' => false,
                'temperature' => 0.8 + ($i * 0.05), // Increase creativity for each variant
                'max_tokens' => 4096,
            ]);

            $parsed = $this->parseGeneratedJobDescription($response['content'] ?? '', $variantParams);
            $parsed['variant'] = chr(65 + $i);
            $parsed['style'] = array_keys($styles)[$i];
            
            $variants[] = $parsed;
        }

        return [
            'success' => true,
            'variants' => $variants,
            'count' => count($variants),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate best-practice suggestions for a job description
     */
    public function generateJobDescriptionSuggestions(string $description, array $context = []): array
    {
        if (!$this->isAvailable()) {
            return [
                'error' => 'AI service unavailable',
                'message' => 'Cannot generate suggestions at this time',
            ];
        }

        $contextStr = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = "Review and provide optimization suggestions for this job description:\n\n{$description}";
        if (!empty($context)) {
            $prompt .= "\n\nAdditional context: {$contextStr}";
        }
        $prompt .= "\n\nProvide specific, actionable suggestions to improve conversion rates, compliance, and candidate engagement.";

        $messages = [
            ['role' => 'system', 'content' => $this->getJobSuggestionsSystemPrompt()],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->chat($messages, [
            'model_type' => 'analytical',
            'enable_tools' => false,
            'temperature' => 0.5,
            'max_tokens' => 2048,
        ]);

        return $this->parseJobSuggestions($response['content'] ?? '');
    }

    /**
     * Generate compliance checklist for healthcare job
     */
    public function generateComplianceChecklist(string $roleType, string $category, array $params = []): array
    {
        if (!$this->isAvailable()) {
            return [
                'error' => 'AI service unavailable',
                'message' => 'Cannot generate compliance checklist at this time',
            ];
        }

        $paramsStr = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = "Generate a comprehensive compliance checklist for a {$roleType} position in the {$category} category.";
        if (!empty($params)) {
            $prompt .= "\n\nAdditional parameters: {$paramsStr}";
        }

        $messages = [
            ['role' => 'system', 'content' => $this->getComplianceChecklistSystemPrompt()],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->chat($messages, [
            'model_type' => 'analytical',
            'enable_tools' => false,
            'temperature' => 0.3,
            'max_tokens' => 2048,
        ]);

        return $this->parseComplianceChecklist($response['content'] ?? '');
    }

    // ============================================================
    // AI JOB DESCRIPTION - PRIVATE METHODS
    // ============================================================

    private function getJobDescriptionSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert job description writer specializing in healthcare and life sciences recruitment. You create compelling, compliant, and conversion-optimized job descriptions that attract top talent.

Your job descriptions must:
1. Be engaging, professional, and inclusive
2. Clearly outline responsibilities, qualifications, and benefits
3. Include required certifications and licenses for healthcare roles
4. Comply with employment law and industry standards
5. Use SEO-friendly language and keywords
6. Be structured for easy scanning (headings, bullet points)
7. Include salary range, shift information, and work location
8. Highlight unique selling points and company culture
9. Include a clear call-to-action

Return job descriptions in structured JSON format with:
- title (optimized job title)
- summary (2-3 sentence overview)
- responsibilities (5-8 bullet points)
- qualifications (required and preferred)
- certifications (required licenses/certs)
- benefits (comprehensive benefits package)
- shift_details (shift type, hours, schedule)
- salary_range (min, max, currency, type)
- about_role (longer description for engagement)
- keywords (SEO/application keywords)
PROMPT;
    }

    private function getJobSuggestionsSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert job description optimizer with deep knowledge of what drives candidate engagement and application conversions.

Analyze the provided job description and provide actionable suggestions to improve:
1. Conversion optimization (compelling language, clear CTAs)
2. Compliance (required certifications, legal requirements)
3. Inclusivity (bias-free, inclusive language)
4. SEO optimization (keywords, searchability)
5. Clarity and scannability (structure, formatting)
6. Competitiveness (salary, benefits, market standards)

Return suggestions in structured JSON format with:
- suggestions (array of {category, suggestion, priority, impact})
- missing_elements (array of important missing elements)
- compliance_issues (array of compliance concerns)
- optimization_score (0-100 overall score)
- recommended_actions (top 5 prioritized actions)

Categories: conversion, compliance, inclusivity, seo, clarity, competitiveness
Priority: high, medium, low
PROMPT;
    }

    private function getComplianceChecklistSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a healthcare recruitment compliance expert. Generate a comprehensive compliance checklist for healthcare job postings, ensuring all legal and industry requirements are met.

For each role, provide:
- Required certifications and licenses
- State-specific requirements
- Experience and education requirements
- Shift and scheduling compliance
- Salary and compensation disclosure requirements
- Equal opportunity and inclusion statements
- Background check and screening requirements

Return in structured JSON format with:
- certifications (array of required certifications)
- licenses (array of required licenses)
- requirements (array of compliance requirements)
- shift_compliance (shift-related compliance items)
- experience_requirements (education, years, level)
- legal_requirements (legal compliance items)
- recommended_disclosures (recommended job posting disclosures)
PROMPT;
    }

    private function buildJobDescriptionPrompt(array $params): string
    {
        $required = $params['required_certifications'] ?? [];
        $benefits = $params['benefits'] ?? [];
        
        $prompt = "Create a compelling job description for the following role:\n\n";
        $prompt .= "**Role:** {$params['role_type']}\n";
        $prompt .= "**Category:** {$params['category']}\n";
        $prompt .= "**Title:** {$params['title']}\n";
        $prompt .= "**Employment Type:** {$params['employment_type']}\n";
        $prompt .= "**Work Mode:** {$params['work_mode']}\n";
        $prompt .= "**Location:** " . ($params['city'] ?? 'N/A') . ", " . ($params['country'] ?? 'N/A') . "\n";
        $prompt .= "**Experience Level:** {$params['experience_level']}\n";
        $prompt .= "**Minimum Experience:** " . ($params['min_experience_years'] ?? 'N/A') . " years\n";
        
        if (!empty($params['shift_type'])) {
            $prompt .= "**Shift Type:** {$params['shift_type']}\n";
        }
        
        if (!empty($params['salary_min']) && !empty($params['salary_max'])) {
            $prompt .= "**Salary Range:** {$params['salary_min']} - {$params['salary_max']} {$params['salary_currency']}\n";
        }
        
        if (!empty($required)) {
            $prompt .= "**Required Certifications:** " . implode(', ', $required) . "\n";
        }
        
        if (!empty($benefits)) {
            $prompt .= "**Benefits:** " . implode(', ', $benefits) . "\n";
        }
        
        if (!empty($params['style'])) {
            $prompt .= "\n**Writing Style:** {$params['style']}\n";
        }
        
        $prompt .= "\nGenerate a complete, conversion-optimized job description in structured JSON format.";
        
        return $prompt;
    }

    private function parseGeneratedJobDescription(string $response, array $params): array
    {
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $description = json_decode($matches[0], true);
            if ($description) {
                return [
                    'success' => true,
                    'description' => $description,
                    'is_ai_generated' => true,
                    'ai_model_used' => 'gpt-4o',
                    'generated_at' => now()->toIso8601String(),
                ];
            }
        }

        return [
            'success' => false,
            'raw_text' => $response,
            'message' => 'Could not parse structured job description. Raw text provided.',
        ];
    }

    private function parseJobSuggestions(string $response): array
    {
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $suggestions = json_decode($matches[0], true);
            if ($suggestions) {
                return [
                    'success' => true,
                    'suggestions' => $suggestions,
                    'generated_at' => now()->toIso8601String(),
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Could not parse structured suggestions',
        ];
    }

    private function parseComplianceChecklist(string $response): array
    {
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $checklist = json_decode($matches[0], true);
            if ($checklist) {
                return [
                    'success' => true,
                    'checklist' => $checklist,
                    'generated_at' => now()->toIso8601String(),
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Could not parse structured compliance checklist',
        ];
    }
}
