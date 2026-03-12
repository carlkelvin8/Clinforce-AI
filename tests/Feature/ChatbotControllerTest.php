<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatbotControllerTest extends TestCase
{
    public function test_chatbot_returns_assistant_message(): void
    {
        Config::set('services.openai.api_key', 'test-key');

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'ok',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = new User([
            'role' => 'employer',
            'status' => 'active',
            'email' => 'test@example.com',
        ]);
        $user->id = 123;
        $user->exists = true;

        Sanctum::actingAs($user);

        $resp = $this->postJson('/api/chatbot', [
            'messages' => [
                ['role' => 'user', 'content' => 'hi'],
            ],
        ]);

        $resp->assertStatus(200);
        $resp->assertJson([
            'role' => 'assistant',
            'content' => 'ok',
        ]);
    }

    public function test_chatbot_returns_config_error_when_key_missing(): void
    {
        Config::set('services.openai.api_key', null);

        $user = new User([
            'role' => 'employer',
            'status' => 'active',
            'email' => 'test@example.com',
        ]);
        $user->id = 124;
        $user->exists = true;

        Sanctum::actingAs($user);

        $resp = $this->postJson('/api/chatbot', [
            'messages' => [
                ['role' => 'user', 'content' => 'hi'],
            ],
        ]);

        $resp->assertStatus(503);
        $resp->assertJsonStructure(['error']);
    }
}

