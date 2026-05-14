<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $maskedNewEmail
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your LookCrim email address was changed')
            ->greeting('Hello!')
            ->line('The email address for your LookCrim account was changed.')
            ->line('Your new email address is: ' . $this->maskedNewEmail)
            ->line('If this was you, no further action is required.')
            ->line('If you did not make this change, please reset your password immediately or contact support.');
    }
}