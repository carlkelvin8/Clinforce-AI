<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TalentPoolController extends ApiController
{
    // ========== TALENT POOLS ==========
    
    public function index(Request $request)
    {
        $user = $request->user();
        
        $pools = DB::table('talent_pools')
            ->where('employer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($pool) {
                $pool->target_skills = json_decode($pool->target_skills);
                $pool->target_experience = json_decode($pool->target_experience);
                
                // Get candidate count
                $pool->candidate_count = DB::table('talent_pool_candidates')
                    ->where('talent_pool_id', $pool->id)
                    ->where('status', 'active')
                    ->count();
                
                return $pool;
            });
        
        return response()->json(['data' => $pools]);
    }
    
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pool_type' => 'required|in:general,future_role,passive,high_potential',
            'target_skills' => 'nullable|array',
            'target_experience' => 'nullable|array',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        $poolId = DB::table('talent_pools')->insertGetId([
            'employer_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'pool_type' => $request->pool_type,
            'target_skills' => json_encode($request->target_skills ?? []),
            'target_experience' => json_encode($request->target_experience ?? []),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $pool = DB::table('talent_pools')->find($poolId);
        $pool->target_skills = json_decode($pool->target_skills);
        $pool->target_experience = json_decode($pool->target_experience);
        $pool->candidate_count = 0;
        
        return response()->json(['message' => 'Talent pool created', 'data' => $pool], 201);
    }
    
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $pool = DB::table('talent_pools')
            ->where('id', $id)
            ->where('employer_id', $user->id)
            ->first();
        
        if (!$pool) {
            return response()->json(['message' => 'Talent pool not found'], 404);
        }
        
        $pool->target_skills = json_decode($pool->target_skills);
        $pool->target_experience = json_decode($pool->target_experience);
        
        // Get candidates
        $candidates = DB::table('talent_pool_candidates as tpc')
            ->join('users as u', 'tpc.candidate_id', '=', 'u.id')
            ->leftJoin('applicant_profiles as ap', 'u.id', '=', 'ap.user_id')
            ->where('tpc.talent_pool_id', $id)
            ->select(
                'tpc.*',
                'u.email',
                'ap.first_name',
                'ap.last_name',
                'ap.public_display_name',
                'ap.avatar',
                'ap.headline',
                'ap.location'
            )
            ->orderBy('tpc.priority', 'asc')
            ->orderBy('tpc.created_at', 'desc')
            ->get()
            ->map(function ($candidate) {
                $candidate->notes = $candidate->notes;
                
                // Get tags
                $candidate->tags = DB::table('candidate_tags as ct')
                    ->join('talent_tags as tt', 'ct.talent_tag_id', '=', 'tt.id')
                    ->where('ct.candidate_id', $candidate->candidate_id)
                    ->select('tt.id', 'tt.name', 'tt.color', 'tt.category')
                    ->get();
                
                return $candidate;
            });
        
        $pool->candidates = $candidates;
        
        return response()->json(['data' => $pool]);
    }
    
    public function update(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'pool_type' => 'sometimes|in:general,future_role,passive,high_potential',
            'target_skills' => 'nullable|array',
            'target_experience' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        $pool = DB::table('talent_pools')
            ->where('id', $id)
            ->where('employer_id', $user->id)
            ->first();
        
        if (!$pool) {
            return response()->json(['message' => 'Talent pool not found'], 404);
        }
        
        $updateData = ['updated_at' => now()];
        
        if ($request->has('name')) $updateData['name'] = $request->name;
        if ($request->has('description')) $updateData['description'] = $request->description;
        if ($request->has('pool_type')) $updateData['pool_type'] = $request->pool_type;
        if ($request->has('target_skills')) $updateData['target_skills'] = json_encode($request->target_skills);
        if ($request->has('target_experience')) $updateData['target_experience'] = json_encode($request->target_experience);
        if ($request->has('is_active')) $updateData['is_active'] = $request->is_active;
        
        DB::table('talent_pools')->where('id', $id)->update($updateData);
        
        return response()->json(['message' => 'Talent pool updated']);
    }
    
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $deleted = DB::table('talent_pools')
            ->where('id', $id)
            ->where('employer_id', $user->id)
            ->delete();
        
        if (!$deleted) {
            return response()->json(['message' => 'Talent pool not found'], 404);
        }
        
        return response()->json(['message' => 'Talent pool deleted']);
    }
    
    // ========== POOL CANDIDATES ==========
    
    public function addCandidate(Request $request, $poolId)
    {
        $v = Validator::make($request->all(), [
            'candidate_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
            'priority' => 'nullable|integer|min:1|max:3',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        // Verify pool ownership
        $pool = DB::table('talent_pools')
            ->where('id', $poolId)
            ->where('employer_id', $user->id)
            ->first();
        
        if (!$pool) {
            return response()->json(['message' => 'Talent pool not found'], 404);
        }
        
        // Check if already exists
        $exists = DB::table('talent_pool_candidates')
            ->where('talent_pool_id', $poolId)
            ->where('candidate_id', $request->candidate_id)
            ->exists();
        
        if ($exists) {
            return response()->json(['message' => 'Candidate already in pool'], 409);
        }
        
        DB::table('talent_pool_candidates')->insert([
            'talent_pool_id' => $poolId,
            'candidate_id' => $request->candidate_id,
            'added_by' => $user->id,
            'notes' => $request->notes,
            'priority' => $request->priority ?? 3,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return response()->json(['message' => 'Candidate added to pool'], 201);
    }
    
    public function removeCandidate(Request $request, $poolId, $candidateId)
    {
        $user = $request->user();
        
        // Verify pool ownership
        $pool = DB::table('talent_pools')
            ->where('id', $poolId)
            ->where('employer_id', $user->id)
            ->first();
        
        if (!$pool) {
            return response()->json(['message' => 'Talent pool not found'], 404);
        }
        
        $deleted = DB::table('talent_pool_candidates')
            ->where('talent_pool_id', $poolId)
            ->where('candidate_id', $candidateId)
            ->delete();
        
        if (!$deleted) {
            return response()->json(['message' => 'Candidate not found in pool'], 404);
        }
        
        return response()->json(['message' => 'Candidate removed from pool']);
    }
    
    public function updateCandidateStatus(Request $request, $poolId, $candidateId)
    {
        $v = Validator::make($request->all(), [
            'status' => 'required|in:active,contacted,engaged,hired,removed',
            'notes' => 'nullable|string',
            'priority' => 'nullable|integer|min:1|max:3',
            'next_contact_at' => 'nullable|date',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        // Verify pool ownership
        $pool = DB::table('talent_pools')
            ->where('id', $poolId)
            ->where('employer_id', $user->id)
            ->first();
        
        if (!$pool) {
            return response()->json(['message' => 'Talent pool not found'], 404);
        }
        
        $updateData = [
            'status' => $request->status,
            'updated_at' => now(),
        ];
        
        if ($request->has('notes')) $updateData['notes'] = $request->notes;
        if ($request->has('priority')) $updateData['priority'] = $request->priority;
        if ($request->has('next_contact_at')) $updateData['next_contact_at'] = $request->next_contact_at;
        
        if ($request->status === 'contacted') {
            $updateData['last_contacted_at'] = now();
        }
        
        $updated = DB::table('talent_pool_candidates')
            ->where('talent_pool_id', $poolId)
            ->where('candidate_id', $candidateId)
            ->update($updateData);
        
        if (!$updated) {
            return response()->json(['message' => 'Candidate not found in pool'], 404);
        }
        
        return response()->json(['message' => 'Candidate status updated']);
    }
    
    // ========== TAGS ==========
    
    public function getTags(Request $request)
    {
        $user = $request->user();
        
        $tags = DB::table('talent_tags')
            ->where('employer_id', $user->id)
            ->orderBy('name')
            ->get();
        
        return response()->json(['data' => $tags]);
    }
    
    public function createTag(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
            'category' => 'nullable|in:skill,experience,location,industry',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        // Check if tag exists
        $exists = DB::table('talent_tags')
            ->where('employer_id', $user->id)
            ->where('name', $request->name)
            ->exists();
        
        if ($exists) {
            return response()->json(['message' => 'Tag already exists'], 409);
        }
        
        $tagId = DB::table('talent_tags')->insertGetId([
            'employer_id' => $user->id,
            'name' => $request->name,
            'color' => $request->color ?? '#3B82F6',
            'category' => $request->category,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $tag = DB::table('talent_tags')->find($tagId);
        
        return response()->json(['message' => 'Tag created', 'data' => $tag], 201);
    }
    
    public function tagCandidate(Request $request, $candidateId)
    {
        $v = Validator::make($request->all(), [
            'tag_id' => 'required|exists:talent_tags,id',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        // Verify tag ownership
        $tag = DB::table('talent_tags')
            ->where('id', $request->tag_id)
            ->where('employer_id', $user->id)
            ->first();
        
        if (!$tag) {
            return response()->json(['message' => 'Tag not found'], 404);
        }
        
        // Check if already tagged
        $exists = DB::table('candidate_tags')
            ->where('candidate_id', $candidateId)
            ->where('talent_tag_id', $request->tag_id)
            ->exists();
        
        if ($exists) {
            return response()->json(['message' => 'Candidate already has this tag'], 409);
        }
        
        DB::table('candidate_tags')->insert([
            'candidate_id' => $candidateId,
            'talent_tag_id' => $request->tag_id,
            'tagged_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return response()->json(['message' => 'Tag added to candidate']);
    }
    
    public function untagCandidate(Request $request, $candidateId, $tagId)
    {
        $user = $request->user();
        
        // Verify tag ownership
        $tag = DB::table('talent_tags')
            ->where('id', $tagId)
            ->where('employer_id', $user->id)
            ->first();
        
        if (!$tag) {
            return response()->json(['message' => 'Tag not found'], 404);
        }
        
        DB::table('candidate_tags')
            ->where('candidate_id', $candidateId)
            ->where('talent_tag_id', $tagId)
            ->delete();
        
        return response()->json(['message' => 'Tag removed from candidate']);
    }
    
    // ========== NURTURE CAMPAIGNS ==========
    
    public function getCampaigns(Request $request)
    {
        $user = $request->user();
        
        $campaigns = DB::table('nurture_campaigns')
            ->where('employer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($campaign) {
                $campaign->target_pools = json_decode($campaign->target_pools);
                
                // Get enrollment count
                $campaign->enrollment_count = DB::table('nurture_enrollments')
                    ->where('nurture_campaign_id', $campaign->id)
                    ->count();
                
                // Get step count
                $campaign->step_count = DB::table('nurture_campaign_steps')
                    ->where('nurture_campaign_id', $campaign->id)
                    ->count();
                
                return $campaign;
            });
        
        return response()->json(['data' => $campaigns]);
    }
    
    public function createCampaign(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:manual,pool_added,time_based',
            'frequency_days' => 'nullable|integer|min:1',
            'max_touches' => 'nullable|integer|min:1',
            'target_pools' => 'nullable|array',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        $campaignId = DB::table('nurture_campaigns')->insertGetId([
            'employer_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'status' => 'draft',
            'trigger_type' => $request->trigger_type,
            'frequency_days' => $request->frequency_days ?? 30,
            'max_touches' => $request->max_touches ?? 5,
            'target_pools' => json_encode($request->target_pools ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $campaign = DB::table('nurture_campaigns')->find($campaignId);
        $campaign->target_pools = json_decode($campaign->target_pools);
        
        return response()->json(['message' => 'Campaign created', 'data' => $campaign], 201);
    }
    
    public function addCampaignStep(Request $request, $campaignId)
    {
        $v = Validator::make($request->all(), [
            'step_order' => 'required|integer|min:1',
            'delay_days' => 'required|integer|min:0',
            'message_type' => 'required|in:email,sms,notification',
            'subject' => 'required_if:message_type,email|string|max:255',
            'message_body' => 'required|string',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        // Verify campaign ownership
        $campaign = DB::table('nurture_campaigns')
            ->where('id', $campaignId)
            ->where('employer_id', $user->id)
            ->first();
        
        if (!$campaign) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }
        
        DB::table('nurture_campaign_steps')->insert([
            'nurture_campaign_id' => $campaignId,
            'step_order' => $request->step_order,
            'delay_days' => $request->delay_days,
            'message_type' => $request->message_type,
            'subject' => $request->subject,
            'message_body' => $request->message_body,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return response()->json(['message' => 'Campaign step added'], 201);
    }
    
    public function activateCampaign(Request $request, $campaignId)
    {
        $user = $request->user();
        
        $campaign = DB::table('nurture_campaigns')
            ->where('id', $campaignId)
            ->where('employer_id', $user->id)
            ->first();
        
        if (!$campaign) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }
        
        // Check if campaign has steps
        $stepCount = DB::table('nurture_campaign_steps')
            ->where('nurture_campaign_id', $campaignId)
            ->count();
        
        if ($stepCount === 0) {
            return response()->json(['message' => 'Campaign must have at least one step'], 422);
        }
        
        DB::table('nurture_campaigns')
            ->where('id', $campaignId)
            ->update([
                'status' => 'active',
                'started_at' => now(),
                'updated_at' => now(),
            ]);
        
        return response()->json(['message' => 'Campaign activated']);
    }
    
    // ========== ANALYTICS ==========
    
    public function getAnalytics(Request $request)
    {
        $user = $request->user();
        $days = $request->query('days', 30);
        
        $startDate = Carbon::now()->subDays($days);
        
        // Total candidates in all pools
        $totalCandidates = DB::table('talent_pool_candidates as tpc')
            ->join('talent_pools as tp', 'tpc.talent_pool_id', '=', 'tp.id')
            ->where('tp.employer_id', $user->id)
            ->distinct('tpc.candidate_id')
            ->count('tpc.candidate_id');
        
        // Active pools
        $activePools = DB::table('talent_pools')
            ->where('employer_id', $user->id)
            ->where('is_active', true)
            ->count();
        
        // Candidates by status
        $candidatesByStatus = DB::table('talent_pool_candidates as tpc')
            ->join('talent_pools as tp', 'tpc.talent_pool_id', '=', 'tp.id')
            ->where('tp.employer_id', $user->id)
            ->select('tpc.status', DB::raw('count(*) as count'))
            ->groupBy('tpc.status')
            ->get();
        
        // Recent interactions
        $recentInteractions = DB::table('candidate_interactions')
            ->where('employer_id', $user->id)
            ->where('interaction_date', '>=', $startDate)
            ->count();
        
        // Engagement rate
        $engagedCount = DB::table('talent_pool_candidates as tpc')
            ->join('talent_pools as tp', 'tpc.talent_pool_id', '=', 'tp.id')
            ->where('tp.employer_id', $user->id)
            ->where('tpc.status', 'engaged')
            ->count();
        
        $engagementRate = $totalCandidates > 0 ? round(($engagedCount / $totalCandidates) * 100, 2) : 0;
        
        // Pool breakdown
        $poolBreakdown = DB::table('talent_pools as tp')
            ->leftJoin('talent_pool_candidates as tpc', 'tp.id', '=', 'tpc.talent_pool_id')
            ->where('tp.employer_id', $user->id)
            ->select('tp.id', 'tp.name', 'tp.pool_type', DB::raw('count(tpc.id) as candidate_count'))
            ->groupBy('tp.id', 'tp.name', 'tp.pool_type')
            ->get();
        
        return response()->json([
            'data' => [
                'total_candidates' => $totalCandidates,
                'active_pools' => $activePools,
                'engagement_rate' => $engagementRate,
                'recent_interactions' => $recentInteractions,
                'candidates_by_status' => $candidatesByStatus,
                'pool_breakdown' => $poolBreakdown,
            ]
        ]);
    }
    
    // ========== INTERACTIONS ==========
    
    public function logInteraction(Request $request)
    {
        $v = Validator::make($request->all(), [
            'candidate_id' => 'required|exists:users,id',
            'interaction_type' => 'required|in:email,call,message,meeting,note',
            'subject' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'interaction_date' => 'nullable|date',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        DB::table('candidate_interactions')->insert([
            'candidate_id' => $request->candidate_id,
            'employer_id' => $user->id,
            'user_id' => $user->id,
            'interaction_type' => $request->interaction_type,
            'subject' => $request->subject,
            'notes' => $request->notes,
            'interaction_date' => $request->interaction_date ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return response()->json(['message' => 'Interaction logged'], 201);
    }
    
    public function getCandidateInteractions(Request $request, $candidateId)
    {
        $user = $request->user();
        
        $interactions = DB::table('candidate_interactions as ci')
            ->leftJoin('users as u', 'ci.user_id', '=', 'u.id')
            ->where('ci.candidate_id', $candidateId)
            ->where('ci.employer_id', $user->id)
            ->select('ci.*', 'u.email as user_email')
            ->orderBy('ci.interaction_date', 'desc')
            ->get();
        
        return response()->json(['data' => $interactions]);
    }
}
