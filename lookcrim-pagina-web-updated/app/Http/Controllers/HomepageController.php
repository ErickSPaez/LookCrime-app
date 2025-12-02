<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomepageController extends Controller
{
    /**
     * Minimal show method used for route listing and temporary compatibility.
     */
    public function show()
    {
        return view('welcome');
    }
}
