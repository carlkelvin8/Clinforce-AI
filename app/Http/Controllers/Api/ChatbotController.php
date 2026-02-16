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

        $user = $request->user();
        $uid = $user?->id ?? 'guest';
        $urole = $user?->role ?? 'guest';

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

                $text = $this->extractTextFromUploadedFile($file);
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
        $baseContent = "You are 'Clinforce AI', an expert recruitment assistant and HR-style secretary for employers on the Clinforce platform.

- Primary role: help employers review and rate candidates, analyze resumes and job descriptions, suggest interview questions, and draft clear professional messages.
- You can search the internal candidate database using the 'search_candidates' tool when they want suggestions from the platform.
- When the user uploads files (CVs, job descriptions, company documents), use their content to answer questions, summarize, and provide 1–5 ratings with clear reasoning when asked.
- If a document cannot be fully read, do not say that the system failed to process it. Instead, acknowledge that you have limited visibility into the document, still give your best assessment based on the file name and the user's description, and politely invite them to paste key text if they want a more detailed analysis.
- Be concise, structured, and practical. Avoid legal, tax, or medical advice.

Current User Context:
- ID: {$uid}
- Role: {$urole}
";

        if (!empty($fileSummaries)) {
            $baseContent .= "\nUploaded files for this request:\n\n" . implode("\n\n", $fileSummaries);
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
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'OpenAI API key not configured.'], 500);
        }

        $response = Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'tools' => $tools,
            'tool_choice' => 'auto',
        ]);

        if ($response->failed()) {
            Log::error('OpenAI API Error', ['body' => $response->body()]);
            $err = $response->json()['error']['message'] ?? 'AI service unavailable.';
            return response()->json(['error' => $err], 503);
        }

        $responseData = $response->json();
        $message = $responseData['choices'][0]['message'];

        // 2. Check for Tool Calls
        if (isset($message['tool_calls'])) {
            // Append the assistant's "tool call" message to history
            $messages[] = $message;

            foreach ($message['tool_calls'] as $toolCall) {
                $fnName = $toolCall['function']['name'];
                $args = json_decode($toolCall['function']['arguments'], true);
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
            $response2 = Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
            ]);

            if ($response2->failed()) {
                return response()->json(['error' => 'AI service unavailable during tool processing.'], 503);
            }
            
            return response()->json($response2->json()['choices'][0]['message']);
        }

        // No tool call, just return text
        return response()->json($message);
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
        // Simple search logic
        $q = trim($query);
        if (!$q) return [];

        // 1. Try exact phrase match first across key fields
        $results = ApplicantProfile::query()
            ->where(function($sub) use ($q) {
                $sub->where('headline', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get(['user_id', 'first_name', 'last_name', 'headline', 'city', 'years_experience']);

        // 2. Fallback: If no results and query has multiple words, try broad keyword search
        if ($results->isEmpty() && str_contains($q, ' ')) {
            $keywords = explode(' ', $q);
            $results = ApplicantProfile::query()
                ->where(function($sub) use ($keywords) {
                    foreach ($keywords as $word) {
                        $word = trim($word);
                        if (strlen($word) > 2) { // Ignore short words like "in", "at"
                             $sub->orWhere('headline', 'like', "%{$word}%")
                                 ->orWhere('summary', 'like', "%{$word}%")
                                 ->orWhere('city', 'like', "%{$word}%")
                                 ->orWhere('first_name', 'like', "%{$word}%")
                                 ->orWhere('last_name', 'like', "%{$word}%");
                        }
                    }
                })
                ->limit(5)
                ->get(['user_id', 'first_name', 'last_name', 'headline', 'city', 'years_experience']);
        }

        if ($results->isEmpty()) return "No candidates found matching '{$q}'. Try broader keywords.";
        return $results->toArray();
    }

    private function searchJobs($query)
    {
        $q = trim($query);
        if (!$q) return [];

        $results = Job::query()
            ->where('status', 'published') // Only published jobs
            ->where(function($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get(['id', 'title', 'employment_type', 'city', 'work_mode']);

        if ($results->isEmpty()) return "No jobs found matching '{$q}'.";
        return $results->toArray();
    }
}
