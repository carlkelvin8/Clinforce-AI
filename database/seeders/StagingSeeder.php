<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\EmployerProfile;
use App\Models\ApplicantProfile;

class StagingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding staging test accounts...');

        // ── Admin ────────────────────────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'admin@aiclinforce.com'],
            [
                'role'              => 'admin',
                'password_hash'     => Hash::make('Admin@Staging2026!'),
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );
        $this->command->info("Admin:     admin@aiclinforce.com  /  Admin\@Staging2026!");

        // ── Employer ─────────────────────────────────────────────────────
        $employer = User::updateOrCreate(
            ['email' => 'employer@aiclinforce.com'],
            [
                'role'              => 'employer',
                'password_hash'     => Hash::make('Employer@Staging2026!'),
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        EmployerProfile::updateOrCreate(
            ['user_id' => $employer->id],
            [
                'business_name'         => 'ClinForce Demo Hospital',
                'business_type'         => 'hospital',
                'country'               => 'Philippines',
                'state'                 => 'NCR',
                'city'                  => 'Manila',
                'billing_currency_code' => 'PHP',
                'verification_status'   => 'verified',
                'verified_at'           => now(),
            ]
        );
        $this->command->info("Employer:  employer@aiclinforce.com  /  Employer\@Staging2026!");

        // ── Candidate ────────────────────────────────────────────────────
        $candidate = User::updateOrCreate(
            ['email' => 'candidate@aiclinforce.com'],
            [
                'role'              => 'applicant',
                'password_hash'     => Hash::make('Candidate@Staging2026!'),
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        ApplicantProfile::updateOrCreate(
            ['user_id' => $candidate->id],
            [
                'first_name'          => 'Demo',
                'last_name'           => 'Candidate',
                'headline'            => 'Registered Nurse · ICU Specialist',
                'summary'             => 'Experienced healthcare professional available for immediate placement. Skilled in critical care, patient assessment, and clinical documentation.',
                'years_experience'    => 5,
                'country'             => 'Philippines',
                'state'               => 'NCR',
                'city'                => 'Quezon City',
                'public_display_name' => 'Demo Candidate',
                'open_to_work'        => true,
            ]
        );
        $this->command->info("Candidate: candidate@aiclinforce.com  /  Candidate\@Staging2026!");

        $this->command->newLine();
        $this->command->info('✅ Staging accounts ready.');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin',     'admin@aiclinforce.com',     'Admin@Staging2026!'],
                ['Employer',  'employer@aiclinforce.com',  'Employer@Staging2026!'],
                ['Candidate', 'candidate@aiclinforce.com', 'Candidate@Staging2026!'],
            ]
        );
    }
}
