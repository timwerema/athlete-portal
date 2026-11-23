<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CoachMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New message about your profile from '.$this->contactMessage->sender_name,
            replyTo: [new Address($this->contactMessage->sender_email, $this->contactMessage->sender_name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.coach-message', with: [
            'm' => $this->contactMessage,
            'inboxUrl' => route('inbox.show', ['message' => $this->contactMessage->id]),
        ]);
    }
}
