<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject(Lang::get('Reset Password Notification'))
                ->line(Lang::get('You are receiving this email because we received a password reset request for your account.'))
                ->action(Lang::get('Reset Password'), $url)
                ->line(Lang::get('This password reset link will expire in :count minutes.', [
                    'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                ]))
                ->line(Lang::get('If you did not request a password reset, no further action is required.'))
                ->withSymfonyMessage(function ($symfonyMessage) {
                    $path = public_path('img/LookCrim_final-04.png');
                    if (! is_string($path) || ! is_file($path)) {
                        return;
                    }

                    $part = new \Symfony\Component\Mime\Part\DataPart(
                        new \Symfony\Component\Mime\Part\File($path),
                        'lookcrime-logo.png',
                        'image/png'
                    );

                    $part->asInline()->setContentId('lookcrime-logo@lookcrime.local');

                    $symfonyMessage->addPart($part);
                });
        });

        // Admin bypass as super-user shortcut.
        Gate::before(function (?User $user) {
            if (!$user) {
                return null;
            }

            if ((bool) ($user->admin ?? false)) {
                return true;
            }

            try {
                if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
                    return true;
                }
            } catch (\Throwable $e) {
                // ignore
            }

            return null;
        });

        // Legacy: simple admin ability based on the `admin` column on users table.
        Gate::define('admin', function (?User $user) {
            if (!$user) {
                return false;
            }

            if ((bool) ($user->admin ?? false)) {
                return true;
            }

            try {
                return method_exists($user, 'hasRole') && $user->hasRole('admin');
            } catch (\Throwable $e) {
                return false;
            }
        });
    }
}
