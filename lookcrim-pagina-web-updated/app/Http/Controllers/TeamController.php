<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;

class TeamController extends Controller
{
    public function create()
    {
        $team = new Team();
        return view('team.create', ['team' => $team]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'content_pt' => 'required',
            'content_en' => 'required',
        ]);

        $team = new Team();
        $team->content_pt = $request->input('content_pt');
        $team->content_en = $request->input('content_en');
        $team->save();

        return redirect()->route('team');
    }

    public function edit()
    {
        $team = Team::first();
        return view('team.edit', ['team' => $team]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'content_pt' => 'required',
            'content_en' => 'required',
        ]);

        $team = Team::first();
        if (! $team) {
            $team = new Team();
        }
        $team->content_pt = $request->input('content_pt');
        $team->content_en = $request->input('content_en');
        $team->save();

        return redirect()->route('team');
    }

    public function show()
    {
        $team = Team::first();
        return view('partials.team.show', ['team' => $team]);
    }
}
