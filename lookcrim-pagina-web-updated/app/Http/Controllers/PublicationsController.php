<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Publications;

class PublicationsController extends Controller
{
    public function __construct()
    {
        // Allow public listing and viewing; protect create/edit/delete to authenticated users
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function create()
    {
        $publications = new Publications;
        return view('publications.create', ['publications' => $publications]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title_pt' => 'required',
            'title_en' => 'required',
            'content_pt' => 'required',
            'content_en' => 'required',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $image = $request->file('image');

        $publications = new Publications;
        $publications->title_pt = $request->input('title_pt');
        $publications->title_en = $request->input('title_en');
        $publications->content_pt = $request->input('content_pt');
        $publications->content_en = $request->input('content_en');
        $publications->latitude = $request->input('latitude');
        $publications->longitude = $request->input('longitude');
        $publications->save(); // necessary to get ID

        if ($request->hasFile('image') && $image && $image->isValid()){
            $ext = $image->getClientOriginalExtension();
            $filename = $publications->id . '.' . $ext;
            $image->storeAs('public/publications', $filename);
            $publications->image = 'storage/publications/' . $filename;
        }

        $publications->embed_url = $request->input('embed_url');
        $publications->embed_url_en = $request->input('embed_url_en');
        $publications->private = $request->has('private') ? $request->input('private') : 0;
        $publications->save();
        return redirect()->route('publications');
    }

    public function edit($id)
    {
        $publications = Publications::findOrFail($id);
        return view('publications.edit', ['publications' => $publications]);
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'title_pt' => 'required',
            'title_en' => 'required',
            'content_pt' => 'required',
            'content_en' => 'required',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $publications = Publications::findOrFail($id);
        $publications->title_pt = $request->input('title_pt');
        $publications->title_en = $request->input('title_en');
        $publications->content_pt = $request->input('content_pt');
        $publications->content_en = $request->input('content_en');
        if($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $ext = $image->getClientOriginalExtension();
            $filename = $publications->id . '.' . $ext;
            $image->storeAs('public/publications', $filename);
            $publications->image = 'storage/publications/' . $filename;
        }
        $publications->embed_url = $request->input('embed_url');
        $publications->embed_url_en = $request->input('embed_url_en');
        $publications->private = $request->has('private') ? $request->input('private') : 0;
        $publications->latitude = $request->input('latitude');
        $publications->longitude = $request->input('longitude');
        $publications->save();
        return redirect()->route('publications');
    }

    public function confirmDelete($id)
    {
        $publications = Publications::findOrFail($id);
        return view('publications.delete', ['publications' => $publications]);
    }

    public function delete($id, Request $request)
    {
        if($request->input('confirm') === 'yes') {
            Publications::findOrFail($id)->delete();
        }
        return redirect()->route('publications');
    }

    public function index()
    {
        if(Auth::check()) {
            $publications = Publications::orderBy('created_at', 'DESC')->paginate(15);
        } else {
            $publications = Publications::where('private', '=', 0)
                ->orderBy('created_at', 'DESC')
                ->paginate(15);
        }
        return view('publications.list', ['publications' => $publications]);
    }

    public function show($id)
    {
        $publications = Publications::findOrFail($id);
        if($publications->private != 0) {
            if(Auth::check())
                return view('publications.show', ['publications' => $publications]);
            else
                return redirect()->route('publications');
        } else {
            return view('publications.show', ['publications' => $publications]);
        }
    }

    /**
     * Show a public map with all publications that have coordinates.
     */
    public function map()
    {
        if(Auth::check()) {
            $publications = Publications::orderBy('created_at', 'DESC')->get();
        } else {
            $publications = Publications::where('private', '=', 0)->orderBy('created_at', 'DESC')->get();
        }
        $mapData = $publications->map(function($p){
            return [
                'id' => $p->id,
                'title' => $p->title(),
                'lat' => $p->latitude,
                'lng' => $p->longitude,
                'image' => $p->image_url(),
                'url' => url('/publications/'.$p->id)
            ];
        })->values();

        return view('publications.map', ['publications' => $publications, 'mapData' => $mapData]);
    }
}
