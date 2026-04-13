<?php

namespace App\Console\Commands;

use App\Mail\CredentialExpiring;
use App\Models\CredentialVerification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckCredentialExpiry extends Command
{
    protected $signature   = 'credentials:check-expiry';
    protected $description = 'Check for expiring/expired credentials and send notifications';

    public function handle(): int
    {
        $now = now();
        $notified = 0;

        // 1. Mark already-expired credentials
        $expired = CredentialVerification::where('status', 'verified')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $now)
            ->get();

        foreach ($expired as $cred) {
            $cred->markExpired();
            $this->info("Marked expired: {$cred->credential_type} ({$cred->license_number}) for user #{$cred->applicant_user_id}");
            $notified++;
        }

        // 2. Send expiry warnings (30 days out)
        $expiringSoon = CredentialVerification::expiringSoon(30)
            ->with(['applicant'])
            ->get();

        foreach ($expiringSoon as $cred) {
            $daysLeft = (int) $now->diffInDays($cred->expiry_date, false);
            $candidateName = $cred->applicant?->name ?? 'a candidate';

            try {
                // Notify the candidate
                if ($cred->applicant?->email) {
                    Mail::to($cred->applicant->email)
                        ->send(new CredentialExpiring($cred, $candidateName, $daysLeft));
                    $this->info("Sent expiry warning to {$cred->applicant->email} ({$daysLeft} days left)");
                }

                // Notify employers who have this candidate's applications
                $applications = $cred->applicant?->applications ?? collect();
                foreach ($applications as $app) {
                    $employerEmail = $app->job?->owner?->email;
                    if ($employerEmail) {
                        Mail::raw(
                            "Alert: {$candidateName}'s {$cred->credential_type} " .
                            "(License: {$cred->license_number}) expires in {$daysLeft} days.\n\n" .
                            "View details in your ClinForce dashboard.",
                            fn($m) => $m->to($employerEmail)
                                ->subject("⚠️ Candidate credential expiring — {$candidateName}")
                        );
                    }
                }

                $notified++;
            } catch (\Throwable $e) {
                $this->warn("Failed to send notification for credential #{$cred->id}: {$e->getMessage()}");
            }
        }

        // 3. Expired credentials check — weekly recheck
        // For credentials that expired but might have been renewed
        $staleExpired = CredentialVerification::where('status', 'expired')
            ->where('last_checked_at', '<', now()->subDays(7))
            ->orWhereNull('last_checked_at')
            ->limit(50)
            ->get();

        foreach ($staleExpired as $cred) {
            $cred->update(['last_checked_at' => now()]);
        }

        $this->info("Credential expiry check complete. Processed {$notified} credentials.");
        return self::SUCCESS;
    }
}
