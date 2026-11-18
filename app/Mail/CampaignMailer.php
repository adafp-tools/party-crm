<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMailer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
        public readonly Member $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->campaign->subject ?? $this->campaign->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign',
        );
    }
}
