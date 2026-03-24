<?php

namespace App\Http\Controllers\Api;

use App\Models\Interview;
use App\Models\InterviewFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewFeedbackController extends ApiController
{
    public function show(Interview $interview): JsonResponse
    {
        $u = $this->requireAuth();
        $this->assertCanAccess($u, $interview);

        $feedback = InterviewFeedback::where('interview_id', $interview->id)->first();
        return $this->ok($feedback);
    }

    public function store(Request $request, Interview $interview): JsonResponse
    {
        $u = $this->requireAuth();
        $this->assertCanAccess($u, $interview);

        $v = $request->validate([
            'rating'              => ['required', 'integer', 'min:1', 'max:5'],
            'notes'               => ['nullable', 'string', 'max:5000'],
            'technical_score'     => ['nullable', 'integer', 'min:1', 'max:5'],
            'communication_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'culture_fit_score'   => ['nullable', 'integer', 'min:1', 'max:5'],
            'recommendation'      => ['nullable', 'in:hire,reject,consider,recommend,neutral,do_not_recommend'],
        ]);

        $feedback = InterviewFeedback::updateOrCreate(
            ['interview_id' => $interview->id],
            array_merge($v, ['submitted_by_user_id' => $u->id])
        );

        return $this->ok($feedback, 'Feedback saved', 201);
    }

    private function assertCanAccess($u, Interview $interview): void
    {
        if ($u->role === 'admin') return;

        $interview->loadMissing('application.job');
        $job = $interview->application?->job;

        $isOwner = $job
            && in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if (!$isOwner) abort(403);
    }
}
