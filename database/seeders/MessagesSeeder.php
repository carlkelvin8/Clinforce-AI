<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;

class MessagesSeeder extends Seeder
{
    public function run(): void
    {
        $employers = User::where('role', 'employer')->get();
        $applicants = User::where('role', 'applicant')->get();

        if ($employers->isEmpty() || $applicants->isEmpty()) return;

        $pairs = [
            [$employers->first(), $applicants->first()],
            [$employers->first(), $applicants->skip(1)->first()],
        ];

        foreach ($pairs as [$employer, $applicant]) {
            if (!$employer || !$applicant) continue;

            $conv = Conversation::create([
                'subject' => 'Job Opportunity Discussion',
                'created_by_user_id' => $employer->id,
                'created_at' => now()->subDays(3),
            ]);

            ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $employer->id]);
            ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $applicant->id]);

            $thread = [
                [$employer->id, 'Hi, we reviewed your application and are impressed with your background.'],
                [$applicant->id, 'Thank you! I am very excited about this opportunity.'],
                [$employer->id, 'Would you be available for an interview next week?'],
                [$applicant->id, 'Absolutely, I am flexible. Please let me know the time.'],
            ];

            foreach ($thread as [$senderId, $body]) {
                Message::create([
                    'conversation_id' => $conv->id,
                    'sender_user_id' => $senderId,
                    'body' => $body,
                ]);
            }
        }
    }
}
