<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

return new class extends Migration {
    public function up(): void
    {
        $keep = [
            'view_all_registers', 'edit_all_registers', 'delete_registers',
            'view_page_management', 'create_user', 'edit_user', 'ban_user', 'send_password_reset',
            'view_page_settings_roles', 'create_role', 'edit_role', 'delete_role',
        ];

        $permissionModelClass = config('permission.models.permission');
        $toDelete = $permissionModelClass::whereNotIn('name', $keep)->get(['id','name']);

        // Detach from roles/models if needed (Spatie usually cascades, but be safe)
        if (class_exists(Spatie\Permission\Models\Role::class)) {
            foreach ($toDelete as $perm) {
                try {
                    \DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
                    \DB::table('model_has_permissions')->where('permission_id', $perm->id)->delete();
                } catch (\Throwable $e) {}
            }
        }

        $permissionModelClass::whereIn('id', $toDelete->pluck('id'))->delete();

        try {
            App::make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        // No-op (can't restore deleted custom permissions reliably)
    }
};
