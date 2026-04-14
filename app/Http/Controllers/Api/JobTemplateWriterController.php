<?php

namespace App\Http\Controllers\Api;

use App\Models\JobTemplate;
use App\Models\TemplateAbTest;
use App\Models\ComplianceChecklist;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobTemplateWriterController extends ApiController
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * GET /job-template-writer/templates
     * Get system templates and user templates with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $category = $request->query('category');
        $roleType = $request->query('role_type');
        $includeSystem = $request->query('include_system', true);

        $query = JobTemplate::query();

        // Include system templates and user's own templates
        $query->where(function ($q) use ($u, $includeSystem) {
            $q->where('owner_user_id', $u->id);
            if (filter_var($includeSystem, FILTER_VALIDATE_BOOLEAN)) {
                $q->orWhere('is_system_template', true);
            }
        });

        if ($category) {
            $query->where('category', $category);
        }

        if ($roleType) {
            $query->where('role_type', 'LIKE', "%{$roleType}%");
        }

        $templates = $query->orderByDesc('usage_count')
            ->orderByDesc('updated_at')
            ->get();

        return $this->ok($templates);
    }

    /**
     * GET /job-template-writer/templates/{template}
     * Get single template details with view tracking
     */
    public function show(JobTemplate $template): JsonResponse
    {
        $u = $this->requireAuth();
        
        // Allow viewing system templates or own templates
        if (!$template->is_system_template && $template->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        // Track views for A/B testing
        if ($template->ab_test_id) {
            $template->incrementViews();
        }

        return $this->ok($template);
    }

    /**
     * POST /job-template-writer/ai/generate
     * Generate job description using AI
     */
    public function generate(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validate([
            'role_type' => 'required|string|max:100',
            'category' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'employment_type' => 'nullable|string|max:50',
            'work_mode' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:10',
            'experience_level' => 'nullable|string|max:50',
            'min_experience_years' => 'nullable|integer|min:0',
            'shift_type' => 'nullable|string|max:100',
            'required_certifications' => 'nullable|array',
            'required_licenses' => 'nullable|array',
            'benefits' => 'nullable|array',
        ]);

        $result = $this->aiService->generateJobDescription($v);

        if (isset($result['error'])) {
            return $this->fail($result['message'] ?? 'AI service error', null, 503);
        }

        return $this->ok($result, 'Job description generated');
    }

    /**
     * POST /job-template-writer/ai/generate-variants
     * Generate A/B test variants of job description
     */
    public function generateVariants(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validate([
            'role_type' => 'required|string|max:100',
            'category' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'employment_type' => 'nullable|string|max:50',
            'work_mode' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:10',
            'experience_level' => 'nullable|string|max:50',
            'min_experience_years' => 'nullable|integer|min:0',
            'shift_type' => 'nullable|string|max:100',
            'required_certifications' => 'nullable|array',
            'required_licenses' => 'nullable|array',
            'benefits' => 'nullable|array',
            'variant_count' => 'nullable|integer|min:2|max:4',
        ]);

        $variantCount = $v['variant_count'] ?? 2;
        unset($v['variant_count']);

        $result = $this->aiService->generateJobDescriptionVariants($v, $variantCount);

        if (isset($result['error'])) {
            return $this->fail($result['message'] ?? 'AI service error', null, 503);
        }

        return $this->ok($result, 'Job description variants generated');
    }

    /**
     * POST /job-template-writer/ai/suggestions
     * Get optimization suggestions for job description
     */
    public function suggestions(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validate([
            'description' => 'required|string',
            'role_type' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
        ]);

        $context = [
            'role_type' => $v['role_type'] ?? null,
            'category' => $v['category'] ?? null,
            'title' => $v['title'] ?? null,
        ];

        $result = $this->aiService->generateJobDescriptionSuggestions($v['description'], $context);

        if (isset($result['error'])) {
            return $this->fail($result['message'] ?? 'AI service error', null, 503);
        }

        return $this->ok($result, 'Suggestions generated');
    }

    /**
     * POST /job-template-writer/ai/compliance
     * Generate compliance checklist for a role
     */
    public function compliance(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validate([
            'role_type' => 'required|string|max:100',
            'category' => 'required|string|max:100',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'experience_level' => 'nullable|string|max:50',
        ]);

        $params = [
            'country' => $v['country'] ?? null,
            'city' => $v['city'] ?? null,
            'experience_level' => $v['experience_level'] ?? null,
        ];

        $result = $this->aiService->generateComplianceChecklist($v['role_type'], $v['category'], $params);

        if (isset($result['error'])) {
            return $this->fail($result['message'] ?? 'AI service error', null, 503);
        }

        return $this->ok($result, 'Compliance checklist generated');
    }

    /**
     * POST /job-template-writer/templates
     * Create new job template (AI-generated or manual)
     */
    public function store(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validate([
            'name' => 'required|string|max:120',
            'category' => 'nullable|string|max:100',
            'role_type' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'employment_type' => 'nullable|string|max:50',
            'work_mode' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:10',
            'is_ai_generated' => 'nullable|boolean',
            'ai_model_used' => 'nullable|string|max:50',
            'ai_suggestions' => 'nullable|array',
            'required_certifications' => 'nullable|array',
            'required_licenses' => 'nullable|array',
            'shift_type' => 'nullable|string|max:100',
            'shift_details' => 'nullable|array',
            'experience_level' => 'nullable|string|max:50',
            'min_experience_years' => 'nullable|integer|min:0',
            'benefits' => 'nullable|array',
            'compliance_checklist' => 'nullable|array',
        ]);

        $template = JobTemplate::create([
            'owner_user_id' => $u->id,
            'name' => $v['name'],
            'category' => $v['category'] ?? null,
            'role_type' => $v['role_type'] ?? null,
            'tags' => $v['tags'] ?? null,
            'title' => $v['title'] ?? null,
            'description' => $v['description'] ?? null,
            'employment_type' => $v['employment_type'] ?? null,
            'work_mode' => $v['work_mode'] ?? null,
            'country' => $v['country'] ?? null,
            'city' => $v['city'] ?? null,
            'salary_min' => $v['salary_min'] ?? null,
            'salary_max' => $v['salary_max'] ?? null,
            'salary_currency' => $v['salary_currency'] ?? null,
            'is_ai_generated' => $v['is_ai_generated'] ?? false,
            'ai_model_used' => $v['ai_model_used'] ?? null,
            'ai_suggestions' => $v['ai_suggestions'] ?? null,
            'required_certifications' => $v['required_certifications'] ?? null,
            'required_licenses' => $v['required_licenses'] ?? null,
            'shift_type' => $v['shift_type'] ?? null,
            'shift_details' => $v['shift_details'] ?? null,
            'experience_level' => $v['experience_level'] ?? null,
            'min_experience_years' => $v['min_experience_years'] ?? null,
            'benefits' => $v['benefits'] ?? null,
            'compliance_checklist' => $v['compliance_checklist'] ?? null,
            'is_system_template' => false,
        ]);

        return $this->ok($template, 'Template created', 201);
    }

    /**
     * PUT /job-template-writer/templates/{template}
     * Update template
     */
    public function update(Request $request, JobTemplate $template): JsonResponse
    {
        $u = $this->requireAuth();
        if ($template->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        if ($template->is_system_template && $u->role !== 'admin') {
            return $this->fail('Cannot modify system templates', null, 403);
        }

        $v = $request->validate([
            'name' => 'sometimes|string|max:120',
            'category' => 'nullable|string|max:100',
            'role_type' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'employment_type' => 'nullable|string|max:50',
            'work_mode' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:10',
            'ai_suggestions' => 'nullable|array',
            'required_certifications' => 'nullable|array',
            'required_licenses' => 'nullable|array',
            'shift_type' => 'nullable|string|max:100',
            'shift_details' => 'nullable|array',
            'experience_level' => 'nullable|string|max:50',
            'min_experience_years' => 'nullable|integer|min:0',
            'benefits' => 'nullable|array',
            'compliance_checklist' => 'nullable|array',
        ]);

        $template->update($v);
        return $this->ok($template, 'Template updated');
    }

    /**
     * DELETE /job-template-writer/templates/{template}
     * Delete template
     */
    public function destroy(Request $request, JobTemplate $template): JsonResponse
    {
        $u = $this->requireAuth();
        if ($template->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        if ($template->is_system_template) {
            return $this->fail('Cannot delete system templates', null, 403);
        }

        $template->delete();
        return $this->ok(null, 'Template deleted');
    }

    /**
     * POST /job-template-writer/templates/{template}/use
     * Create a draft job from template with tracking
     */
    public function useTemplate(Request $request, JobTemplate $template): JsonResponse
    {
        $u = $this->requireAuth();
        if (!$template->is_system_template && $template->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        // Track conversion
        if ($template->ab_test_id) {
            $template->incrementConversions();
        }
        $template->increment('usage_count');

        // Create job from template
        $job = \App\Models\Job::create([
            'owner_type' => $u->role,
            'owner_user_id' => $u->id,
            'title' => $template->title ?? 'Untitled Job',
            'description' => $template->description,
            'employment_type' => $template->employment_type,
            'work_mode' => $template->work_mode,
            'country' => $template->country,
            'state' => $request->input('state'),
            'city' => $template->city,
            'salary_min' => $template->salary_min,
            'salary_max' => $template->salary_max,
            'salary_currency' => $template->salary_currency,
            'status' => 'draft',
        ]);

        return $this->ok($job, 'Draft job created from template', 201);
    }

    /**
     * GET /job-template-writer/categories
     * Get available template categories
     */
    public function categories(): JsonResponse
    {
        return $this->ok([
            'nursing' => [
                'label' => 'Nursing',
                'role_types' => [
                    'ER Nurse',
                    'Travel RN',
                    'ICU Nurse',
                    'OR Nurse',
                    'Pediatric Nurse',
                    'Labor & Delivery Nurse',
                    'Med-Surg Nurse',
                    'Oncology Nurse',
                    'Psychiatric Nurse',
                    'Home Health Nurse',
                ],
            ],
            'allied_health' => [
                'label' => 'Allied Health',
                'role_types' => [
                    'Physical Therapist',
                    'Occupational Therapist',
                    'Respiratory Therapist',
                    'Medical Laboratory Technician',
                    'Radiologic Technologist',
                    'Pharmacy Technician',
                    'Surgical Technologist',
                ],
            ],
            'physician' => [
                'label' => 'Physician',
                'role_types' => [
                    'Emergency Medicine Physician',
                    'Family Medicine Physician',
                    'Internal Medicine Physician',
                    'Hospitalist',
                    'Surgeon',
                    'Anesthesiologist',
                    'Radiologist',
                ],
            ],
            'healthcare_support' => [
                'label' => 'Healthcare Support',
                'role_types' => [
                    'Medical Assistant',
                    'Certified Nursing Assistant',
                    'Patient Care Technician',
                    'Medical Secretary',
                    'Healthcare Administrator',
                ],
            ],
        ]);
    }

    /**
     * GET /job-template-writer/compliance-helper
     * Get compliance checklist helper data
     */
    public function complianceHelper(): JsonResponse
    {
        return $this->ok([
            'certifications' => ComplianceChecklist::getCommonCertifications('ER Nurse'),
            'shift_types' => ComplianceChecklist::getShiftTypes(),
            'experience_levels' => ComplianceChecklist::getExperienceLevels(),
        ]);
    }
}
