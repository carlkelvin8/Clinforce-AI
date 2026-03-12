<?php

namespace App\Services;

use App\Models\TrialIdentity;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialService
{
    public function __construct(private AuditLogger $audit)
    {
    }

    public function ensureActivated(User $user, ?Request $request = null): void
    {
        if ($user->role !== 'employer') {
            return;
        }

        if (!$user->email_verified_at) {
            return;
        }

        if ($user->trial_started_at || $user->trial_consumed) {
            return;
        }

        if (!$this->deviceHashFromRequest($request)) {
            return;
        }

        $this->activate($user, $request);
    }

    public function activate(User $user, ?Request $request = null): array
    {
        $deviceHash = $this->deviceHashFromRequest($request);
        $ip = $request?->ip();
        $ua = $request?->userAgent();

        if (!$deviceHash) {
            return ['status' => 'missing_device'];
        }

        return DB::transaction(function () use ($user, $deviceHash, $ip, $ua, $request) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($locked->trial_started_at || $locked->trial_consumed) {
                return ['status' => 'noop'];
            }

            if (!$locked->email_verified_at) {
                return ['status' => 'not_verified'];
            }

            if ($deviceHash) {
                $existing = TrialIdentity::query()
                    ->where('identity_type', 'device')
                    ->where('identity_hash', $deviceHash)
                    ->first();

                if ($existing && $existing->first_user_id && $existing->first_user_id !== $locked->id) {
                    $this->audit->log($locked, 'trial_activation_denied', 'user', $locked->id, [
                        'reason' => 'device_already_used',
                    ], $request);
                    $locked->trial_consumed = true;
                    $locked->save();
                    return ['status' => 'denied'];
                }

                if (!$existing) {
                    try {
                        TrialIdentity::query()->create([
                            'identity_type' => 'device',
                            'identity_hash' => $deviceHash,
                            'first_user_id' => $locked->id,
                            'trial_consumed_at' => now(),
                            'ip_address' => $ip,
                            'user_agent' => $ua ? substr($ua, 0, 255) : null,
                        ]);
                    } catch (QueryException $e) {
                        $this->audit->log($locked, 'trial_activation_denied', 'user', $locked->id, [
                            'reason' => 'device_race_or_duplicate',
                        ], $request);
                        $locked->trial_consumed = true;
                        $locked->save();
                        return ['status' => 'denied'];
                    }
                }
            }

            $locked->trial_started_at = now();
            $locked->trial_ends_at = now()->addDays(7);
            $locked->trial_consumed = true;
            $locked->trial_activated_ip = $ip;
            $locked->trial_activated_user_agent = $ua ? substr($ua, 0, 255) : null;
            $locked->trial_device_hash = $deviceHash;
            $locked->save();

            $this->audit->log($locked, 'trial_activated', 'user', $locked->id, [
                'trial_started_at' => $locked->trial_started_at?->toIso8601String(),
                'trial_ends_at' => $locked->trial_ends_at?->toIso8601String(),
            ], $request);

            return ['status' => 'activated'];
        }, 3);
    }

    private function deviceHashFromRequest(?Request $request): ?string
    {
        $deviceId = $request?->header('X-Device-Id');
        if (!$deviceId) {
            return null;
        }
        $deviceId = trim((string) $deviceId);
        if ($deviceId === '' || strlen($deviceId) > 200) {
            return null;
        }
        return hash('sha256', $deviceId);
    }
}
