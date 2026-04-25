<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\Interview;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CandidateAnalyticsController extends ApiController
{
    /**
     * Get comprehensive analytics for the authenticated candidate
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'applicant') {
            return $this->fail('Unauthorized', 403);
        }

        $timeRange = $request->get('time_range', '6_months');
        $startDate = $this->getStartDate($timeRange);

        $analytics = [
            'overview' => $this->getOverviewMetrics($user->id, $startDate),
            'application_funnel' => $this->getApplicationFunnel($user->id, $startDate),
            'interview_metrics' => $this->getInterviewMetrics($user->id, $startDate),
            'salary_insights' => $this->getSalaryInsights($user->id, $startDate),
            'skills_analysis' => $this->getSkillsAnalysis($user->id, $startDate),
            'market_position' => $this->getMarketPosition($user->id, $startDate),
            'trends' => $this->getTrends($user->id, $startDate),
            'recommendations' => $this->getRecommendations($user->id, $startDate),
        ];

        return $this->ok($analytics);
    }

    /**
     * Get overview metrics
     */
    private function getOverviewMetrics($userId, $startDate)
    {
        $applications = JobApplication::where('applicant_user_id', $userId)
            ->where('job_applications.created_at', '>=', $startDate)
            ->get();

        $interviews = Interview::whereHas('application', function($query) use ($userId) {
            $query->where('applicant_user_id', $userId);
        })->where('interviews.created_at', '>=', $startDate)->get();

        $totalApplications = $applications->count();
        $interviewsReceived = $interviews->count();
        $offersReceived = $applications->where('status', 'hired')->count();
        $rejections = $applications->where('status', 'rejected')->count();

        return [
            'total_applications' => $totalApplications,
            'interviews_received' => $interviewsReceived,
            'offers_received' => $offersReceived,
            'rejections' => $rejections,
            'response_rate' => $totalApplications > 0 ? round(($interviewsReceived / $totalApplications) * 100, 1) : 0,
            'success_rate' => $totalApplications > 0 ? round(($offersReceived / $totalApplications) * 100, 1) : 0,
            'interview_conversion' => $interviewsReceived > 0 ? round(($offersReceived / $interviewsReceived) * 100, 1) : 0,
        ];
    }

    /**
     * Get application funnel data
     */
    private function getApplicationFunnel($userId, $startDate)
    {
        $applications = JobApplication::where('applicant_user_id', $userId)
            ->where('job_applications.created_at', '>=', $startDate)
            ->get();

        $funnel = [
            ['stage' => 'Applied', 'count' => $applications->count(), 'percentage' => 100],
            ['stage' => 'Under Review', 'count' => $applications->whereIn('status', ['review', 'screening'])->count(), 'percentage' => 0],
            ['stage' => 'Interview', 'count' => $applications->where('status', 'interview')->count(), 'percentage' => 0],
            ['stage' => 'Offer', 'count' => $applications->where('status', 'hired')->count(), 'percentage' => 0],
        ];

        $total = $applications->count();
        if ($total > 0) {
            foreach ($funnel as &$stage) {
                if ($stage['stage'] !== 'Applied') {
                    $stage['percentage'] = round(($stage['count'] / $total) * 100, 1);
                }
            }
        }

        return $funnel;
    }

    /**
     * Get interview metrics
     */
    private function getInterviewMetrics($userId, $startDate)
    {
        $interviews = Interview::whereHas('application', function($query) use ($userId) {
            $query->where('applicant_user_id', $userId);
        })->where('interviews.created_at', '>=', $startDate)->get();

        $interviewsByType = $interviews->groupBy('mode')->map->count();
        $interviewsByStatus = $interviews->groupBy('status')->map->count();

        return [
            'total_interviews' => $interviews->count(),
            'by_type' => $interviewsByType->toArray(),
            'by_status' => $interviewsByStatus->toArray(),
            'average_duration' => $interviews->avg('duration_minutes') ?? 0,
            'upcoming_count' => $interviews->where('status', 'scheduled')
                ->where('scheduled_start', '>', now())->count(),
        ];
    }

    /**
     * Get salary insights
     */
    private function getSalaryInsights($userId, $startDate)
    {
        $applications = JobApplication::with('job')
            ->where('applicant_user_id', $userId)
            ->where('job_applications.created_at', '>=', $startDate)
            ->get();

        $salaryData = $applications->filter(function($app) {
            return $app->job && ($app->job->salary_min || $app->job->salary_max);
        });

        if ($salaryData->isEmpty()) {
            return [
                'average_salary_applied' => 0,
                'salary_range_min' => 0,
                'salary_range_max' => 0,
                'offers_with_salary' => 0,
                'salary_progression' => [],
            ];
        }

        $salaries = $salaryData->map(function($app) {
            return ($app->job->salary_min + $app->job->salary_max) / 2;
        })->filter();

        return [
            'average_salary_applied' => round($salaries->avg(), 0),
            'salary_range_min' => $salaries->min(),
            'salary_range_max' => $salaries->max(),
            'offers_with_salary' => $applications->where('status', 'hired')->count(),
            'salary_progression' => $this->getSalaryProgression($userId, $startDate),
        ];
    }

    /**
     * Get skills analysis based on applications and rejections
     */
    private function getSkillsAnalysis($userId, $startDate)
    {
        $applications = JobApplication::with('job')
            ->where('applicant_user_id', $userId)
            ->where('job_applications.created_at', '>=', $startDate)
            ->get();

        $rejectedJobs = $applications->where('status', 'rejected')->pluck('job');
        $successfulJobs = $applications->where('status', 'hired')->pluck('job');

        // Analyze job titles for common skills/keywords
        $rejectedTitles = $rejectedJobs->pluck('title')->implode(' ');
        $successfulTitles = $successfulJobs->pluck('title')->implode(' ');

        $commonRejectionKeywords = $this->extractKeywords($rejectedTitles);
        $successfulKeywords = $this->extractKeywords($successfulTitles);

        return [
            'applications_by_role_type' => $this->groupJobsByType($applications),
            'success_rate_by_role' => $this->getSuccessRateByRole($applications),
            'skill_gaps' => array_slice($commonRejectionKeywords, 0, 5),
            'strong_areas' => array_slice($successfulKeywords, 0, 5),
        ];
    }

    /**
     * Get market position compared to peers
     */
    private function getMarketPosition($userId, $startDate)
    {
        // Get user's profile for comparison
        $userApplications = JobApplication::where('applicant_user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Get average applications for similar candidates (same time period)
        $avgApplications = JobApplication::where('job_applications.created_at', '>=', $startDate)
            ->groupBy('applicant_user_id')
            ->selectRaw('COUNT(*) as app_count')
            ->get()
            ->avg('app_count');

        $userInterviews = Interview::whereHas('application', function($query) use ($userId) {
            $query->where('applicant_user_id', $userId);
        })->where('interviews.created_at', '>=', $startDate)->count();

        $avgInterviews = Interview::where('interviews.created_at', '>=', $startDate)
            ->join('job_applications', 'interviews.application_id', '=', 'job_applications.id')
            ->groupBy('job_applications.applicant_user_id')
            ->selectRaw('COUNT(*) as interview_count')
            ->get()
            ->avg('interview_count');

        return [
            'applications_vs_average' => [
                'user' => $userApplications,
                'market_average' => round($avgApplications ?? 0, 1),
                'percentile' => $this->calculatePercentile($userApplications, $avgApplications ?? 0),
            ],
            'interviews_vs_average' => [
                'user' => $userInterviews,
                'market_average' => round($avgInterviews ?? 0, 1),
                'percentile' => $this->calculatePercentile($userInterviews, $avgInterviews ?? 0),
            ],
        ];
    }

    /**
     * Get trends over time
     */
    private function getTrends($userId, $startDate)
    {
        $applications = JobApplication::where('applicant_user_id', $userId)
            ->where('job_applications.created_at', '>=', $startDate)
            ->selectRaw('DATE(job_applications.created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $interviews = Interview::whereHas('application', function($query) use ($userId) {
            $query->where('applicant_user_id', $userId);
        })
        ->where('interviews.created_at', '>=', $startDate)
        ->selectRaw('DATE(interviews.created_at) as date, COUNT(*) as count')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return [
            'applications_over_time' => $applications->toArray(),
            'interviews_over_time' => $interviews->toArray(),
        ];
    }

    /**
     * Get personalized recommendations
     */
    private function getRecommendations($userId, $startDate)
    {
        $applications = JobApplication::where('applicant_user_id', $userId)
            ->where('job_applications.created_at', '>=', $startDate)
            ->get();

        $recommendations = [];

        // Response rate recommendation
        $responseRate = $applications->count() > 0 ? 
            ($applications->whereIn('status', ['interview', 'hired'])->count() / $applications->count()) * 100 : 0;

        if ($responseRate < 10) {
            $recommendations[] = [
                'type' => 'profile',
                'title' => 'Improve Your Profile',
                'description' => 'Your response rate is below average. Consider updating your resume and profile.',
                'priority' => 'high',
            ];
        }

        // Application volume recommendation
        if ($applications->count() < 5) {
            $recommendations[] = [
                'type' => 'activity',
                'title' => 'Increase Application Volume',
                'description' => 'Apply to more positions to increase your chances of success.',
                'priority' => 'medium',
            ];
        }

        // Interview conversion recommendation
        $interviews = Interview::whereHas('application', function($query) use ($userId) {
            $query->where('applicant_user_id', $userId);
        })->where('interviews.created_at', '>=', $startDate)->count();

        if ($interviews > 0 && $applications->where('status', 'hired')->count() === 0) {
            $recommendations[] = [
                'type' => 'interview',
                'title' => 'Improve Interview Skills',
                'description' => 'You\'re getting interviews but not offers. Consider interview practice.',
                'priority' => 'high',
            ];
        }

        return $recommendations;
    }

    /**
     * Helper methods
     */
    private function getStartDate($timeRange)
    {
        switch ($timeRange) {
            case '1_month':
                return Carbon::now()->subMonth();
            case '3_months':
                return Carbon::now()->subMonths(3);
            case '6_months':
                return Carbon::now()->subMonths(6);
            case '1_year':
                return Carbon::now()->subYear();
            default:
                return Carbon::now()->subMonths(6);
        }
    }

    private function getSalaryProgression($userId, $startDate)
    {
        $applications = JobApplication::with('job')
            ->where('applicant_user_id', $userId)
            ->where('job_applications.created_at', '>=', $startDate)
            ->orderBy('job_applications.created_at')
            ->get();

        return $applications->filter(function($app) {
            return $app->job && ($app->job->salary_min || $app->job->salary_max);
        })->map(function($app) {
            return [
                'date' => $app->created_at->format('Y-m-d'),
                'salary' => ($app->job->salary_min + $app->job->salary_max) / 2,
                'status' => $app->status,
            ];
        })->values()->toArray();
    }

    private function extractKeywords($text)
    {
        $words = str_word_count(strtolower($text), 1);
        $keywords = array_filter($words, function($word) {
            return strlen($word) > 3 && !in_array($word, ['nurse', 'hospital', 'medical', 'healthcare']);
        });
        
        return array_keys(array_count_values($keywords));
    }

    private function groupJobsByType($applications)
    {
        return $applications->groupBy(function($app) {
            $title = strtolower($app->job->title ?? 'other');
            if (str_contains($title, 'nurse')) return 'Nursing';
            if (str_contains($title, 'doctor') || str_contains($title, 'physician')) return 'Physician';
            if (str_contains($title, 'tech')) return 'Technology';
            if (str_contains($title, 'admin')) return 'Administrative';
            return 'Other';
        })->map->count()->toArray();
    }

    private function getSuccessRateByRole($applications)
    {
        $grouped = $applications->groupBy(function($app) {
            $title = strtolower($app->job->title ?? 'other');
            if (str_contains($title, 'nurse')) return 'Nursing';
            if (str_contains($title, 'doctor') || str_contains($title, 'physician')) return 'Physician';
            if (str_contains($title, 'tech')) return 'Technology';
            if (str_contains($title, 'admin')) return 'Administrative';
            return 'Other';
        });

        $successRates = [];
        foreach ($grouped as $role => $apps) {
            $total = $apps->count();
            $successful = $apps->where('status', 'hired')->count();
            $successRates[$role] = $total > 0 ? round(($successful / $total) * 100, 1) : 0;
        }

        return $successRates;
    }

    private function calculatePercentile($userValue, $average)
    {
        if ($average == 0) return 50;
        
        $ratio = $userValue / $average;
        if ($ratio >= 1.5) return 90;
        if ($ratio >= 1.2) return 75;
        if ($ratio >= 1.0) return 60;
        if ($ratio >= 0.8) return 40;
        if ($ratio >= 0.5) return 25;
        return 10;
    }
}