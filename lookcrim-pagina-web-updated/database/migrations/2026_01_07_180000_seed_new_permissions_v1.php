<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

return new class extends Migration {
    public function up(): void
    {
        // Ensure permission cache is cleared before seeding
        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore if registrar not bound yet during early migration
        }

        $permissions = [
            // Registers
            ['name' => 'view_all_registers', 'category' => 'registers'],
            ['name' => 'edit_all_registers', 'category' => 'registers'],
            ['name' => 'delete_registers',    'category' => 'registers'],

            // Management
            ['name' => 'view_page_management',  'category' => 'management'],
            ['name' => 'create_user',           'category' => 'management'],
            ['name' => 'edit_user',             'category' => 'management'],
            ['name' => 'ban_user',              'category' => 'management'],
            ['name' => 'send_password_reset',   'category' => 'management'],

            // Roles
            ['name' => 'view_page_settings_roles', 'category' => 'roles'],
            ['name' => 'create_role',              'category' => 'roles'],
            ['name' => 'edit_role',                'category' => 'roles'],
            ['name' => 'delete_role',              'category' => 'roles'],
        ];

        // Use configured Permission model (App\Models\Permission)
        $permissionModelClass = config('permission.models.permission');
        foreach ($permissions as $perm) {
            $permissionModelClass::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                ['category' => $perm['category']]
            );
        }
    }

    public function down(): void
    {
        $names = [
            'view_all_registers', 'edit_all_registers', 'delete_registers',
            'view_page_management', 'create_user', 'edit_user', 'ban_user', 'send_password_reset',
            'view_page_settings_roles', 'create_role', 'edit_role', 'delete_role',
        ];

        $permissionModelClass = config('permission.models.permission');
        $permissionModelClass::whereIn('name', $names)->delete();

        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
