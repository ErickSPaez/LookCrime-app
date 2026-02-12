<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\LegacyHTML;
use App\Support\LegacyForm;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register legacy facades for quick compatibility with old Blade templates
        if (!class_exists('HTML')) {
            class_alias(LegacyHTML::class, 'HTML');
        }
        if (!class_exists('Form')) {
            class_alias(LegacyForm::class, 'Form');
        }
    }
}
