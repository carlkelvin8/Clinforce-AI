<?php

namespace App\Mail;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationReviewed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly VerificationRequest $verificationRequest) {}

    public function envelope(): Envelope
    {
        $status = $this->verificationRequest->status === 'approved' ? 'Approved' : 'Rejected';
        return new Envelope(subject: "Verification request {$status}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verification_reviewed');
    }

    public function attachments(): array { return []; }
}
