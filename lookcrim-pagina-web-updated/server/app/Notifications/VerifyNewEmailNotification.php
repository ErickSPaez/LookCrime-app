<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyNewEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $verificationUrl
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your new email address')
            ->greeting('Hello!')
            ->line('We received a request to change the email address for your LookCrim account.')
            ->line('Click the button below to confirm this new email address.')
            ->action('Confirm Email', $this->verificationUrl)
            ->line('This link will expire in 60 minutes.')
            ->line('If you did not request this change, you can ignore this email.');
    }
}