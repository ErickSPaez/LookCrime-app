<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Project;

class ProjectController extends Controller
{
    public function __construct()
    {
        // Project content is editable only by authenticated users; public can view
        $this->middleware('auth')->except(['show']);
    }

    public function create()
    {
        $project = new Project;
        return view('project.create', ['project' => $project]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'content_pt' => 'required',
            'content_en' => 'required',
        ]);

        $project = new Project;
        $project->content_pt = $request->input('content_pt');
        $project->content_en = $request->input('content_en');
        $project->save();
        return redirect()->route('project');
    }

    public function edit()
    {
        $project = Project::findOrFail(1);
        return view('project.edit', ['project' => $project]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'content_pt' => 'required',
            'content_en' => 'required',
        ]);

        $project = Project::findOrFail(1);
        $project->content_pt = $request->input('content_pt');
        $project->content_en = $request->input('content_en');
        $project->save();
        return redirect()->route('project');
    }

    public function show()
    {
        $project = Project::find(1);
        return view('project.show', ['project' => $project]);
    }
}
    
