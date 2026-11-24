<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the OLD address after a change, so a takeover cannot happen silently. */
class EmailChanged extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your account email was changed')
            ->line('The email address on your '.config('app.name').' account was just changed, so this address is no longer used to log in.')
            ->line('If you made this change, you do not need to do anything.')
            ->line('If you did not, someone else may have access to your account. Reply to this email so we can help.');
    }
}
