<?php

namespace App\Http\Controllers\Api;

use App\Models\Job;
use App\Models\JobTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobTemplatesController extends ApiController
{
    /** GET /job-templates */
    public function index(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $templates = JobTemplate::query()
            ->where('owner_user_id', $u->id)
            ->orderByDesc('updated_at')
            ->get();

        return $this->ok($templates);
    }

    /** POST /job-templates — create from scratch or from existing job */
    public function store(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validate([
            'name'            => 'required|string|max:120',
            'title'           => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'employment_type' => 'nullable|string|max:50',
            'work_mode'       => 'nullable|string|max:50',
            'country'         => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            'salary_min'      => 'nullable|numeric|min:0',
            'salary_max'      => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:10',
            'from_job_id'     => 'nullable|integer|exists:jobs,id',
        ]);

        // If from_job_id, copy fields from that job
        if (!empty($v['from_job_id'])) {
            $job = Job::find($v['from_job_id']);
            if ($job && ($job->owner_user_id === $u->id || $u->role === 'admin')) {
                $v = array_merge([
                    'title'           => $job->title,
                    'description'     => $job->description,
                    'employment_type' => $job->employment_type,
                    'work_mode'       => $job->work_mode,
                    'country'         => $job->country,
                    'city'            => $job->city,
                    'salary_min'      => $job->salary_min,
                    'salary_max'      => $job->salary_max,
                    'salary_currency' => $job->salary_currency,
                ], $v);
            }
        }

        $template = JobTemplate::create([
            'owner_user_id'   => $u->id,
            'name'            => $v['name'],
            'title'           => $v['title'] ?? null,
            'description'     => $v['description'] ?? null,
            'employment_type' => $v['employment_type'] ?? null,
            'work_mode'       => $v['work_mode'] ?? null,
            'country'         => $v['country'] ?? null,
            'city'            => $v['city'] ?? null,
            'salary_min'      => $v['salary_min'] ?? null,
            'salary_max'      => $v['salary_max'] ?? null,
            'salary_currency' => $v['salary_currency'] ?? null,
        ]);

        return $this->ok($template, 'Template saved', 201);
    }

    /** PUT /job-templates/{template} */
    public function update(Request $request, JobTemplate $jobTemplate): JsonResponse
    {
        $u = $this->requireAuth();
        if ($jobTemplate->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validate([
            'name'            => 'sometimes|string|max:120',
            'title'           => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'employment_type' => 'nullable|string|max:50',
            'work_mode'       => 'nullable|string|max:50',
            'country'         => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            'salary_min'      => 'nullable|numeric|min:0',
            'salary_max'      => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:10',
        ]);

        $jobTemplate->update($v);
        return $this->ok($jobTemplate, 'Updated');
    }

    /** DELETE /job-templates/{template} */
    public function destroy(JobTemplate $jobTemplate): JsonResponse
    {
        $u = $this->requireAuth();
        if ($jobTemplate->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }
        $jobTemplate->delete();
        return $this->ok(null, 'Deleted');
    }

    /** POST /job-templates/{template}/use — create a draft job from template */
    public function useTemplate(JobTemplate $jobTemplate): JsonResponse
    {
        $u = $this->requireAuth();
        if ($jobTemplate->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        $job = Job::create([
            'owner_type'      => $u->role,
            'owner_user_id'   => $u->id,
            'title'           => $jobTemplate->title ?? 'Untitled Job',
            'description'     => $jobTemplate->description,
            'employment_type' => $jobTemplate->employment_type,
            'work_mode'       => $jobTemplate->work_mode,
            'country'         => $jobTemplate->country,
            'city'            => $jobTemplate->city,
            'salary_min'      => $jobTemplate->salary_min,
            'salary_max'      => $jobTemplate->salary_max,
            'salary_currency' => $jobTemplate->salary_currency,
            'status'          => 'draft',
        ]);

        return $this->ok($job, 'Draft job created from template', 201);
    }
}
