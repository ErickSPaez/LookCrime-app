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

        $permissions = [
            ['name' => 'view_own_registers', 'category' => 'registers'],
            ['name' => 'create_registers', 'category' => 'registers'],
            ['name' => 'edit_own_registers', 'category' => 'registers'],
        ];

        $permissionModelClass = config('permission.models.permission');
        foreach ($permissions as $perm) {
            $permissionModelClass::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                ['category' => $perm['category']]
            );
        }

        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        $names = ['view_own_registers', 'create_registers', 'edit_own_registers'];

        $permissionModelClass = config('permission.models.permission');
        $toDelete = $permissionModelClass::whereIn('name', $names)->get(['id']);

        foreach ($toDelete as $perm) {
            try {
                DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $perm->id)->delete();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $permissionModelClass::whereIn('name', $names)->delete();

        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
