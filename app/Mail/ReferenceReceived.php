<?php

namespace App\Mail;

use App\Models\ReferenceCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferenceReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ReferenceCheck $check,
        public readonly string $candidateName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reference received — {$this->candidateName}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reference_received');
    }

    public function attachments(): array
    {
        return [];
    }
}
