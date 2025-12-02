<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function setLanguage($lang)
    {
        // minimal implementation for compatibility
        session(['app_locale' => $lang]);
        return redirect()->back();
    }
}
