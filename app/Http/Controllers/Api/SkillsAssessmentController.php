<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentTemplate;
use App\Models\SkillsAssessment;
use App\Models\VerifiedSkill;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SkillsAssessmentController extends ApiController
{
    private AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * List available assessment templates
     */
    public function templates(Request $request)
    {
        $query = AssessmentTemplate::active();

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        if ($request->has('skill')) {
            $query->bySkill($request->skill);
        }

        $templates = $query->orderBy('difficulty')->orderBy('title')->get();

        // Add user's completion status
        $templates->each(function ($template) use ($request) {
            $user = $request->user();
            $userAttempt = $template->assessments()
                ->where('user_id', $user->id)
                ->where('passed', true)
                ->orderBy('score', 'desc')
                ->first();

            $template->user_passed = $userAttempt ? true : false;
            $template->user_best_score = $userAttempt ? $userAttempt->score : null;
            $template->user_attempts_count = $template->assessments()->where('user_id', $user->id)->count();
        });

        return $this->ok($templates);
    }

    /**
     * Start an assessment
     */
    public function start(Request $request, AssessmentTemplate $template)
    {
        $user = $request->user();
        
        // Check if user can attempt
        $canAttempt = $template->canUserAttempt($user);
        
        if (!$canAttempt['can_attempt']) {
            return $this->fail($canAttempt['reason'], 429);
        }

        $attemptNumber = $canAttempt['next_attempt_number'] ?? 1;

        // Return questions (without answers)
        $questions = collect($template->questions)->map(function ($q) {
            return [
                'id' => $q['id'],
                'question' => $q['question'],
                'options' => $q['options'] ?? [],
                'type' => $q['type'] ?? 'multiple_choice',
            ];
        });

        $assessment = SkillsAssessment::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'attempt_number' => $attemptNumber,
            'total_questions' => count($template->questions),
            'started_at' => now(),
            'completed_at' => null,
            'score' => 0,
            'correct_answers' => 0,
            'passed' => false,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->ok([
            'assessment_id' => $assessment->id,
            'template' => [
                'id' => $template->id,
                'title' => $template->title,
                'duration_minutes' => $template->duration_minutes,
                'passing_score' => $template->passing_score,
                'difficulty' => $template->difficulty,
            ],
            'questions' => $questions,
            'attempt_number' => $attemptNumber,
            'started_at' => $assessment->started_at,
        ]);
    }

    /**
     * Submit assessment answers
     */
    public function submit(Request $request, SkillsAssessment $assessment)
    {
        $user = $request->user();
        
        if ($assessment->user_id !== $user->id) {
            return $this->fail('Unauthorized', 403);
        }

        if ($assessment->completed_at) {
            return $this->fail('Assessment already submitted', 400);
        }

        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'time_taken_seconds' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors(), 422);
        }

        $template = $assessment->template;
        $answers = $request->answers;
        $timeTaken = $request->time_taken_seconds ?? now()->diffInSeconds($assessment->started_at);

        // Grade answers
        $correctAnswers = 0;
        $totalQuestions = count($template->questions);

        foreach ($template->questions as $q) {
            $userAnswer = $answers[$q['id']] ?? null;
            $correctAnswer = $q['correct_answer'] ?? null;

            if ($userAnswer === $correctAnswer) {
                $correctAnswers++;
            }
        }

        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;
        $passed = $score >= $template->passing_score;

        // Generate AI feedback for weak areas
        $weakAreas = [];
        $questionResults = [];
        
        foreach ($template->questions as $q) {
            $userAnswer = $answers[$q['id']] ?? null;
            $correctAnswer = $q['correct_answer'] ?? null;
            $isCorrect = $userAnswer === $correctAnswer;

            $questionResults[] = [
                'question_id' => $q['id'],
                'question' => $q['question'],
                'correct' => $isCorrect,
                'category' => $q['category'] ?? $template->skill_name,
            ];

            if (!$isCorrect) {
                $weakAreas[] = $q['category'] ?? $template->skill_name;
            }
        }

        // Generate AI feedback
        $feedback = null;
        if (!empty($weakAreas)) {
            $feedback = $this->generateFeedback($template, $weakAreas, $score);
        }

        // Update assessment
        $assessment->update([
            'score' => $score,
            'correct_answers' => $correctAnswers,
            'time_taken_seconds' => $timeTaken,
            'passed' => $passed,
            'answers' => $answers,
            'feedback' => $feedback,
            'weak_areas' => array_unique($weakAreas),
            'completed_at' => now(),
        ]);

        // Create verified skill if passed with good score
        if ($passed && $score >= 80) {
            VerifiedSkill::updateOrCreate(
                ['user_id' => $user->id, 'skill_name' => $template->skill_name],
                [
                    'assessment_id' => $assessment->id,
                    'proficiency_level' => $score,
                    'is_verified' => true,
                    'verified_at' => now(),
                    'badge_url' => "/badges/skills/{$template->slug}.svg",
                ]
            );
        }

        // Increment template completions
        $template->incrementCompletions();

        return $this->ok([
            'assessment_id' => $assessment->id,
            'score' => $score,
            'grade' => $assessment->getScoreGrade(),
            'passed' => $passed,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'time_taken' => $assessment->getTimeFormatted(),
            'feedback' => $feedback,
            'verified_skill_created' => $passed && $score >= 80,
        ]);
    }

    /**
     * Get user's assessment history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $assessments = SkillsAssessment::with('template:id,title,skill_name,category,difficulty')
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->get();

        $summary = [
            'total_attempts' => $assessments->count(),
            'passed_count' => $assessments->where('passed', true)->count(),
            'failed_count' => $assessments->where('passed', false)->count(),
            'average_score' => round($assessments->avg('score'), 2),
            'best_score' => $assessments->max('score'),
            'skills_verified' => VerifiedSkill::where('user_id', $user->id)
                ->where('is_verified', true)
                ->count(),
        ];

        return $this->ok([
            'assessments' => $assessments,
            'summary' => $summary,
        ]);
    }

    /**
     * Get verified skills
     */
    public function verifiedSkills(Request $request)
    {
        $user = $request->user();
        
        $skills = VerifiedSkill::with('assessment.template:id,title')
            ->where('user_id', $user->id)
            ->orderBy('proficiency_level', 'desc')
            ->get();

        return $this->ok($skills);
    }

    /**
     * Generate AI feedback
     */
    private function generateFeedback($template, array $weakAreas, int $score): array
    {
        // Simple feedback generation (can be enhanced with AI)
        $strengthAreas = array_unique(array_column($template->questions, 'category'));
        $strengthAreas = array_diff($strengthAreas, $weakAreas);

        return [
            'overall' => $this->getOverallFeedback($score, $template->difficulty),
            'strengths' => array_values($strengthAreas),
            'areas_for_improvement' => array_values($weakAreas),
            'recommendations' => $this->getRecommendations($score, $weakAreas),
            'next_steps' => $score >= 80 
                ? 'Consider taking advanced level assessment'
                : 'Review the weak areas and retake the assessment after study',
        ];
    }

    private function getOverallFeedback(int $score, string $difficulty): string
    {
        if ($score >= 95) {
            return "Outstanding! You've demonstrated expert-level knowledge in {$difficulty} {$difficulty}.";
        } elseif ($score >= 85) {
            return "Excellent performance! You have strong understanding of the subject matter.";
        } elseif ($score >= 70) {
            return "Good job! You passed the assessment. Focus on weak areas to improve further.";
        } else {
            return "Keep studying! Review the recommended materials and retake when ready.";
        }
    }

    private function getRecommendations(int $score, array $weakAreas): array
    {
        $recommendations = [];
        
        foreach ($weakAreas as $area) {
            $recommendations[] = "Study {$area} fundamentals";
            $recommendations[] = "Practice {$area} case studies";
        }

        return array_unique($recommendations);
    }
}
