<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicEmployerController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        // Support both slug and numeric ID
        $profile = is_numeric($slug)
            ? EmployerProfile::where('user_id', (int)$slug)->firstOrFail()
            : EmployerProfile::where('slug', $slug)->firstOrFail();

        $jobs = Job::where('owner_user_id', $profile->user_id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get(['id', 'title', 'employment_type', 'work_mode', 'city', 'country', 'published_at']);

        return response()->json([
            'data' => [
                'id'                  => $profile->user_id,
                'business_name'       => $profile->business_name,
                'business_type'       => $profile->business_type,
                'description'         => $profile->description,
                'website_url'         => $profile->website_url,
                'country'             => $profile->country,
                'city'                => $profile->city,
                'verification_status' => $profile->verification_status,
                'jobs'                => $jobs,
                'jobs_count'          => $jobs->count(),
            ],
        ]);
    }
}
