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
                    'role_at_join' => $target?->role,
                    'last_read_at' => null,
                    'created_at' => now(),
                ]);
            }

            $msg = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_user_id' => $u->id,
                'body' => $v['first_message'],
                'created_at' => now(),
            ]);

            // mark creator as read
            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $u->id)
                ->update(['last_read_at' => now()]);
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
        ]);

        // Paginate messages — newest first, 30 per page
        $perPage = 30;
        $page    = (int) request()->query('page', 1);

        $messages = \App\Models\Message::query()
            ->where('conversation_id', $conversation->id)
            ->with(['sender.applicantProfile', 'sender.employerProfile', 'sender.agencyProfile'])
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->ok([
            'conversation' => $conversation,
            'messages'     => $messages->items(),
            'pagination'   => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'total'        => $messages->total(),
                'per_page'     => $perPage,
                'has_more'     => $messages->hasMorePages(),
            ],
        ]);
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
                ->update(['last_read_at' => now()]);
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

        ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $u->id)
            ->update(['last_read_at' => now()]);

        return $this->ok(['ok' => true], 'Read updated');
    }

    private function isParticipant(int $conversationId, int $userId): bool
    {
        return ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * GET /api/conversations/unread-count
     * Returns total unread message count for the current user.
     */
    public function unreadCount(): JsonResponse
    {
        $u = $this->requireAuth();

        $count = ConversationParticipant::query()
            ->where('user_id', $u->id)
            ->whereHas('conversation.messages', function ($q) use ($u) {
                $q->where('sender_user_id', '!=', $u->id)
                  ->where(function ($inner) use ($u) {
                      $inner->whereHas('conversation.participants', function ($p) use ($u) {
                          $p->where('user_id', $u->id)
                            ->where(function ($pp) {
                                $pp->whereNull('last_read_at')
                                   ->orWhereColumn('messages.created_at', '>', 'conversation_participants.last_read_at');
                            });
                      });
                  });
            })
            ->count();

        // Simpler approach: count conversations where last message is newer than last_read_at
        $unreadConversations = ConversationParticipant::query()
            ->where('user_id', $u->id)
            ->with(['conversation.lastMessage'])
            ->get()
            ->filter(function ($participant) use ($u) {
                $lastMsg = $participant->conversation?->lastMessage;
                if (!$lastMsg) return false;
                if ((int)$lastMsg->sender_user_id === (int)$u->id) return false;
                if (!$participant->last_read_at) return true;
                return $lastMsg->created_at > $participant->last_read_at;
            })
            ->count();

        return $this->ok(['count' => $unreadConversations]);
    }
}
