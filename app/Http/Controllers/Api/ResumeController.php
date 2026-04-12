<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApplicantProfile;
use App\Models\Portfolio;
use App\Models\SkillsAssessment;
use App\Models\VerifiedSkill;
use App\Models\Endorsement;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResumeController extends ApiController
{
    private AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate AI-powered resume from profile data
     */
    public function generate(Request $request)
    {
        $user = $request->user();
        $profile = ApplicantProfile::where('user_id', $user->id)
            ->with(['portfolios' => fn($q) => $q->where('is_featured', true)])
            ->firstOrFail();

        // Gather comprehensive profile data
        $profileData = [
            'personal_info' => [
                'name' => trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')),
                'headline' => $profile->headline,
                'location' => trim(($profile->city ?? '') . ', ' . ($profile->state ?? ' ') . ' ' . ($profile->country ?? '')),
                'email' => $user->email,
                'phone' => $user->phone ?? null,
            ],
            'summary' => $profile->summary,
            'years_of_experience' => $profile->years_experience,
            'skills' => $profile->skills ?? [],
            'work_experience' => $profile->work_experience ?? [],
            'education' => $profile->education ?? [],
            'certifications' => $profile->certifications ?? [],
            'verified_skills' => VerifiedSkill::where('user_id', $user->id)
                ->where('is_verified', true)
                ->get()
                ->map(fn($s) => [
                    'skill' => $s->skill_name,
                    'proficiency' => $s->proficiency_level,
                    'verified_at' => $s->verified_at,
                ])
                ->toArray(),
            'portfolio_items' => $profile->portfolios->map(fn($p) => [
                'title' => $p->title,
                'description' => $p->description,
                'type' => $p->type,
                'category' => $p->category,
            ])->toArray(),
            'passed_assessments' => SkillsAssessment::where('user_id', $user->id)
                ->where('passed', true)
                ->with('template:id,title,skill_name')
                ->get()
                ->map(fn($a) => [
                    'assessment' => $a->template->title,
                    'skill' => $a->template->skill_name,
                    'score' => $a->score,
                    'grade' => $a->getScoreGrade(),
                ])
                ->toArray(),
            'endorsements_count' => Endorsement::where('recipient_user_id', $user->id)
                ->where('is_hidden', false)
                ->count(),
            'availability' => [
                'available_from' => $profile->available_from,
                'notice_period' => $profile->notice_period,
                'open_to_work' => $profile->open_to_work,
            ],
        ];

        $result = $this->aiService->generateResume($profileData);

        if (isset($result['success']) && $result['success']) {
            // Save generated resume to profile
            $profile->update([
                'ai_resume' => $result['resume'],
                'ai_resume_generated' => true,
                'ai_resume_generated_at' => now(),
            ]);
        }

        return $this->ok($result);
    }

    /**
     * Get generated resume
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = ApplicantProfile::where('user_id', $user->id)->firstOrFail();

        if (!$profile->ai_resume_generated || !$profile->ai_resume) {
            return $this->fail('No AI-generated resume available. Generate one first.', 404);
        }

        return $this->ok([
            'resume' => $profile->ai_resume,
            'generated_at' => $profile->ai_resume_generated_at,
            'profile' => [
                'name' => trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')),
                'headline' => $profile->headline,
                'completeness' => $profile->profile_completeness,
            ],
        ]);
    }

    /**
     * Get profile completeness
     */
    public function completeness(Request $request)
    {
        $user = $request->user();
        $profile = ApplicantProfile::where('user_id', $user->id)->firstOrFail();

        $score = $profile->calculateProfileCompleteness();

        $breakdown = [
            'basic_info' => [
                'score' => ($profile->first_name && $profile->last_name ? 5 : 0) +
                           ($profile->headline ? 5 : 0) +
                           ($profile->summary && strlen($profile->summary) > 50 ? 10 : 0) +
                           ($profile->avatar ? 5 : 0),
                'max' => 25,
                'items' => [
                    'name' => (bool) ($profile->first_name && $profile->last_name),
                    'headline' => (bool) $profile->headline,
                    'summary' => (bool) ($profile->summary && strlen($profile->summary) > 50),
                    'photo' => (bool) $profile->avatar,
                ],
            ],
            'experience_education' => [
                'score' => ($profile->work_experience ? count($profile->work_experience) > 0 ? 10 : 0 : 0) +
                           ($profile->education ? count($profile->education) > 0 ? 10 : 0 : 0),
                'max' => 20,
                'items' => [
                    'work_experience' => (bool) ($profile->work_experience && count($profile->work_experience) > 0),
                    'education' => (bool) ($profile->education && count($profile->education) > 0),
                ],
            ],
            'skills' => [
                'score' => ($profile->skills && count($profile->skills) > 0 ? 15 : 0),
                'max' => 15,
                'items' => [
                    'skills_added' => (bool) ($profile->skills && count($profile->skills) > 0),
                ],
            ],
            'video_intro' => [
                'score' => ($profile->video_intro_url ? 10 : 0),
                'max' => 10,
                'items' => [
                    'video_uploaded' => (bool) $profile->video_intro_url,
                ],
            ],
            'availability' => [
                'score' => ($profile->available_from ? 5 : 0) + ($profile->notice_period ? 5 : 0),
                'max' => 10,
                'items' => [
                    'available_from_set' => (bool) $profile->available_from,
                    'notice_period_set' => (bool) $profile->notice_period,
                ],
            ],
            'portfolio' => [
                'score' => ($profile->portfolios()->count() > 0 ? 10 : 0),
                'max' => 10,
                'items' => [
                    'portfolio_items' => $profile->portfolios()->count(),
                ],
            ],
            'verified_skills' => [
                'score' => ($profile->verifiedSkills()->count() > 0 ? 10 : 0),
                'max' => 10,
                'items' => [
                    'verified_count' => $profile->verifiedSkills()->count(),
                ],
            ],
        ];

        return $this->ok([
            'total_score' => $score,
            'max_score' => 100,
            'percentage' => $score,
            'breakdown' => $breakdown,
            'suggestions' => $this->getProfileSuggestions($profile),
        ]);
    }

    /**
     * Update profile enhancement fields
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $profile = ApplicantProfile::where('user_id', $user->id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'available_from' => 'nullable|date',
            'notice_period' => 'nullable|string|max:50',
            'available_for_contract' => 'boolean',
            'available_for_fulltime' => 'boolean',
            'available_for_parttime' => 'boolean',
            'available_for_freelance' => 'boolean',
            'certifications' => 'nullable|array',
            'languages' => 'nullable|array',
            'work_preferences' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors(), 422);
        }

        $profile->update($validator->validated());
        $profile->calculateProfileCompleteness();

        return $this->ok($profile);
    }

    /**
     * Get suggestions to improve profile
     */
    private function getProfileSuggestions(ApplicantProfile $profile): array
    {
        $suggestions = [];

        if (!$profile->headline) {
            $suggestions[] = [
                'type' => 'headline',
                'priority' => 'high',
                'message' => 'Add a professional headline to describe your expertise',
                'points' => 5,
            ];
        }

        if (!$profile->summary || strlen($profile->summary) < 50) {
            $suggestions[] = [
                'type' => 'summary',
                'priority' => 'high',
                'message' => 'Write a compelling professional summary (at least 50 characters)',
                'points' => 10,
            ];
        }

        if (!$profile->avatar) {
            $suggestions[] = [
                'type' => 'avatar',
                'priority' => 'medium',
                'message' => 'Upload a professional profile photo',
                'points' => 5,
            ];
        }

        if (!$profile->video_intro_url) {
            $suggestions[] = [
                'type' => 'video_intro',
                'priority' => 'medium',
                'message' => 'Record a 60-second video introduction to stand out',
                'points' => 10,
            ];
        }

        if ($profile->portfolios()->count() === 0) {
            $suggestions[] = [
                'type' => 'portfolio',
                'priority' => 'medium',
                'message' => 'Add portfolio items to showcase your work',
                'points' => 10,
            ];
        }

        if ($profile->verifiedSkills()->count() === 0) {
            $suggestions[] = [
                'type' => 'verified_skills',
                'priority' => 'low',
                'message' => 'Take skills assessments to get verified badges',
                'points' => 10,
            ];
        }

        if (!$profile->available_from) {
            $suggestions[] = [
                'type' => 'availability',
                'priority' => 'low',
                'message' => 'Set your availability to help employers know when you can start',
                'points' => 5,
            ];
        }

        return $suggestions;
    }
}
