<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
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
        $users = User::with('roles')->orderBy('id', 'desc')->paginate(25);
        return view('user.management', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->pluck('name')->all();
        return view('user.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'nickname' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'role' => 'nullable|string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'nickname' => $data['nickname'] ?? null,
            'institution' => $data['institution'] ?? null,
            'admin' => false,
        ]);

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return Redirect::route('users-list')->with('success', 'Usuario creado.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        if ($id == Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        $user->loadMissing('roles');
        $roles = Role::orderBy('name')->pluck('name')->all();
        return view('user.edit', compact('user','roles'));
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
            'role' => 'nullable|string|exists:roles,name',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->nickname = $data['nickname'] ?? $user->nickname;
        $user->institution = $data['institution'] ?? $user->institution;
        $user->save();

        if (array_key_exists('role', $data)) {
            $user->syncRoles($data['role'] ? [$data['role']] : []);
        }

        return Redirect::route('users-list')->with('success', 'Usuario actualizado.');
    }

    public function ban($id)
    {
        $user = User::findOrFail($id);
        $user->banned = !(bool) $user->banned;
        $user->save();
        return Redirect::back()->with('success', 'Estado de ban actualizado.');
    }

}