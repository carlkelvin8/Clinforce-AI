<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ConversationMarkReadRequest;
use App\Http\Requests\Api\ConversationStoreRequest;
use App\Http\Requests\Api\MessageStoreRequest;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Invitation;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MessagesController extends ApiController
{
    /**
     * GET /api/conversations
     * List conversations for current user (with lastMessage + participants)
     */
    public function index(): JsonResponse
    {
        $u = $this->requireAuth();

        $conversations = Conversation::query()
            ->whereHas('participants', fn($q) => $q->where('user_id', $u->id))
            ->with([
                'lastMessage.sender.applicantProfile',
                'lastMessage.sender.employerProfile',
                'lastMessage.sender.agencyProfile',
                'participants.user.applicantProfile',
                'participants.user.employerProfile',
                'participants.user.agencyProfile',
            ])
            ->orderByDesc(
                Message::select('id')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->latest('id')
                    ->limit(1)
            )
            ->get();

        return $this->ok($conversations);
    }

    /**
     * POST /api/conversations
     * Create a conversation + first message
     */
    public function store(ConversationStoreRequest $request): JsonResponse
    {
        $u = $this->requireAuth();
        $v = $request->validated();

        $participantIds = collect($v['participant_user_ids'])
            ->map(fn($x) => (int) $x)
            ->unique()
            ->values();

        // ensure self is included
        if (!$participantIds->contains((int)$u->id)) {
            $participantIds->push((int)$u->id);
        }

        // validate roles exist (optional but helpful)
        $users = User::query()
            ->whereIn('id', $participantIds->all())
            ->get(['id','role','email']);

        if ($users->count() !== $participantIds->count()) {
            return $this->fail('Invalid participants', null, 422);
        }

        $conversation = null;

        // Restrict who employers/agencies can start conversations with:
        // they may only message candidates they have invited OR who applied to their jobs.
        if (in_array($u->role, ['employer', 'agency'], true)) {
            $targetIds = $participantIds->filter(fn($id) => (int)$id !== (int)$u->id)->values();

            if ($targetIds->isNotEmpty()) {
                // Invitations from this employer/agency to these candidates
                $invitedIds = Invitation::query()
                    ->where('employer_id', $u->id)
                    ->whereIn('candidate_id', $targetIds->all())
                    ->pluck('candidate_id')
                    ->map(fn($id) => (int)$id)
                    ->unique();

                // Applications to jobs owned by this employer/agency
                $appliedIds = JobApplication::query()
                    ->whereIn('applicant_user_id', $targetIds->all())
                    ->whereHas('job', function ($q) use ($u) {
                        $q->where('owner_user_id', $u->id)
                          ->where('owner_type', $u->role);
                    })
                    ->pluck('applicant_user_id')
                    ->map(fn($id) => (int)$id)
                    ->unique();

                $eligibleIds = $invitedIds->merge($appliedIds)->unique();

                $unauthorized = $targetIds->reject(fn($id) => $eligibleIds->contains((int)$id));

                if ($unauthorized->isNotEmpty()) {
                    return $this->fail(
                        'You can only start conversations with candidates you invited or who applied to your jobs.',
                        null,
                        403
                    );
                }
            }
        }

        DB::transaction(function () use (&$conversation, $u, $v, $users, $participantIds) {
            $conversation = Conversation::query()->create([
                'created_by_user_id' => $u->id,
                'subject' => $v['subject'] ?? null,
            ]);

            foreach ($participantIds as $pid) {
                $target = $users->firstWhere('id', (int)$pid);

                ConversationParticipant::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => (int)$pid,
                    'role' => $target?->role,
                    'last_read_message_id' => null,
                    'created_at' => now(),
                ]);
            }

            $msg = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_user_id' => $u->id,
                'body' => $v['first_message'],
                'attachments_json' => null,
                'created_at' => now(),
            ]);

            // mark creator as read up to first message
            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $u->id)
                ->update(['last_read_message_id' => $msg->id]);
        });

        $conversation->load([
            'lastMessage.sender.applicantProfile',
            'lastMessage.sender.employerProfile',
            'lastMessage.sender.agencyProfile',
            'participants.user.applicantProfile',
            'participants.user.employerProfile',
            'participants.user.agencyProfile',
        ]);

        return $this->ok($conversation, 'Conversation created', 201);
    }

    /**
     * GET /api/conversations/{conversation}
     * View conversation with messages (only if participant)
     */
    public function show(Conversation $conversation): JsonResponse
    {
        $u = $this->requireAuth();

        if (!$this->isParticipant($conversation->id, $u->id)) {
            return $this->fail('Forbidden', null, 403);
        }

        $conversation->load([
            'participants.user.applicantProfile',
            'participants.user.employerProfile',
            'participants.user.agencyProfile',
            'messages.sender.applicantProfile',
            'messages.sender.employerProfile',
            'messages.sender.agencyProfile',
        ]);

        return $this->ok($conversation);
    }

    /**
     * POST /api/conversations/{conversation}/messages
     * Send a message (only participant)
     */
    public function send(MessageStoreRequest $request, Conversation $conversation): JsonResponse
    {
        $u = $this->requireAuth();

        if (!$this->isParticipant($conversation->id, $u->id)) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validated();

        $msg = null;

        DB::transaction(function () use (&$msg, $u, $conversation, $v) {
            $msg = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_user_id' => $u->id,
                'body' => $v['body'],
                'attachments_json' => $v['attachments_json'] ?? null,
                'created_at' => now(),
            ]);

            // mark sender read
            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $u->id)
                ->update(['last_read_message_id' => $msg->id]);
        });

        $msg->load(['sender.applicantProfile', 'sender.employerProfile', 'sender.agencyProfile']);

        return $this->ok($msg, 'Sent', 201);
    }

    /**
     * POST /api/conversations/{conversation}/read
     * Update last read pointer for current user
     */
    public function markRead(ConversationMarkReadRequest $request, Conversation $conversation): JsonResponse
    {
        $u = $this->requireAuth();

        if (!$this->isParticipant($conversation->id, $u->id)) {
            return $this->fail('Forbidden', null, 403);
        }

        $v = $request->validated();

        // ensure message belongs to same conversation
        $exists = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('id', (int)$v['last_read_message_id'])
            ->exists();

        if (!$exists) {
            return $this->fail('Invalid last_read_message_id', null, 422);
        }

        ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $u->id)
            ->update(['last_read_message_id' => (int)$v['last_read_message_id']]);

        return $this->ok(['ok' => true], 'Read updated');
    }

    private function isParticipant(int $conversationId, int $userId): bool
    {
        return ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->exists();
    }
}
