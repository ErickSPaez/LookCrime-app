<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
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

        // Admin bypass as super-user shortcut.
        Gate::before(function (?User $user) {
            if ($user && ($user->admin ?? false)) {
                return true;
            }
            return null;
        });

        // Legacy: simple admin ability based on the `admin` column on users table.
        Gate::define('admin', fn (?User $user) => (bool) ($user?->admin ?? false));
    }
}
