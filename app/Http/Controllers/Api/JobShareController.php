<?php

namespace App\Http\Controllers\Api;

use App\Models\Job;
use App\Models\JobShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobShareController extends ApiController
{
    /** POST /jobs/{job}/share — create or return existing share link */
    public function store(Request $request, Job $job): JsonResponse
    {
        $u = $this->requireAuth();

        $share = JobShare::firstOrCreate(
            ['job_id' => $job->id, 'shared_by_user_id' => $u->id],
            ['share_token' => JobShare::generateToken(), 'clicks' => 0, 'created_at' => now()]
        );

        $url = url("/candidate/jobs/{$job->id}?ref={$share->share_token}");

        return $this->ok(['share_token' => $share->share_token, 'url' => $url, 'clicks' => $share->clicks]);
    }

    /** GET /jobs/{job}/share-analytics */
    public function analytics(Job $job): JsonResponse
    {
        $u = $this->requireAuth();

        $job->loadMissing([]);
        if ($u->role !== 'admin') {
            if (!in_array($u->role, ['employer', 'agency'], true)
                || $job->owner_user_id !== $u->id) {
                abort(403);
            }
        }

        $shares = JobShare::where('job_id', $job->id)
            ->orderByDesc('clicks')
            ->get(['share_token', 'clicks', 'created_at']);

        return $this->ok(['shares' => $shares, 'total_clicks' => $shares->sum('clicks')]);
    }

    /** GET /share/{token} — track click and redirect (web route) */
    public function track(string $token)
    {
        $share = JobShare::where('share_token', $token)->first();
        if (!$share) abort(404);

        $share->increment('clicks');

        return redirect("/candidate/jobs/{$share->job_id}");
    }
}
