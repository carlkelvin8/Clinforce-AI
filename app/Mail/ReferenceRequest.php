<?php

namespace App\Mail;

use App\Models\ReferenceCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferenceRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ReferenceCheck $check,
        public readonly string $candidateName,
        public readonly string $jobTitle,
        public readonly string $respondUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reference request for {$this->candidateName} — {$this->jobTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reference_request');
    }

    public function attachments(): array
    {
        return [];
    }
}
