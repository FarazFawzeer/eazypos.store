<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\StaffInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StaffInvitation $invitation,
        public Business $business,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You have been invited to '.$this->business->name.' on EazyPOS');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.staff-invitation');
    }
}
