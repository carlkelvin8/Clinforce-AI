<?php

namespace App\Http\Controllers\Api;

use App\Models\ApplicantProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TalentSearchController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        // Only allow employers or agencies or admins
        $u = $request->user();
        if (!in_array($u->role, ['employer', 'agency', 'admin'])) {
            return $this->fail('Unauthorized access.', null, 403);
        }

        $query = ApplicantProfile::query();

        // 1. Keyword search (q)
        if ($q = $request->input('q')) {
            $q = trim($q);
            $query->where(function($sub) use ($q) {
                $sub->where('headline', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%");
            });
        }

        // 2. Specialty/Role filter (optional, if you want structured filtering later)
        // For now, relies on 'headline' or 'summary' in keyword search, 
        // unless you have a specific column for 'specialty'. 
        // Based on ApplicantProfile model, we don't have a 'specialty' column, just headline/summary.
        
        // 3. Location filter
        if ($loc = $request->input('location')) {
            if ($loc !== 'all') {
                $query->where('city', 'like', "%{$loc}%");
            }
        }

        $results = $query->with('user')->paginate(20);

        // Transform results
        $data = $results->getCollection()->map(function($p) use ($q) {
            // Simple match score calculation
            $matchScore = null;
            if ($q) {
                $score = 0;
                $qLower = strtolower($q);
                // Weight headline matches higher
                if (str_contains(strtolower($p->headline ?? ''), $qLower)) $score += 50;
                // Summary matches
                if (str_contains(strtolower($p->summary ?? ''), $qLower)) $score += 30;
                // City matches
                if (str_contains(strtolower($p->city ?? ''), $qLower)) $score += 20;
                // Name matches
                if (str_contains(strtolower($p->first_name . ' ' . $p->last_name), $qLower)) $score += 10;

                $matchScore = min(98, max(10, $score)); // Cap between 10 and 98
            }

            return [
                'id' => $p->user_id,
                'name' => $p->public_display_name ?? ($p->first_name . ' ' . substr($p->last_name, 0, 1) . '.'),
                'headline' => $p->headline,
                'summary' => $p->summary,
                'city' => $p->city,
                'country_code' => $p->country_code,
                'years_experience' => $p->years_experience,
                'avatar' => $p->avatar ? asset('storage/' . $p->avatar) : null,
                'updated_at' => $p->updated_at,
                'match' => $matchScore,
            ];
        });

        return $this->ok([
            'data' => $data,
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'total' => $results->total(),
            ]
        ]);
    }
}
