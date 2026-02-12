<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Register;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CitiesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $user = Auth::user();
        if (!$user || !$user->can('view_page_settings_city')) {
            abort(403);
        }

        $cities = City::orderBy('name')->get();
        return view('settings.cities.index', compact('cities'));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user || !$user->can('create_city')) {
            abort(403);
        }

        $city = new City();
        return view('settings.cities.create', compact('city'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->can('create_city')) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'center_lat' => 'required|numeric|between:-90,90',
            'center_lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'required|numeric|min:0.1|max:2000',
        ]);

        $slug = Str::slug($data['name']);
        $baseSlug = $slug;
        $i = 2;
        while (City::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i;
            $i++;
        }

        City::create([
            'name' => $data['name'],
            'slug' => $slug,
            'center_lat' => $data['center_lat'],
            'center_lng' => $data['center_lng'],
            'radius_m' => (int) round(((float) $data['radius_km']) * 1000),
        ]);

        return Redirect::route('settings.city.index')->with('success', __('pages.city_created'));
    }

    public function edit(string $slug)
    {
        $user = Auth::user();
        if (!$user || !$user->can('edit_city')) {
            abort(403);
        }

        $city = City::where('slug', $slug)->firstOrFail();
        return view('settings.cities.edit', compact('city'));
    }

    public function update(Request $request, string $slug)
    {
        $user = Auth::user();
        if (!$user || !$user->can('edit_city')) {
            abort(403);
        }

        $city = City::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'center_lat' => 'required|numeric|between:-90,90',
            'center_lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'required|numeric|min:0.1|max:2000',
        ]);

        $city->name = $data['name'];
        $city->center_lat = $data['center_lat'];
        $city->center_lng = $data['center_lng'];
        $city->radius_m = (int) round(((float) $data['radius_km']) * 1000);
        $city->save();

        return Redirect::route('settings.city.index')->with('success', __('pages.city_updated'));
    }

    public function destroy(string $slug)
    {
        $user = Auth::user();
        if (!$user || !$user->can('delete_city')) {
            abort(403);
        }

        $city = City::where('slug', $slug)->firstOrFail();

        $inUse = User::where('city_id', $city->id)->exists();
        if (!$inUse) {
            $inUse = Register::where('city_id', $city->id)->exists();
        }
        if ($inUse) {
            return Redirect::back()->with('error', __('pages.cannot_delete_city_in_use'));
        }

        $city->delete();

        return Redirect::route('settings.city.index')->with('success', __('pages.city_deleted'));
    }
}
