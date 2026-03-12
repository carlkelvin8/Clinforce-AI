<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationsController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        $q = Notification::query()->where('user_id', $u->id)->orderByDesc('id');

        if ($v = $request->query('category')) $q->where('category', $v);
        if ($v = $request->query('type')) $q->where('type', $v);
        if ($v = $request->query('is_read')) $q->where('is_read', (int) ($v === 'true' || $v === '1'));
        if ($from = $request->query('from')) $q->where('created_at', '>=', $from);
        if ($to = $request->query('to')) $q->where('created_at', '<=', $to);

        return $this->ok($q->paginate(20));
    }

    public function unreadCount(): JsonResponse
    {
        $u = $this->requireAuth();
        $count = Notification::query()->where('user_id', $u->id)->where('is_read', false)->count();
        return $this->ok(['count' => $count]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        $ids = (array) $request->input('ids', []);
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) return $this->ok(['updated' => 0], 'No items');
        $updated = Notification::query()->where('user_id', $u->id)->whereIn('id', $ids)->update(['is_read' => true]);
        return $this->ok(['updated' => $updated], 'Read');
    }

    public function markAllRead(): JsonResponse
    {
        $u = $this->requireAuth();
        $updated = Notification::query()->where('user_id', $u->id)->update(['is_read' => true]);
        return $this->ok(['updated' => $updated], 'All read');
    }

    public function preferencesGet(): JsonResponse
    {
        $u = $this->requireAuth();
        $p = NotificationPreference::query()->firstOrCreate(
            ['user_id' => $u->id],
            [
                'email_enabled' => true,
                'in_app_enabled' => true,
                'frequency' => 'immediate',
                'category_toggles' => null,
            ]
        );
        return $this->ok($p);
    }

    public function preferencesUpdate(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        $p = NotificationPreference::query()->firstOrCreate(
            ['user_id' => $u->id],
            [
                'email_enabled' => true,
                'in_app_enabled' => true,
                'frequency' => 'immediate',
                'category_toggles' => null,
            ]
        );
        $v = $request->validate([
            'email_enabled' => ['sometimes','boolean'],
            'in_app_enabled' => ['sometimes','boolean'],
            'frequency' => ['sometimes','string','in:immediate,daily,weekly'],
            'category_toggles' => ['sometimes','array'],
        ]);
        $p->fill($v);
        $p->save();
        return $this->ok($p, 'Updated');
    }

    public function stream(Request $request): StreamedResponse
    {
        $u = $this->requireAuth();
        $lastId = (int) ($request->headers->get('Last-Event-ID') ?: $request->query('last_id', 0));
        $pref = NotificationPreference::query()->firstOrCreate(
            ['user_id' => $u->id],
            [
                'email_enabled' => true,
                'in_app_enabled' => true,
                'frequency' => 'immediate',
                'category_toggles' => null,
            ]
        );
        // Suppress real-time stream if in-app disabled or frequency is not immediate
        if (!$pref->in_app_enabled || ($pref->frequency && $pref->frequency !== 'immediate')) {
            return new StreamedResponse(function () {
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                echo "event: heartbeat\n";
                echo "data: {}\n\n";
                @ob_flush();
                @flush();
            });
        }
        $response = new StreamedResponse(function () use ($u, $lastId) {
            $items = Notification::query()
                ->where('user_id', $u->id)
                ->when($lastId > 0, fn ($q) => $q->where('id', '>', $lastId))
                ->orderBy('id')
                ->limit(50)
                ->get();
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            foreach ($items as $n) {
                $data = [
                    'id' => $n->id,
                    'category' => $n->category,
                    'type' => $n->type,
                    'title' => $n->title,
                    'body' => $n->body,
                    'data' => $n->data,
                    'url' => $n->url,
                    'is_read' => $n->is_read,
                    'created_at' => optional($n->created_at)->toISOString(),
                ];
                echo "id: {$n->id}\n";
                echo "event: notification\n";
                echo 'data: ' . json_encode($data) . "\n\n";
                @ob_flush();
                @flush();
            }
        });
        $response->headers->set('X-Accel-Buffering', 'no');
        return $response;
    }
}
