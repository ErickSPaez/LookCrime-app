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

        try {
            $roleModelClass = config('permission.models.role');
            $permissionModelClass = config('permission.models.permission');

            $adminRole = $roleModelClass::where('name', 'admin')
                ->where('guard_name', 'web')
                ->first();

            if (!$adminRole) {
                return;
            }

            $allPermNames = $permissionModelClass::where('guard_name', 'web')
                ->pluck('name')
                ->all();

            $adminRole->syncPermissions($allPermNames);
        } finally {
            try {
                App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        // Intentionally no-op: do not remove permissions from admin.
        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
