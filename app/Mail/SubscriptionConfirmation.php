<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Subscription $subscription) {}

    public function envelope(): Envelope
    {
        $planName = $this->subscription->plan?->name ?? 'your plan';
        return new Envelope(subject: "Subscription confirmed — {$planName}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription_confirmation');
    }

    public function attachments(): array { return []; }
}
