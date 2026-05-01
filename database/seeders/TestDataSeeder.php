<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Interview;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get test users
        $employer1 = User::where('email', 'employer1@demo.com')->first();
        $employer2 = User::where('email', 'employer2@demo.com')->first();
        
        $applicant1 = User::where('email', 'applicant1@demo.com')->first();
        $applicant2 = User::where('email', 'applicant2@demo.com')->first();
        $applicant3 = User::where('email', 'applicant3@demo.com')->first();

        if (!$employer1 || !$applicant1) {
            $this->command->error('Please run UsersSeeder first: php artisan db:seed --class=UsersSeeder');
            return;
        }

        // Create Jobs
        $jobs = [];
        
        $jobs[] = Job::firstOrCreate(
            ['title' => 'Senior ICU Nurse'],
            [
                'owner_user_id' => $employer1->id,
                'owner_type' => 'employer',
                'description' => 'We are seeking an experienced ICU nurse to join our critical care team. Must have 5+ years of ICU experience.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country_code' => 'US',
                'state' => 'NY',
                'city' => 'New York',
                'salary_min' => 75000,
                'salary_max' => 95000,
                'salary_currency' => 'USD',
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ]
        );

        $jobs[] = Job::firstOrCreate(
            ['title' => 'Emergency Room Nurse'],
            [
                'owner_user_id' => $employer1->id,
                'owner_type' => 'employer',
                'description' => 'Join our fast-paced ER team. Looking for nurses with trauma experience and excellent decision-making skills.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country_code' => 'US',
                'state' => 'NY',
                'city' => 'New York',
                'salary_min' => 70000,
                'salary_max' => 90000,
                'salary_currency' => 'USD',
                'status' => 'published',
                'published_at' => now()->subDays(3),
            ]
        );

        $jobs[] = Job::firstOrCreate(
            ['title' => 'Physical Therapist'],
            [
                'owner_user_id' => $employer2->id,
                'owner_type' => 'employer',
                'description' => 'Seeking a licensed physical therapist to work with orthopedic and sports injury patients.',
                'employment_type' => 'full_time',
                'work_mode' => 'hybrid',
                'country_code' => 'US',
                'state' => 'CA',
                'city' => 'Los Angeles',
                'salary_min' => 65000,
                'salary_max' => 85000,
                'salary_currency' => 'USD',
                'status' => 'published',
                'published_at' => now()->subDays(7),
            ]
        );

        $this->command->info('Created ' . count($jobs) . ' jobs');

        // Create Applications
        $applications = [];

        // Applicant 1 applies to Job 1 (ICU Nurse)
        $applications[] = JobApplication::firstOrCreate(
            [
                'job_id' => $jobs[0]->id,
                'applicant_user_id' => $applicant1->id,
            ],
            [
                'status' => 'interview',
                'cover_letter' => 'I am very interested in this ICU position. I have 8 years of critical care experience.',
                'created_at' => now()->subDays(4),
            ]
        );

        // Applicant 2 applies to Job 1 (ICU Nurse)
        $applications[] = JobApplication::firstOrCreate(
            [
                'job_id' => $jobs[0]->id,
                'applicant_user_id' => $applicant2->id,
            ],
            [
                'status' => 'review',
                'cover_letter' => 'I would love to join your ICU team. I specialize in critical care.',
                'created_at' => now()->subDays(3),
            ]
        );

        // Applicant 1 applies to Job 2 (ER Nurse)
        $applications[] = JobApplication::firstOrCreate(
            [
                'job_id' => $jobs[1]->id,
                'applicant_user_id' => $applicant1->id,
            ],
            [
                'status' => 'submitted',
                'cover_letter' => 'I have extensive ER experience and would be a great fit for your team.',
                'created_at' => now()->subDays(2),
            ]
        );

        // Applicant 3 applies to Job 3 (Physical Therapist)
        $applications[] = JobApplication::firstOrCreate(
            [
                'job_id' => $jobs[2]->id,
                'applicant_user_id' => $applicant3->id,
            ],
            [
                'status' => 'interview',
                'cover_letter' => 'I am a licensed PT with 3 years of experience in sports medicine.',
                'created_at' => now()->subDays(6),
            ]
        );

        $this->command->info('Created ' . count($applications) . ' applications');

        // Create Interviews (upcoming interviews for testing)
        $interviews = [];

        // Interview 1: Applicant 1 for ICU Nurse (tomorrow)
        $interviews[] = Interview::firstOrCreate(
            [
                'application_id' => $applications[0]->id,
            ],
            [
                'scheduled_start' => Carbon::tomorrow()->setTime(10, 0),
                'scheduled_end' => Carbon::tomorrow()->setTime(11, 0),
                'mode' => 'video',
                'meeting_link' => 'https://zoom.us/j/123456789',
                'status' => 'confirmed',
                'created_by_user_id' => $employer1->id,
            ]
        );

        // Interview 2: Applicant 1 for ICU Nurse (next week)
        $interviews[] = Interview::firstOrCreate(
            [
                'application_id' => $applications[0]->id,
                'scheduled_start' => Carbon::now()->addDays(7)->setTime(14, 0),
            ],
            [
                'scheduled_end' => Carbon::now()->addDays(7)->setTime(15, 0),
                'mode' => 'video',
                'meeting_link' => 'https://zoom.us/j/987654321',
                'status' => 'proposed',
                'created_by_user_id' => $employer1->id,
            ]
        );

        // Interview 3: Applicant 3 for Physical Therapist (in 3 days)
        $interviews[] = Interview::firstOrCreate(
            [
                'application_id' => $applications[3]->id,
            ],
            [
                'scheduled_start' => Carbon::now()->addDays(3)->setTime(15, 30),
                'scheduled_end' => Carbon::now()->addDays(3)->setTime(16, 30),
                'mode' => 'video',
                'meeting_link' => 'https://zoom.us/j/555666777',
                'status' => 'confirmed',
                'created_by_user_id' => $employer2->id,
            ]
        );

        $this->command->info('Created ' . count($interviews) . ' interviews');

        $this->command->info('✅ Test data seeded successfully!');
        $this->command->info('');
        $this->command->info('Test Accounts:');
        $this->command->info('- Employer: employer1@demo.com / Password1!');
        $this->command->info('- Candidate: applicant1@demo.com / Password1! (has 2 upcoming interviews)');
        $this->command->info('- Candidate: applicant3@demo.com / Password1! (has 1 upcoming interview)');
    }
}
