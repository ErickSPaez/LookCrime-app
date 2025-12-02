<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of users (simple admin view).
     */
    public function index(Request $request)
    {
        $users = User::orderBy('id', 'desc')->paginate(25);
        return view('user.management', compact('users'));
    }

    public function create()
    {
        return view('user.create');
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
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'nickname' => $data['nickname'] ?? null,
            'institution' => $data['institution'] ?? null,
            'admin' => isset($data['admin']) ? (bool)$data['admin'] : false,
        ]);

        return Redirect::route('users-list')->with('success', 'Usuario creado.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        // Legacy behavior: prevent editing your own account from admin panel
        if ($id == Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        return view('user.edit', compact('user'));
    }

    public function password_replacement($id)
    {
        $user = User::findOrFail($id);

        // Send password reset link to user's email
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
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->nickname = $data['nickname'] ?? $user->nickname;
        $user->institution = $data['institution'] ?? $user->institution;
        $user->admin = isset($data['admin']) ? (bool)$data['admin'] : $user->admin;
        $user->save();

        return Redirect::route('users-list')->with('success', 'Usuario actualizado.');
    }

    public function ban($id)
    {
        $user = User::findOrFail($id);
        $user->banned = ! (bool) $user->banned;
        $user->save();
        return Redirect::back()->with('success', 'Estado de ban actualizado.');
    }
}
