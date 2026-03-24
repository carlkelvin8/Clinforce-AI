<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AiScreening;
use App\Models\JobApplication;

class AiScreeningsSeeder extends Seeder
{
    public function run(): void
    {
        $applications = JobApplication::with('job')->get();
        $nextId = (int) \DB::table('ai_screenings')->max('id') + 1;

        foreach ($applications as $application) {
            if (AiScreening::where('application_id', $application->id)->exists()) continue;

            $score = round(rand(55, 98) / 10, 1); // 5.5 to 9.8
            AiScreening::create([
                'id' => $nextId++,
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'applicant_user_id' => $application->applicant_user_id,
                'model_name' => 'gpt-4o',
                'score' => $score,
                'summary' => 'Candidate demonstrates strong clinical background with relevant experience. Communication skills appear solid based on cover letter analysis. Overall a good match for the role.',
                'suggestions' => [
                    'Verify license credentials before proceeding',
                    'Ask about specific experience with EMR systems',
                    'Confirm availability for required shift hours',
                ],
                'created_at' => now()->subDays(rand(1, 15)),
            ]);
        }
    }
}
