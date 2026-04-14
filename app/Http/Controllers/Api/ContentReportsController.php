<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContentReportsController extends ApiController
{
    private function requireAdmin(): User
    {
        $u = $this->requireAuth();
        if ($u->role !== 'admin') abort(403, 'Admin only');
        return $u;
    }

    // ── List all reports (admin) ─────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $status   = $request->query('status');
        $reason   = $request->query('reason');
        $severity = $request->query('severity');
        $q        = trim((string) $request->query('q', ''));

        $query = DB::table('content_reports')
            ->join('users as reporter', 'reporter.id', '=', 'content_reports.reported_by_user_id')
            ->leftJoin('users as reported', 'reported.id', '=', 'content_reports.reported_user_id')
            ->select(
                'content_reports.*',
                'reporter.email as reporter_email',
                'reported.email as reported_user_email'
            )
            ->orderByDesc('content_reports.id');

        if ($status)   $query->where('content_reports.status', $status);
        if ($reason)   $query->where('content_reports.reason', $reason);
        if ($severity) $query->where('content_reports.severity', $severity);
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('reporter.email', 'like', "%{$q}%")
                   ->orWhere('reported.email', 'like', "%{$q}%")
                   ->orWhere('content_reports.reason', 'like', "%{$q}%");
            });
        }

        return $this->ok($query->paginate(20));
    }

    // ── Review a report ──────────────────────────────────────────────────
    public function review(Request $request, int $id): JsonResponse
    {
        $admin = $this->requireAdmin();

        $data = $request->validate([
            'status'           => 'required|in:under_review,action_taken,dismissed,escalated',
            'resolution_notes' => 'nullable|string|max:2000',
            'action_taken'     => 'nullable|array',
        ]);

        $resolved = in_array($data['status'], ['action_taken', 'dismissed']);

        DB::table('content_reports')->where('id', $id)->update([
            'status'              => $data['status'],
            'resolution_notes'    => $data['resolution_notes'] ?? null,
            'action_taken'        => isset($data['action_taken']) ? json_encode($data['action_taken']) : null,
            'assigned_to_user_id' => $admin->id,
            'resolved_by_user_id' => $resolved ? $admin->id : null,
            'resolved_at'         => $resolved ? now() : null,
            'updated_at'          => now(),
        ]);

        return $this->ok(['message' => 'Report updated']);
    }

    // ── Moderation Queue (admin) ─────────────────────────────────────────
    public function moderationQueue(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $status   = $request->query('status', 'queued');
        $priority = $request->query('priority');

        $query = DB::table('moderation_queue')
            ->leftJoin('users as reporter', 'reporter.id', '=', 'moderation_queue.reported_by_user_id')
            ->select('moderation_queue.*', 'reporter.email as reporter_email')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderByDesc('moderation_queue.id');

        if ($status)   $query->where('moderation_queue.status', $status);
        if ($priority) $query->where('moderation_queue.priority', $priority);

        return $this->ok($query->paginate(20));
    }

    // ── Review moderation queue item ─────────────────────────────────────
    public function reviewModerationItem(Request $request, int $id): JsonResponse
    {
        $admin = $this->requireAdmin();

        $data = $request->validate([
            'status'          => 'required|in:in_review,approved,rejected,escalated',
            'moderator_notes' => 'nullable|string|max:2000',
            'action_taken'    => 'nullable|array',
        ]);

        DB::table('moderation_queue')->where('id', $id)->update([
            'status'              => $data['status'],
            'moderator_notes'     => $data['moderator_notes'] ?? null,
            'action_taken'        => isset($data['action_taken']) ? json_encode($data['action_taken']) : null,
            'reviewed_by_user_id' => $admin->id,
            'reviewed_at'         => now(),
            'action_taken_at'     => !empty($data['action_taken']) ? now() : null,
            'updated_at'          => now(),
        ]);

        return $this->ok(['message' => 'Moderation item updated']);
    }
}
