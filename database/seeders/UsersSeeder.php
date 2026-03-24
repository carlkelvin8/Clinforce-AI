<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\EmployerProfile;
use App\Models\ApplicantProfile;
use App\Models\AgencyProfile;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Employers
        $employers = [
            ['email' => 'employer1@demo.com', 'business_name' => 'City General Hospital', 'business_type' => 'hospital', 'country' => 'US', 'state' => 'NY', 'city' => 'New York'],
            ['email' => 'employer2@demo.com', 'business_name' => 'Sunrise Clinic', 'business_type' => 'clinic', 'country' => 'US', 'state' => 'CA', 'city' => 'Los Angeles'],
            ['email' => 'employer3@demo.com', 'business_name' => 'Metro Health Center', 'business_type' => 'other', 'country' => 'US', 'state' => 'TX', 'city' => 'Houston'],
        ];

        foreach ($employers as $e) {
            $user = User::firstOrCreate(['email' => $e['email']], [
                'role' => 'employer',
                'password_hash' => Hash::make('Password1!'),
                'status' => 'active',
                'email_verified_at' => now(),
                'subscription_status' => 'active',
            ]);
            EmployerProfile::firstOrCreate(['user_id' => $user->id], [
                'business_name' => $e['business_name'],
                'business_type' => $e['business_type'],
                'country' => $e['country'],
                'state' => $e['state'],
                'city' => $e['city'],
                'verification_status' => 'verified',
                'verified_at' => now(),
                'billing_currency_code' => 'USD',
            ]);
        }

        // Applicants
        $applicants = [
            ['email' => 'applicant1@demo.com', 'first_name' => 'James', 'last_name' => 'Smith', 'headline' => 'Senior Registered Nurse', 'years_experience' => 8, 'country' => 'US', 'state' => 'NY', 'city' => 'New York'],
            ['email' => 'applicant2@demo.com', 'first_name' => 'Ana', 'last_name' => 'Lee', 'headline' => 'ICU Specialist', 'years_experience' => 5, 'country' => 'US', 'state' => 'CA', 'city' => 'San Francisco'],
            ['email' => 'applicant3@demo.com', 'first_name' => 'Carlos', 'last_name' => 'Rivera', 'headline' => 'Physical Therapist', 'years_experience' => 3, 'country' => 'US', 'state' => 'FL', 'city' => 'Miami'],
            ['email' => 'applicant4@demo.com', 'first_name' => 'Sarah', 'last_name' => 'Johnson', 'headline' => 'Medical Lab Technician', 'years_experience' => 6, 'country' => 'US', 'state' => 'TX', 'city' => 'Dallas'],
            ['email' => 'applicant5@demo.com', 'first_name' => 'Michael', 'last_name' => 'Chen', 'headline' => 'Emergency Room Nurse', 'years_experience' => 10, 'country' => 'US', 'state' => 'WA', 'city' => 'Seattle'],
        ];

        foreach ($applicants as $a) {
            $user = User::firstOrCreate(['email' => $a['email']], [
                'role' => 'applicant',
                'password_hash' => Hash::make('Password1!'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            ApplicantProfile::firstOrCreate(['user_id' => $user->id], [
                'first_name' => $a['first_name'],
                'last_name' => $a['last_name'],
                'headline' => $a['headline'],
                'summary' => 'Experienced healthcare professional with a passion for patient care.',
                'years_experience' => $a['years_experience'],
                'country' => $a['country'],
                'state' => $a['state'],
                'city' => $a['city'],
                'public_display_name' => $a['first_name'] . ' ' . $a['last_name'],
            ]);
        }
    }
}
