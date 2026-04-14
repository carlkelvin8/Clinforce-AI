<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LearningDevelopmentController extends ApiController
{
    protected function getAuthenticatedUser(): User
    {
        return $this->requireAuth();
    }

    // ── Learning Dashboard ───────────────────────────────────────────────
    public function dashboard(Request $request): JsonResponse
    {
        $u = $this->getAuthenticatedUser();
        
        $cacheKey = "learning_dashboard_{$u->id}";
        
        $data = Cache::remember($cacheKey, 1800, function () use ($u) {
            return [
                'overview' => $this->getLearningOverview($u->id),
                'skill_gaps' => $this->getSkillGapsForUser($u->id),
                'recommendations' => $this->getLearningRecommendationsForUser($u->id),
                'progress' => $this->getLearningProgressForUser($u->id),
                'certifications' => $this->getCertificationStatusForUser($u->id),
                'mentorship' => $this->getMentorshipStatusForUser($u->id),
            ];
        });

        return $this->ok($data);
    }

    // ── Skill Management ─────────────────────────────────────────────────
    public function getSkillsCatalog(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => 'nullable|string',
            'specialty' => 'nullable|string',
            'search' => 'nullable|string',
        ]);

        $query = DB::table('skills_catalog')
            ->where('is_active', true)
            ->when($data['category'] ?? null, function ($q, $category) {
                return $q->where('category', $category);
            })
            ->when($data['specialty'] ?? null, function ($q, $specialty) {
                return $q->where('specialty', $specialty);
            })
            ->when($data['search'] ?? null, function ($q, $search) {
                return $q->where(function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                         ->orWhere('description', 'like', "%{$search}%");
                });
            });

        $skills = $query
            ->select([
                'id', 'name', 'category', 'specialty', 'description',
                'importance_score', 'requires_certification', 'certification_body'
            ])
            ->orderBy('importance_score', 'desc')
            ->orderBy('name')
            ->get();

        return $this->ok($skills);
    }

    public function getUserSkills(Request $request): JsonResponse
    {
        $u = $this->getAuthenticatedUser();
        
        $skills = DB::table('user_skills')
            ->join('skills_catalog', 'skills_catalog.id', '=', 'user_skills.skill_id')
            ->where('user_skills.user_id', $u->id)
            ->select([
                'user_skills.*',
                'skills_catalog.name',
                'skills_catalog.category',
                'skills_catalog.specialty',
                'skills_catalog.description',
                'skills_catalog.importance_score'
            ])
            ->orderBy('skills_catalog.importance_score', 'desc')
            ->get();

        return $this->ok($skills);
    }

    public function addUserSkill(Request $request): JsonResponse
    {
        $u = $this->getAuthenticatedUser();
        
        $data = $request->validate([
            'skill_id' => 'required|exists:skills_catalog,id',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_experience' => 'nullable|integer|min:0|max:50',
            'acquired_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'is_featured' => 'boolean',
        ]);

        // Check if skill already exists for user
        $existing = DB::table('user_skills')
            ->where('user_id', $u->id)
            ->where('skill_id', $data['skill_id'])
            ->first();

        if ($existing) {
            return $this->fail('Skill already exists for user', null, 400);
        }

        $id = DB::table('user_skills')->insertGetId([
            'user_id' => $u->id,
            'skill_id' => $data['skill_id'],
            'proficiency_level' => $data['proficiency_level'],
            'years_experience' => $data['years_experience'] ?? null,
            'acquired_date' => $data['acquired_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_featured' => $data['is_featured'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Clear cache
        Cache::forget("learning_dashboard_{$u->id}");

        return $this->ok(['id' => $id], 'Skill added successfully', 201);
    }

    public function updateUserSkill(Request $request, int $skillId): JsonResponse
    {
        $u = $this->getAuthenticatedUser();
        
        $data = $request->validate([
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_experience' => 'nullable|integer|min:0|max:50',
            'last_used_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'is_featured' => 'boolean',
        ]);

        $updated = DB::table('user_skills')
            ->where('user_id', $u->id)
            ->where('id', $skillId)
            ->update([
                'proficiency_level' => $data['proficiency_level'],
                'years_experience' => $data['years_experience'] ?? null,
                'last_used_date' => $data['last_used_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_featured' => $data['is_featured'] ?? false,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return $this->fail('Skill not found', null, 404);
        }

        Cache::forget("learning_dashboard_{$u->id}");

        return $this->ok(null, 'Skill updated successfully');
    }

    // ── Skill Gap Analysis ───────────────────────────────────────────────
    public function analyzeSkillGaps(Request $request): JsonResponse
    {
        $u = $this->getAuthenticatedUser();
        
        $data = $request->validate([
            'target_role_id' => 'nullable|exists:jobs_table,id',
            'target_role_title' => 'nullable|string',
        ]);

        // Get required skills for target role or general career advancement
        $requiredSkills = $this->getRequiredSkillsForRole($data['target_role_id'] ?? null, $data['target_role_title'] ?? null);
        
        // Get user's current skills
        $userSkills = DB::table('user_skills')
            ->where('user_id', $u->id)
            ->pluck('proficiency_level', 'skill_id')
            ->toArray();

        $gaps = [];
        
        foreach ($requiredSkills as $skill) {
            $currentLevel = $userSkills[$skill['skill_id']] ?? 'none';
            $requiredLevel = $skill['required_level'];
            
            $gapScore = $this->calculateGapScore($currentLevel, $requiredLevel);
            
            if ($gapScore > 0) {
                $gaps[] = [
                    'skill_id' => $skill['skill_id'],
                    'skill_name' => $skill['skill_name'],
                    'current_level' => $currentLevel,
                    'required_level' => $requiredLevel,
                    'gap_score' => $gapScore,
                    'priority' => $this->determinePriority($gapScore, $skill['importance_score']),
                ];
            }
        }

        // Save analysis results
        $this->saveSkillGapAnalysis($u->id, $gaps, $data);

        return $this->ok([
            'gaps' => $gaps,
            'total_gaps' => count($gaps),
            'critical_gaps' => count(array_filter($gaps, fn($g) => $g['priority'] === 'critical')),
            'recommendations' => $this->generateGapRecommendations($gaps),
        ]);
    }

    public function getSkillGaps(Request $request): JsonResponse
    {
        $u = $this->getAuthenticatedUser();
        
        $gaps = DB::table('skill_gap_analysis')
            ->join('skills_catalog', 'skills_catalog.id', '=', 'skill_gap_analysis.skill_id')
            ->where('skill_gap_analysis.user_id', $u->id)
            ->where('skill_gap_analysis.status', '!=', 'completed')
            ->select([
                'skill_gap_analysis.*',
                'skills_catalog.name as skill_name',
                'skills_catalog.category',
                'skills_catalog.specialty'
            ])
            ->orderBy('skill_gap_analysis.priority', 'desc')
            ->orderBy('skill_gap_analysis.gap_score', 'desc')
            ->get();

        return $this->ok($gaps);
    }

    // ── Learning Courses ─────────────────────────────────────────────────
    public function getCourses(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => 'nullable|string',
            'specialty' => 'nullable|string',
            'difficulty_level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'format' => 'nullable|string',
            'offers_certificate' => 'nullable|boolean',
            'offers_ceu' => 'nullable|boolean',
            'max_price' => 'nullable|numeric|min:0',
            'search' => 'nullable|string',
            'sort' => 'nullable|in:rating,price,duration,newest',
        ]);

        $query = DB::table('learning_courses')
            ->join('learning_providers', 'learning_providers.id', '=', 'learning_courses.provider_id')
            ->where('learning_courses.is_active', true)
            ->where('learning_providers.is_active', true);

        // Apply filters
        foreach (['category', 'specialty', 'difficulty_level', 'format'] as $field) {
            if (!empty($data[$field])) {
                $query->where("learning_courses.{$field}", $data[$field]);
            }
        }

        if (isset($data['offers_certificate'])) {
            $query->where('learning_courses.offers_certificate', $data['offers_certificate']);
        }

        if (isset($data['offers_ceu'])) {
            $query->where('learning_courses.offers_ceu', $data['offers_ceu']);
        }

        if (!empty($data['max_price'])) {
            $query->where('learning_courses.price', '<=', $data['max_price']);
        }

        if (!empty($data['search'])) {
            $query->where(function ($q) use ($data) {
                $q->where('learning_courses.title', 'like', "%{$data['search']}%")
                  ->orWhere('learning_courses.description', 'like', "%{$data['search']}%");
            });
        }

        // Apply sorting
        switch ($data['sort'] ?? 'rating') {
            case 'price':
                $query->orderBy('learning_courses.price');
                break;
            case 'duration':
                $query->orderBy('learning_courses.duration_hours');
                break;
            case 'newest':
                $query->orderBy('learning_courses.created_at', 'desc');
                break;
            default:
                $query->orderBy('learning_courses.rating', 'desc');
        }

        $courses = $query
            ->select([
                'learning_courses.*',
                'learning_providers.name as provider_name',
                'learning_providers.type as provider_type',
                'learning_providers.logo_url as provider_logo'
            ])
            ->paginate(20);

        return $this->ok($courses);
    }

    public function getCourse(int $courseId): JsonResponse
    {
        $course = DB::table('learning_courses')
            ->join('learning_providers', 'learning_providers.id', '=', 'learning_courses.provider_id')
            ->where('learning_courses.id', $courseId)
            ->select([
                'learning_courses.*',
                'learning_providers.name as provider_name',
                'learning_providers.type as provider_type',
                'learning_providers.website_url as provider_website',
                'learning_providers.logo_url as provider_logo'
            ])
            ->first();

        if (!$course) {
            return $this->fail('Course not found', null, 404);
        }

        return $this->ok($course);
    }

    public function enrollInCourse(Request $request, int $courseId): JsonResponse
    {
        $u = $this->getAuthenticatedUser();
        
        $data = $request->validate([
            'payment_status' => 'nullable|in:free,paid,employer_sponsored',
            'sponsored_by_employer_id' => 'nullable|exists:users,id',
        ]);

        // Check if already enrolled
        $existing = DB::table('course_enrollments')
            ->where('user_id', $u->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['enrolled', 'in_progress'])
            ->first();

        if ($existing) {
            return $this->fail('Already enrolled in this course', null, 400);
        }

        $enrollmentId = DB::table('course_enrollments')->insertGetId([
            'user_id' => $u->id,
            'course_id' => $courseId,
            'status' => 'enrolled',
            'enrolled_date' => now()->toDateString(),
            'payment_status' => $data['payment_status'] ?? 'free',
            'sponsored_by_employer_id' => $data['sponsored_by_employer_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['enrollment_id' => $enrollmentId], 'Successfully enrolled in course', 201);
    }

    // ── Learning Recommendations ─────────────────────────────────────────
    public function getRecommendations(Request $request): JsonResponse
    {
        $u = $this->getAuthenticatedUser();
        
        $recommendations = DB::table('learning_recommendations')
            ->leftJoin('learning_courses', 'learning_courses.id', '=', 'learning_recommendations.course_id')
            ->leftJoin('skills_catalog', 'skills_catalog.id', '=', 'learning_recommendations.skill_id')
            ->where('learning_recommendations.user_id', $u->id)
            ->where('learning_recommendations.status', 'pending')
            ->select([
                'learning_recommendations.*',
                'learning_courses.title as course_title',
                'learning_courses.provider_id',
                'learning_courses.duration_hours',
                'learning_courses.price',
                'skills_catalog.name as skill_name'
            ])
            ->orderBy('learning_recommendations.priority_score', 'desc')
            ->orderBy('learning_recommendations.relevance_score', 'desc')
            ->limit(10)
            ->get();

        return $this->ok($recommendations);
    }

    public function generateRecommendations(Request $request): JsonResponse
    {
        $u = $this->getAuthenticatedUser();
        
        // Clear existing pending recommendations
        DB::table('learning_recommendations')
            ->where('user_id', $u->id)
            ->where('status', 'pending')
            ->delete();

        $recommendations = [];
        
        // Skill gap based recommendations
        $skillGaps = DB::table('skill_gap_analysis')
            ->where('user_id', $u->id)
            ->where('status', '!=', 'completed')
            ->orderBy('priority', 'desc')
            ->limit(5)
            ->get();

        foreach ($skillGaps as $gap) {
            $courses = $this->findCoursesForSkill($gap->skill_id);
            foreach ($courses->take(2) as $course) {
                $recommendations[] = [
                    'user_id' => $u->id,
                    'course_id' => $course->id,
                    'skill_id' => $gap->skill_id,
                    'recommendation_type' => 'skill_gap',
                    'reason' => "Addresses skill gap in {$course->skill_name}",
                    'relevance_score' => $gap->gap_score,
                    'priority_score' => $this->calculatePriorityScore($gap->priority, $course->rating),
                    'personalization_factors' => json_encode([
                        'gap_score' => $gap->gap_score,
                        'priority' => $gap->priority,
                        'course_rating' => $course->rating
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Career goal based recommendations
        $careerPlan = DB::table('user_career_plans')
            ->where('user_id', $u->id)
            ->where('status', 'active')
            ->first();

        if ($careerPlan) {
            $careerCourses = $this->findCoursesForCareerLevel($careerPlan->target_level);
            foreach ($careerCourses->take(3) as $course) {
                $recommendations[] = [
                    'user_id' => $u->id,
                    'course_id' => $course->id,
                    'recommendation_type' => 'career_goal',
                    'reason' => "Supports career advancement to {$careerPlan->target_level}",
                    'relevance_score' => 85,
                    'priority_score' => 75,
                    'personalization_factors' => json_encode([
                        'target_level' => $careerPlan->target_level,
                        'course_rating' => $course->rating ?? 4.0
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        } else {
            // Add some general recommendations if no career plan exists
            $generalCourses = DB::table('learning_courses')
                ->where('is_active', true)
                ->orderBy('rating', 'desc')
                ->limit(3)
                ->get();
                
            foreach ($generalCourses as $course) {
                $recommendations[] = [
                    'user_id' => $u->id,
                    'course_id' => $course->id,
                    'recommendation_type' => 'general',
                    'reason' => "Popular course in healthcare education",
                    'relevance_score' => 70,
                    'priority_score' => 60,
                    'personalization_factors' => json_encode([
                        'course_rating' => $course->rating ?? 4.0,
                        'popularity' => 'high'
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert recommendations
        if (!empty($recommendations)) {
            DB::table('learning_recommendations')->insert($recommendations);
        }

        return $this->ok([
            'generated_count' => count($recommendations),
            'recommendations' => array_slice($recommendations, 0, 10)
        ]);
    }

    // ── Helper Methods ───────────────────────────────────────────────────
    private function getLearningOverview(int $userId): array
    {
        return [
            'total_skills' => DB::table('user_skills')->where('user_id', $userId)->count(),
            'skill_gaps' => DB::table('skill_gap_analysis')->where('user_id', $userId)->where('status', '!=', 'completed')->count(),
            'active_courses' => DB::table('course_enrollments')->where('user_id', $userId)->whereIn('status', ['enrolled', 'in_progress'])->count(),
            'completed_courses' => DB::table('course_enrollments')->where('user_id', $userId)->where('status', 'completed')->count(),
            'certifications' => DB::table('user_certifications')->where('user_id', $userId)->where('status', 'active')->count(),
            'expiring_certifications' => DB::table('user_certifications')->where('user_id', $userId)->where('status', 'active')->where('expiration_date', '<=', now()->addMonths(3))->count(),
        ];
    }

    private function getRequiredSkillsForRole(?int $roleId, ?string $roleTitle): array
    {
        // This would typically come from job requirements or career path data
        // For now, return sample data
        return [
            ['skill_id' => 1, 'skill_name' => 'Patient Care', 'required_level' => 'advanced', 'importance_score' => 95],
            ['skill_id' => 2, 'skill_name' => 'Medical Documentation', 'required_level' => 'intermediate', 'importance_score' => 85],
            ['skill_id' => 3, 'skill_name' => 'Emergency Response', 'required_level' => 'intermediate', 'importance_score' => 90],
        ];
    }

    private function calculateGapScore(string $currentLevel, string $requiredLevel): int
    {
        $levels = ['none' => 0, 'beginner' => 25, 'intermediate' => 50, 'advanced' => 75, 'expert' => 100];
        $current = $levels[$currentLevel] ?? 0;
        $required = $levels[$requiredLevel] ?? 100;
        
        return max(0, $required - $current);
    }

    private function determinePriority(int $gapScore, int $importanceScore): string
    {
        $combinedScore = ($gapScore + $importanceScore) / 2;
        
        if ($combinedScore >= 80) return 'critical';
        if ($combinedScore >= 60) return 'high';
        if ($combinedScore >= 40) return 'medium';
        return 'low';
    }

    private function saveSkillGapAnalysis(int $userId, array $gaps, array $targetData): void
    {
        // Clear existing analysis for this target
        DB::table('skill_gap_analysis')
            ->where('user_id', $userId)
            ->where('target_role_id', $targetData['target_role_id'] ?? null)
            ->delete();

        $records = [];
        foreach ($gaps as $gap) {
            $records[] = [
                'user_id' => $userId,
                'target_role_id' => $targetData['target_role_id'] ?? null,
                'target_role_title' => $targetData['target_role_title'] ?? null,
                'skill_id' => $gap['skill_id'],
                'current_level' => $gap['current_level'],
                'required_level' => $gap['required_level'],
                'gap_score' => $gap['gap_score'],
                'priority' => $gap['priority'],
                'analyzed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($records)) {
            DB::table('skill_gap_analysis')->insert($records);
        }
    }

    private function findCoursesForSkill(int $skillId): object
    {
        // For now, return courses that match the skill category
        $skill = DB::table('skills_catalog')->find($skillId);
        if (!$skill) {
            return collect([]);
        }
        
        return DB::table('learning_courses')
            ->where('learning_courses.is_active', true)
            ->where('learning_courses.category', $skill->category)
            ->select(['learning_courses.*', DB::raw("'{$skill->name}' as skill_name")])
            ->orderBy('learning_courses.rating', 'desc')
            ->get();
    }

    private function findCoursesForCareerLevel(string $targetLevel): object
    {
        return DB::table('learning_courses')
            ->where('is_active', true)
            ->where('category', 'leadership')
            ->orderBy('rating', 'desc')
            ->get();
    }

    private function calculatePriorityScore(string $priority, ?float $courseRating): int
    {
        $priorityScores = ['low' => 25, 'medium' => 50, 'high' => 75, 'critical' => 100];
        $baseScore = $priorityScores[$priority] ?? 50;
        $ratingBonus = ($courseRating ?? 3) * 5;
        
        return min(100, $baseScore + $ratingBonus);
    }

    // ── Helper Methods for Dashboard ─────────────────────────────────────
    private function getSkillGapsForUser(int $userId): array
    {
        $gaps = DB::table('skill_gap_analysis')
            ->join('skills_catalog', 'skills_catalog.id', '=', 'skill_gap_analysis.skill_id')
            ->where('skill_gap_analysis.user_id', $userId)
            ->where('skill_gap_analysis.status', '!=', 'completed')
            ->select([
                'skill_gap_analysis.*',
                'skills_catalog.name as skill_name',
                'skills_catalog.category',
                'skills_catalog.specialty'
            ])
            ->orderBy('skill_gap_analysis.priority', 'desc')
            ->orderBy('skill_gap_analysis.gap_score', 'desc')
            ->limit(5)
            ->get();

        return $gaps->toArray();
    }

    private function getLearningRecommendationsForUser(int $userId): array
    {
        $recommendations = DB::table('learning_recommendations')
            ->leftJoin('learning_courses', 'learning_courses.id', '=', 'learning_recommendations.course_id')
            ->leftJoin('skills_catalog', 'skills_catalog.id', '=', 'learning_recommendations.skill_id')
            ->where('learning_recommendations.user_id', $userId)
            ->where('learning_recommendations.status', 'pending')
            ->select([
                'learning_recommendations.*',
                'learning_courses.title as course_title',
                'learning_courses.provider_id',
                'learning_courses.duration_hours',
                'learning_courses.price',
                'skills_catalog.name as skill_name'
            ])
            ->orderBy('learning_recommendations.priority_score', 'desc')
            ->orderBy('learning_recommendations.relevance_score', 'desc')
            ->limit(5)
            ->get();

        return $recommendations->toArray();
    }

    private function getLearningProgressForUser(int $userId): array
    {
        return [
            'active_enrollments' => DB::table('course_enrollments')->where('user_id', $userId)->whereIn('status', ['enrolled', 'in_progress'])->count(),
            'completed_courses' => DB::table('course_enrollments')->where('user_id', $userId)->where('status', 'completed')->count(),
            'total_learning_hours' => DB::table('course_enrollments')
                ->join('learning_courses', 'learning_courses.id', '=', 'course_enrollments.course_id')
                ->where('course_enrollments.user_id', $userId)
                ->where('course_enrollments.status', 'completed')
                ->sum('learning_courses.duration_hours') ?? 0,
            'certificates_earned' => DB::table('course_enrollments')->where('user_id', $userId)->where('status', 'completed')->count(),
        ];
    }

    private function getCertificationStatusForUser(int $userId): array
    {
        return [
            'total_certifications' => DB::table('user_certifications')->where('user_id', $userId)->count(),
            'active_certifications' => DB::table('user_certifications')->where('user_id', $userId)->where('status', 'active')->count(),
            'expiring_soon' => DB::table('user_certifications')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where('expiration_date', '<=', now()->addMonths(3))
                ->count(),
            'expired' => DB::table('user_certifications')->where('user_id', $userId)->where('status', 'expired')->count(),
        ];
    }

    private function getMentorshipStatusForUser(int $userId): array
    {
        return [
            'is_mentor' => DB::table('mentor_profiles')->where('user_id', $userId)->exists(),
            'is_mentee' => DB::table('mentee_profiles')->where('user_id', $userId)->exists(),
            'active_relationships' => DB::table('mentorship_relationships')
                ->where(function($query) use ($userId) {
                    $query->where('mentor_id', $userId)->orWhere('mentee_id', $userId);
                })
                ->where('status', 'active')
                ->count(),
            'pending_matches' => DB::table('mentorship_matches')
                ->where('mentee_id', $userId)
                ->where('status', 'suggested')
                ->count(),
        ];
    }

    private function generateGapRecommendations(array $gaps): array
    {
        $recommendations = [];
        
        foreach ($gaps as $gap) {
            if ($gap['priority'] === 'critical' || $gap['priority'] === 'high') {
                $recommendations[] = [
                    'type' => 'course',
                    'title' => "Improve {$gap['skill_name']} skills",
                    'description' => "Focus on advancing from {$gap['current_level']} to {$gap['required_level']} level",
                    'priority' => $gap['priority'],
                    'estimated_time' => $this->estimateTimeForGap($gap['gap_score']),
                ];
            }
        }
        
        return array_slice($recommendations, 0, 5); // Return top 5 recommendations
    }

    private function estimateTimeForGap(int $gapScore): string
    {
        if ($gapScore >= 75) return '3-6 months';
        if ($gapScore >= 50) return '1-3 months';
        if ($gapScore >= 25) return '2-4 weeks';
        return '1-2 weeks';
    }
}