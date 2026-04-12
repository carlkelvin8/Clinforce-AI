<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\ApplicantProfile;
use App\Models\Job;
use App\Models\User;

/**
 * Enterprise-Grade AI Chatbot Controller
 * 
 * Features:
 * - Multi-model AI with automatic failover
 * - Intelligent tool calling (candidate search, job search, analytics)
 * - Document analysis (PDF, DOCX, TXT, images)
 * - Context-aware conversations with memory
 * - Real-time streaming support
 * - Advanced error recovery
 * - Usage analytics & rate limiting
 * - Semantic search capabilities
 */
class ChatbotController extends Controller
{
    private AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Main chat endpoint with enterprise AI capabilities
     */
    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'messages' => 'required|array|min:1|max:50',
            'messages.*.role' => 'required|string|in:user,assistant,system',
            'messages.*.content' => 'required|string|max:10000',
            'files' => 'nullable|array|max:5',
            'files.*' => 'file|mimes:pdf,doc,docx,txt,jpg,jpeg,png|max:10240',
            'mode' => 'nullable|string|in:chat,analysis,interview,matching,summary',
            'streaming' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid input',
                'details' => $validator->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }

        $authUser = $request->user();
        $sessionId = $request->input('session_id');
        $mode = $request->input('mode', 'chat');

        try {
            // Parse messages
            $messages = $this->parseMessages($request->input('messages'));
            
            // Process uploaded files
            $fileContext = $this->processUploadedFiles($request->file('files', []));
            
            // Build enhanced system prompt
            $systemPrompt = $this->buildSystemPrompt($authUser, $mode, $fileContext);
            
            // Add context from conversation history
            $context = $this->aiService->getContext($authUser?->id ?? 'anonymous');
            
            // Prepend system message
            array_unshift($messages, ['role' => 'system', 'content' => $systemPrompt]);
            
            // Detect intent and select appropriate tools
            $tools = $this->getToolsForMode($mode);
            $lastUserMessage = $this->getLastUserMessage($messages);
            $intent = $this->detectIntent($lastUserMessage);

            // Execute chat with AI service
            $options = [
                'model_type' => $this->selectModel($intent),
                'enable_tools' => !empty($tools),
                'tools' => $tools,
                'tool_choice' => 'auto',
                'user_id' => $authUser?->id,
                'session_id' => $sessionId,
                'temperature' => $this->getTemperature($mode),
                'max_tokens' => $this->getMaxTokens($mode),
            ];

            // Handle streaming vs non-streaming
            if ($request->input('streaming', false)) {
                return $this->handleStreaming($messages, $options, $authUser);
            }

            $response = $this->aiService->chat($messages, $options);

            // Check if AI wants to use tools
            if (isset($response['tool_calls']) && is_array($response['tool_calls'])) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $response['content'] ?? null,
                    'tool_calls' => $response['tool_calls'],
                ];

                // Execute tool calls
                foreach ($response['tool_calls'] as $toolCall) {
                    $result = $this->executeToolCall($toolCall, $authUser);
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ];
                }

                // Get final response with tool results
                $options['enable_tools'] = false;
                unset($options['tools']);
                $response = $this->aiService->chat($messages, $options);
            }

            // Update conversation context
            $this->updateContext($authUser?->id ?? 'anonymous', $lastUserMessage, $response);

            // Log successful interaction
            Log::info('AI Chat Success', [
                'user_id' => $authUser?->id,
                'mode' => $mode,
                'intent' => $intent,
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                'response_time_ms' => $response['usage']['duration_ms'] ?? null,
            ]);

            return response()->json([
                'role' => $response['role'] ?? 'assistant',
                'content' => $response['content'] ?? '',
                'usage' => $response['usage'] ?? [],
                'mode' => $mode,
                'intent' => $intent,
                'metadata' => [
                    'model' => $response['model'] ?? 'unknown',
                    'finish_reason' => $response['finish_reason'] ?? null,
                    'context_updated' => true,
                ],
            ]);

        } catch (\Exception $e) {
            return $this->handleError($e, $authUser);
        }
    }

    /**
     * Analyze uploaded document
     */
    public function analyzeDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
            'type' => 'nullable|string|in:resume,job_description,general,comparison',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid input',
                'details' => $validator->errors(),
            ], 422);
        }

        $authUser = $request->user();
        
        try {
            $file = $request->file('file');
            $text = $this->extractTextFromFile($file);
            
            if (empty($text)) {
                return response()->json([
                    'error' => 'Could not extract text from file',
                    'suggestions' => [
                        'Ensure the file is not password-protected',
                        'Try converting to plain text first',
                        'For images, ensure text is clear and readable',
                    ],
                ], 400);
            }

            $analysisType = $request->input('type', 'general');
            
            $response = $this->aiService->analyzeDocument($text, [
                'type' => $analysisType,
                'user_id' => $authUser?->id,
            ]);

            return response()->json([
                'analysis' => $response['content'] ?? '',
                'type' => $analysisType,
                'file_name' => $file->getClientOriginalName(),
                'word_count' => str_word_count($text),
                'metadata' => [
                    'model' => $response['model'] ?? 'unknown',
                    'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                ],
            ]);

        } catch (\Exception $e) {
            return $this->handleError($e, $authUser);
        }
    }

    /**
     * Get AI-powered candidate matches for a job
     */
    public function matchCandidates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|integer|exists:jobs,id',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid input',
                'details' => $validator->errors(),
            ], 422);
        }

        $authUser = $request->user();
        
        try {
            $job = Job::with('employer')->findOrFail($request->job_id);
            $limit = $request->input('limit', 10);

            // Get job requirements
            $jobRequirements = [
                'title' => $job->title,
                'description' => $job->description,
                'required_skills' => $job->required_skills ?? [],
                'experience_level' => $job->experience_level ?? 'mid',
                'location' => trim(($job->city ?? '') . ', ' . ($job->state ?? '')),
                'employment_type' => $job->employment_type ?? 'full-time',
            ];

            // Get active candidate profiles
            $candidates = ApplicantProfile::query()
                ->whereHas('user', function ($q) {
                    $q->where('status', 'active');
                })
                ->limit(50)
                ->get()
                ->map(function ($profile) {
                    return [
                        'id' => $profile->user_id,
                        'name' => trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')),
                        'headline' => $profile->headline ?? '',
                        'summary' => $profile->summary ?? '',
                        'skills' => $profile->skills ?? '',
                        'specialization' => $profile->specialization ?? '',
                        'years_experience' => $profile->years_experience ?? 0,
                        'location' => trim(($profile->city ?? '') . ', ' . ($profile->state ?? '')),
                    ];
                })
                ->toArray();

            $matching = $this->aiService->matchCandidates($jobRequirements, $candidates, $limit);

            return response()->json([
                'job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                ],
                'matches' => $matching['matches'] ?? [],
                'total_evaluated' => count($candidates),
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'algorithm' => 'ai_semantic_matching',
                ],
            ]);

        } catch (\Exception $e) {
            return $this->handleError($e, $authUser);
        }
    }

    /**
     * Generate interview questions for a candidate
     */
    public function generateInterviewQuestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|integer|exists:jobs,id',
            'candidate_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid input',
                'details' => $validator->errors(),
            ], 422);
        }

        $authUser = $request->user();
        
        try {
            $job = Job::findOrFail($request->job_id);
            
            $jobDescription = [
                'title' => $job->title,
                'description' => $job->description,
                'requirements' => $job->required_skills ?? [],
                'responsibilities' => $job->responsibilities ?? [],
            ];

            $candidateProfile = [];
            if ($request->has('candidate_id')) {
                $profile = ApplicantProfile::where('user_id', $request->candidate_id)->first();
                if ($profile) {
                    $candidateProfile = [
                        'headline' => $profile->headline ?? '',
                        'summary' => $profile->summary ?? '',
                        'skills' => $profile->skills ?? '',
                        'experience' => $profile->years_experience ?? 0,
                        'specialization' => $profile->specialization ?? '',
                    ];
                }
            }

            $result = $this->aiService->generateInterviewQuestions($jobDescription, $candidateProfile);

            return response()->json([
                'job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                ],
                'candidate' => $candidateProfile ? ['id' => $request->candidate_id] : null,
                'questions' => $result['questions'] ?? [],
                'raw_response' => $result['raw_response'] ?? '',
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->handleError($e, $authUser);
        }
    }

    /**
     * Get AI service health status
     */
    public function health()
    {
        return response()->json($this->aiService->healthCheck());
    }

    // ============================================================
    // PRIVATE HELPER METHODS
    // ============================================================

    private function parseMessages($raw): array
    {
        $messages = [];
        
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : null;
        }

        if (is_array($raw)) {
            foreach ($raw as $m) {
                if (!is_array($m)) continue;
                $role = $m['role'] ?? 'user';
                $content = $m['content'] ?? '';
                if ($role && $content) {
                    $messages[] = ['role' => $role, 'content' => $content];
                }
            }
        }

        return $messages;
    }

    private function processUploadedFiles($files): array
    {
        if (empty($files) || !is_array($files)) {
            return [];
        }

        $fileContext = [];
        
        foreach ($files as $file) {
            if (!$file) continue;
            
            try {
                $text = $this->extractTextFromFile($file);
                $fileContext[] = [
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getClientMimeType(),
                    'size_kb' => round($file->getSize() / 1024, 1),
                    'content' => $text ? mb_substr($text, 0, 3000) : null,
                ];
            } catch (\Exception $e) {
                Log::warning('File extraction failed', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $fileContext;
    }

    private function extractTextFromFile($file): ?string
    {
        if (!$file || !is_readable($file->getRealPath())) {
            return null;
        }

        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        $path = $file->getRealPath();

        return match ($ext) {
            'txt' => file_get_contents($path) ?: null,
            'docx' => $this->extractTextFromDocx($path),
            'pdf' => $this->extractTextFromPdf($path),
            'jpg', 'jpeg', 'png' => $this->extractTextFromImage($path),
            default => null,
        };
    }

    private function extractTextFromDocx(string $path): ?string
    {
        if (!class_exists(\ZipArchive::class)) {
            return null;
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        $index = $zip->locateName('word/document.xml');
        if ($index === false) {
            $zip->close();
            return null;
        }

        $xml = $zip->getFromIndex($index);
        $zip->close();

        if ($xml === false) {
            return null;
        }

        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        $text = strip_tags($xml);

        return html_entity_decode($text, ENT_QUOTES | ENT_XML1);
    }

    private function extractTextFromPdf(string $path): ?string
    {
        // Try pdftotext command first
        $cmd = 'pdftotext -layout ' . escapeshellarg($path) . ' -';
        $output = @shell_exec($cmd);
        
        if (is_string($output) && trim($output) !== '') {
            return $output;
        }

        // Fallback: Try reading with PHP if available
        if (class_exists('\Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($path);
                return $pdf->getText();
            } catch (\Exception $e) {
                Log::debug('PDF parser fallback failed', ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    private function extractTextFromImage(string $path): ?string
    {
        // OCR would go here if Tesseract is installed
        // For now, return placeholder
        return '[Image file detected - OCR not available. Please describe the image content for analysis.]';
    }

    private function buildSystemPrompt(?User $user, string $mode, array $fileContext): string
    {
        $uid = $user?->id ?? 'anonymous';
        $urole = $user?->role ?? 'guest';
        $company = $user?->company_name ?? 'Clinforce';
        
        $basePrompt = "You are Clinforce AI, an elite-tier recruitment intelligence platform powering hiring decisions for healthcare and life sciences organizations worldwide.

## YOUR CAPABILITIES
- Deep resume analysis with structured scoring
- Intelligent candidate-job matching with gap analysis
- Interview question generation with evaluation rubrics
- Market intelligence and compensation benchmarking
- Compliance and regulatory guidance for HR
- Document drafting (job descriptions, offer letters, rejection emails)
- Strategic workforce planning and talent analytics

## RESPONSE STANDARDS
1. Evidence-Based: Always cite specific details from provided documents
2. Structured: Use clear sections, bullet points, and tables
3. Actionable: Every response ends with specific next steps
4. Professional: Authoritative yet approachable tone
5. Quantified: Use metrics and ratings (1-10 scale) where possible
6. Comparative: When evaluating, always provide relative rankings

## ANALYSIS FRAMEWORK
When evaluating candidates or jobs, always consider:
- Skills Match (technical & soft skills)
- Experience Relevance (depth & breadth)
- Cultural Indicators (values alignment, work style)
- Growth Trajectory (career progression, learning agility)
- Risk Factors (gaps, inconsistencies, red flags)
- Opportunity Factors (unique strengths, differentiators)

## CURRENT SESSION
User ID: {$uid}
User Role: {$urole}
Organization: {$company}
Mode: {$mode}
Platform: Clinforce AI Enterprise
";

        // Add file context
        if (!empty($fileContext)) {
            $basePrompt .= "\n## UPLOADED DOCUMENTS FOR ANALYSIS\n";
            foreach ($fileContext as $i => $file) {
                $basePrompt .= "\n### Document " . ($i + 1) . ": {$file['name']}\n";
                $basePrompt .= "Type: {$file['type']} | Size: {$file['size_kb']} KB\n";
                if ($file['content']) {
                    $basePrompt .= "\nContent:\n{$file['content']}\n";
                } else {
                    $basePrompt .= "\n[Content could not be extracted - analyze based on filename and user description]\n";
                }
            }
            $basePrompt .= "\nProvide comprehensive analysis of all uploaded documents.";
        }

        // Mode-specific instructions
        $modeInstructions = match ($mode) {
            'analysis' => "\n\n## ANALYSIS MODE\nProvide deep, multi-dimensional analysis with scoring matrices and recommendations.",
            'interview' => "\n\n## INTERVIEW MODE\nGenerate targeted interview questions with evaluation criteria and red flag indicators.",
            'matching' => "\n\n## MATCHING MODE\nEvaluate candidate-job fit with detailed gap analysis and hiring recommendations.",
            'summary' => "\n\n## SUMMARY MODE\nProvide concise executive summaries with key takeaways and action items.",
            default => "\n\n## CHAT MODE\nEngage in helpful, expert-level conversation about recruitment, HR strategy, and talent management.",
        };

        return $basePrompt . $modeInstructions;
    }

    private function getToolsForMode(string $mode): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_candidates',
                    'description' => 'Search the candidate database by skills, location, specialization, or keywords',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Search terms (e.g., "pediatric nurse Boston")'],
                            'limit' => ['type' => 'integer', 'description' => 'Max results (default 10)'],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_jobs',
                    'description' => 'Search published job openings',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Search terms (e.g., "CRA remote clinical research")'],
                            'limit' => ['type' => 'integer', 'description' => 'Max results (default 10)'],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_analytics',
                    'description' => 'Get recruitment analytics and platform statistics',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'metric' => ['type' => 'string', 'description' => 'Metric type (candidates, jobs, subscriptions, revenue)'],
                            'period' => ['type' => 'string', 'description' => 'Time period (today, this_week, this_month)'],
                        ],
                        'required' => ['metric'],
                    ],
                ],
            ],
        ];
    }

    private function detectIntent(string $message): string
    {
        $message = strtolower($message);
        
        if (preg_match('/search|find|look|candidates?|applicant/', $message)) {
            return 'search_candidates';
        }
        if (preg_match('/job|position|opening|vacancy/', $message)) {
            return 'search_jobs';
        }
        if (preg_match('/analyz|review|evaluat|assess|score/', $message)) {
            return 'analysis';
        }
        if (preg_match('/interview|question.*ask/', $message)) {
            return 'interview';
        }
        if (preg_match('/match|fit|compatible|suitable/', $message)) {
            return 'matching';
        }
        if (preg_match('/stat|metric|dashboard|report|trend/', $message)) {
            return 'analytics';
        }

        return 'general';
    }

    private function selectModel(string $intent): string
    {
        return match ($intent) {
            'analysis', 'matching' => 'analytical',
            'analytics' => 'fast',
            default => 'primary',
        };
    }

    private function getTemperature(string $mode): float
    {
        return match ($mode) {
            'analysis' => 0.3,
            'interview' => 0.5,
            'matching' => 0.2,
            default => 0.7,
        };
    }

    private function getMaxTokens(string $mode): int
    {
        return match ($mode) {
            'analysis' => 4096,
            'interview' => 3072,
            'matching' => 4096,
            default => 2048,
        };
    }

    private function getLastUserMessage(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if ($messages[$i]['role'] === 'user') {
                return $messages[$i]['content'];
            }
        }
        return '';
    }

    private function executeToolCall(array $toolCall, ?User $user): array
    {
        $fnName = $toolCall['function']['name'] ?? '';
        $args = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

        return match ($fnName) {
            'search_candidates' => $this->searchCandidates($args['query'] ?? '', $args['limit'] ?? 10),
            'search_jobs' => $this->searchJobs($args['query'] ?? '', $args['limit'] ?? 10),
            'get_analytics' => $this->getAnalytics($args['metric'] ?? 'candidates', $args['period'] ?? 'today'),
            default => ['error' => "Unknown function: {$fnName}"],
        };
    }

    private function searchCandidates(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if (!$query) {
            return ['error' => 'No search query provided', 'results' => []];
        }

        $results = ApplicantProfile::query()
            ->with('user:id,email,phone,status,company_name')
            ->where(function ($sub) use ($query) {
                $sub->where('headline', 'like', "%{$query}%")
                    ->orWhere('summary', 'like', "%{$query}%")
                    ->orWhere('bio', 'like', "%{$query}%")
                    ->orWhere('specialization', 'like', "%{$query}%")
                    ->orWhere('skills', 'like', "%{$query}%")
                    ->orWhere('city', 'like', "%{$query}%")
                    ->orWhere('state', 'like', "%{$query}%")
                    ->orWhere('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->whereHas('user', fn($q) => $q->where('status', 'active'))
            ->limit($limit)
            ->get();

        // Fallback: keyword search
        if ($results->isEmpty() && str_contains($query, ' ')) {
            $keywords = array_filter(explode(' ', $query), fn($w) => strlen(trim($w)) > 2);
            
            $results = ApplicantProfile::query()
                ->with('user:id,email,phone,status')
                ->where(function ($sub) use ($keywords) {
                    foreach ($keywords as $word) {
                        $word = trim($word);
                        $sub->orWhere('headline', 'like', "%{$word}%")
                            ->orWhere('specialization', 'like', "%{$word}%")
                            ->orWhere('skills', 'like', "%{$word}%");
                    }
                })
                ->whereHas('user', fn($q) => $q->where('status', 'active'))
                ->limit($limit)
                ->get();
        }

        if ($results->isEmpty()) {
            return [
                'message' => "No active candidates found matching '{$query}'",
                'suggestions' => [
                    'Try broader search terms',
                    'Search by location or specialization',
                    'Check if candidates have active status',
                ],
                'total_found' => 0,
            ];
        }

        return [
            'total_found' => $results->count(),
            'search_query' => $query,
            'candidates' => $results->map(fn($p) => [
                'id' => $p->user_id,
                'name' => trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')),
                'email' => $p->user->email ?? 'N/A',
                'headline' => $p->headline ?? 'Healthcare Professional',
                'specialization' => $p->specialization ?? 'General',
                'location' => trim(($p->city ?? '') . ', ' . ($p->state ?? ' ') . ' ' . ($p->country ?? '')),
                'years_experience' => $p->years_experience ?? 0,
                'skills_preview' => $p->skills ? mb_substr($p->skills, 0, 150) . '...' : null,
                'summary_preview' => $p->summary ? mb_substr($p->summary, 0, 200) . '...' : null,
                'status' => $p->user->status ?? 'unknown',
            ])->toArray(),
            'note' => 'Showing top candidates from Clinforce database. Review profiles and contact promising matches.',
        ];
    }

    private function searchJobs(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if (!$query) {
            return ['error' => 'No search query provided', 'results' => []];
        }

        $results = Job::query()
            ->where('status', 'published')
            ->where(function ($sub) use ($query) {
                $sub->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('city', 'like', "%{$query}%")
                    ->orWhere('state', 'like', "%{$query}%")
                    ->orWhere('employment_type', 'like', "%{$query}%")
                    ->orWhere('specialization', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        if ($results->isEmpty()) {
            return [
                'message' => "No published jobs found matching '{$query}'",
                'suggestions' => [
                    'Try different keywords',
                    'Search by job type or location',
                    'Check if jobs are published',
                ],
                'total_found' => 0,
            ];
        }

        return [
            'total_found' => $results->count(),
            'search_query' => $query,
            'jobs' => $results->map(fn($j) => [
                'id' => $j->id,
                'title' => $j->title,
                'type' => $j->employment_type ?? 'Not specified',
                'work_mode' => $j->work_mode ?? 'Not specified',
                'location' => trim(($j->city ?? '') . ', ' . ($j->state ?? ' ') . ' ' . ($j->country ?? '')),
                'salary_range' => ($j->salary_min && $j->salary_max)
                    ? '$' . number_format($j->salary_min) . ' - $' . number_format($j->salary_max)
                    : 'Not disclosed',
                'description_preview' => $j->description ? mb_substr($j->description, 0, 200) . '...' : null,
                'published_at' => $j->published_at?->format('Y-m-d'),
            ])->toArray(),
            'note' => 'Currently published positions on Clinforce. Click to view full details and start sourcing.',
        ];
    }

    private function getAnalytics(string $metric, string $period): array
    {
        $dateFilter = match ($period) {
            'this_week' => now()->startOfWeek(),
            'this_month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        $data = match ($metric) {
            'candidates' => [
                'total' => ApplicantProfile::count(),
                'active' => ApplicantProfile::whereHas('user', fn($q) => $q->where('status', 'active'))->count(),
                'new_' . $period => ApplicantProfile::where('created_at', '>=', $dateFilter)->count(),
            ],
            'jobs' => [
                'total' => Job::count(),
                'published' => Job::where('status', 'published')->count(),
                'new_' . $period => Job::where('created_at', '>=', $dateFilter)->count(),
            ],
            'subscriptions' => [
                'total' => \App\Models\Subscription::count(),
                'active' => \App\Models\Subscription::where('status', 'active')->count(),
                'revenue_month' => \App\Models\Payment::where('created_at', '>=', now()->startOfMonth())->sum('amount_cents'),
            ],
            default => ['message' => "Unknown metric: {$metric}", 'available' => ['candidates', 'jobs', 'subscriptions']],
        };

        return [
            'metric' => $metric,
            'period' => $period,
            'data' => $data,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function updateContext(string $userId, string $lastMessage, array $response): void
    {
        $context = $this->aiService->getContext($userId);
        
        // Store conversation topic
        $context['last_topic'] = $this->extractTopic($lastMessage);
        $context['last_intent'] = $this->detectIntent($lastMessage);
        $context['last_interaction_at'] = now()->toIso8601String();
        $context['interaction_count'] = ($context['interaction_count'] ?? 0) + 1;
        
        $this->aiService->setContext($userId, $context);
    }

    private function extractTopic(string $message): string
    {
        $words = str_word_count(strtolower($message), 1);
        $keywords = array_filter($words, fn($w) => strlen($w) > 4);
        return implode(', ', array_slice(array_values($keywords), 0, 3)) ?: 'general';
    }

    private function handleStreaming(array $messages, array $options, ?User $user)
    {
        return response()->stream(function () use ($messages, $options, $user) {
            $this->aiService->chatStream($messages, function ($chunk) {
                echo json_encode($chunk) . "\n";
                ob_flush();
                flush();
            }, $options);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function handleError(\Exception $e, ?User $user): \Illuminate\Http\JsonResponse
    {
        Log::error('AI Chatbot Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => $user?->id,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        $errorType = $this->classifyError($e->getMessage());
        
        $userMessage = match ($errorType) {
            'configuration' => "I'm currently undergoing maintenance. Our team is working to restore my full capabilities. Please try again shortly!",
            'rate_limit' => "We're experiencing high demand right now. Please wait a moment and try again.",
            'timeout' => "I'm taking longer than expected to respond. Please try again or simplify your request.",
            'validation' => "I couldn't understand your request. Please check your input and try again.",
            default => "I encountered a temporary issue. My engineering team has been automatically notified. Please try again in a moment.",
        };

        return response()->json([
            'error' => $userMessage,
            'error_type' => $errorType,
            'technical_details' => config('app.debug') ? [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : null,
            'support' => [
                'message' => 'If this issue persists, contact support with the timestamp below',
                'timestamp' => now()->toIso8601String(),
            ],
        ], 500);
    }

    private function classifyError(string $errorMessage): string
    {
        $msg = strtolower($errorMessage);
        
        if (str_contains($msg, 'api key') || str_contains($msg, 'authentication') || str_contains($msg, 'not configured')) {
            return 'configuration';
        }
        if (str_contains($msg, 'rate limit') || str_contains($msg, 'too many requests')) {
            return 'rate_limit';
        }
        if (str_contains($msg, 'timeout') || str_contains($msg, 'connection')) {
            return 'timeout';
        }
        if (str_contains($msg, 'validation') || str_contains($msg, 'invalid')) {
            return 'validation';
        }
        
        return 'unknown';
    }
}
