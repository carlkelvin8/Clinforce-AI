<?php
// app/Http/Controllers/Api/UsersController.php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UsersController extends ApiController
{
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
            'avatar' => ['nullable', 'image', 'max:5120'], // 5MB max
        ]);

        if ($v->fails()) {
            return $this->fail('Validation failed', $v->errors(), 422);
        }

        // Ensure at least one contact method remains
        $newEmail = $request->has('email') ? $request->input('email') : $user->email;
        $newPhone = $request->has('phone') ? $request->input('phone') : $user->phone;

        if (!$newEmail && !$newPhone) {
            return $this->fail('You must have at least an email or a phone number.', null, 422);
        }

        // Update fields
        if ($request->has('email')) {
            $user->email = $request->input('email');
        }
        if ($request->has('phone')) {
            $user->phone = $request->input('phone');
        }
        if ($request->filled('password')) {
            $user->password_hash = Hash::make($request->input('password'));
        }

        // Handle avatar
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            
            $profile = $user->applicantProfile;
            if (!$profile) {
                $profile = $user->applicantProfile()->create([
                    'first_name' => '',
                    'last_name' => '',
                ]);
            }
            $profile->avatar = $path;
            $profile->save();
        }

        $user->save();

        $user->load('applicantProfile');
        $avatarUrl = $user->applicantProfile && $user->applicantProfile->avatar 
            ? asset('storage/' . $user->applicantProfile->avatar) 
            : null;

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
