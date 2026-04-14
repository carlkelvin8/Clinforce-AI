<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MentorshipController extends ApiController
{
    public function getMentorProfile(Request $request)
    {
        $user = $this->requireAuth();
        
        $profile = DB::table('mentor_profiles')
            ->where('user_id', $user->id)
            ->first();

        return response()->json(['data' => $profile]);
    }

    public function createMentorProfile(Request $request)
    {
        $user = $this->requireAuth();
        
        $validated = $request->validate([
            'years_experience' => 'required|integer|min:1',
            'specialties' => 'required|array',
            'mentoring_areas' => 'required|array',
            'bio' => 'required|string|max:1000',
            'mentoring_philosophy' => 'nullable|string|max:500',
            'mentoring_style' => 'required|in:hands_on,advisory,coaching,mixed',
            'max_mentees' => 'required|integer|min:1|max:10',
            'preferred_communication' => 'required|array',
            'session_duration_minutes' => 'required|integer|min:30|max:120',
            'commitment_level' => 'required|in:casual,regular,intensive'
        ]);

        $profileId = DB::table('mentor_profiles')->insertGetId([
            'user_id' => $user->id,
            'years_experience' => $validated['years_experience'],
            'specialties' => json_encode($validated['specialties']),
            'mentoring_areas' => json_encode($validated['mentoring_areas']),
            'bio' => $validated['bio'],
            'mentoring_philosophy' => $validated['mentoring_philosophy'],
            'mentoring_style' => $validated['mentoring_style'],
            'max_mentees' => $validated['max_mentees'],
            'preferred_communication' => json_encode($validated['preferred_communication']),
            'session_duration_minutes' => $validated['session_duration_minutes'],
            'commitment_level' => $validated['commitment_level'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Mentor profile created successfully', 'id' => $profileId]);
    }

    public function updateMentorProfile(Request $request)
    {
        $user = $this->requireAuth();
        
        $validated = $request->validate([
            'is_available' => 'boolean',
            'years_experience' => 'integer|min:1',
            'specialties' => 'array',
            'mentoring_areas' => 'array',
            'bio' => 'string|max:1000',
            'mentoring_philosophy' => 'nullable|string|max:500',
            'mentoring_style' => 'in:hands_on,advisory,coaching,mixed',
            'max_mentees' => 'integer|min:1|max:10',
            'preferred_communication' => 'array',
            'session_duration_minutes' => 'integer|min:30|max:120',
            'commitment_level' => 'in:casual,regular,intensive'
        ]);

        $updateData = array_filter($validated, function($value) {
            return $value !== null;
        });

        if (isset($updateData['specialties'])) {
            $updateData['specialties'] = json_encode($updateData['specialties']);
        }
        if (isset($updateData['mentoring_areas'])) {
            $updateData['mentoring_areas'] = json_encode($updateData['mentoring_areas']);
        }
        if (isset($updateData['preferred_communication'])) {
            $updateData['preferred_communication'] = json_encode($updateData['preferred_communication']);
        }

        $updateData['updated_at'] = now();

        DB::table('mentor_profiles')
            ->where('user_id', $user->id)
            ->update($updateData);

        return response()->json(['message' => 'Mentor profile updated successfully']);
    }

    public function getMenteeProfile(Request $request)
    {
        $user = $this->requireAuth();
        
        $profile = DB::table('mentee_profiles')
            ->where('user_id', $user->id)
            ->first();

        return response()->json(['data' => $profile]);
    }

    public function createMenteeProfile(Request $request)
    {
        $user = $this->requireAuth();
        
        $validated = $request->validate([
            'career_goals' => 'required|array',
            'areas_for_development' => 'required|array',
            'preferred_mentor_qualities' => 'required|array',
            'experience_level' => 'required|in:student,new_grad,early_career,mid_career,career_change',
            'background_summary' => 'required|string|max:1000',
            'what_seeking' => 'required|string|max:500',
            'preferred_communication' => 'required|array',
            'commitment_level' => 'required|in:casual,regular,intensive',
            'has_had_mentor_before' => 'boolean',
            'previous_mentoring_experience' => 'nullable|string|max:500'
        ]);

        $profileId = DB::table('mentee_profiles')->insertGetId([
            'user_id' => $user->id,
            'career_goals' => json_encode($validated['career_goals']),
            'areas_for_development' => json_encode($validated['areas_for_development']),
            'preferred_mentor_qualities' => json_encode($validated['preferred_mentor_qualities']),
            'experience_level' => $validated['experience_level'],
            'background_summary' => $validated['background_summary'],
            'what_seeking' => $validated['what_seeking'],
            'preferred_communication' => json_encode($validated['preferred_communication']),
            'commitment_level' => $validated['commitment_level'],
            'has_had_mentor_before' => $validated['has_had_mentor_before'] ?? false,
            'previous_mentoring_experience' => $validated['previous_mentoring_experience'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Mentee profile created successfully', 'id' => $profileId]);
    }

    public function findMentors(Request $request)
    {
        $mentors = DB::table('mentor_profiles')
            ->join('users', 'mentor_profiles.user_id', '=', 'users.id')
            ->join('applicant_profiles', 'users.id', '=', 'applicant_profiles.user_id')
            ->where('mentor_profiles.is_available', true)
            ->where('mentor_profiles.current_mentees', '<', DB::raw('mentor_profiles.max_mentees'))
            ->select([
                'mentor_profiles.*',
                'users.name',
                'users.email',
                'applicant_profiles.avatar'
            ])
            ->orderBy('mentor_profiles.rating', 'desc')
            ->paginate(20);

        return response()->json(['data' => $mentors]);
    }

    public function generateMentorMatches(Request $request)
    {
        // Simple matching algorithm based on specialties and experience level
        $user = $this->requireAuth();
        $userId = $user->id;
        
        $menteeProfile = DB::table('mentee_profiles')->where('user_id', $userId)->first();
        if (!$menteeProfile) {
            return response()->json(['error' => 'Mentee profile not found'], 404);
        }

        $mentors = DB::table('mentor_profiles')
            ->join('users', 'mentor_profiles.user_id', '=', 'users.id')
            ->where('mentor_profiles.is_available', true)
            ->where('mentor_profiles.current_mentees', '<', DB::raw('mentor_profiles.max_mentees'))
            ->get();

        $matches = [];
        foreach ($mentors as $mentor) {
            $compatibilityScore = $this->calculateCompatibility($menteeProfile, $mentor);
            
            if ($compatibilityScore > 50) {
                $matches[] = [
                    'mentor_id' => $mentor->user_id,
                    'mentee_id' => $userId,
                    'compatibility_score' => $compatibilityScore,
                    'matching_factors' => json_encode([
                        'specialty_match' => true,
                        'communication_preference' => true,
                        'commitment_level' => $mentor->commitment_level === $menteeProfile->commitment_level
                    ]),
                    'compatibility_breakdown' => json_encode([
                        'experience' => 25,
                        'specialties' => 30,
                        'communication' => 20,
                        'availability' => 25
                    ]),
                    'suggested_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert matches
        if (!empty($matches)) {
            DB::table('mentorship_matches')->insert($matches);
        }

        return response()->json(['message' => 'Mentor matches generated', 'count' => count($matches)]);
    }

    private function calculateCompatibility($menteeProfile, $mentor)
    {
        // Simple compatibility scoring
        $score = 50; // Base score

        // Add points for experience match
        if ($mentor->years_experience >= 5) {
            $score += 20;
        }

        // Add points for commitment level match
        if ($mentor->commitment_level === $menteeProfile->commitment_level) {
            $score += 15;
        }

        // Add points for communication preference overlap
        $menteeComm = json_decode($menteeProfile->preferred_communication, true) ?? [];
        $mentorComm = json_decode($mentor->preferred_communication, true) ?? [];
        if (array_intersect($menteeComm, $mentorComm)) {
            $score += 15;
        }

        return min(100, $score);
    }

    public function getMentorMatches(Request $request)
    {
        $user = $this->requireAuth();
        
        $matches = DB::table('mentorship_matches')
            ->join('mentor_profiles', 'mentorship_matches.mentor_id', '=', 'mentor_profiles.user_id')
            ->join('users', 'mentor_profiles.user_id', '=', 'users.id')
            ->where('mentorship_matches.mentee_id', $user->id)
            ->where('mentorship_matches.status', 'suggested')
            ->select([
                'mentorship_matches.*',
                'mentor_profiles.bio',
                'mentor_profiles.specialties',
                'mentor_profiles.years_experience',
                'users.name'
            ])
            ->orderBy('mentorship_matches.compatibility_score', 'desc')
            ->get();

        return response()->json(['data' => $matches]);
    }

    public function requestMentorship(Request $request)
    {
        $user = $this->requireAuth();
        
        $validated = $request->validate([
            'mentor_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500'
        ]);

        $relationshipId = DB::table('mentorship_relationships')->insertGetId([
            'mentor_id' => $validated['mentor_id'],
            'mentee_id' => $user->id,
            'status' => 'pending',
            'mentee_notes' => $validated['message'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update match status
        DB::table('mentorship_matches')
            ->where('mentor_id', $validated['mentor_id'])
            ->where('mentee_id', $user->id)
            ->update(['status' => 'contacted', 'contacted_at' => now()]);

        return response()->json(['message' => 'Mentorship request sent', 'id' => $relationshipId]);
    }

    public function respondToMentorshipRequest(Request $request, $relationshipId)
    {
        $user = $this->requireAuth();
        
        $validated = $request->validate([
            'response' => 'required|in:accept,decline',
            'message' => 'nullable|string|max:500'
        ]);

        $relationship = DB::table('mentorship_relationships')->where('id', $relationshipId)->first();
        if (!$relationship) {
            return response()->json(['error' => 'Relationship not found'], 404);
        }

        if ($relationship->mentor_id != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $updateData = [
            'updated_at' => now(),
            'mentor_notes' => $validated['message'] ?? null,
        ];

        if ($validated['response'] === 'accept') {
            $updateData['status'] = 'active';
            $updateData['start_date'] = now()->toDateString();
            
            // Update mentor's current mentee count
            DB::table('mentor_profiles')
                ->where('user_id', $user->id)
                ->increment('current_mentees');
        } else {
            $updateData['status'] = 'terminated';
            $updateData['termination_reason'] = 'mentor_declined';
        }

        DB::table('mentorship_relationships')->where('id', $relationshipId)->update($updateData);

        return response()->json(['message' => 'Response recorded successfully']);
    }

    public function getMentorshipRelationships(Request $request)
    {
        $user = $this->requireAuth();
        $userId = $user->id;

        $relationships = DB::table('mentorship_relationships')
            ->leftJoin('users as mentors', 'mentorship_relationships.mentor_id', '=', 'mentors.id')
            ->leftJoin('users as mentees', 'mentorship_relationships.mentee_id', '=', 'mentees.id')
            ->where(function($query) use ($userId) {
                $query->where('mentorship_relationships.mentor_id', $userId)
                      ->orWhere('mentorship_relationships.mentee_id', $userId);
            })
            ->select([
                'mentorship_relationships.*',
                'mentors.name as mentor_name',
                'mentees.name as mentee_name'
            ])
            ->orderBy('mentorship_relationships.created_at', 'desc')
            ->get();

        return response()->json(['data' => $relationships]);
    }
}