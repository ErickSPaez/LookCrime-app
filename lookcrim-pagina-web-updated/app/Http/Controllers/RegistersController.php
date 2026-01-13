<?php

namespace App\Http\Controllers;

use App\Models\Register;
use App\Models\RegisterImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (Auth::check()) {
            if (Auth::user()->can('view_all_registers')) {
                $registers = Register::with('images')->orderBy('created_at', 'DESC')->paginate(15);
            } else {
                $registers = Register::with('images')->where('user_id', Auth::id())
                    ->orderBy('created_at', 'DESC')
                    ->paginate(15);
            }
        } else {
            $registers = Register::with('images')->where('private', '=', 0)
                ->orderBy('created_at', 'DESC')
                ->paginate(15);
        }

        return view('registers.list', ['registers' => $registers]);
    }

    public function show($id)
    {
        $register = Register::with(['user', 'images'])
            ->select('*', DB::raw('ST_Y(location) as lat_from_location'), DB::raw('ST_X(location) as lng_from_location'))
            ->findOrFail($id);

        $isOwner = Auth::check() && (Auth::id() === ($register->user_id ?? null));
        $canViewAll = Auth::check() && Auth::user()->can('view_all_registers');

        if ($register->private != 0) {
            if ($isOwner || $canViewAll) {
                return view('registers.show', ['register' => $register]);
            }
            return redirect()->route('registers.index');
        }

        if (Auth::check() && !$isOwner && !$canViewAll) {
            return redirect()->route('registers.index');
        }

        return view('registers.show', ['register' => $register]);
    }

    public function create()
    {
        $register = new Register();
        return view('registers.create', ['register' => $register]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'category' => 'nullable|string|max:64',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'images' => 'nullable|array|max:3',
            'images.*' => 'image|max:8192',
            'image' => 'nullable|image|max:8192',
        ]);

        $register = new Register();
        $register->user_id = Auth::id();

        $title = $request->input('title');
        $content = $request->input('content');

        $register->title_pt = $title;
        $register->title_en = $title;
        $register->content_pt = $content;
        $register->content_en = $content;
        $register->latitude = $request->input('latitude');
        $register->longitude = $request->input('longitude');
        $register->category = $request->input('category');
        $register->save();

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        if (!is_null($lat) && !is_null($lng)) {
            DB::update('UPDATE registers SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?', [$lng, $lat, $register->id]);
        }

        $files = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images') ?? [];
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
        }
        $this->replaceRegisterImages($register, $files);

        $embed = $request->input('embed_url');
        $register->embed_url = $embed;
        $register->embed_url_en = $embed;
        $register->private = $request->has('private') ? $request->input('private') : 0;
        $register->save();

        return redirect()->route('registers.index');
    }

    public function edit($id)
    {
        $register = Register::findOrFail($id);
        if (!(Auth::id() === ($register->user_id ?? null) || Auth::user()->can('edit_all_registers'))) {
            abort(403);
        }

        return view('registers.edit', ['register' => $register]);
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'category' => 'nullable|string|max:64',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'images' => 'nullable|array|max:3',
            'images.*' => 'image|max:8192',
            'image' => 'nullable|image|max:8192',
        ]);

        $register = Register::findOrFail($id);
        if (!(Auth::id() === ($register->user_id ?? null) || Auth::user()->can('edit_all_registers'))) {
            abort(403);
        }

        if (empty($register->user_id) && Auth::check()) {
            $register->user_id = Auth::id();
        }

        $title = $request->input('title');
        $content = $request->input('content');

        $register->title_pt = $title;
        $register->title_en = $title;
        $register->content_pt = $content;
        $register->content_en = $content;
        $register->category = $request->input('category');

        $hasAnyNewImages = $request->hasFile('images') || $request->hasFile('image');
        if ($hasAnyNewImages) {
            $files = [];
            if ($request->hasFile('images')) {
                $files = $request->file('images') ?? [];
            } elseif ($request->hasFile('image')) {
                $files = [$request->file('image')];
            }
            $this->replaceRegisterImages($register, $files);
        }

        $embed = $request->input('embed_url');
        $register->embed_url = $embed;
        $register->embed_url_en = $embed;
        $register->private = $request->has('private') ? $request->input('private') : 0;
        $register->latitude = $request->input('latitude');
        $register->longitude = $request->input('longitude');

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        if (!is_null($lat) && !is_null($lng)) {
            DB::update('UPDATE registers SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?', [$lng, $lat, $register->id]);
        }

        $register->save();

        return redirect()->route('registers.index');
    }

    public function confirmDelete($id)
    {
        $register = Register::findOrFail($id);
        if (!Auth::user()->can('delete_registers')) {
            abort(403);
        }

        return view('registers.delete', ['register' => $register]);
    }

    public function delete($id, Request $request)
    {
        if (!Auth::user()->can('delete_registers')) {
            abort(403);
        }

        if ($request->input('confirm') === 'yes') {
            Register::findOrFail($id)->delete();
        }

        return redirect()->route('registers.index');
    }

    public function map()
    {
        if (Auth::check()) {
            if (Auth::user()->can('view_all_registers')) {
                $registers = Register::with('images')->select('*', DB::raw('ST_Y(location) as lat_from_location'), DB::raw('ST_X(location) as lng_from_location'))
                    ->orderBy('created_at', 'DESC')->get();
            } else {
                $registers = Register::with('images')->where('user_id', Auth::id())
                    ->select('*', DB::raw('ST_Y(location) as lat_from_location'), DB::raw('ST_X(location) as lng_from_location'))
                    ->orderBy('created_at', 'DESC')->get();
            }
        } else {
            $registers = Register::with('images')->where('private', '=', 0)
                ->select('*', DB::raw('ST_Y(location) as lat_from_location'), DB::raw('ST_X(location) as lng_from_location'))
                ->orderBy('created_at', 'DESC')->get();
        }

        $mapData = $registers->map(function ($p) {
            return [
                'id' => $p->id,
                'title' => $p->title(),
                'lat' => $p->lat_from_location ?? $p->latitude,
                'lng' => $p->lng_from_location ?? $p->longitude,
                'image' => $p->image_url(),
                'url' => url('/registers/' . $p->id),
                'category' => $p->category ?? null,
                'user_id' => $p->user_id ?? null,
                'created_at' => optional($p->created_at)->toDateString(),
            ];
        })->values();

        $categoryLabels = [
            'robo' => trans('pages.robo'),
            'poco_iluminacion' => trans('pages.poco_iluminacion'),
            'zona_insegura' => trans('pages.zona_insegura'),
            'zona_transitada' => trans('pages.zona_transitada'),
            'construccion' => trans('pages.construccion'),
            'otro' => trans('pages.otro'),
        ];

        $users = [];
        try {
            $users = \App\Models\User::select('id', 'name', 'email')->orderBy('name')->get();
        } catch (\Throwable $e) {
            $users = [];
        }

        return view('registers.map', ['mapData' => $mapData, 'categoryLabels' => $categoryLabels, 'users' => $users]);
    }

    private function replaceRegisterImages(Register $register, array $files): void
    {
        $validFiles = array_values(array_filter($files, function ($f) {
            return $f && method_exists($f, 'isValid') && $f->isValid();
        }));

        if (count($validFiles) === 0) {
            return;
        }

        // Hard limit: max 3
        $validFiles = array_slice($validFiles, 0, 3);

        // Delete existing images (and their files) via model events
        try {
            $register->images()->get()->each(function (RegisterImage $img) {
                $img->delete();
            });
        } catch (\Throwable $e) {
            // ignore
        }

        $disk = 'public';
        $firstPath = null;

        foreach ($validFiles as $i => $file) {
            $ext = $file->getClientOriginalExtension() ?: $file->extension();
            $ext = $ext ?: 'jpg';
            $filename = (string) Str::uuid() . '.' . $ext;
            $dir = 'registers/' . $register->id;

            $path = $file->storeAs($dir, $filename, $disk);
            if (!$firstPath) {
                $firstPath = $path;
            }

            RegisterImage::create([
                'register_id' => $register->id,
                'disk' => $disk,
                'path' => $path,
                'sort_order' => $i + 1,
            ]);
        }

        // Keep legacy column populated for compatibility
        if ($firstPath) {
            $register->image = 'storage/' . $firstPath;
            $register->save();
        }
    }
}
