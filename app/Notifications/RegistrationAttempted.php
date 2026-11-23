<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when somebody tries to sign up with an email that already has an
 * account. The sign-up screen itself gives nothing away, so nobody can use it
 * to find out whether a particular athlete has an account.
 */
class RegistrationAttempted extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You already have an account')
            ->line('Someone, probably you, just tried to create an account with this email address. You already have one.')
            ->action('Log in', route('login'))
            ->line('If you have forgotten your password you can reset it from the log-in page.')
            ->line('If this was not you, you do not need to do anything. Nobody got into your account.');
    }
}
