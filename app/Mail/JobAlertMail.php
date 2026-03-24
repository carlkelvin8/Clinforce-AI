<?php

namespace App\Mail;

use App\Models\Job;
use App\Models\JobAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Job $job,
        public readonly JobAlert $alert
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New job match: ' . $this->job->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.job_alert');
    }
}
