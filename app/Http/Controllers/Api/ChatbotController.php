<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ApplicantProfile;
use App\Models\Job;
use App\Models\User;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $authUser = $request->user();
        try {
            // Normalize messages from JSON or multipart form
            $raw = $request->input('messages');
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = is_array($decoded) ? $decoded : null;
            }

            $messages = [];
            if (is_array($raw)) {
                foreach ($raw as $m) {
                    if (!is_array($m)) continue;
                    $role = isset($m['role']) ? (string)$m['role'] : 'user';
                    $content = array_key_exists('content', $m) ? (string)$m['content'] : '';
                    $messages[] = ['role' => $role, 'content' => $content];
                }
            }
            if (empty($messages)) {
                return response()->json([
                    'error' => 'Invalid messages payload.',
                    'details' => ['messages' => ['Provide an array of {role, content} messages']],
                ], 422);
            }

            $uid = $authUser?->id ?? 'guest';
            $urole = $authUser?->role ?? 'guest';

        // Extract text content from any uploaded files (PDF, DOCX, TXT)
        $uploadedFiles = $request->file('files', []);
        if ($uploadedFiles && !is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        $fileSummaries = [];
        if (is_array($uploadedFiles)) {
            foreach ($uploadedFiles as $file) {
                if (!$file) {
                    continue;
                }

                $name = (string) $file->getClientOriginalName();
                $mime = (string) $file->getClientMimeType();
                $sizeBytes = (int) $file->getSize();
                $sizeKb = $sizeBytes > 0 ? round($sizeBytes / 1024, 1) : 0;

                $text = null;
                try {
                    $text = $this->extractTextFromUploadedFile($file);
                } catch (\Throwable $e) {
                    $text = null;
                }
                $snippet = null;
                if (is_string($text) && $text !== '') {
                    $snippet = mb_substr($text, 0, 2000);
                }

                $summary = "File: {$name} ({$mime}, {$sizeKb} KB)";
                if ($snippet !== null) {
                    $summary .= "\n\nContent preview (truncated):\n{$snippet}";
                } else {
                    $summary .= "\n\nContent preview not available (for example, if the file is image-based, scanned, or protected). You only know the file name, type, and approximate size, so base your answer on the user's description and any other available context. Invite the user to paste key text snippets if they want a deeper review.";
                }

                $fileSummaries[] = $summary;
            }
        }

        // System prompt to define persona
        $baseContent = "You are 'Clinforce AI', an expert recruitment assistant and HR consultant for the Clinforce healthcare staffing platform.

Your Core Capabilities:
- Analyze resumes, CVs, and job descriptions with detailed assessments
- Provide structured candidate evaluations with ratings (1-5 scale)
- Suggest tailored interview questions based on role requirements
- Draft professional messages, job postings, and candidate communications
- Search the internal candidate database for matching profiles
- Compare multiple candidates and provide hiring recommendations

Response Guidelines:
1. Be Comprehensive: Provide detailed, actionable insights rather than brief summaries
2. Use Structure: Format responses with clear headings, bullet points, and sections
3. Provide Ratings: When evaluating candidates, always include:
   - Overall Rating (1-5 stars)
   - Skills Match (1-5)
   - Experience Level (1-5)
   - Cultural Fit Indicators
   - Specific strengths and areas of concern
4. Be Specific: Reference actual details from documents (qualifications, years of experience, certifications)
5. Offer Next Steps: Always conclude with actionable recommendations

When Analyzing Resumes:
- Extract key qualifications, certifications, and licenses
- Highlight relevant healthcare experience
- Identify skill gaps or areas for development
- Suggest specific interview questions to probe deeper
- Provide a hiring recommendation with clear reasoning

When Searching Candidates:
- Present results in a structured format with key highlights
- Include years of experience, specializations, and location
- Suggest which candidates to prioritize and why

Document Handling:
- If a document cannot be fully read, acknowledge limitations gracefully
- Work with available information (filename, user description, partial content)
- Invite users to paste key sections for deeper analysis
- Never claim the system failed - focus on what you CAN provide

Tone:
- Professional yet approachable
- Confident in assessments but open to discussion
- Empathetic to hiring challenges
- Proactive in offering solutions

IMPORTANT: Do not use markdown bold formatting (** or __). Use plain text with clear structure instead.

Current User Context:
- User ID: {$uid}
- Role: {$urole}
- Platform: Clinforce Healthcare Staffing
";

        if (!empty($fileSummaries)) {
            $baseContent .= "\n\nUploaded Documents for Analysis:\n\n" . implode("\n\n", $fileSummaries);
            $baseContent .= "\n\nProvide a comprehensive analysis of the uploaded document(s). Include specific details, ratings, and actionable recommendations.";
        }

        $systemMessage = [
            'role' => 'system',
            'content' => $baseContent,
        ];

        // Prepend system message
        array_unshift($messages, $systemMessage);

        // Define Tools
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_candidates',
                    'description' => 'Search for candidates/applicants based on keywords (skills, title, location).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'The search keywords (e.g. "nurse", "clinical research", "New York").',
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_jobs',
                    'description' => 'Search for job postings based on keywords.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'The search keywords (e.g. "CRA", "remote", "manager").',
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
        ];

        // 1. First Call to OpenAI
        $apiKey = (string) config('services.openai.api_key');
        if (!$apiKey) {
            return response()->json(['error' => 'AI service is not configured (missing OPENAI_API_KEY).'], 503);
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(60)
            ->connectTimeout(10)
            ->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'tools' => $tools,
            'tool_choice' => 'auto',
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        if ($response->failed()) {
            Log::error('OpenAI API Error', ['body' => $response->body()]);
            $err = $response->json()['error']['message'] ?? 'AI service unavailable.';
            return response()->json(['error' => $err], 503);
        }

        $responseData = $response->json();
        $message = $responseData['choices'][0]['message'] ?? null;
        if (!is_array($message) || !isset($message['role'])) {
            return response()->json(['error' => 'AI service returned an unexpected response.'], 503);
        }
        if (!array_key_exists('content', $message)) {
            $message['content'] = '';
        }

        // 2. Check for Tool Calls
        if (isset($message['tool_calls']) && is_array($message['tool_calls'])) {
            // Append the assistant's "tool call" message to history
            $messages[] = $message;

            foreach ($message['tool_calls'] as $toolCall) {
                $fnName = (string) ($toolCall['function']['name'] ?? '');
                $args = json_decode((string) ($toolCall['function']['arguments'] ?? ''), true);
                if (!is_array($args)) $args = [];
                $result = '';

                if ($fnName === 'search_candidates') {
                    $result = $this->searchCandidates($args['query'] ?? '');
                } elseif ($fnName === 'search_jobs') {
                    $result = $this->searchJobs($args['query'] ?? '');
                }

                // Append tool result to history
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'],
                    'content' => json_encode($result),
                ];
            }

            // 3. Second Call to OpenAI (with tool results)
            $response2 = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(60)
                ->connectTimeout(10)
                ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            if ($response2->failed()) {
                return response()->json(['error' => 'AI service unavailable during tool processing.'], 503);
            }

            $message2 = $response2->json()['choices'][0]['message'] ?? null;
            if (!is_array($message2) || !isset($message2['role'])) {
                return response()->json(['error' => 'AI service returned an unexpected response during tool processing.'], 503);
            }
            if (!array_key_exists('content', $message2)) {
                $message2['content'] = '';
            }

            return response()->json($message2);
        }

        // No tool call, just return text
        return response()->json($message);
        
        } catch (\Exception $e) {
            Log::error('Chatbot error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $authUser?->id,
            ]);
            return response()->json([
                'error' => 'An error occurred while processing your request. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function extractTextFromUploadedFile($file): ?string
    {
        if (!$file) {
            return null;
        }

        $name = (string) $file->getClientOriginalName();
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = (string) $file->getClientMimeType();
        $path = $file->getRealPath();

        if (!$path || !is_readable($path)) {
            return null;
        }

        if ($ext === 'txt' || str_starts_with($mime, 'text/')) {
            $contents = @file_get_contents($path);
            return $contents !== false ? $contents : null;
        }

        if ($ext === 'docx') {
            return $this->extractTextFromDocx($path);
        }

        if ($ext === 'pdf') {
            return $this->extractTextFromPdf($path);
        }

        return null;
    }

    private function extractTextFromDocx(string $path): ?string
    {
        if (!is_readable($path)) {
            return null;
        }

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
        if (!is_readable($path)) {
            return null;
        }

        $cmd = 'pdftotext -layout ' . escapeshellarg($path) . ' -';
        $output = @shell_exec($cmd);

        if (!is_string($output)) {
            return null;
        }

        $output = trim($output);
        if ($output === '') {
            return null;
        }

        return $output;
    }

    private function searchCandidates($query)
    {
        $q = trim($query);
        if (!$q) return ['error' => 'No search query provided'];

        // Search across multiple fields
        $results = ApplicantProfile::query()
            ->with('user:id,email,phone,status')
            ->where(function($sub) use ($q) {
                $sub->where('headline', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%")
                    ->orWhere('bio', 'like', "%{$q}%")
                    ->orWhere('specialization', 'like', "%{$q}%")
                    ->orWhere('skills', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('state', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get();

        // Fallback: keyword search if no results
        if ($results->isEmpty() && str_contains($q, ' ')) {
            $keywords = array_filter(explode(' ', $q), fn($w) => strlen(trim($w)) > 2);
            
            $results = ApplicantProfile::query()
                ->with('user:id,email,phone,status')
                ->where(function($sub) use ($keywords) {
                    foreach ($keywords as $word) {
                        $word = trim($word);
                        $sub->orWhere('headline', 'like', "%{$word}%")
                            ->orWhere('summary', 'like', "%{$word}%")
                            ->orWhere('specialization', 'like', "%{$word}%")
                            ->orWhere('skills', 'like', "%{$word}%");
                    }
                })
                ->limit(10)
                ->get();
        }

        if ($results->isEmpty()) {
            return [
                'message' => "No candidates found matching '{$q}'",
                'suggestions' => [
                    'Try broader keywords (e.g., "nurse" instead of "pediatric ICU nurse")',
                    'Search by location (e.g., "New York", "California")',
                    'Search by specialization (e.g., "ICU", "ER", "Surgery")',
                ]
            ];
        }

        // Format results with detailed information
        $formatted = $results->map(function($profile) {
            return [
                'id' => $profile->user_id,
                'name' => trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')),
                'email' => $profile->user->email ?? 'N/A',
                'headline' => $profile->headline ?? 'Healthcare Professional',
                'specialization' => $profile->specialization ?? 'General',
                'location' => trim(($profile->city ?? '') . ', ' . ($profile->state ?? '') . ' ' . ($profile->country ?? '')),
                'years_experience' => $profile->years_experience ?? 0,
                'license_number' => $profile->license_number ?? null,
                'skills' => $profile->skills ? (strlen($profile->skills) > 100 ? substr($profile->skills, 0, 100) . '...' : $profile->skills) : null,
                'summary' => $profile->summary ? (strlen($profile->summary) > 200 ? substr($profile->summary, 0, 200) . '...' : $profile->summary) : null,
                'status' => $profile->user->status ?? 'unknown',
            ];
        })->toArray();

        return [
            'total_found' => count($formatted),
            'search_query' => $q,
            'candidates' => $formatted,
            'note' => 'These candidates are from the Clinforce platform database. Review their profiles and reach out to promising matches.'
        ];
    }

    private function searchJobs($query)
    {
        $q = trim($query);
        if (!$q) return ['error' => 'No search query provided'];

        $results = Job::query()
            ->where('status', 'published')
            ->where(function($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('state', 'like', "%{$q}%")
                    ->orWhere('employment_type', 'like', "%{$q}%")
                    ->orWhere('specialization', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get();

        if ($results->isEmpty()) {
            return [
                'message' => "No published jobs found matching '{$q}'",
                'suggestions' => [
                    'Try different keywords',
                    'Search by job type (e.g., "full-time", "contract")',
                    'Search by location',
                ]
            ];
        }

        $formatted = $results->map(function($job) {
            return [
                'id' => $job->id,
                'title' => $job->title,
                'employment_type' => $job->employment_type ?? 'Not specified',
                'work_mode' => $job->work_mode ?? 'Not specified',
                'location' => trim(($job->city ?? '') . ', ' . ($job->state ?? '') . ' ' . ($job->country ?? '')),
                'salary_range' => $job->salary_min && $job->salary_max 
                    ? '$' . number_format($job->salary_min) . ' - $' . number_format($job->salary_max) 
                    : 'Not disclosed',
                'description' => $job->description ? (strlen($job->description) > 200 ? substr($job->description, 0, 200) . '...' : $job->description) : null,
                'published_at' => $job->published_at?->format('Y-m-d'),
            ];
        })->toArray();

        return [
            'total_found' => count($formatted),
            'search_query' => $q,
            'jobs' => $formatted,
            'note' => 'These are currently published job openings on the Clinforce platform.'
        ];
    }
}
