<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'nickname',
        'institution',
        'email',
        'password',
        'admin',
        'banned',
        'role',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'admin' => 'boolean',
        'banned' => 'boolean',
        'permissions' => 'array',
    ];

    /**
     * Return permissions merged: role defaults + per-user overrides (if provided).
     */
    public function effectivePermissions(): array
    {
        $role = $this->role ?? 'user';
        $roleDefaults = config("roles.definitions.$role", config('roles.definitions.user', []));
        $custom = $this->permissions ?? [];

        // Merge: user custom overrides role defaults; ensure booleans.
        $merged = [];
        foreach ($roleDefaults as $perm => $value) {
            $merged[$perm] = (bool) ($custom[$perm] ?? $value);
        }
        // Include any extra custom flags not in role defaults
        foreach ($custom as $perm => $value) {
            if (!array_key_exists($perm, $merged)) {
                $merged[$perm] = (bool) $value;
            }
        }
        return $merged;
    }
}
