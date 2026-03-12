<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZoomWebhookController extends Controller
{
    public function handle(Request $request)
    {
        if (!$this->verifySignature($request)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $payload = $request->input('payload');

        if ($event === 'endpoint.url_validation') {
            return response()->json([
                'plainToken' => $payload['plainToken'],
                'encryptedToken' => hash_hmac('sha256', $payload['plainToken'], config('services.zoom.webhook_secret')),
            ]);
        }

        if ($event === 'meeting.participant_joined') {
            $this->handleParticipantJoined($payload);
        }

        if ($event === 'meeting.transcript_message') {
            $this->handleTranscript($payload);
        }

        return response()->json(['status' => 'ok']);
    }

    private function verifySignature(Request $request): bool
    {
        $message = 'v0:' . $request->header('x-zm-request-timestamp') . ':' . $request->getContent();
        $hash = hash_hmac('sha256', $message, config('services.zoom.webhook_secret'));
        $signature = 'v0=' . $hash;
        return hash_equals($signature, $request->header('x-zm-signature'));
    }

    private function handleTranscript(array $payload)
    {
        $content = $payload['object']['content'] ?? '';
        $meetingId = $payload['object']['id'] ?? '';
        $participantId = $payload['object']['participant_id'] ?? '';

        if ($this->containsEmail($content)) {
             Log::warning('Zoom transcript email detected', [
                 'meeting_id' => $meetingId,
                 'participant_id' => $participantId,
                 'content_len' => strlen((string) $content),
                 'content_sha256' => hash('sha256', (string) $content),
             ]);
        }
    }

    private function containsEmail(string $text): bool
    {
        return preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $text) === 1;
    }

    private function handleParticipantJoined(array $payload)
    {
        $object = $payload['object'] ?? [];
        $participant = $object['participant'] ?? [];

        $userName = (string) ($participant['user_name'] ?? '');
        $email = $participant['email'] ?? '';

        if ($this->shouldFilter($userName, $email)) {
            Log::info('Zoom participant name flagged', [
                'meeting_id' => $object['id'] ?? null,
                'participant_id' => $participant['id'] ?? null,
            ]);
        }
    }

    private function shouldFilter(string $name, string $email): bool
    {
        if (filter_var($name, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $name)) {
            return true;
        }

        return false;
    }
}
