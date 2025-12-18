<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class RolesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','can:admin']);
    }

    public function index()
    {
        $roles = Role::orderBy('slug')->get();
        // Get union of permission keys across roles
        $allPerms = [];
        foreach ($roles as $r) {
            foreach (($r->permissions ?? []) as $k => $v) { $allPerms[$k] = true; }
        }
        $permissionsList = array_keys($allPerms);
        return view('settings.roles.index', compact('roles','permissionsList'));
    }

    public function edit(string $slug)
    {
        $role = Role::where('slug',$slug)->firstOrFail();
        // Build all perms from config as baseline
        $defs = config('roles.definitions', []);
        $base = $defs[$role->slug] ?? [];
        $perms = array_merge($base, $role->permissions ?? []);
        $permissionsList = array_keys($perms);
        return view('settings.roles.edit', compact('role','permissionsList','perms'));
    }

    public function update(Request $request, string $slug)
    {
        $role = Role::where('slug',$slug)->firstOrFail();
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_pt' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'in:0,1',
        ]);

        $incomingPerms = [];
        foreach (($data['permissions'] ?? []) as $k => $v) {
            $incomingPerms[$k] = (bool)$v;
        }

        $role->name_en = $data['name_en'];
        $role->name_pt = $data['name_pt'];
        $role->permissions = $incomingPerms;
        $role->save();

        return redirect()->route('settings.roles.index')->with('success', __('pages.role_updated_successfully'));
    }

    public function create()
    {
        // build permissions list from config and existing roles
        $defs = config('roles.definitions', []);
        $all = [];
        foreach ($defs as $r => $perms) {
            foreach ($perms as $k => $v) { $all[$k] = true; }
        }
        $permissionsList = array_keys($all);
        return view('settings.roles.create', compact('permissionsList'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required|alpha_dash|unique:roles,slug',
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'in:0,1',
        ]);

        $incomingPerms = [];
        foreach (($data['permissions'] ?? []) as $k => $v) {
            $incomingPerms[$k] = (bool)$v;
        }

        $role = new Role();
        $role->slug = $data['slug'];
        // store same provided name for both locales; UI shows localized name from DB per locale
        $role->name_en = $data['name'];
        $role->name_pt = $data['name'];
        $role->permissions = $incomingPerms;
        $role->save();

        return redirect()->route('settings.roles.index')->with('success', __('pages.role_created'));
    }

    public function destroy(string $slug)
    {
        $role = Role::where('slug',$slug)->firstOrFail();
        // prevent deletion if users still assigned
        $userCount = \App\Models\User::where('role', $role->slug)->count();
        if ($userCount > 0) {
            return redirect()->route('settings.roles.index')->with('error', __('pages.cannot_delete_role_in_use'));
        }
        $role->delete();
        return redirect()->route('settings.roles.index')->with('success', __('pages.role_deleted'));
    }
}
