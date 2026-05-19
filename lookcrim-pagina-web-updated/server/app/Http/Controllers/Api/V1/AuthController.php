<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyNewEmailNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if ($user && (bool) ($user->banned ?? false)) {
            return response()->json([
                'message' => 'User is banned.',
            ], 403);
        }

        if (!$user || !Hash::check($data['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user->loadMissing(['roles', 'city']);

        $token = $user->createToken('flutter')->plainTextToken;

        $roleName = 'user';

        try {
            $roleNames = $user->roles->pluck('name');
            $roleName = $roleNames->contains('admin') ? 'admin' : ($roleNames->first() ?: 'user');
        } catch (\Throwable $e) {
            $roleName = 'user';
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,

                'city_id' => $user->city_id,
                'city_name' => $user->city?->name,
                'city_center_lat' => $user->city?->center_lat,
                'city_center_lng' => $user->city?->center_lng,
                'city_radius_m' => $user->city?->radius_m,

                'role_name' => $roleName,
            ],
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $startedAt = microtime(true);

        logger()->info('LC_API_PASSWORD_RESET_ATTEMPT', [
            'email_hash' => sha1((string) $request->input('email')),
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            logger()->error('LC_API_PASSWORD_RESET_SEND_FAILED', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'mail_mailer' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_encryption' => config('mail.mailers.smtp.encryption'),
                'duration_ms' => $durationMs,
            ]);

            report($e);

            return response()->json([
                'message' => __('passwords.send_failed'),
            ], 500);
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        logger()->info('LC_API_PASSWORD_RESET_STATUS', [
            'status' => $status,
            'duration_ms' => $durationMs,
            'email_hash' => sha1((string) $request->input('email')),
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => 'Password reset link sent successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->loadMissing(['roles', 'city']);

        $permissions = [];

        try {
            $permissions = $user->getAllPermissions()->pluck('name')->values()->all();
        } catch (\Throwable $e) {
            $permissions = [];
        }

        $roleName = 'user';

        try {
            $roleNames = $user->roles->pluck('name');
            $roleName = $roleNames->contains('admin') ? 'admin' : ($roleNames->first() ?: 'user');
        } catch (\Throwable $e) {
            $roleName = 'user';
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,

                'city_id' => $user->city_id,
                'city_name' => $user->city?->name,
                'city_center_lat' => $user->city?->center_lat,
                'city_center_lng' => $user->city?->center_lng,
                'city_radius_m' => $user->city?->radius_m,

                'role_name' => $roleName,
            ],
            'permissions' => $permissions,
        ]);
    }

    public function updateMe(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->name = $data['name'];
        $user->save();

        return $this->me($request);
    }

    public function requestEmailChange(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        if (!Hash::check($data['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The current password is incorrect.'),
            ]);
        }

        $newEmail = strtolower(trim($data['email']));

        if ($newEmail === strtolower((string) $user->email)) {
            throw ValidationException::withMessages([
                'email' => __('The new email must be different from your current email.'),
            ]);
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

        return response()->json([
            'message' => 'Verification link sent to the new email address.',
            'pending_email' => $newEmail,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The current password is incorrect.'),
            ]);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json(null, 204);
    }
}