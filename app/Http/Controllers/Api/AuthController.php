<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $v = Validator::make($request->all(), [
            'role' => ['required', Rule::in(['admin', 'employer', 'agency', 'applicant'])],

            // require at least one: email or phone
            'email' => ['nullable', 'email', 'max:190', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:30', 'required_without:email'],

            'password' => ['required', 'string', 'min:8', 'max:72'],
        ], [
            'email.required_without' => 'Email or phone is required.',
            'phone.required_without' => 'Email or phone is required.',
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $email = $request->input('email');
        $phone = $request->input('phone');

        if ($email && User::where('email', $email)->exists()) {
            return response()->json(['message' => 'Email already exists.'], 409);
        }
        if ($phone && User::where('phone', $phone)->exists()) {
            return response()->json(['message' => 'Phone already exists.'], 409);
        }

        $user = User::create([
            'role'          => $request->input('role'),
            'email'         => $email,
            'phone'         => $phone,
            'status'        => 'active',
            'password_hash' => Hash::make($request->input('password')),
        ]);

        $token = $user->createToken('clinforce-ai')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully.',
            'data' => [
                'token' => $token,
                'user'  => $this->userPayload($user),
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $v = Validator::make($request->all(), [
            'identifier' => ['required', 'string', 'max:190'], // email or phone
            'password'   => ['required', 'string', 'max:72'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $identifier = trim((string) $request->input('identifier'));
        $password   = (string) $request->input('password');

        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account is disabled.'], 403);
        }

        if (!Hash::check($password, (string) $user->password_hash)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user->last_login_at = now();
        $user->save();

        // Optional: revoke previous tokens to enforce single-session login
        // $user->tokens()->delete();

        $token = $user->createToken('clinforce')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'data' => [
                'token' => $token,
                'user'  => $this->userPayload($user),
            ],
        ]);
    }

    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    private function userPayload(User $user): array
    {
        $avatar = null;
        $name = null;

        if ($user->role === 'applicant') {
            $user->load('applicantProfile');
            $profile = $user->applicantProfile;
            if ($profile) {
                $avatar = $profile->avatar ? asset('storage/' . $profile->avatar) : null;
                $name = trim($profile->first_name . ' ' . $profile->last_name) ?: $profile->public_display_name;
            }
        } elseif ($user->role === 'employer') {
            $user->load('employerProfile');
            $profile = $user->employerProfile;
            if ($profile) {
                $avatar = asset('assets/brand/default-hospital-logo.svg');
                $name = $profile->business_name ?: $user->name;
            }
        } elseif ($user->role === 'agency') {
            $user->load('agencyProfile');
            $profile = $user->agencyProfile;
            if ($profile) {
                $avatar = $profile->logo ? asset('storage/' . $profile->logo) : null;
                $name = $profile->agency_name;
            }
        }

        return [
            'id' => $user->id,
            'role' => $user->role,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'name' => $name,
            'avatar' => $avatar,
            'avatar_url' => $avatar,
            'email_verified_at' => optional($user->email_verified_at)->toISOString(),
            'last_login_at' => optional($user->last_login_at)->toISOString(),
            'created_at' => optional($user->created_at)->toISOString(),
            'updated_at' => optional($user->updated_at)->toISOString(),
        ];
    }
}
