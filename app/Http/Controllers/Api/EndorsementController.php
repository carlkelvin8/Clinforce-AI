<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Endorsement;
use App\Models\EndorsementVote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EndorsementController extends ApiController
{
    /**
     * Get endorsements received by current user
     */
    public function myEndorsements(Request $request)
    {
        $user = $request->user();

        $endorsements = Endorsement::with(['endorser:id,first_name,last_name,role,avatar'])
            ->where('recipient_user_id', $user->id)
            ->where('is_hidden', false)
            ->withCount('votes')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by type
        $grouped = [
            'skill' => $endorsements->where('type', 'skill')->values(),
            'recommendation' => $endorsements->where('type', 'recommendation')->values(),
            'character' => $endorsements->where('type', 'character')->values(),
            'work_ethic' => $endorsements->where('type', 'work_ethic')->values(),
            'leadership' => $endorsements->where('type', 'leadership')->values(),
        ];

        $summary = [
            'total_endorsements' => $endorsements->count(),
            'verified_endorsements' => $endorsements->where('is_verified', true)->count(),
            'average_rating' => round($endorsements->whereNotNull('rating')->avg('rating'), 2),
            'top_skills' => $endorsements->where('type', 'skill')
                ->groupBy('skill_name')
                ->map(fn($group, $key) => [
                    'skill' => $key,
                    'count' => $group->count(),
                    'verified' => $group->where('is_verified', true)->count(),
                ])
                ->sortByDesc('count')
                ->take(5)
                ->values(),
        ];

        return $this->ok([
            'endorsements' => $endorsements,
            'grouped' => $grouped,
            'summary' => $summary,
        ]);
    }

    /**
     * Get endorsements for a specific user (public)
     */
    public function showUserEndorsements(Request $request, int $userId)
    {
        $endorsements = Endorsement::with(['endorser:id,first_name,last_name,role'])
            ->where('recipient_user_id', $userId)
            ->where('is_hidden', false)
            ->visible()
            ->recent()
            ->get();

        return $this->ok($endorsements);
    }

    /**
     * Give endorsement to another user
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'recipient_user_id' => 'required|exists:users,id|different:user_id',
            'type' => 'required|in:skill,recommendation,character,work_ethic,leadership',
            'skill_name' => 'nullable|string|max:100|required_if:type,skill',
            'message' => 'nullable|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
            'relationship' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors(), 422);
        }

        // Prevent self-endorsement
        if ($request->recipient_user_id == $user->id) {
            return $this->fail('You cannot endorse yourself', 400);
        }

        // Check for duplicate endorsement
        $existing = Endorsement::where('recipient_user_id', $request->recipient_user_id)
            ->where('endorser_user_id', $user->id)
            ->where('skill_name', $request->skill_name)
            ->where('type', $request->type)
            ->first();

        if ($existing) {
            return $this->fail('You have already endorsed this skill for this user', 409);
        }

        $endorsement = Endorsement::create(array_merge(
            $validator->validated(),
            ['endorser_user_id' => $user->id]
        ));

        // Auto-verify if from same company
        if ($endorsement->checkVerification()) {
            $endorsement->verify();
        }

        // Create notification
        $recipient = User::find($request->recipient_user_id);
        if ($recipient) {
            $recipient->pushNotification(
                'new_endorsement',
                "You received a {$endorsement->type} endorsement from {$user->full_name}",
                ['endorsement_id' => $endorsement->id]
            );
        }

        return $this->ok($endorsement->load('endorser:id,first_name,last_name,role'), 201);
    }

    /**
     * Update endorsement
     */
    public function update(Request $request, Endorsement $endorsement)
    {
        $user = $request->user();
        
        if ($endorsement->endorser_user_id !== $user->id) {
            return $this->fail('Unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
            'relationship' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors(), 422);
        }

        $endorsement->update($validator->validated());

        return $this->ok($endorsement);
    }

    /**
     * Delete endorsement
     */
    public function destroy(Request $request, Endorsement $endorsement)
    {
        $user = $request->user();
        
        if ($endorsement->endorser_user_id !== $user->id) {
            return $this->fail('Unauthorized', 403);
        }

        $endorsement->delete();

        return $this->ok(['message' => 'Endorsement deleted']);
    }

    /**
     * Hide endorsement (recipient only)
     */
    public function hide(Request $request, Endorsement $endorsement)
    {
        $user = $request->user();
        
        if ($endorsement->recipient_user_id !== $user->id) {
            return $this->fail('Unauthorized', 403);
        }

        $endorsement->hide();

        return $this->ok(['message' => 'Endorsement hidden']);
    }

    /**
     * Show hidden endorsement
     */
    public function show(Request $request, Endorsement $endorsement)
    {
        $user = $request->user();
        
        if ($endorsement->recipient_user_id !== $user->id) {
            return $this->fail('Unauthorized', 403);
        }

        $endorsement->show();

        return $this->ok(['message' => 'Endorsement shown']);
    }

    /**
     * Vote on endorsement helpfulness
     */
    public function vote(Request $request, Endorsement $endorsement)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'is_helpful' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors(), 422);
        }

        // Check if already voted
        $existingVote = EndorsementVote::where('endorsement_id', $endorsement->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingVote) {
            return $this->fail('You have already voted on this endorsement', 409);
        }

        EndorsementVote::create([
            'endorsement_id' => $endorsement->id,
            'user_id' => $user->id,
            'is_helpful' => $request->is_helpful,
        ]);

        if ($request->is_helpful) {
            $endorsement->increment('helpful_count');
        }

        return $this->ok([
            'message' => 'Vote recorded',
            'helpful_count' => $endorsement->helpful_count,
        ]);
    }

    /**
     * Get skill endorsement suggestions
     */
    public function suggestEndorsees(Request $request)
    {
        $user = $request->user();

        // Suggest colleagues from same applications or companies
        $applications = $user->jobApplications()->with('job.applications.user')->get();
        
        $colleagues = collect();
        
        foreach ($applications as $application) {
            $jobApplications = $application->job->applications;
            foreach ($jobApplications as $jobApp) {
                if ($jobApp->user_id !== $user->id) {
                    $colleague = User::with('applicantProfile')
                        ->where('id', $jobApp->user_id)
                        ->where('role', 'applicant')
                        ->first();
                    
                    if ($colleague) {
                        $colleagues->push($colleague);
                    }
                }
            }
        }

        $endorsedIds = Endorsement::where('endorser_user_id', $user->id)
            ->pluck('recipient_user_id')
            ->toArray();

        $suggestions = $colleagues->unique('id')
            ->filter(fn($u) => !in_array($u->id, $endorsedIds))
            ->take(10)
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->full_name,
                'headline' => $u->applicantProfile->headline ?? null,
                'skills' => $u->applicantProfile->skills ?? [],
            ])
            ->values();

        return $this->ok($suggestions);
    }
}
