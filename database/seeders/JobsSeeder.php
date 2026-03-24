<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;
use App\Models\User;

class JobsSeeder extends Seeder
{
    public function run(): void
    {
        $employers = User::where('role', 'employer')->get();
        if ($employers->isEmpty()) return;

        $jobs = [
            ['title' => 'Senior Registered Nurse', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'status' => 'published', 'country' => 'US', 'city' => 'New York'],
            ['title' => 'ICU Specialist', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'status' => 'published', 'country' => 'US', 'city' => 'Los Angeles'],
            ['title' => 'Physical Therapist', 'employment_type' => 'part_time', 'work_mode' => 'hybrid', 'status' => 'published', 'country' => 'US', 'city' => 'Houston'],
            ['title' => 'Medical Lab Technician', 'employment_type' => 'contract', 'work_mode' => 'on_site', 'status' => 'published', 'country' => 'US', 'city' => 'Dallas'],
            ['title' => 'Emergency Room Nurse', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'status' => 'published', 'country' => 'US', 'city' => 'Seattle'],
            ['title' => 'Radiologist Technician', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'status' => 'draft', 'country' => 'US', 'city' => 'Chicago'],
            ['title' => 'Home Health Aide', 'employment_type' => 'part_time', 'work_mode' => 'remote', 'status' => 'archived', 'country' => 'US', 'city' => 'Phoenix'],
        ];

        foreach ($jobs as $i => $j) {
            $employer = $employers[$i % $employers->count()];
            Job::firstOrCreate(
                ['title' => $j['title'], 'owner_user_id' => $employer->id],
                [
                    'owner_type' => 'employer',
                    'owner_user_id' => $employer->id,
                    'title' => $j['title'],
                    'description' => 'We are looking for a qualified ' . $j['title'] . ' to join our team. The ideal candidate will have relevant clinical experience and a commitment to excellent patient care.',
                    'employment_type' => $j['employment_type'],
                    'work_mode' => $j['work_mode'],
                    'country' => $j['country'],
                    'city' => $j['city'],
                    'status' => $j['status'],
                    'published_at' => $j['status'] === 'published' ? now()->subDays(rand(1, 30)) : null,
                    'archived_at' => $j['status'] === 'archived' ? now()->subDays(rand(1, 10)) : null,
                ]
            );
        }
    }
}
