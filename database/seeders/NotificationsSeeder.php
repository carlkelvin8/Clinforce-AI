<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $notifications = match ($user->role) {
                'employer' => [
                    ['category' => 'application', 'type' => 'new_application', 'title' => 'New Application Received', 'body' => 'A new applicant has applied to your job posting.', 'url' => '/employer/applications'],
                    ['category' => 'interview', 'type' => 'interview_scheduled', 'title' => 'Interview Scheduled', 'body' => 'An interview has been scheduled for tomorrow at 10:00 AM.', 'url' => '/employer/interviews'],
                ],
                'applicant' => [
                    ['category' => 'application', 'type' => 'application_reviewed', 'title' => 'Application Reviewed', 'body' => 'Your application has been reviewed by the employer.', 'url' => '/applicant/applications'],
                    ['category' => 'interview', 'type' => 'interview_invitation', 'title' => 'Interview Invitation', 'body' => 'You have been invited for an interview. Check your schedule.', 'url' => '/applicant/interviews'],
                ],
                default => [],
            };

            foreach ($notifications as $n) {
                Notification::create([
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'category' => $n['category'],
                    'type' => $n['type'],
                    'title' => $n['title'],
                    'body' => $n['body'],
                    'url' => $n['url'],
                    'is_read' => (bool) rand(0, 1),
                    'created_at' => now()->subMinutes(rand(5, 1440)),
                ]);
            }
        }
    }
}
