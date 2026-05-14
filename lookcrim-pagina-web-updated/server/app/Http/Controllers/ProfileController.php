<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Notifications\EmailChangedNotification;
use App\Notifications\VerifyNewEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile name.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Request an email change.
     */
    public function requestEmailChange(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validateWithBag('emailChange', [
            'current_password' => ['required', 'current_password'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $newEmail = strtolower(trim($data['email']));

        if ($newEmail === strtolower((string) $user->email)) {
            return Redirect::route('profile.edit')
                ->with('status', 'email-change-same');
        }

        $plainToken = Str::random(64);

        $user->pending_email = $newEmail;
        $user->email_change_token = hash('sha256', $plainToken);
        $user->email_change_expires_at = now()->addMinutes(60);
        $user->save();

        $verificationUrl = route('profile.email-change.verify', [
            'token' => $plainToken,
        ]);

        Notification::route('mail', $newEmail)
            ->notify(new VerifyNewEmailNotification($verificationUrl));

        return Redirect::route('profile.edit')->with('status', 'email-change-sent');
    }

    /**
     * Verify the requested email change.
     */
public function verifyEmailChange(string $token): RedirectResponse
{
    $hashedToken = hash('sha256', $token);

    /** @var User|null $user */
    $user = User::query()
        ->where('email_change_token', $hashedToken)
        ->whereNotNull('pending_email')
        ->where('email_change_expires_at', '>', now())
        ->first();

    if (!$user) {
        return Redirect::route('login')->with('status', 'email-change-invalid');
    }

    $oldEmail = $user->email;
    $newEmail = $user->pending_email;

    $user->email = $newEmail;
    $user->pending_email = null;
    $user->email_change_token = null;
    $user->email_change_expires_at = null;
    $user->email_verified_at = now();
    $user->save();

    if ($oldEmail && $oldEmail !== $newEmail) {
        try {
            Notification::route('mail', $oldEmail)
                ->notify(new EmailChangedNotification($this->maskEmail((string) $newEmail)));
        } catch (\Throwable $e) {
            // Do not block the email change if the notification to the old email fails.
        }
    }

    return Redirect::route('login')->with('status', 'email-change-confirmed');
}

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return $email;
        }

        [$name, $domain] = $parts;

        if (strlen($name) <= 2) {
            return substr($name, 0, 1) . '***@' . $domain;
        }

        return substr($name, 0, 1)
            . str_repeat('*', max(strlen($name) - 2, 3))
            . substr($name, -1)
            . '@'
            . $domain;
    }
}