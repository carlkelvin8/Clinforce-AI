<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CountriesSeeder::class,
            ExchangeRateSeeder::class,
            PlansSeeder::class,
            UsersSeeder::class,
            SubscriptionsSeeder::class,
            JobsSeeder::class,
            JobApplicationsSeeder::class,
            InterviewsSeeder::class,
            DocumentsSeeder::class,
            AiScreeningsSeeder::class,
            MessagesSeeder::class,
            NotificationsSeeder::class,
            AssessmentTemplatesSeeder::class,
            PortfolioSeeder::class,
        ]);
    }
}
