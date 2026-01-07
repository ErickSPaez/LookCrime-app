<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RolesController extends Controller
{
    public function __construct()
    {
        // Auth is enforced here; fine-grained permissions are handled in routes
        $this->middleware(['auth']);
    }

    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        return view('settings.roles.index', compact('roles'));
    }

    public function edit(string $slug)
    {
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
        $role = Role::where('name', $slug)->firstOrFail();

        $modelHasRoles = config('permission.table_names.model_has_roles', 'model_has_roles');
        $userCount = DB::table($modelHasRoles)
            ->where('role_id', $role->id)
            ->where('model_type', 'App\\Models\\User')
            ->count();
        if ($userCount > 0) {
            return redirect()->route('settings.roles.index')->with('error', __('pages.cannot_delete_role_in_use'));
        }
        $role->delete();
        return redirect()->route('settings.roles.index')->with('success', __('pages.role_deleted'));
    }
}
