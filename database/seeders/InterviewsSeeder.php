<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\User;

class InterviewsSeeder extends Seeder
{
    public function run(): void
    {
        $applications = JobApplication::whereIn('status', ['shortlisted', 'interview', 'hired'])->with('job')->get();
        $employers = User::where('role', 'employer')->get();

        if ($applications->isEmpty() || $employers->isEmpty()) return;

        foreach ($applications as $application) {
            if (Interview::where('application_id', $application->id)->exists()) continue;

            $start = now()->addDays(rand(1, 14))->setHour(rand(9, 16))->setMinute(0)->setSecond(0);
            Interview::create([
                'application_id' => $application->id,
                'scheduled_start' => $start,
                'scheduled_end' => $start->copy()->addHour(),
                'mode' => ['video', 'phone', 'in_person'][rand(0, 2)],
                'meeting_link' => 'https://zoom.us/j/demo' . $application->id,
                'status' => 'confirmed',
                'created_by_user_id' => $employers->first()->id,
            ]);
        }
    }
}
