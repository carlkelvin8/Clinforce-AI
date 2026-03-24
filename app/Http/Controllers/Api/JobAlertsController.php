<?php

namespace App\Http\Controllers\Api;

use App\Models\JobAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobAlertsController extends ApiController
{
    public function index(): JsonResponse
    {
        $u = $this->requireAuth();
        $alerts = JobAlert::where('user_id', $u->id)->orderByDesc('id')->get();
        return $this->ok($alerts->map(fn($a) => array_merge($a->toArray(), ['is_active' => (bool) $a->active])));
    }

    public function store(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        $v = $request->validate([
            'keywords'        => ['nullable', 'string', 'max:200'],
            'location'        => ['nullable', 'string', 'max:200'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,temporary,internship'],
            'work_mode'       => ['nullable', 'in:remote,on_site,hybrid'],
            'is_active'       => ['nullable', 'boolean'],
        ]);

        $alert = JobAlert::create([
            'user_id'         => $u->id,
            'keywords'        => $v['keywords'] ?? null,
            'location'        => $v['location'] ?? null,
            'employment_type' => $v['employment_type'] ?? null,
            'active'          => $v['is_active'] ?? true,
        ]);

        return $this->ok($this->withIsActive($alert), 'Alert created', 201);
    }

    public function update(Request $request, JobAlert $jobAlert): JsonResponse
    {
        $u = $this->requireAuth();
        if ($jobAlert->user_id !== $u->id) abort(403);

        $v = $request->validate([
            'keywords'        => ['nullable', 'string', 'max:200'],
            'location'        => ['nullable', 'string', 'max:200'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,temporary,internship'],
            'work_mode'       => ['nullable', 'in:remote,on_site,hybrid'],
            'is_active'       => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('is_active', $v)) {
            $v['active'] = $v['is_active'];
            unset($v['is_active']);
        }

        $jobAlert->update($v);
        return $this->ok($this->withIsActive($jobAlert->fresh()), 'Alert updated');
    }

    public function destroy(JobAlert $jobAlert): JsonResponse
    {
        $u = $this->requireAuth();
        if ($jobAlert->user_id !== $u->id) abort(403);
        $jobAlert->delete();
        return $this->ok(null, 'Alert deleted');
    }

    private function withIsActive(JobAlert $alert): array
    {
        $arr = $alert->toArray();
        $arr['is_active'] = (bool) $alert->active;
        return $arr;
    }
}
