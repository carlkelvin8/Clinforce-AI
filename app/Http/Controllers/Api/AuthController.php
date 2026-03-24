<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use App\Services\TrialService;
use App\Rules\StrongPassword;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $v = Validator::make($request->all(), [
            'role' => ['required', Rule::in(['admin', 'employer', 'agency', 'applicant'])],

            // require at least one: email or phone
            'email' => ['nullable', 'email', 'max:190', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:30', 'required_without:email'],

            'password' => ['required', 'string', 'min:8', 'max:72', new StrongPassword()],
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
        if ($user->email) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Registered successfully. Please check your email to verify your account.',
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

        // Rate limit: 5 attempts per minute per identifier+IP
        $throttleKey = 'login:' . strtolower($identifier) . '|' . $request->ip();
        $maxAttempts = 5;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
                'errors'  => ['identifier' => ["Account temporarily locked. Try again in {$seconds}s."]],
            ], 429);
        }

        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (!$user || !Hash::check($password, (string) $user->password_hash)) {
            RateLimiter::hit($throttleKey, $decaySeconds);
            $remaining = $maxAttempts - RateLimiter::attempts($throttleKey);
            return response()->json([
                'message' => 'Invalid credentials.' . ($remaining > 0 ? " {$remaining} attempt(s) remaining." : ''),
                'errors'  => ['identifier' => ['Invalid credentials.']],
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account is disabled.'], 403);
        }

        if (in_array($user->role, ['employer', 'applicant'], true) && $user->email && !$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Please verify your email address before logging in.'], 403);
        }

        // Successful login — clear the rate limiter
        RateLimiter::clear($throttleKey);

        $user->last_login_at = now();
        $user->save();

        app(TrialService::class)->ensureActivated($user, $request);
        $user->refresh();

        $token = $user->createToken('clinforce', ['*'], now()->addMinutes(config('sanctum.expiration', 10080)))->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'data' => [
                'token' => $token,
                'user'  => $this->userPayload($user),
            ],
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email' => ['required','email','max:190'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $status = Password::broker('users')->sendResetLink(
            ['email' => $request->input('email')]
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent to your email.',
            ]);
        }

        return response()->json([
            'message' => 'We could not send a reset link to that email address.',
        ], 400);
    }

    public function resetPassword(Request $request)
    {
        $v = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password_hash' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully.',
            ]);
        }

        return response()->json([
            'message' => 'Failed to reset password. The token may be invalid or expired.',
        ], 400);
    }

    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        app(TrialService::class)->ensureActivated($user, $request);
        $user->refresh();

        return response()->json([
            'data' => [
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    public function verificationLink(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (!$user || !$user->email) {
            return response()->json(['message' => 'No email to verify.'], 400);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'data' => ['verified' => true],
            ]);
        }

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        return response()->json([
            'message' => 'Verification link generated.',
            'data' => [
                'url' => $url,
                'verified' => false,
            ],
        ]);
    }

    public function verifyEmail(Request $request, $id, $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'data' => ['verified' => true],
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Email verified successfully!',
            'data' => ['verified' => true],
        ]);
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        
        if (!$user || !$user->email) {
            return response()->json(['message' => 'No email to verify.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent successfully.',
        ]);
    }

    public function googleCompleteRegistration(Request $request)
    {
        $v = Validator::make($request->all(), [
            'data' => ['required', 'string'],
            'role' => ['required', Rule::in(['employer', 'applicant', 'agency'])],
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }

        try {
            $decoded = json_decode(base64_decode($request->input('data')), true);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid registration data.'], 422);
        }

        $email = $decoded['email'] ?? null;
        $googleId = $decoded['google_id'] ?? null;
        $avatarUrl = $decoded['avatar'] ?? null;

        if (!$email || !$googleId) {
            return response()->json(['message' => 'Invalid registration data.'], 422);
        }

        // Guard: if user already exists just log them in
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'role' => $request->input('role'),
                'email' => $email,
                'phone' => null,
                'status' => 'active',
                'password_hash' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ]);
        }

        // Save avatar for applicants
        if ($avatarUrl && $user->role === 'applicant') {
            try {
                $binary = @file_get_contents($avatarUrl);
                if ($binary !== false) {
                    $destDir = public_path('uploads/avatars');
                    if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
                    $pathPart = parse_url($avatarUrl, PHP_URL_PATH) ?: '';
                    $ext = strtolower(pathinfo($pathPart, PATHINFO_EXTENSION) ?: 'jpg');
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) $ext = 'jpg';
                    $name = 'u' . $user->id . '_google_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    file_put_contents($destDir . DIRECTORY_SEPARATOR . $name, $binary);
                    $profile = $user->applicantProfile;
                    if (!$profile) {
                        $display = trim(explode('@', $user->email)[0]);
                        $profile = $user->applicantProfile()->create([
                            'first_name' => '', 'last_name' => '', 'public_display_name' => $display,
                        ]);
                    }
                    $profile->avatar = 'uploads/avatars/' . $name;
                    $profile->save();
                }
            } catch (\Throwable $e) {}
        }

        app(TrialService::class)->ensureActivated($user, $request);
        $user->refresh();

        $token = $user->createToken('clinforce')->plainTextToken;

        return response()->json([
            'message' => 'Registration complete.',
            'data' => [
                'token' => $token,
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    public function googleRedirect(Request $request)
    {
        $redirect = $request->query('redirect');
        $role = $request->query('role');

        $state = base64_encode(json_encode(array_filter([
            'redirect' => $redirect,
            'role' => ($role && in_array($role, ['employer', 'applicant', 'agency'], true)) ? $role : null,
        ])));

        return Socialite::driver('google')->stateless()->with(['state' => $state])->redirect();
    }

    public function googleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return redirect('/login?social=error');
        }

        $email = $googleUser->getEmail();
        if (!$email) {
            return redirect('/login?social=error');
        }

        // Decode state passed through OAuth flow
        $stateRaw = $request->query('state');
        $stateData = [];
        if ($stateRaw) {
            try {
                $stateData = json_decode(base64_decode($stateRaw), true) ?? [];
            } catch (\Throwable $e) {}
        }
        $role = $stateData['role'] ?? null;
        $redirect = $stateData['redirect'] ?? null;

        $user = User::where('email', $email)->first();

        if (!$user) {
            if (!$role) {
                $tempData = base64_encode(json_encode([
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName(),
                    'avatar' => method_exists($googleUser, 'getAvatar') ? $googleUser->getAvatar() : null,
                ]));
                
                return redirect('/auth/select-role?data=' . $tempData);
            }

            $user = User::create([
                'role' => $role,
                'email' => $email,
                'phone' => null,
                'status' => 'active',
                'password_hash' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ]);
        } elseif (!$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        $avatarUrl = method_exists($googleUser, 'getAvatar') ? $googleUser->getAvatar() : null;
        if ($avatarUrl && $user->role === 'applicant') {
            try {
                $binary = @file_get_contents($avatarUrl);
                if ($binary !== false) {
                    $destDir = public_path('uploads/avatars');
                    if (!is_dir($destDir)) {
                        @mkdir($destDir, 0755, true);
                    }
                    $pathPart = parse_url($avatarUrl, PHP_URL_PATH) ?: '';
                    $ext = strtolower(pathinfo($pathPart, PATHINFO_EXTENSION) ?: 'jpg');
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                        $ext = 'jpg';
                    }
                    $name = 'u' . $user->id . '_google_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $fullPath = $destDir . DIRECTORY_SEPARATOR . $name;
                    file_put_contents($fullPath, $binary);
                    $publicPath = 'uploads/avatars/' . $name;

                    $profile = $user->applicantProfile;
                    if (!$profile) {
                        $display = trim(($user->email ?? '') !== '' ? explode('@', $user->email)[0] : 'Candidate');
                        $profile = $user->applicantProfile()->create([
                            'first_name' => '',
                            'last_name' => '',
                            'public_display_name' => $display,
                        ]);
                    }
                    $profile->avatar = $publicPath;
                    $profile->save();
                }
            } catch (\Throwable $e) {
            }
        }

        $token = $user->createToken('clinforce')->plainTextToken;

        $payload = base64_encode(json_encode([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]));

        $query = http_build_query(array_filter([
            'payload' => $payload,
            'redirect' => $redirect,
        ]));

        return redirect('/auth/social/callback' . ($query ? ('?' . $query) : ''));
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
        $avatar = $user->avatar_url;
        $name = null;

        if ($user->role === 'applicant') {
            $user->load('applicantProfile');
            $profile = $user->applicantProfile;
            if ($profile) {
                $name = trim($profile->first_name . ' ' . $profile->last_name) ?: $profile->public_display_name;
            }
        } elseif ($user->role === 'employer') {
            $user->load('employerProfile');
            $profile = $user->employerProfile;
            if ($profile) {
                $name = $profile->business_name ?: $user->name;
            }
        } elseif ($user->role === 'agency') {
            $user->load('agencyProfile');
            $profile = $user->agencyProfile;
            if ($profile) {
                $name = $profile->agency_name;
            }
        }

        $hasActiveSubscription = $user->subscription()->exists();
        $inGracePeriod = false;
        if (in_array($user->role, ['employer', 'agency'], true)) {
            $inGracePeriod = app(\App\Services\SubscriptionService::class)->isInGracePeriod($user->id);
        }
        $accountStatus = 'active';
        if ($user->status !== 'active') {
            $accountStatus = 'suspended';
        } elseif ($user->role === 'employer') {
            if ($hasActiveSubscription) {
                $accountStatus = 'subscribed';
            } elseif (!$user->email_verified_at) {
                $accountStatus = 'trial_pending';
            } elseif ($user->onTrial()) {
                $accountStatus = 'trial_active';
            } elseif ($user->hasExpiredTrial() || $user->trial_consumed) {
                $accountStatus = 'subscription_required';
            } else {
                $accountStatus = 'trial_pending';
            }
        }

        return [
            'id' => $user->id,
            'role' => $user->role,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'account_status' => $accountStatus,
            'name' => $name,
            'avatar' => $avatar,
            'avatar_url' => $avatar,
            'email_verified_at' => optional($user->email_verified_at)->toISOString(),
            'last_login_at' => optional($user->last_login_at)->toISOString(),
            'created_at' => optional($user->created_at)->toISOString(),
            'updated_at' => optional($user->updated_at)->toISOString(),
            'trial_ends_at' => optional($user->trial_ends_at)->toISOString(),
            'on_trial' => $user->onTrial(),
            'has_expired_trial' => $user->hasExpiredTrial(),
            'subscription_status' => $user->subscription_status, // From our new column or computed
            'has_active_subscription' => $hasActiveSubscription,
            'in_grace_period' => $inGracePeriod,
        ];
    }
}
