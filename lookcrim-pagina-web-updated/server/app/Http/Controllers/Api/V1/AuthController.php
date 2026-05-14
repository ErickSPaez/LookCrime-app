<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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