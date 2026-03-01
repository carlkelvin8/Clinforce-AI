<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password {email} {password}';
    protected $description = 'Reset user password and verify email';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }

        $user->password_hash = Hash::make($password);
        $user->email_verified_at = now();
        $user->status = 'active';
        $user->save();

        $this->info("Password reset successfully for: {$email}");
        $this->info("Email: {$user->email}");
        $this->info("Role: {$user->role}");
        $this->info("Status: {$user->status}");
        $this->info("Email verified: Yes");

        return 0;
    }
}
