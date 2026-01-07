<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = config('permission.table_names');
        $rolesTable = $tables['roles'] ?? 'roles';
        $permissionsTable = $tables['permissions'] ?? 'permissions';
        $roleHasPermissions = $tables['role_has_permissions'] ?? 'role_has_permissions';
        $modelHasRoles = $tables['model_has_roles'] ?? 'model_has_roles';
        $modelHasPermissions = $tables['model_has_permissions'] ?? 'model_has_permissions';

        if (!Schema::hasTable($rolesTable) || !Schema::hasTable($permissionsTable)) {
            return;
        }

        $guard = 'web';

        // Minimal permissions set (you can expand later like the screenshot)
        $permissionCatalog = [
            ['name' => 'manage_publications', 'category' => 'Publications'],
            ['name' => 'manage_projects', 'category' => 'Projects'],
            ['name' => 'manage_users', 'category' => 'Users'],
            ['name' => 'manage_settings', 'category' => 'Settings'],
        ];

        foreach ($permissionCatalog as $p) {
            $exists = DB::table($permissionsTable)
                ->where('name', $p['name'])
                ->where('guard_name', $guard)
                ->exists();

            if (!$exists) {
                DB::table($permissionsTable)->insert([
                    'name' => $p['name'],
                    'guard_name' => $guard,
                    'category' => $p['category'] ?? 'General',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $permissionsByName = DB::table($permissionsTable)
            ->where('guard_name', $guard)
            ->pluck('id', 'name')
            ->all();

        // Seed roles from existing config roles.php if present; otherwise create a safe default set.
        $roleDefs = config('roles.definitions', []);
        if (empty($roleDefs)) {
            $roleDefs = [
                'user' => [],
                'programador' => ['manage_publications' => true, 'manage_projects' => true, 'manage_settings' => true],
                'admin' => ['manage_publications' => true, 'manage_projects' => true, 'manage_users' => true, 'manage_settings' => true],
            ];
        }

        foreach ($roleDefs as $roleName => $defs) {
            $exists = DB::table($rolesTable)
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->exists();

            if (!$exists) {
                DB::table($rolesTable)->insert([
                    'name' => $roleName,
                    'guard_name' => $guard,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $roleId = DB::table($rolesTable)
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->value('id');

            // Assign permissions to role (only true values)
            foreach ($defs as $permName => $allowed) {
                if (!$allowed) {
                    continue;
                }
                $permId = $permissionsByName[$permName] ?? null;
                if (!$permId) {
                    continue;
                }

                // Postgres upsert-like insert
                DB::statement(
                    "INSERT INTO {$roleHasPermissions} (permission_id, role_id) VALUES (?, ?) ON CONFLICT DO NOTHING",
                    [$permId, $roleId]
                );
            }
        }

        $rolesByName = DB::table($rolesTable)
            ->where('guard_name', $guard)
            ->pluck('id', 'name')
            ->all();

        // Migrate existing users: assign role based on legacy users.role column if present.
        if (Schema::hasTable('users')) {
            $select = ['id'];
            $hasRoleColumn = Schema::hasColumn('users', 'role');
            $hasAdminColumn = Schema::hasColumn('users', 'admin');
            $hasPermissionsColumn = Schema::hasColumn('users', 'permissions');

            if ($hasRoleColumn) {
                $select[] = 'role';
            }
            if ($hasAdminColumn) {
                $select[] = 'admin';
            }
            if ($hasPermissionsColumn) {
                $select[] = 'permissions';
            }

            $users = DB::table('users')->select($select)->orderBy('id')->get();

            foreach ($users as $u) {
                $legacyRole = $hasRoleColumn ? ($u->role ?? null) : null;
                $roleName = (is_string($legacyRole) && strlen($legacyRole) > 0) ? $legacyRole : 'user';

                // Ensure role exists
                if (!isset($rolesByName[$roleName])) {
                    DB::table($rolesTable)->insert([
                        'name' => $roleName,
                        'guard_name' => $guard,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $rolesByName[$roleName] = DB::table($rolesTable)->where('name', $roleName)->where('guard_name', $guard)->value('id');
                }

                // Admin shortcut: also assign admin role (optional)
                $legacyAdmin = $hasAdminColumn ? (bool) ($u->admin ?? false) : false;
                if ($legacyAdmin && isset($rolesByName['admin'])) {
                    DB::statement(
                        "INSERT INTO {$modelHasRoles} (role_id, model_type, model_id) VALUES (?, ?, ?) ON CONFLICT DO NOTHING",
                        [$rolesByName['admin'], 'App\\Models\\User', $u->id]
                    );
                }

                DB::statement(
                    "INSERT INTO {$modelHasRoles} (role_id, model_type, model_id) VALUES (?, ?, ?) ON CONFLICT DO NOTHING",
                    [$rolesByName[$roleName], 'App\\Models\\User', $u->id]
                );

                // If legacy per-user permissions JSON exists, grant true permissions directly.
                $legacyPerms = null;
                if ($hasPermissionsColumn && !empty($u->permissions)) {
                    $legacyPerms = is_string($u->permissions) ? json_decode($u->permissions, true) : $u->permissions;
                }
                if (is_array($legacyPerms)) {
                    foreach ($legacyPerms as $permName => $allowed) {
                        if (!$allowed) {
                            continue;
                        }
                        $permId = $permissionsByName[$permName] ?? null;
                        if (!$permId) {
                            continue;
                        }
                        DB::statement(
                            "INSERT INTO {$modelHasPermissions} (permission_id, model_type, model_id) VALUES (?, ?, ?) ON CONFLICT DO NOTHING",
                            [$permId, 'App\\Models\\User', $u->id]
                        );
                    }
                }
            }
        }

        // Clear Spatie cache
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        // Intentionally no-op: data migration.
    }
};
