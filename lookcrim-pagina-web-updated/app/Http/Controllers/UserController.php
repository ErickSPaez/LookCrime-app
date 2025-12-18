<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('id', 'desc')->paginate(25);
        return view('user.management', compact('users'));
    }

    public function create()
    {
        $roleDefinitions = $this->getRoleDefinitions();
        $roles = array_keys($roleDefinitions);
        $permissionsList = $this->permissionsList($roleDefinitions);
        return view('user.create', compact('roles', 'roleDefinitions', 'permissionsList'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'nickname' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'admin' => 'nullable|in:0,1',
            'role' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'in:0,1',
        ]);

        $roleDefinitions = $this->getRoleDefinitions();
        $role = $data['role'] ?? 'user';
        if (!array_key_exists($role, $roleDefinitions)) {
            $role = 'user';
        }
        $perms = $this->buildPermissionsPayload($roleDefinitions[$role] ?? [], $data['permissions'] ?? []);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'nickname' => $data['nickname'] ?? null,
            'institution' => $data['institution'] ?? null,
            'admin' => ($role === 'super_usuario') ? true : (isset($data['admin']) ? (bool)$data['admin'] : false),
            'role' => $role,
            'permissions' => $perms,
        ]);

        return Redirect::route('users-list')->with('success', 'Usuario creado.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        if ($id == Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        $roleDefinitions = $this->getRoleDefinitions();
        $roles = array_keys($roleDefinitions);
        $permissionsList = $this->permissionsList($roleDefinitions);
        return view('user.edit', compact('user','roles','roleDefinitions','permissionsList'));
    }

    public function password_replacement($id)
    {
        $user = User::findOrFail($id);
        Password::sendResetLink(['email' => $user->email]);
        return view('auth.success-email');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'nickname' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'admin' => 'nullable|in:0,1',
            'role' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'in:0,1',
        ]);

        $roleDefinitions = $this->getRoleDefinitions();
        $role = $data['role'] ?? $user->role ?? 'user';
        if (!array_key_exists($role, $roleDefinitions)) {
            $role = 'user';
        }
        $perms = $this->buildPermissionsPayload($roleDefinitions[$role] ?? [], $data['permissions'] ?? []);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->nickname = $data['nickname'] ?? $user->nickname;
        $user->institution = $data['institution'] ?? $user->institution;
        $user->admin = ($role === 'super_usuario') ? true : (isset($data['admin']) ? (bool)$data['admin'] : $user->admin);
        $user->role = $role;
        $user->permissions = $perms;
        $user->save();

        return Redirect::route('users-list')->with('success', 'Usuario actualizado.');
    }

    public function ban($id)
    {
        $user = User::findOrFail($id);
        $user->banned = !(bool) $user->banned;
        $user->save();
        return Redirect::back()->with('success', 'Estado de ban actualizado.');
    }

    private function getRoleDefinitions(): array
    {
        $configDefs = config('roles.definitions', []);
        $dbRoles = \App\Models\Role::orderBy('slug')->get();

        $result = [];
        // start with config defaults
        foreach ($configDefs as $slug => $perms) {
            $result[$slug] = $perms;
        }
        // override or add with DB roles (DB permissions take precedence)
        foreach ($dbRoles as $r) {
            $result[$r->slug] = $r->permissions ?? ($result[$r->slug] ?? []);
        }

        return $result;
    }

    private function permissionsList(array $roleDefinitions): array
    {
        $perms = [];
        foreach ($roleDefinitions as $defs) {
            foreach ($defs as $k => $v) {
                $perms[$k] = true;
            }
        }
        return array_keys($perms);
    }

    private function buildPermissionsPayload(array $roleDefaults, array $incoming): array
    {
        $payload = [];
        foreach ($roleDefaults as $perm => $value) {
            if (array_key_exists($perm, $incoming)) {
                $payload[$perm] = (bool)$incoming[$perm];
            } else {
                $payload[$perm] = (bool)$value;
            }
        }
        foreach ($incoming as $perm => $value) {
            if (!array_key_exists($perm, $payload)) {
                $payload[$perm] = (bool)$value;
            }
        }
        return $payload;
    }
}