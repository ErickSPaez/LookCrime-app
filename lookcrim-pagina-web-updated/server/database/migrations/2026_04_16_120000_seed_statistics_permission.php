<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

return new class extends Migration {
    public function up(): void
    {
        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }

        $permissionModelClass = config('permission.models.permission');
        $permissionModelClass::firstOrCreate(
            ['name' => 'view_page_statistics', 'guard_name' => 'web'],
            ['category' => 'statistics']
        );

        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        $permissionModelClass = config('permission.models.permission');
        $permissionModelClass::where('name', 'view_page_statistics')->delete();

        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
