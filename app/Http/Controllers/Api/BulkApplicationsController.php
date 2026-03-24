<?php

namespace App\Http\Controllers\Api;

use App\Models\ApplicationStatusHistory;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulkApplicationsController extends ApiController
{
    private const ALLOWED_ACTIONS = ['shortlist', 'reject', 'interview'];

    public function bulkAction(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validate([
            'application_ids' => ['required', 'array', 'min:1', 'max:100'],
            'application_ids.*' => ['integer'],
            'action' => ['required', 'in:shortlist,reject,interview'],
        ]);

        $toStatus = match($v['action']) {
            'shortlist' => 'shortlisted',
            'reject'    => 'rejected',
            'interview' => 'interview',
        };

        $apps = JobApplication::query()
            ->with('job')
            ->whereIn('id', $v['application_ids'])
            ->get();

        // Verify ownership
        foreach ($apps as $app) {
            if ($u->role !== 'admin') {
                $job = $app->job;
                if (!$job
                    || $job->owner_user_id !== $u->id
                    || $job->owner_type !== $u->role) {
                    return $this->fail("Application #{$app->id} does not belong to your jobs", null, 403);
                }
            }
        }

        $updated = 0;

        DB::transaction(function () use ($apps, $toStatus, $u, &$updated) {
            foreach ($apps as $app) {
                if (in_array($app->status, ['hired', 'withdrawn'], true)) continue;

                $from = $app->status;
                $app->status = $toStatus;
                $app->save();

                ApplicationStatusHistory::create([
                    'application_id'     => $app->id,
                    'from_status'        => $from,
                    'to_status'          => $toStatus,
                    'changed_by_user_id' => $u->id,
                    'note'               => 'Bulk action',
                    'created_at'         => now(),
                ]);

                $updated++;
            }
        });

        return $this->ok(['updated' => $updated], "{$updated} application(s) updated");
    }
}
