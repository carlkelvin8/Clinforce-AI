<?php

namespace App\Mail;

use App\Models\CredentialVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredentialExpiring extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly CredentialVerification $credential,
        public readonly string $candidateName,
        public readonly int $daysUntilExpiry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ {$this->credential->credential_type} expiring in {$this->daysUntilExpiry} days — {$this->candidateName}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.credential_expiring');
    }

    public function attachments(): array
    {
        return [];
    }
}
