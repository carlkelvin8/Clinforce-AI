<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly JobApplication $application,
        public readonly string $fromStatus,
        public readonly string $toStatus,
    ) {}

    public function envelope(): Envelope
    {
        $jobTitle = $this->application->job?->title ?? 'a position';
        return new Envelope(subject: "Your application for \"{$jobTitle}\" has been updated");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.application_status_updated');
    }

    public function attachments(): array { return []; }
}
