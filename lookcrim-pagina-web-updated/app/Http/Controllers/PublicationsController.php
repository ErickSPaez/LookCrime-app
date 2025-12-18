<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'title' => 'required',
            'content' => 'required',
            'category' => 'nullable|string|max:64',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $image = $request->file('image');

        $publications = new Publications;
        // Single-language input: fill both locale columns for compatibility
        $title = $request->input('title');
        $content = $request->input('content');
        $publications->title_pt = $title;
        $publications->title_en = $title;
        $publications->content_pt = $content;
        $publications->content_en = $content;
        $publications->latitude = $request->input('latitude');
        $publications->longitude = $request->input('longitude');
        $publications->category = $request->input('category');
        $publications->save(); // necessary to get ID

        // If lat/lng provided, store as PostGIS geometry Point (SRID 4326)
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        if (!is_null($lat) && !is_null($lng)) {
            // Use parameter binding to avoid SQL injection
            DB::update('UPDATE registers SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?', [$lng, $lat, $publications->id]);
        }

        if ($request->hasFile('image') && $image && $image->isValid()){
            $ext = $image->getClientOriginalExtension();
            $filename = $publications->id . '.' . $ext;
            $image->storeAs('public/publications', $filename);
            $publications->image = 'storage/publications/' . $filename;
        }

        $embed = $request->input('embed_url');
        $publications->embed_url = $embed;
        $publications->embed_url_en = $embed;
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
            'title' => 'required',
            'content' => 'required',
            'category' => 'nullable|string|max:64',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $publications = Publications::findOrFail($id);
        $title = $request->input('title');
        $content = $request->input('content');
        $publications->title_pt = $title;
        $publications->title_en = $title;
        $publications->content_pt = $content;
        $publications->content_en = $content;
        $publications->category = $request->input('category');
        if($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $ext = $image->getClientOriginalExtension();
            $filename = $publications->id . '.' . $ext;
            $image->storeAs('public/publications', $filename);
            $publications->image = 'storage/publications/' . $filename;
        }
        $embed = $request->input('embed_url');
        $publications->embed_url = $embed;
        $publications->embed_url_en = $embed;
        $publications->private = $request->has('private') ? $request->input('private') : 0;
        $publications->latitude = $request->input('latitude');
        $publications->longitude = $request->input('longitude');
        // Update geometry column if lat/lng given
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        if (!is_null($lat) && !is_null($lng)) {
            DB::update('UPDATE registers SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?', [$lng, $lat, $publications->id]);
        }
        $publications->category = $request->input('category');
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
            $publications = Publications::select('*', DB::raw('ST_Y(location) as lat_from_location'), DB::raw('ST_X(location) as lng_from_location'))->orderBy('created_at', 'DESC')->get();
        } else {
            $publications = Publications::where('private', '=', 0)->select('*', DB::raw('ST_Y(location) as lat_from_location'), DB::raw('ST_X(location) as lng_from_location'))->orderBy('created_at', 'DESC')->get();
        }
        $mapData = $publications->map(function($p){
            return [
                'id' => $p->id,
                'title' => $p->title(),
                // Prefer coordinates extracted from geometry; fallback to numeric columns
                'lat' => $p->lat_from_location ?? $p->latitude,
                'lng' => $p->lng_from_location ?? $p->longitude,
                'image' => $p->image_url(),
                'url' => url('/registers/'.$p->id),
                'category' => $p->category ?? null
            ];
        })->values();

        // Prepare translated labels for categories according to current locale
        $categoryLabels = [
            'robo' => trans('pages.robo'),
            'poco_iluminacion' => trans('pages.poco_iluminacion'),
            'zona_insegura' => trans('pages.zona_insegura'),
            'zona_transitada' => trans('pages.zona_transitada'),
            'construccion' => trans('pages.construccion'),
            'otro' => trans('pages.otro'),
        ];

        return view('publications.map', ['publications' => $publications, 'mapData' => $mapData, 'categoryLabels' => $categoryLabels]);
    }
}
