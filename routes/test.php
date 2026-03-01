<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\JobApplication;
use App\Models\Job;

Route::get('/test-applications/{userId}', function ($userId) {
    $user = User::find($userId);
    
    if (!$user) {
        return response()->json(['error' => 'User not found']);
    }
    
    // Check jobs owned by this user
    $jobs = Job::where('owner_user_id', $user->id)->get();
    
    // Check applications
    $apps = JobApplication::with(['job', 'applicant.applicantProfile'])
        ->whereHas('job', function($q) use ($user) {
            $q->where('owner_user_id', $user->id)
              ->where('owner_type', $user->role);
        })
        ->get();
    
    return response()->json([
        'user_id' => $user->id,
        'user_role' => $user->role,
        'user_email' => $user->email,
        'jobs_count' => $jobs->count(),
        'jobs' => $jobs->map(fn($j) => ['id' => $j->id, 'title' => $j->title]),
        'applications_count' => $apps->count(),
        'applications' => $apps->map(function($app) {
            return [
                'id' => $app->id,
                'job_id' => $app->job_id,
                'job_title' => $app->job->title ?? 'N/A',
                'applicant_id' => $app->applicant_user_id,
                'applicant_email' => $app->applicant->email ?? 'N/A',
                'applicant_name' => ($app->applicant->applicantProfile->first_name ?? '') . ' ' . ($app->applicant->applicantProfile->last_name ?? ''),
                'status' => $app->status,
            ];
        }),
    ]);
});

Route::get('/test-raw-data', function() {
    return response()->json([
        'total_users' => User::count(),
        'total_employers' => User::where('role', 'employer')->count(),
        'total_applicants' => User::where('role', 'applicant')->count(),
        'total_jobs' => Job::count(),
        'total_applications' => JobApplication::count(),
        'sample_application' => JobApplication::with(['job', 'applicant'])->first(),
    ]);
});

