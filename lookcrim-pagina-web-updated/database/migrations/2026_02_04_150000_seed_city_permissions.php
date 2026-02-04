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

        $permissions = [
            ['name' => 'view_page_settings_city', 'category' => 'cities'],
            ['name' => 'view_any_city', 'category' => 'cities'],
            ['name' => 'create_city', 'category' => 'cities'],
            ['name' => 'edit_city', 'category' => 'cities'],
            ['name' => 'delete_city', 'category' => 'cities'],

            // Registers (cross-city)
            ['name' => 'view_any_city_registers', 'category' => 'registers'],
            ['name' => 'create_any_city_registers', 'category' => 'registers'],
            ['name' => 'edit_any_city_registers', 'category' => 'registers'],
            ['name' => 'delete_any_city_registers', 'category' => 'registers'],
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
        $permissionModelClass = config('permission.models.permission');
        $permissionModelClass::whereIn('name', [
            'view_page_settings_city',
            'view_any_city',
            'create_city',
            'edit_city',
            'delete_city',

            'view_any_city_registers',
            'create_any_city_registers',
            'edit_any_city_registers',
            'delete_any_city_registers',
        ])->delete();

        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
