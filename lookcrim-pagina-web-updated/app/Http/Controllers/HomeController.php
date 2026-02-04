<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        $canViewRegisters =
            $user->can('view_any_registers') ||
            $user->can('view_all_registers') ||
            $user->can('view_own_registers');

        if ($canViewRegisters) {
            return redirect()->route('registers.map');
        }

        $canCreateRegisters =
            $user->can('create_own_registers') ||
            $user->can('create_registers');

        if ($canCreateRegisters) {
            return redirect()->route('registers.create');
        }

        return view('no-permissions');
    }
}
