<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventAttendanceConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Event $event,
    ) {}

    public function envelope(): Envelope
    {
        $name = $this->event->payload['name'] ?? 'your event';

        return new Envelope(
            subject: "You're on the list for {$name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance-confirmation',
        );
    }
}
