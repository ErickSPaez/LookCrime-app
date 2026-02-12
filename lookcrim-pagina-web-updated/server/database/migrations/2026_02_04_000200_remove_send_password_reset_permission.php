<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }

        $permissionModelClass = config('permission.models.permission');

        $perm = $permissionModelClass::where('name', 'send_password_reset')
            ->where('guard_name', 'web')
            ->first();

        if ($perm) {
            try {
                DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $perm->id)->delete();
            } catch (\Throwable $e) {
                // ignore
            }

            $perm->delete();
        }

        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // No-op. Feature removed intentionally.
    }
};
