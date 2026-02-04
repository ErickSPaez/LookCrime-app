<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class SetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject(Lang::get('Set Password'))
            ->line(Lang::get('An administrator created an account for you.'))
            ->line(Lang::get('Click the button below to set your password and access the platform.'))
            ->action(Lang::get('Set Password'), $url)
            ->line(Lang::get('This link will expire in :count minutes.', [
                'count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire'),
            ]))
            ->line(Lang::get('If you did not expect this email, you can ignore it.'));
    }
}
