<?php

namespace App\Http\Controllers\Api;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\ScreeningQuestion;
use App\Models\ScreeningAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScreeningQuestionsController extends ApiController
{
    /**
     * GET /jobs/{job}/screening-questions
     * List all screening questions for a job.
     */
    public function index(Job $job): JsonResponse
    {
        $u = $this->requireAuth();

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $questions = $job->screeningQuestions()
            ->orderBy('order')
            ->get();

        return $this->ok($questions);
    }

    /**
     * POST /jobs/{job}/screening-questions
     * Create or replace all screening questions for a job.
     */
    public function store(Request $request, Job $job): JsonResponse
    {
        $u = $this->requireAuth();

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|max:20',
            'questions.*.question' => 'required|string|max:500',
            'questions.*.type' => 'nullable|string|in:text,yes_no,multiple_choice,number',
            'questions.*.options' => 'nullable|array',
            'questions.*.is_knockout' => 'nullable|boolean',
            'questions.*.knockout_value' => 'nullable|string|max:50',
            'questions.*.order' => 'nullable|integer',
            'questions.*.help_text' => 'nullable|string|max:1000',
            'questions.*.is_required' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        $result = DB::transaction(function () use ($job, $request) {
            // Delete existing questions and recreate
            $job->screeningQuestions()->delete();

            $created = [];
            foreach ($request->questions as $i => $q) {
                $question = ScreeningQuestion::create([
                    'job_id' => $job->id,
                    'question' => $q['question'],
                    'type' => $q['type'] ?? 'text',
                    'options' => $q['options'] ?? null,
                    'is_knockout' => $q['is_knockout'] ?? false,
                    'knockout_value' => $q['knockout_value'] ?? null,
                    'order' => $q['order'] ?? $i,
                    'help_text' => $q['help_text'] ?? null,
                    'is_required' => $q['is_required'] ?? true,
                ]);
                $created[] = $question;
            }

            return $created;
        });

        return $this->ok(collect($result), 'Screening questions saved', 201);
    }

    /**
     * POST /jobs/{job}/screening-questions/{question}/duplicate
     */
    public function duplicate(Request $request, Job $job, ScreeningQuestion $question): JsonResponse
    {
        $u = $this->requireAuth();

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        if ($question->job_id !== $job->id) {
            return $this->fail('Question not found for this job', null, 404);
        }

        $copy = $question->replicate();
        $copy->order = $question->order + 1;
        $copy->save();

        // Reorder subsequent questions
        $job->screeningQuestions()
            ->where('order', '>', $question->order)
            ->where('id', '!=', $copy->id)
            ->increment('order');

        return $this->ok($copy, 'Question duplicated', 201);
    }

    /**
     * PUT /jobs/{job}/screening-questions/{question}
     * Update a single question.
     */
    public function update(Request $request, Job $job, ScreeningQuestion $question): JsonResponse
    {
        $u = $this->requireAuth();

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        if ($question->job_id !== $job->id) {
            return $this->fail('Question not found for this job', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'question' => 'sometimes|string|max:500',
            'type' => 'sometimes|string|in:text,yes_no,multiple_choice,number',
            'options' => 'nullable|array',
            'is_knockout' => 'sometimes|boolean',
            'knockout_value' => 'nullable|string|max:50',
            'order' => 'sometimes|integer',
            'help_text' => 'nullable|string|max:1000',
            'is_required' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        $question->update($validator->validated());

        return $this->ok($question, 'Question updated');
    }

    /**
     * DELETE /jobs/{job}/screening-questions/{question}
     */
    public function destroy(Job $job, ScreeningQuestion $question): JsonResponse
    {
        $u = $this->requireAuth();

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        if ($question->job_id !== $job->id) {
            return $this->fail('Question not found for this job', null, 404);
        }

        $question->delete();

        return $this->ok(null, 'Question deleted');
    }

    /**
     * PUT /screening-questions/reorder
     * Reorder questions for a job.
     */
    public function reorder(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        $validator = Validator::make($request->all(), [
            'job_id' => 'required|integer|exists:jobs,id',
            'order' => 'required|array',
            'order.*.id' => 'required|integer',
            'order.*.order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        $job = Job::findOrFail($request->job_id);
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        DB::transaction(function () use ($request) {
            foreach ($request->order as $item) {
                ScreeningQuestion::where('id', $item['id'])->update(['order' => $item['order']]);
            }
        });

        return $this->ok(null, 'Questions reordered');
    }

    /**
     * POST /applications/{application}/screening-answers
     * Submit answers to screening questions (called by candidate or auto-filled).
     */
    public function submitAnswers(Request $request, JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();

        // Only the applicant or job owner can submit answers
        $isApplicant = $application->applicant_user_id === $u->id;
        $application->load('job');
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $application->job?->owner_user_id === $u->id
            && $application->job?->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isApplicant && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:screening_questions,id',
            'answers.*.answer' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        $knockoutTriggered = false;
        $knockoutQuestions = [];

        $saved = DB::transaction(function () use ($request, $application, &$knockoutTriggered, &$knockoutQuestions) {
            $saved = [];

            foreach ($request->answers as $a) {
                $question = ScreeningQuestion::findOrFail($a['question_id']);

                // Verify question belongs to the same job
                if ($question->job_id !== $application->job_id) {
                    continue;
                }

                $isKnockout = $question->isKnockoutAnswer($a['answer'] ?? '');

                if ($isKnockout) {
                    $knockoutTriggered = true;
                    $knockoutQuestions[] = $question->question;
                }

                $answer = ScreeningAnswer::updateOrCreate(
                    [
                        'question_id' => $a['question_id'],
                        'application_id' => $application->id,
                    ],
                    [
                        'answer' => $a['answer'] ?? null,
                        'knockout_triggered' => $isKnockout,
                    ]
                );

                $saved[] = $answer;
            }

            return $saved;
        });

        // Auto-reject application if knockout triggered
        if ($knockoutTriggered && $isApplicant) {
            $application->update(['status' => 'rejected']);
        }

        return $this->ok([
            'answers' => $saved,
            'knockout_triggered' => $knockoutTriggered,
            'knockout_questions' => $knockoutQuestions,
            'application_status' => $application->fresh()->status,
        ], 'Answers saved', 201);
    }

    /**
     * GET /applications/{application}/screening-answers
     * Get all answers for an application.
     */
    public function getAnswers(JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();

        $application->load(['job', 'screeningAnswers.question']);

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $application->job?->owner_user_id === $u->id
            && $application->job?->owner_type === $u->role;

        $isApplicant = $application->applicant_user_id === $u->id;

        if ($u->role !== 'admin' && !$isOwner && !$isApplicant) {
            return $this->fail('Forbidden', null, 403);
        }

        $answers = $application->screeningAnswers->map(function ($answer) {
            return [
                'question_id' => $answer->question_id,
                'question' => $answer->question->question,
                'question_type' => $answer->question->type,
                'answer' => $answer->answer,
                'is_knockout' => $answer->question->is_knockout,
                'knockout_triggered' => $answer->knockout_triggered,
            ];
        });

        return $this->ok($answers);
    }
}
