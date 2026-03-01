<?php

namespace App\Console\Commands;

use App\Models\DocumentAccessPayment;
use Illuminate\Console\Command;

class CheckDocumentAccess extends Command
{
    protected $signature = 'check:doc-access {employer_id} {applicant_id}';
    protected $description = 'Check if employer has document access for applicant';

    public function handle()
    {
        $employerId = $this->argument('employer_id');
        $applicantId = $this->argument('applicant_id');

        $this->info("Checking document access:");
        $this->info("Employer ID: {$employerId}");
        $this->info("Applicant ID: {$applicantId}");

        $payments = DocumentAccessPayment::where('employer_user_id', $employerId)
            ->where('applicant_user_id', $applicantId)
            ->get();

        $this->info("\nPayments found: " . $payments->count());

        foreach ($payments as $payment) {
            $this->line("- Payment #{$payment->id}: Status: {$payment->status} | Amount: {$payment->amount_cents} {$payment->currency_code} | Paid: " . ($payment->paid_at ?? 'NULL'));
        }

        $hasAccess = DocumentAccessPayment::hasAccess($employerId, $applicantId);
        $this->info("\nHas Access: " . ($hasAccess ? 'YES' : 'NO'));

        return 0;
    }
}
