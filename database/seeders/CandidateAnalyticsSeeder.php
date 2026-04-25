<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Interview;
use Carbon\Carbon;

class CandidateAnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        // Get the first candidate (applicant1@demo.com - James Smith)
        $candidate = User::where('email', 'applicant1@demo.com')->first();
        
        if (!$candidate) {
            $this->command->error('Candidate not found. Please run UsersSeeder first.');
            return;
        }

        // Get some jobs to apply to
        $jobs = Job::limit(15)->get();
        
        if ($jobs->isEmpty()) {
            $this->command->error('No jobs found. Please create some jobs first.');
            return;
        }

        $this->command->info('Creating analytics data for James Smith (applicant1@demo.com)...');

        // Create applications over the last 6 months with realistic progression
        $applications = [];
        $interviews = [];
        
        $statuses = ['new', 'review', 'interview', 'hired', 'rejected'];
        $interviewModes = ['video', 'phone', 'in_person'];
        
        foreach ($jobs as $index => $job) {
            // Create applications spread over 6 months
            $createdAt = Carbon::now()->subDays(rand(1, 180));
            
            // Determine status based on realistic progression
            $status = $this->getRealisticStatus($index, count($jobs));
            
            $application = JobApplication::create([
                'job_id' => $job->id,
                'applicant_user_id' => $candidate->id,
                'status' => $status,
                'cover_letter' => 'I am very interested in this position and believe my experience makes me a great fit.',
                'submitted_at' => $createdAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            
            $applications[] = $application;
            
            // Create interviews for applications that reached interview stage
            if (in_array($status, ['interview', 'hired'])) {
                $interviewDate = $createdAt->copy()->addDays(rand(3, 14));
                
                $interview = Interview::create([
                    'application_id' => $application->id,
                    'interview_type' => $interviewModes[array_rand($interviewModes)],
                    'scheduled_at' => $interviewDate,
                    'scheduled_start' => $interviewDate,
                    'scheduled_end' => $interviewDate->copy()->addMinutes(rand(30, 90)),
                    'duration_minutes' => rand(30, 90),
                    'mode' => $interviewModes[array_rand($interviewModes)],
                    'status' => $status === 'hired' ? 'completed' : ($interviewDate->isPast() ? 'completed' : 'scheduled'),
                    'meeting_link' => 'https://zoom.us/j/123456789',
                    'location_text' => 'Video Call',
                    'notes' => 'Interview scheduled with hiring manager',
                    'created_at' => $createdAt->copy()->addDays(2),
                    'updated_at' => $createdAt->copy()->addDays(2),
                ]);
                
                $interviews[] = $interview;
            }
        }

        $this->command->info('Created ' . count($applications) . ' job applications');
        $this->command->info('Created ' . count($interviews) . ' interviews');
        
        // Summary of what was created
        $statusCounts = collect($applications)->groupBy('status')->map->count();
        $this->command->info('Application status breakdown:');
        foreach ($statusCounts as $status => $count) {
            $this->command->info("  - {$status}: {$count}");
        }
        
        $this->command->info('Analytics data created successfully! 🎉');
        $this->command->info('Login as applicant1@demo.com to see the analytics dashboard.');
    }
    
    private function getRealisticStatus($index, $total)
    {
        // Create a realistic distribution of application statuses
        $percentage = ($index + 1) / $total;
        
        if ($percentage <= 0.15) {
            return 'hired'; // 15% success rate
        } elseif ($percentage <= 0.35) {
            return 'interview'; // 20% get to interview stage
        } elseif ($percentage <= 0.55) {
            return 'review'; // 20% under review
        } elseif ($percentage <= 0.85) {
            return 'rejected'; // 30% rejected
        } else {
            return 'new'; // 15% still new/pending
        }
    }
}