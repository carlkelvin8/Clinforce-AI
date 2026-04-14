<?php

namespace App\Http\Controllers\Api;

use App\Models\JobTemplate;
use App\Models\TemplateAbTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TemplateAbTestController extends ApiController
{
    /**
     * GET /job-template-writer/ab-tests
     * Get all A/B tests for the user
     */
    public function index(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $status = $request->query('status');

        $query = TemplateAbTest::where('owner_user_id', $u->id)
            ->with(['baseTemplate', 'variants']);

        if ($status) {
            $query->where('status', $status);
        }

        $tests = $query->orderByDesc('created_at')->get();

        return $this->ok($tests);
    }

    /**
     * GET /job-template-writer/ab-tests/{test}
     * Get single A/B test with results
     */
    public function show(TemplateAbTest $test): JsonResponse
    {
        $u = $this->requireAuth();
        if ($test->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        $test->load(['baseTemplate', 'variants']);

        // Calculate current results if running
        if ($test->isRunning()) {
            $test->calculateResults();
        }

        return $this->ok($test);
    }

    /**
     * POST /job-template-writer/ab-tests
     * Create new A/B test
     */
    public function store(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
            'base_template_id' => 'required|exists:job_templates,id',
            'variant_templates' => 'required|array|min:1|max:3',
            'variant_templates.*' => 'exists:job_templates,id',
            'test_type' => 'nullable|string|in:conversion,clicks,applications',
            'target_sample_size' => 'nullable|integer|min:100',
            'confidence_level' => 'nullable|numeric|min:80|max:99',
        ]);

        // Verify base template ownership
        $baseTemplate = JobTemplate::find($v['base_template_id']);
        if ($baseTemplate->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden - you do not own the base template', null, 403);
        }

        // Verify variant templates ownership
        foreach ($v['variant_templates'] as $variantId) {
            $variant = JobTemplate::find($variantId);
            if ($variant->owner_user_id !== $u->id && $u->role !== 'admin') {
                return $this->fail('Forbidden - you do not own all variant templates', null, 403);
            }
        }

        $test = TemplateAbTest::create([
            'owner_user_id' => $u->id,
            'name' => $v['name'],
            'description' => $v['description'] ?? null,
            'base_template_id' => $v['base_template_id'],
            'variant_ids' => $v['variant_templates'],
            'test_type' => $v['test_type'] ?? 'conversion',
            'target_sample_size' => $v['target_sample_size'] ?? null,
            'confidence_level' => $v['confidence_level'] ?? 95.00,
            'status' => 'draft',
        ]);

        // Update templates with test ID and variants
        $baseTemplate->update([
            'ab_test_id' => $test->id,
            'ab_variant' => 'A',
        ]);

        $variantLabels = ['B', 'C', 'D'];
        foreach ($v['variant_templates'] as $index => $variantId) {
            JobTemplate::find($variantId)->update([
                'ab_test_id' => $test->id,
                'ab_variant' => $variantLabels[$index] ?? 'Z',
            ]);
        }

        return $this->ok($test->load(['baseTemplate', 'variants']), 'A/B test created', 201);
    }

    /**
     * POST /job-template-writer/ab-tests/{test}/start
     * Start an A/B test
     */
    public function start(TemplateAbTest $test): JsonResponse
    {
        $u = $this->requireAuth();
        if ($test->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        if ($test->status !== 'draft') {
            return $this->fail('Test can only be started from draft status', null, 400);
        }

        $test->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Update all variants
        $test->variants()->update([
            'ab_test_started_at' => now(),
        ]);
        $test->baseTemplate()->update([
            'ab_test_started_at' => now(),
        ]);

        return $this->ok($test, 'A/B test started');
    }

    /**
     * POST /job-template-writer/ab-tests/{test}/stop
     * Stop an A/B test and calculate results
     */
    public function stop(TemplateAbTest $test): JsonResponse
    {
        $u = $this->requireAuth();
        if ($test->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        if (!$test->isRunning()) {
            return $this->fail('Test is not running', null, 400);
        }

        $results = $test->calculateResults();

        // Determine winner
        if (isset($results['winner'])) {
            $winnerVariant = $results['winner'];
            $test->variants()->where('ab_variant', $winnerVariant)
                ->update(['is_ab_winner' => true]);
            $test->baseTemplate()->where('ab_variant', $winnerVariant)
                ->update(['is_ab_winner' => true]);
        }

        $test->markAsCompleted();

        // Update all variants with end time
        $test->variants()->update(['ab_test_ended_at' => now()]);
        $test->baseTemplate()->update(['ab_test_ended_at' => now()]);

        return $this->ok([
            'test' => $test,
            'results' => $results,
        ], 'A/B test completed');
    }

    /**
     * DELETE /job-template-writer/ab-tests/{test}
     * Cancel/delete an A/B test
     */
    public function destroy(TemplateAbTest $test): JsonResponse
    {
        $u = $this->requireAuth();
        if ($test->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        if ($test->isCompleted()) {
            return $this->fail('Cannot delete completed tests', null, 400);
        }

        // Clear test references from templates
        $test->variants()->update([
            'ab_test_id' => null,
            'ab_variant' => null,
            'ab_test_started_at' => null,
            'ab_test_ended_at' => null,
        ]);
        if ($test->baseTemplate) {
            $test->baseTemplate()->update([
                'ab_test_id' => null,
                'ab_variant' => null,
                'ab_test_started_at' => null,
                'ab_test_ended_at' => null,
            ]);
        }

        $test->delete();
        return $this->ok(null, 'A/B test deleted');
    }

    /**
     * GET /job-template-writer/ab-tests/{test}/analytics
     * Get detailed analytics for an A/B test
     */
    public function analytics(TemplateAbTest $test): JsonResponse
    {
        $u = $this->requireAuth();
        if ($test->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        $variants = $test->variants()->get();
        $baseTemplate = $test->baseTemplate;

        $analytics = [];
        $totalViews = 0;
        $totalConversions = 0;

        // Add base template (Variant A)
        if ($baseTemplate) {
            $analytics['A'] = [
                'template_id' => $baseTemplate->id,
                'name' => $baseTemplate->name,
                'views' => $baseTemplate->views_count,
                'conversions' => $baseTemplate->conversions_count,
                'conversion_rate' => $baseTemplate->conversion_rate,
                'is_winner' => $baseTemplate->is_ab_winner,
            ];
            $totalViews += $baseTemplate->views_count;
            $totalConversions += $baseTemplate->conversions_count;
        }

        // Add variants
        foreach ($variants as $variant) {
            $analytics[$variant->ab_variant] = [
                'template_id' => $variant->id,
                'name' => $variant->name,
                'views' => $variant->views_count,
                'conversions' => $variant->conversions_count,
                'conversion_rate' => $variant->conversion_rate,
                'is_winner' => $variant->is_ab_winner,
            ];
            $totalViews += $variant->views_count;
            $totalConversions += $variant->conversions_count;
        }

        // Statistical significance calculation (simplified)
        $statisticalSignificance = null;
        if ($totalViews > 0) {
            // Basic confidence calculation
            $overallRate = $totalConversions / $totalViews;
            $statisticalSignificance = [
                'total_views' => $totalViews,
                'total_conversions' => $totalConversions,
                'overall_conversion_rate' => round($overallRate * 100, 2),
                'target_sample_size' => $test->target_sample_size,
                'confidence_level' => $test->confidence_level,
                'is_significant' => $test->isCompleted(),
            ];
        }

        return $this->ok([
            'test' => [
                'id' => $test->id,
                'name' => $test->name,
                'status' => $test->status,
                'test_type' => $test->test_type,
                'started_at' => $test->started_at,
                'completed_at' => $test->completed_at,
            ],
            'variants' => $analytics,
            'statistical_significance' => $statisticalSignificance,
        ]);
    }

    /**
     * POST /job-template-writer/ab-tests/{test}/create-variants
     * Auto-generate A/B test variants using AI
     */
    public function createVariants(Request $request, TemplateAbTest $test): JsonResponse
    {
        $u = $this->requireAuth();
        if ($test->owner_user_id !== $u->id && $u->role !== 'admin') {
            return $this->fail('Forbidden', null, 403);
        }

        if ($test->status !== 'draft') {
            return $this->fail('Can only create variants for draft tests', null, 400);
        }

        $baseTemplate = $test->baseTemplate;
        if (!$baseTemplate) {
            return $this->fail('Base template not found', null, 404);
        }

        $aiService = new \App\Services\AiService();
        
        $params = [
            'role_type' => $baseTemplate->role_type,
            'category' => $baseTemplate->category,
            'title' => $baseTemplate->title,
            'employment_type' => $baseTemplate->employment_type,
            'work_mode' => $baseTemplate->work_mode,
            'country' => $baseTemplate->country,
            'city' => $baseTemplate->city,
            'salary_min' => $baseTemplate->salary_min,
            'salary_max' => $baseTemplate->salary_max,
            'salary_currency' => $baseTemplate->salary_currency,
            'experience_level' => $baseTemplate->experience_level,
            'min_experience_years' => $baseTemplate->min_experience_years,
            'shift_type' => $baseTemplate->shift_type,
            'required_certifications' => $baseTemplate->required_certifications,
            'benefits' => $baseTemplate->benefits,
        ];

        $result = $aiService->generateJobDescriptionVariants($params, 2);

        if (isset($result['error'])) {
            return $this->fail($result['message'] ?? 'AI service error', null, 503);
        }

        // Create variant templates
        $variantIds = [];
        foreach ($result['variants'] as $variantData) {
            $variantTemplate = JobTemplate::create([
                'owner_user_id' => $u->id,
                'name' => $baseTemplate->name . ' - Variant ' . ($variantData['variant'] ?? 'X'),
                'category' => $baseTemplate->category,
                'role_type' => $baseTemplate->role_type,
                'tags' => $baseTemplate->tags,
                'title' => $variantData['description']['title'] ?? $baseTemplate->title,
                'description' => json_encode($variantData['description'] ?? []),
                'employment_type' => $baseTemplate->employment_type,
                'work_mode' => $baseTemplate->work_mode,
                'country' => $baseTemplate->country,
                'city' => $baseTemplate->city,
                'salary_min' => $baseTemplate->salary_min,
                'salary_max' => $baseTemplate->salary_max,
                'salary_currency' => $baseTemplate->salary_currency,
                'is_ai_generated' => true,
                'ai_model_used' => 'gpt-4o',
                'required_certifications' => $baseTemplate->required_certifications,
                'required_licenses' => $baseTemplate->required_licenses,
                'shift_type' => $baseTemplate->shift_type,
                'shift_details' => $baseTemplate->shift_details,
                'experience_level' => $baseTemplate->experience_level,
                'min_experience_years' => $baseTemplate->min_experience_years,
                'benefits' => $baseTemplate->benefits,
                'compliance_checklist' => $baseTemplate->compliance_checklist,
                'ab_test_id' => $test->id,
                'ab_variant' => $variantData['variant'] ?? 'X',
            ]);

            $variantIds[] = $variantTemplate->id;
        }

        $test->update(['variant_ids' => $variantIds]);

        return $this->ok([
            'test' => $test,
            'variants' => $result['variants'],
            'created_templates' => $variantIds,
        ], 'Variants created');
    }
}
