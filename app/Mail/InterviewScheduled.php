<?php

namespace App\Mail;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewScheduled extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Interview $interview) {}

    public function envelope(): Envelope
    {
        $jobTitle = $this->interview->application?->job?->title ?? 'a position';
        return new Envelope(subject: "Interview scheduled for \"{$jobTitle}\"");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.interview_scheduled');
    }

    public function attachments(): array { return []; }
}
