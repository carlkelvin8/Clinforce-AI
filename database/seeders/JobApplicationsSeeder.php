<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;

class JobApplicationsSeeder extends Seeder
{
    public function run(): void
    {
        $applicants = User::where('role', 'applicant')->get();
        $jobs = Job::where('status', 'published')->get();

        if ($applicants->isEmpty() || $jobs->isEmpty()) return;

        $statuses = ['submitted', 'shortlisted', 'rejected', 'interview', 'hired'];

        foreach ($jobs as $i => $job) {
            // Each job gets 2-3 applicants
            $jobApplicants = $applicants->random(min(3, $applicants->count()));
            foreach ($jobApplicants as $j => $applicant) {
                JobApplication::firstOrCreate(
                    ['job_id' => $job->id, 'applicant_user_id' => $applicant->id],
                    [
                        'status' => $statuses[($i + $j) % count($statuses)],
                        'cover_letter' => 'I am very interested in this position and believe my experience makes me a strong candidate. I look forward to discussing how I can contribute to your team.',
                        'submitted_at' => now()->subDays(rand(1, 20)),
                    ]
                );
            }
        }
    }
}
