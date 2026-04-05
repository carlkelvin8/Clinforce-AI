<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'employer@test.com',  'role' => 'employer',  'status' => 'active'],
            ['email' => 'applicant@test.com', 'role' => 'applicant', 'status' => 'active'],
            ['email' => 'admin@test.com',     'role' => 'admin',     'status' => 'active'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'role'              => $u['role'],
                    'status'            => $u['status'],
                    'password_hash'     => Hash::make('Password1!'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info('Test users seeded.');
    }
}
