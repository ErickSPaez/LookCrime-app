<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $startedAt = microtime(true);
        logger()->info('LC_PASSWORD_RESET_ATTEMPT', [
            'email_hash' => sha1((string) $request->input('email')),
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            logger()->error('LC_PASSWORD_RESET_SEND_FAILED', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'mail_mailer' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_encryption' => config('mail.mailers.smtp.encryption'),
                'duration_ms' => $durationMs,
            ]);
            report($e);

            // Debug-only: allow re-throwing the exception so staging can show the 500 page
            // and Cloud Run request logs clearly reflect the failure.
            if (filter_var(env('LC_PASSWORD_RESET_DEBUG_THROW', false), FILTER_VALIDATE_BOOLEAN)) {
                throw $e;
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('passwords.send_failed')]);
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        logger()->info('LC_PASSWORD_RESET_STATUS', [
            'status' => $status,
            'duration_ms' => $durationMs,
            'email_hash' => sha1((string) $request->input('email')),
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            logger()->warning('LC_PASSWORD_RESET_NOT_SENT', [
                'status' => $status,
                'email_hash' => sha1((string) $request->input('email')),
            ]);
        }

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                            ->withErrors(['email' => __($status)]);
    }
}
