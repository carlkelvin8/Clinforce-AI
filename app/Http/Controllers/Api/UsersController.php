<?php
// app/Http/Controllers/Api/UsersController.php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UsersController extends ApiController
{
    public function sessions(Request $request): JsonResponse
    {
        $user = $this->requireAuth();
        $currentTokenId = $user->currentAccessToken()?->id;

        $sessions = $user->tokens()
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn ($t) => [
                'id'           => $t->id,
                'name'         => $t->name,
                'last_used_at' => optional($t->last_used_at)->toISOString(),
                'created_at'   => optional($t->created_at)->toISOString(),
                'is_current'   => $t->id === $currentTokenId,
            ]);

        return $this->ok($sessions);
    }

    public function revokeSession(Request $request, int $tokenId): JsonResponse
    {
        $user = $this->requireAuth();
        $deleted = $user->tokens()->where('id', $tokenId)->delete();
        if (!$deleted) return $this->fail('Session not found', null, 404);
        return $this->ok(['message' => 'Session revoked.']);
    }

    public function revokeAllSessions(Request $request): JsonResponse
    {
        $user = $this->requireAuth();
        $currentId = $user->currentAccessToken()?->id;
        // Revoke all except current
        $user->tokens()->where('id', '!=', $currentId)->delete();
        return $this->ok(['message' => 'All other sessions revoked.']);
    }

    public function loginHistory(Request $request): JsonResponse
    {
        $user = $this->requireAuth();
        $history = \DB::table('login_locations')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
        return $this->ok($history);
    }

    public function gdprExport(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $this->requireAuth();
        $user->load(['applicantProfile', 'employerProfile', 'agencyProfile']);

        $applications = \App\Models\JobApplication::query()
            ->where('applicant_user_id', $user->id)
            ->with('job:id,title,city,country')
            ->get(['id', 'job_id', 'status', 'cover_letter', 'submitted_at', 'created_at']);

        $conversations = \App\Models\ConversationParticipant::query()
            ->where('user_id', $user->id)
            ->with(['conversation.messages' => fn($q) => $q->where('sender_user_id', $user->id)->select('id', 'conversation_id', 'body', 'created_at')])
            ->get();

        $documents = \App\Models\Document::query()
            ->where('user_id', $user->id)
            ->get(['id', 'doc_type', 'file_name', 'created_at']);

        $export = [
            'exported_at'   => now()->toIso8601String(),
            'user'          => [
                'id'         => $user->id,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'role'       => $user->role,
                'created_at' => optional($user->created_at)->toIso8601String(),
            ],
            'profile'       => $user->applicantProfile?->toArray() ?? $user->employerProfile?->toArray() ?? [],
            'applications'  => $applications->toArray(),
            'messages_sent' => $conversations->flatMap(fn($p) => $p->conversation?->messages ?? [])->values()->toArray(),
            'documents'     => $documents->toArray(),
        ];

        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response()->stream(function () use ($json) {
            echo $json;
        }, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="clinforce-data-export-' . $user->id . '.json"',
        ]);
    }

    public function requestDeletion(Request $request): JsonResponse
    {
        $user = $this->requireAuth();

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        // Schedule deletion 30 days from now — store in cache
        $key = "account_deletion:{$user->id}";
        \Illuminate\Support\Facades\Cache::put($key, [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'reason'     => $data['reason'] ?? null,
            'requested_at' => now()->toIso8601String(),
            'delete_at'  => now()->addDays(30)->toIso8601String(),
        ], now()->addDays(31));

        // Email confirmation
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Hi,\n\nWe received a request to delete your ClinForce account.\n\n" .
                "Your account will be permanently deleted on " . now()->addDays(30)->format('F j, Y') . ".\n\n" .
                "If you change your mind, log in before that date and cancel the deletion from your settings.\n\n" .
                "— ClinForce Team",
                fn ($m) => $m->to($user->email)->subject('Account deletion scheduled — ClinForce')
            );
        } catch (\Throwable $e) {}

        return $this->ok([
            'message'   => 'Account deletion scheduled.',
            'delete_at' => now()->addDays(30)->toIso8601String(),
        ]);
    }

    public function cancelDeletion(): JsonResponse
    {
        $user = $this->requireAuth();
        \Illuminate\Support\Facades\Cache::forget("account_deletion:{$user->id}");
        return $this->ok(['message' => 'Account deletion cancelled.']);
    }

    public function deletionStatus(): JsonResponse
    {
        $user = $this->requireAuth();
        $pending = \Illuminate\Support\Facades\Cache::get("account_deletion:{$user->id}");
        return $this->ok(['pending' => $pending]);
    }

    public function index(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        // Only staff can search users (prevents applicants enumerating users)
        if (!in_array($u->role, ['admin', 'employer', 'agency'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $role = $request->query('role'); // admin|employer|agency|applicant|null
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 20);
        if ($limit < 1) $limit = 20;
        if ($limit > 50) $limit = 50;

        $query = User::query()
            ->select(['id', 'role', 'email', 'phone', 'status', 'created_at', 'updated_at'])
            ->where('status', 'active');

        if ($role && in_array($role, ['admin', 'employer', 'agency', 'applicant'], true)) {
            $query->where('role', $role);
        }

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                if (ctype_digit($q)) {
                    $qq->orWhere('id', (int) $q);
                }
                $qq->orWhere('email', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $rows = $query
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($r) {
                $display = $r->email ?: ($r->phone ?: ('User #' . $r->id));
                return [
                    'id' => (int) $r->id,
                    'role' => (string) $r->role,
                    'email' => $r->email,
                    'phone' => $r->phone,
                    'display_name' => $display, // frontend label
                ];
            })
            ->values();

        return $this->ok($rows);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $this->requireAuth();

        $v = Validator::make($request->all(), [
            'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:72', 'confirmed'],
            'avatar' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp'], // 5MB max
        ]);

        if ($v->fails()) {
            return $this->fail('Validation failed', $v->errors(), 422);
        }

        if (config('app.env') !== 'local' && $user->email && !$user->hasVerifiedEmail()) {
            if ($request->has('email') || $request->filled('password')) {
                return $this->fail('Please verify your email address before changing email or password.', null, 403);
            }
        }

        // Ensure at least one contact method remains
        $newEmail = $request->has('email') ? $request->input('email') : $user->email;
        $newPhone = $request->has('phone') ? $request->input('phone') : $user->phone;

        if (!$newEmail && !$newPhone) {
            if (!$request->hasFile('avatar')) {
                return $this->fail('You must have at least an email or a phone number.', null, 422);
            }
        }

        // Update fields
        $passwordChanged = false;
        if ($request->has('email')) {
            $newEmail = $request->input('email');
            if ($newEmail !== $user->email) {
                $user->email = $newEmail;
                $user->email_verified_at = null;
            }
        }
        if ($request->has('phone')) {
            $user->phone = $request->input('phone');
        }
        if ($request->filled('password')) {
            $user->password_hash = Hash::make($request->input('password'));
            $passwordChanged = true;
        }

        // Handle avatar
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            // Store directly under public/uploads/avatars to avoid storage symlink issues
            $destDir = public_path('uploads/avatars');
            if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $name = 'u' . $user->id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $file->move($destDir, $name);
            $publicPath = 'uploads/avatars/' . $name; // relative to public/
            
            $profile = $user->applicantProfile;
            if (!$profile) {
                $display = trim(($user->email ?? '') !== '' ? explode('@', $user->email)[0] : 'Candidate');
                $profile = $user->applicantProfile()->create([
                    'first_name' => '',
                    'last_name' => '',
                    'public_display_name' => $display,
                ]);
            }
            $profile->avatar = $publicPath; // store public-relative path
            $profile->save();
        }

        $user->save();

        if ($user->email && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        if ($passwordChanged && $user->email) {
            Mail::raw(
                'Your password on Clinforce has just been changed. If this was not you, please reset your password immediately or contact support.',
                function ($m) use ($user) {
                    $m->to($user->email)
                      ->subject('Your password was changed');
                }
            );
        }

        $user->load('applicantProfile');
        $avatarUrl = $user->avatar_url;

        return $this->ok([
            'message' => 'Settings updated successfully.',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'avatar' => $avatarUrl,
            ]
        ]);
    }
}
