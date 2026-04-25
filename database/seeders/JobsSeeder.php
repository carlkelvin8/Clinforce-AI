<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Job;
use Carbon\Carbon;

class JobsSeeder extends Seeder
{
    public function run(): void
    {
        // Get the first employer
        $employer = User::where('role', 'employer')->first();
        
        if (!$employer) {
            $this->command->error('No employer found. Please run UsersSeeder first.');
            return;
        }

        $jobs = [
            [
                'title' => 'Senior Registered Nurse - ICU',
                'description' => 'We are seeking an experienced ICU nurse to join our critical care team.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'NY',
                'city' => 'New York',
                'salary_min' => 75000,
                'salary_max' => 95000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Emergency Room Nurse',
                'description' => 'Join our fast-paced ER team providing critical patient care.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'NY',
                'city' => 'New York',
                'salary_min' => 70000,
                'salary_max' => 90000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Medical Surgical Nurse',
                'description' => 'Provide comprehensive nursing care in our medical-surgical unit.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'NY',
                'city' => 'New York',
                'salary_min' => 65000,
                'salary_max' => 80000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Pediatric Nurse',
                'description' => 'Care for our youngest patients in the pediatric ward.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'CA',
                'city' => 'Los Angeles',
                'salary_min' => 68000,
                'salary_max' => 85000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Operating Room Nurse',
                'description' => 'Assist in surgical procedures in our state-of-the-art OR.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'TX',
                'city' => 'Houston',
                'salary_min' => 72000,
                'salary_max' => 92000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Oncology Nurse',
                'description' => 'Provide specialized care for cancer patients.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'FL',
                'city' => 'Miami',
                'salary_min' => 70000,
                'salary_max' => 88000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Cardiac Care Nurse',
                'description' => 'Specialize in cardiovascular patient care.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'WA',
                'city' => 'Seattle',
                'salary_min' => 76000,
                'salary_max' => 96000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Nurse Practitioner',
                'description' => 'Advanced practice nursing role with diagnostic responsibilities.',
                'employment_type' => 'full_time',
                'work_mode' => 'hybrid',
                'country' => 'US',
                'state' => 'NY',
                'city' => 'New York',
                'salary_min' => 95000,
                'salary_max' => 120000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Travel Nurse - ICU',
                'description' => 'Short-term assignment in our ICU department.',
                'employment_type' => 'contract',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'CA',
                'city' => 'San Francisco',
                'salary_min' => 80000,
                'salary_max' => 100000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Charge Nurse - Night Shift',
                'description' => 'Lead the nursing team during night shifts.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'TX',
                'city' => 'Dallas',
                'salary_min' => 78000,
                'salary_max' => 98000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Rehabilitation Nurse',
                'description' => 'Help patients recover and regain independence.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'IL',
                'city' => 'Chicago',
                'salary_min' => 66000,
                'salary_max' => 82000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Mental Health Nurse',
                'description' => 'Provide care for patients with mental health conditions.',
                'employment_type' => 'full_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'MA',
                'city' => 'Boston',
                'salary_min' => 71000,
                'salary_max' => 89000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Telehealth Nurse',
                'description' => 'Provide remote patient care and consultation.',
                'employment_type' => 'full_time',
                'work_mode' => 'remote',
                'country' => 'US',
                'state' => 'Remote',
                'city' => 'Remote',
                'salary_min' => 65000,
                'salary_max' => 85000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Clinical Nurse Educator',
                'description' => 'Train and mentor new nursing staff.',
                'employment_type' => 'full_time',
                'work_mode' => 'hybrid',
                'country' => 'US',
                'state' => 'GA',
                'city' => 'Atlanta',
                'salary_min' => 73000,
                'salary_max' => 93000,
                'salary_currency' => 'USD',
            ],
            [
                'title' => 'Per Diem Nurse',
                'description' => 'Flexible scheduling for experienced nurses.',
                'employment_type' => 'part_time',
                'work_mode' => 'on_site',
                'country' => 'US',
                'state' => 'AZ',
                'city' => 'Phoenix',
                'salary_min' => 35,
                'salary_max' => 45,
                'salary_currency' => 'USD',
            ],
        ];

        foreach ($jobs as $jobData) {
            Job::create([
                'owner_type' => 'employer',
                'owner_user_id' => $employer->id,
                'title' => $jobData['title'],
                'description' => $jobData['description'],
                'employment_type' => $jobData['employment_type'],
                'work_mode' => $jobData['work_mode'],
                'country' => $jobData['country'],
                'state' => $jobData['state'],
                'city' => $jobData['city'],
                'status' => 'published',
                'published_at' => Carbon::now()->subDays(rand(1, 30)),
                'salary_min' => $jobData['salary_min'],
                'salary_max' => $jobData['salary_max'],
                'salary_currency' => $jobData['salary_currency'],
                'salary_type' => $jobData['employment_type'] === 'part_time' ? 'hourly' : 'annual',
                'view_count' => rand(50, 500),
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }

        $this->command->info('Created ' . count($jobs) . ' healthcare jobs successfully! 🏥');
    }
}