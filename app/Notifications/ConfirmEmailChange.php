<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to a NEW address to prove the athlete can read it before the account switches to it. */
class ConfirmEmailChange extends Notification
{
    public function __construct(public string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your new email address')
            ->line('You asked to use this address for your '.config('app.name').' account.')
            ->action('Confirm this address', $this->url)
            ->line('The link works for 60 minutes and only while you are logged in.')
            ->line('If you did not ask for this, ignore this email. Nothing will change.');
    }
}
