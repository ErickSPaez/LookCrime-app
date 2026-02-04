<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RolesController extends Controller
{
    private const PROTECTED_ROLES = ['admin'];

    private function isProtectedRoleSlug(string $slug): bool
    {
        return in_array($slug, self::PROTECTED_ROLES, true);
    }
    public function __construct()
    {
        // Auth is enforced here; fine-grained permissions are handled in routes
        $this->middleware(['auth']);
    }

    public function index()
    {
        $roles = Role::with('permissions')
            ->orderByRaw("CASE WHEN name = ? THEN 1 ELSE 0 END", ['admin'])
            ->orderBy('name')
            ->get();
        return view('settings.roles.index', compact('roles'));
    }

    public function edit(string $slug)
    {
        if ($this->isProtectedRoleSlug($slug)) {
            return redirect()->route('settings.roles.index')->with('error', __('pages.cannot_modify_protected_role'));
        }

        $role = Role::where('name', $slug)->firstOrFail();
        $permissionGroups = Permission::orderBy('category')->orderBy('name')->get()->groupBy('category');

        $assigned = $role->permissions->pluck('name')->all();

        return view('settings.roles.edit', [
            'role' => $role,
            'permissionGroups' => $permissionGroups,
            'assigned' => $assigned,
        ]);
    }

    public function update(Request $request, string $slug)
    {
        if ($this->isProtectedRoleSlug($slug)) {
            return redirect()->route('settings.roles.index')->with('error', __('pages.cannot_modify_protected_role'));
        }

        $role = Role::where('name', $slug)->firstOrFail();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'in:0,1',
        ]);

        $incomingPermNames = array_keys($data['permissions'] ?? []);
        $existingPermNames = Permission::whereIn('name', $incomingPermNames)->pluck('name')->all();

        // Single input controls both localizations to keep them in sync
        $role->name_en = $data['name'];
        $role->name_pt = $data['name'];
        $role->save();

        $role->syncPermissions($existingPermNames);

        return redirect()->route('settings.roles.index')->with('success', __('pages.role_updated_successfully'));
    }

    public function create()
    {
        $permissionGroups = Permission::orderBy('category')->orderBy('name')->get()->groupBy('category');
        return view('settings.roles.create', compact('permissionGroups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required|alpha_dash|unique:roles,name',
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'in:0,1',
        ]);

        $incomingPermNames = array_keys($data['permissions'] ?? []);
        $existingPermNames = Permission::whereIn('name', $incomingPermNames)->pluck('name')->all();

        $role = Role::create([
            'name' => $data['slug'],
            'guard_name' => 'web',
            // store same provided name for both locales; UI shows localized name from DB per locale
            'name_en' => $data['name'],
            'name_pt' => $data['name'],
        ]);

        $role->syncPermissions($existingPermNames);

        return redirect()->route('settings.roles.index')->with('success', __('pages.role_created'));
    }

    public function destroy(string $slug)
    {
        if ($this->isProtectedRoleSlug($slug)) {
            return redirect()->route('settings.roles.index')->with('error', __('pages.cannot_modify_protected_role'));
        }

        $role = Role::where('name', $slug)->firstOrFail();

        $modelHasRoles = config('permission.table_names.model_has_roles', 'model_has_roles');
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $modelMorphKey = config('permission.column_names.model_morph_key') ?: 'model_id';
        $userModel = User::class;

        $fallbackRole = Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'web'],
            ['name_en' => 'User', 'name_pt' => 'User']
        );

        DB::transaction(function () use ($modelHasRoles, $rolePivotKey, $modelMorphKey, $userModel, $role, $fallbackRole) {
            $affectedUserIds = DB::table($modelHasRoles)
                ->where($rolePivotKey, $role->id)
                ->where('model_type', $userModel)
                ->pluck($modelMorphKey)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            // Remove the role assignment rows for all users.
            DB::table($modelHasRoles)
                ->where($rolePivotKey, $role->id)
                ->where('model_type', $userModel)
                ->delete();

            // Delete the role itself.
            $role->delete();

            // Any user left with no roles gets the fallback 'user' role.
            foreach ($affectedUserIds as $userId) {
                $remainingRoles = DB::table($modelHasRoles)
                    ->where('model_type', $userModel)
                    ->where($modelMorphKey, $userId)
                    ->count();

                if ($remainingRoles === 0) {
                    DB::table($modelHasRoles)->insert([
                        $rolePivotKey => $fallbackRole->id,
                        'model_type' => $userModel,
                        $modelMorphKey => $userId,
                    ]);
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('settings.roles.index')->with('success', __('pages.role_deleted'));
    }
}
