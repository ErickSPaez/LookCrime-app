<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\TestSmtpMail;
use App\Models\City;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'city'])->orderBy('id', 'desc')->paginate(25);
        return view('user.management', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->pluck('name')->all();
        $cities = City::orderBy('name')->get(['id','name','slug']);
        return view('user.create', compact('roles','cities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'nickname' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'role' => 'nullable|string|exists:roles,name',
            // Non-admin users must always have a default city for /map centering.
            'city_id' => 'required|integer|exists:cities,id',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            // Admin does not set passwords. User will choose it via the reset-token email.
            'password' => Hash::make(Str::random(48)),
            'nickname' => $data['nickname'] ?? '',
            'institution' => $data['institution'] ?? '',
            'admin' => false,
            'city_id' => (int) $data['city_id'],
        ]);

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        Password::sendResetLink(['email' => $user->email]);

        return Redirect::route('users-list')->with('success', __('User created.'));
    }

    public function resendPasswordSetupEmail($id)
    {
        $user = User::findOrFail($id);

        Password::sendResetLink(['email' => $user->email]);

        return Redirect::route('users-list')->with('success', __('Password setup email sent.'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        if ($id == Auth::id() && !Auth::user()?->can('admin')) {
            abort(403, 'Unauthorized action.');
        }
        $user->loadMissing('roles');
        $roles = Role::orderBy('name')->pluck('name')->all();
        $cities = City::orderBy('name')->get(['id','name','slug']);
        return view('user.edit', compact('user','roles','cities'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'nickname' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'role' => 'nullable|string|exists:roles,name',
            // Admin may omit city; all non-admin users must have a city.
            'city_id' => ($user->admin ? 'nullable' : 'required') . '|integer|exists:cities,id',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->nickname = $data['nickname'] ?? $user->nickname;
        $user->institution = $data['institution'] ?? $user->institution;
        $user->city_id = array_key_exists('city_id', $data) ? ($data['city_id'] !== null ? (int) $data['city_id'] : null) : $user->city_id;
        $user->save();

        // Keep admin accounts pinned to the 'admin' role.
        if ($user->admin) {
            $user->syncRoles(['admin']);
        } elseif (array_key_exists('role', $data)) {
            $user->syncRoles($data['role'] ? [$data['role']] : []);
        }

        return Redirect::route('users-list')->with('success', __('User updated.'));
    }

    public function ban($id)
    {
        $user = User::findOrFail($id);

        if ((int) $user->id === (int) Auth::id()) {
            return Redirect::back()->with('error', __('You cannot ban your own account.'));
        }

        $user->banned = !(bool) $user->banned;
        $user->save();
        return Redirect::back()->with('success', __('User status updated.'));
    }

    public function sendTestEmail(Request $request)
    {
        $data = $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            Mail::to($data['test_email'])->send(new TestSmtpMail());
        } catch (\Throwable $e) {
            return Redirect::back()->with('error', __('Test email failed: :message', ['message' => $e->getMessage()]));
        }

        return Redirect::back()->with('success', __('Test email sent.'));
    }

}