<?php

namespace App\Http\Controllers;

class ResearchController extends Controller
{
    public function index()
    {
        return view('research.index');
    }

    public function show($id)
    {
        return view('research.show', ['id' => $id]);
    }
}
