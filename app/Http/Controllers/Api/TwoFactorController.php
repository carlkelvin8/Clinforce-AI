<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TwoFactorController extends ApiController
{
    /** GET /2fa/status */
    public function status(): JsonResponse
    {
        $u = $this->requireAuth();
        $row = DB::table('two_factor_auth')->where('user_id', $u->id)->first();
        return $this->ok([
            'enabled' => $row && $row->enabled_at !== null,
            'setup'   => $row !== null,
        ]);
    }

    /** POST /2fa/setup — generate a TOTP secret + QR URI */
    public function setup(): JsonResponse
    {
        $u = $this->requireAuth();

        // Generate a base32 secret (16 chars = 80 bits)
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        DB::table('two_factor_auth')->updateOrInsert(
            ['user_id' => $u->id],
            ['secret' => $secret, 'enabled_at' => null, 'backup_codes' => null, 'updated_at' => now(), 'created_at' => now()]
        );

        $issuer = urlencode(config('app.name', 'Clinforce'));
        $email  = urlencode($u->email);
        $otpUri = "otpauth://totp/{$issuer}:{$email}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";

        // Use Google Charts QR API (no package needed)
        $qrUrl = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($otpUri);

        return $this->ok(['secret' => $secret, 'otp_uri' => $otpUri, 'qr_code_url' => $qrUrl]);
    }

    /** POST /2fa/enable — verify code and enable */
    public function enable(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $row = DB::table('two_factor_auth')->where('user_id', $u->id)->first();
        if (!$row) return $this->fail('Run setup first', null, 400);
        if ($row->enabled_at) return $this->fail('2FA already enabled', null, 409);

        if (!$this->verifyTotp($row->secret, $request->code)) {
            return $this->fail('Invalid code', ['code' => ['Invalid or expired code']], 422);
        }

        $backupCodes = collect(range(1, 8))->map(fn() => strtoupper(Str::random(8)))->all();

        DB::table('two_factor_auth')->where('user_id', $u->id)->update([
            'enabled_at'   => now(),
            'backup_codes' => json_encode($backupCodes),
            'updated_at'   => now(),
        ]);

        return $this->ok(['backup_codes' => $backupCodes], '2FA enabled');
    }

    /** POST /2fa/disable */
    public function disable(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        $row = DB::table('two_factor_auth')->where('user_id', $u->id)->first();
        if (!$row || !$row->enabled_at) return $this->fail('2FA not enabled', null, 400);

        // Code is optional — if provided, verify it; if not, allow disable (user already confirmed via UI)
        if ($request->filled('code')) {
            $valid = $this->verifyTotp($row->secret, $request->code)
                || $this->consumeBackupCode($u->id, $row, $request->code);
            if (!$valid) return $this->fail('Invalid code', ['code' => ['Invalid code']], 422);
        }

        DB::table('two_factor_auth')->where('user_id', $u->id)->update([
            'enabled_at' => null, 'updated_at' => now(),
        ]);

        return $this->ok(null, '2FA disabled');
    }

    /** POST /auth/verify-2fa — called after login when 2FA is enabled */
    public function verify(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        $request->validate(['code' => ['required', 'string']]);

        $row = DB::table('two_factor_auth')->where('user_id', $u->id)->first();
        if (!$row || !$row->enabled_at) return $this->ok(['verified' => true]); // not enabled, pass through

        $valid = $this->verifyTotp($row->secret, $request->code)
            || $this->consumeBackupCode($u->id, $row, $request->code);

        if (!$valid) return $this->fail('Invalid 2FA code', ['code' => ['Invalid or expired']], 422);

        return $this->ok(['verified' => true]);
    }

    // ── TOTP helpers (RFC 6238, SHA1, 6 digits, 30s window) ──

    private function verifyTotp(string $secret, string $code): bool
    {
        $time = (int) floor(time() / 30);
        foreach ([-1, 0, 1] as $offset) {
            if ($this->totpCode($secret, $time + $offset) === $code) return true;
        }
        return false;
    }

    private function totpCode(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $msg = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $input): string
    {
        $map = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $input = strtoupper($input);
        $bits = '';
        foreach (str_split($input) as $c) {
            if (!isset($map[$c])) continue;
            $bits .= str_pad(decbin($map[$c]), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) $out .= chr(bindec($byte));
        }
        return $out;
    }

    private function consumeBackupCode(int $userId, object $row, string $code): bool
    {
        $codes = json_decode($row->backup_codes ?? '[]', true);
        $code  = strtoupper(trim($code));
        $idx   = array_search($code, $codes, true);
        if ($idx === false) return false;
        array_splice($codes, $idx, 1);
        DB::table('two_factor_auth')->where('user_id', $userId)->update([
            'backup_codes' => json_encode($codes), 'updated_at' => now(),
        ]);
        return true;
    }
}
