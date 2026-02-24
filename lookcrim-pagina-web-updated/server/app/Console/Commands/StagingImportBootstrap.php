<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StagingImportBootstrap extends Command
{
    protected $signature = 'staging:import-bootstrap
        {--source= : Conexion fuente (por defecto: la default)}
        {--target=staging : Conexion destino (por defecto: staging)}
        {--user-ids= : IDs de usuarios a copiar (CSV)}
        {--user-emails= : Emails de usuarios a copiar (CSV)}
        {--city-ids= : IDs de ciudades a copiar (CSV)}
        {--city-slugs= : Slugs de ciudades a copiar (CSV)}
        {--auto-pick : Elige 1 admin + 3 usuarios automaticamente}
        {--dry-run : No escribe nada (por defecto)}
        {--force : Ejecuta escritura real (desactiva dry-run)}';

    protected $description = 'Copia roles/permisos (Spatie), 3 ciudades y 3 usuarios + admin desde PostGIS local hacia Supabase (conexion staging).';

    public function handle(): int
    {
        $source = (string) ($this->option('source') ?: config('database.default'));
        $target = (string) ($this->option('target') ?: 'staging');

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        if ($force) {
            $dryRun = false;
        }
        if (!$force && !$dryRun) {
            $dryRun = true;
        }

        $this->line('Fuente: ['.$source.']');
        $this->line('Destino: ['.$target.']');
        $this->line('Modo: '.($dryRun ? 'DRY-RUN (no escribe)' : 'WRITE (escribe)'));

        if (!Schema::connection($source)->hasTable('users')) {
            $this->error("No existe la tabla users en la conexion fuente [$source].");
            return self::FAILURE;
        }
        if (!Schema::connection($source)->hasTable('cities')) {
            $this->error("No existe la tabla cities en la conexion fuente [$source].");
            return self::FAILURE;
        }
        if (!Schema::connection($target)->hasTable('users') || !Schema::connection($target)->hasTable('cities')) {
            $this->error("En el destino [$target] faltan tablas (users/cities). Corre migraciones primero.");
            return self::FAILURE;
        }

        $usersToCopy = $this->resolveUsersToCopy($source);
        if (empty($usersToCopy)) {
            $this->error('No se seleccionaron usuarios. Usa --user-ids, --user-emails o --auto-pick.');
            return self::FAILURE;
        }

        $citiesToCopy = $this->resolveCitiesToCopy($source);
        if (empty($citiesToCopy)) {
            $this->error('No se seleccionaron ciudades. Usa --city-ids o --city-slugs.');
            return self::FAILURE;
        }

        if (count($usersToCopy) !== 4) {
            $this->warn('Aviso: seleccionaste '.count($usersToCopy).' usuarios (esperado: 4).');
        }
        if (count($citiesToCopy) !== 3) {
            $this->warn('Aviso: seleccionaste '.count($citiesToCopy).' ciudades (esperado: 3).');
        }

        $permissionsConfigLoaded = config('permission.table_names');
        $spatieOk = is_array($permissionsConfigLoaded) && !empty($permissionsConfigLoaded);

        $plan = [
            'roles' => $spatieOk,
            'permissions' => $spatieOk,
            'role_has_permissions' => $spatieOk,
            'model_has_roles' => $spatieOk,
            'model_has_permissions' => $spatieOk,
            'cities' => true,
            'users' => true,
        ];

        $this->line('Plan:');
        foreach ($plan as $key => $enabled) {
            $this->line(' - '.$key.': '.($enabled ? 'SI' : 'NO (config/tabla no disponible)'));
        }

        if ($dryRun) {
            $this->info('DRY-RUN: nada sera escrito. Agrega --force para ejecutar.');
        }

        $targetDb = DB::connection($target);
        $targetDb->beginTransaction();

        try {
            $roleIdMap = [];
            $permissionIdMap = [];

            if ($spatieOk) {
                [$roleIdMap, $permissionIdMap] = $this->copySpatieRolesAndPermissions($source, $target, $dryRun);
                $this->copySpatieRolePermissionsPivots($source, $target, $dryRun, $roleIdMap, $permissionIdMap);
            } else {
                $this->warn('Spatie: no se encontro config/permission.php o table_names; se omite copia de roles/permisos.');
            }

            $cityIdMap = $this->copyCities($source, $target, $dryRun, $citiesToCopy);
            $userIdMap = $this->copyUsers($source, $target, $dryRun, $usersToCopy, $cityIdMap);

            if ($spatieOk) {
                $this->copySpatieModelPivotsForUsers($source, $target, $dryRun, $userIdMap, $roleIdMap, $permissionIdMap);
            }

            if ($dryRun) {
                $targetDb->rollBack();
            } else {
                $targetDb->commit();
            }

            if (class_exists('Spatie\\Permission\\PermissionRegistrar')) {
                app('cache')
                    ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
                    ->forget(config('permission.cache.key'));
            }

            $this->info('Listo.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $targetDb->rollBack();
            $this->error($e->getMessage());
            $this->line((string) $e);
            return self::FAILURE;
        }
    }

    /**
     * @return array<int, object>
     */
    private function resolveUsersToCopy(string $source): array
    {
        $autoPick = (bool) $this->option('auto-pick');

        $userIds = $this->parseCsvInts((string) $this->option('user-ids'));
        $userEmails = $this->parseCsvStrings((string) $this->option('user-emails'));

        $query = DB::connection($source)->table('users');

        if (!empty($userIds)) {
            return $query->whereIn('id', $userIds)->orderByDesc('admin')->orderBy('id')->get()->all();
        }

        if (!empty($userEmails)) {
            return $query->whereIn('email', $userEmails)->orderByDesc('admin')->orderBy('id')->get()->all();
        }

        if ($autoPick) {
            $admin = DB::connection($source)->table('users')
                ->where('admin', 1)
                ->orderBy('id')
                ->first();

            $others = DB::connection($source)->table('users')
                ->where(function ($q) {
                    $q->whereNull('admin')->orWhere('admin', 0);
                })
                ->orderBy('id')
                ->limit(3)
                ->get();

            $result = [];
            if ($admin) {
                $result[] = $admin;
            }
            foreach ($others as $row) {
                $result[] = $row;
            }
            return $result;
        }

        return [];
    }

    /**
     * @return array<int, object>
     */
    private function resolveCitiesToCopy(string $source): array
    {
        $cityIds = $this->parseCsvInts((string) $this->option('city-ids'));
        $citySlugs = $this->parseCsvStrings((string) $this->option('city-slugs'));

        $query = DB::connection($source)->table('cities');

        if (!empty($cityIds)) {
            return $query->whereIn('id', $cityIds)->orderBy('id')->get()->all();
        }

        if (!empty($citySlugs)) {
            return $query->whereIn('slug', $citySlugs)->orderBy('id')->get()->all();
        }

        return [];
    }

    /**
     * @return array{0: array<int,int>, 1: array<int,int>}
     */
    private function copySpatieRolesAndPermissions(string $source, string $target, bool $dryRun): array
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (!Schema::connection($source)->hasTable($rolesTable) || !Schema::connection($target)->hasTable($rolesTable)) {
            $this->warn("Spatie: no existe tabla [$rolesTable] en fuente/destino.");
            return [[], []];
        }
        if (!Schema::connection($source)->hasTable($permissionsTable) || !Schema::connection($target)->hasTable($permissionsTable)) {
            $this->warn("Spatie: no existe tabla [$permissionsTable] en fuente/destino.");
            return [[], []];
        }

        $roles = DB::connection($source)->table($rolesTable)->get();
        $perms = DB::connection($source)->table($permissionsTable)->get();

        $this->info('Roles a copiar: '.$roles->count());
        $this->info('Permisos a copiar: '.$perms->count());

        $roleIdMap = [];
        $permIdMap = [];

        foreach ($roles as $role) {
            if (!$dryRun) {
                DB::connection($target)->table($rolesTable)->updateOrInsert(
                    ['name' => $role->name, 'guard_name' => $role->guard_name],
                    [
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                        'updated_at' => $role->updated_at ?? now(),
                        'created_at' => $role->created_at ?? now(),
                    ]
                );
            }

            $targetRoleId = DB::connection($target)->table($rolesTable)
                ->where('name', $role->name)
                ->where('guard_name', $role->guard_name)
                ->value('id');

            if ($targetRoleId) {
                $roleIdMap[(int) $role->id] = (int) $targetRoleId;
            }
        }

        foreach ($perms as $perm) {
            if (!$dryRun) {
                DB::connection($target)->table($permissionsTable)->updateOrInsert(
                    ['name' => $perm->name, 'guard_name' => $perm->guard_name],
                    [
                        'name' => $perm->name,
                        'guard_name' => $perm->guard_name,
                        'updated_at' => $perm->updated_at ?? now(),
                        'created_at' => $perm->created_at ?? now(),
                    ]
                );
            }

            $targetPermId = DB::connection($target)->table($permissionsTable)
                ->where('name', $perm->name)
                ->where('guard_name', $perm->guard_name)
                ->value('id');

            if ($targetPermId) {
                $permIdMap[(int) $perm->id] = (int) $targetPermId;
            }
        }

        return [$roleIdMap, $permIdMap];
    }

    private function copySpatieRolePermissionsPivots(
        string $source,
        string $target,
        bool $dryRun,
        array $roleIdMap,
        array $permissionIdMap
    ): void {
        $tableNames = config('permission.table_names');
        $pivotTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';

        if (!Schema::connection($source)->hasTable($pivotTable) || !Schema::connection($target)->hasTable($pivotTable)) {
            $this->warn("Spatie: no existe tabla [$pivotTable] en fuente/destino.");
            return;
        }

        $rows = DB::connection($source)->table($pivotTable)->get();
        $this->info('role_has_permissions a copiar (merge): '.$rows->count());

        $toInsert = [];
        foreach ($rows as $row) {
            $srcPermId = (int) ($row->permission_id ?? 0);
            $srcRoleId = (int) ($row->role_id ?? 0);

            if (!isset($permissionIdMap[$srcPermId]) || !isset($roleIdMap[$srcRoleId])) {
                continue;
            }

            $toInsert[] = [
                'permission_id' => $permissionIdMap[$srcPermId],
                'role_id' => $roleIdMap[$srcRoleId],
            ];
        }

        if (!$dryRun && !empty($toInsert)) {
            // In Postgres, upsert with empty update columns may degrade to a plain insert.
            // We want idempotency here, so ignore duplicates by PK (permission_id, role_id).
            DB::connection($target)->table($pivotTable)->insertOrIgnore($toInsert);
        }
    }

    /**
     * @param array<int, object> $cities
     * @return array<int,int> source city id => target city id
     */
    private function copyCities(string $source, string $target, bool $dryRun, array $cities): array
    {
        $this->info('Ciudades a copiar: '.count($cities));

        $targetColumns = Schema::connection($target)->getColumnListing('cities');

        $map = [];

        foreach ($cities as $city) {
            $slug = (string) ($city->slug ?? '');
            $name = (string) ($city->name ?? '');

            if ($slug === '' && $name !== '') {
                $slug = Str::slug($name);
            }
            if ($slug === '') {
                $this->warn('Ciudad sin slug ni name, se omite (id fuente: '.($city->id ?? 'null').')');
                continue;
            }

            $payload = [
                'name' => $name,
                'slug' => $slug,
                'center_lat' => $city->center_lat ?? null,
                'center_lng' => $city->center_lng ?? null,
                'radius_m' => $city->radius_m ?? null,
            ];

            $payload = Arr::only($payload, $targetColumns);

            if (!$dryRun) {
                DB::connection($target)->table('cities')->updateOrInsert(
                    ['slug' => $slug],
                    $payload
                );
            }

            $targetCityId = DB::connection($target)->table('cities')->where('slug', $slug)->value('id');
            if ($targetCityId) {
                $map[(int) $city->id] = (int) $targetCityId;
            }
        }

        return $map;
    }

    /**
     * @param array<int, object> $users
     * @param array<int,int> $cityIdMap
     * @return array<int,int> source user id => target user id
     */
    private function copyUsers(string $source, string $target, bool $dryRun, array $users, array $cityIdMap): array
    {
        $this->info('Usuarios a copiar: '.count($users));

        $targetColumns = Schema::connection($target)->getColumnListing('users');
        $sourceColumns = Schema::connection($source)->getColumnListing('users');

        $copyColumns = array_values(array_diff(array_intersect($targetColumns, $sourceColumns), ['id']));

        $map = [];

        foreach ($users as $user) {
            $email = (string) ($user->email ?? '');
            if ($email === '') {
                $this->warn('Usuario sin email, se omite (id fuente: '.($user->id ?? 'null').')');
                continue;
            }

            $payload = [];
            foreach ($copyColumns as $col) {
                $payload[$col] = $user->{$col} ?? null;
            }

            if (array_key_exists('city_id', $payload)) {
                $srcCityId = $user->city_id ?? null;
                if ($srcCityId !== null) {
                    $srcCityId = (int) $srcCityId;
                    if (isset($cityIdMap[$srcCityId])) {
                        $payload['city_id'] = $cityIdMap[$srcCityId];
                    } else {
                        $payload['city_id'] = null;
                        $this->warn('Usuario '.$email.' tiene city_id='.$srcCityId.' que no esta en las 3 ciudades seleccionadas; se deja NULL.');
                    }
                }
            }

            if (!$dryRun) {
                DB::connection($target)->table('users')->updateOrInsert(
                    ['email' => $email],
                    $payload
                );
            }

            $targetUserId = DB::connection($target)->table('users')->where('email', $email)->value('id');
            if ($targetUserId) {
                $map[(int) $user->id] = (int) $targetUserId;
            }
        }

        return $map;
    }

    private function copySpatieModelPivotsForUsers(
        string $source,
        string $target,
        bool $dryRun,
        array $userIdMap,
        array $roleIdMap,
        array $permissionIdMap
    ): void {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        $modelHasRoles = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $modelHasPermissions = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        $userModelType = User::class;

        if (Schema::connection($source)->hasTable($modelHasRoles) && Schema::connection($target)->hasTable($modelHasRoles)) {
            $rows = DB::connection($source)->table($modelHasRoles)
                ->where('model_type', $userModelType)
                ->whereIn($modelMorphKey, array_keys($userIdMap))
                ->get();

            $this->info('model_has_roles (usuarios seleccionados): '.$rows->count());

            $toInsert = [];
            foreach ($rows as $row) {
                $srcUserId = (int) ($row->{$modelMorphKey} ?? 0);
                $srcRoleId = (int) ($row->role_id ?? 0);
                if (!isset($userIdMap[$srcUserId]) || !isset($roleIdMap[$srcRoleId])) {
                    continue;
                }
                $toInsert[] = [
                    'role_id' => $roleIdMap[$srcRoleId],
                    'model_type' => $userModelType,
                    $modelMorphKey => $userIdMap[$srcUserId],
                ];
            }

            if (!$dryRun && !empty($toInsert)) {
                DB::connection($target)->table($modelHasRoles)->insertOrIgnore($toInsert);
            }
        }

        if (Schema::connection($source)->hasTable($modelHasPermissions) && Schema::connection($target)->hasTable($modelHasPermissions)) {
            $rows = DB::connection($source)->table($modelHasPermissions)
                ->where('model_type', $userModelType)
                ->whereIn($modelMorphKey, array_keys($userIdMap))
                ->get();

            $this->info('model_has_permissions (usuarios seleccionados): '.$rows->count());

            $toInsert = [];
            foreach ($rows as $row) {
                $srcUserId = (int) ($row->{$modelMorphKey} ?? 0);
                $srcPermId = (int) ($row->permission_id ?? 0);
                if (!isset($userIdMap[$srcUserId]) || !isset($permissionIdMap[$srcPermId])) {
                    continue;
                }
                $toInsert[] = [
                    'permission_id' => $permissionIdMap[$srcPermId],
                    'model_type' => $userModelType,
                    $modelMorphKey => $userIdMap[$srcUserId],
                ];
            }

            if (!$dryRun && !empty($toInsert)) {
                DB::connection($target)->table($modelHasPermissions)->insertOrIgnore($toInsert);
            }
        }
    }

    /**
     * @return array<int>
     */
    private function parseCsvInts(string $csv): array
    {
        $csv = trim($csv);
        if ($csv === '') {
            return [];
        }
        return array_values(array_filter(array_map(static function ($v) {
            $v = trim((string) $v);
            return ctype_digit($v) ? (int) $v : null;
        }, explode(',', $csv)), static fn ($v) => $v !== null));
    }

    /**
     * @return array<int,string>
     */
    private function parseCsvStrings(string $csv): array
    {
        $csv = trim($csv);
        if ($csv === '') {
            return [];
        }
        return array_values(array_filter(array_map(static function ($v) {
            $v = trim((string) $v);
            return $v === '' ? null : $v;
        }, explode(',', $csv)), static fn ($v) => $v !== null));
    }
}
