<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Register;
use App\Models\RegisterImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (!Auth::check()) {
            abort(401);
        }

        $user = Auth::user();
        $canViewPage =
            $user->can('view_page_registers') ||
            $user->can('view_any_registers') ||
            $user->can('view_all_registers') ||
            $user->can('view_own_registers') ||
            $user->can('create_own_registers') ||
            $user->can('create_registers') ||
            $user->can('edit_any_registers') ||
            $user->can('edit_all_registers') ||
            $user->can('edit_own_registers') ||
            $user->can('delete_any_registers') ||
            $user->can('delete_own_registers') ||
            $user->can('delete_registers');

        if (!$canViewPage) {
            abort(403);
        }

        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');

        if ($canViewAny) {
            $q = Register::with('images')->orderBy('created_at', 'DESC');
            $q = $this->applyCityReadRestriction($q);
            $registers = $q->paginate(15);
        } elseif ($canViewOwn) {
            $q = Register::with('images')->where('user_id', Auth::id())
                ->orderBy('created_at', 'DESC');
            $q = $this->applyCityReadRestriction($q);
            $registers = $q->paginate(15);
        } else {
            abort(403);
        }

        return view('registers.list', ['registers' => $registers]);
    }

    public function show($id)
    {
        $user = Auth::user();
        $canViewPage =
            $user->can('view_page_registers') ||
            $user->can('view_any_registers') ||
            $user->can('view_all_registers') ||
            $user->can('view_own_registers');

        if (!$canViewPage) {
            abort(403);
        }

        $q = Register::with(['user', 'images'])
            ->select('*', DB::raw('ST_Y(location) as lat_from_location'), DB::raw('ST_X(location) as lng_from_location'))
            ->where('id', $id);
        $q = $this->applyCityReadRestriction($q);
        $register = $q->firstOrFail();

        $isOwner = Auth::check() && (Auth::id() === ($register->user_id ?? null));
        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');

        if ($canViewAny) {
            return view('registers.show', ['register' => $register]);
        }

        if ($isOwner && $canViewOwn) {
            return view('registers.show', ['register' => $register]);
        }

        abort(403);
    }

    public function create()
    {
        $user = Auth::user();
        $canViewPage =
            $user->can('view_page_registers') ||
            $user->can('create_own_registers') ||
            $user->can('create_registers');
        if (!$canViewPage) {
            abort(403);
        }

        $canCreate = $user->can('create_own_registers') || $user->can('create_registers');
        if (!$canCreate) {
            abort(403);
        }

        // Ensure city is assigned for non-admins.
        $city = $this->getCityForWriteRestriction();

        $allowOutsideCity = (bool) ($user?->admin ?? false) || $user->can('create_any_city_registers');

        $register = new Register();
        return view('registers.create', ['register' => $register, 'city' => $city, 'allowOutsideCity' => $allowOutsideCity]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $canViewPage =
            $user->can('view_page_registers') ||
            $user->can('create_own_registers') ||
            $user->can('create_registers');
        if (!$canViewPage) {
            abort(403);
        }

        $canCreate = $user->can('create_own_registers') || $user->can('create_registers');
        if (!$canCreate) {
            abort(403);
        }
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

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $cityIdForRegister = $this->assertPointAllowedAndGetCityId(
            !is_null($lat) ? (float) $lat : null,
            !is_null($lng) ? (float) $lng : null,
            'create_any_city_registers'
        );

        $register = new Register();
        $register->user_id = Auth::id();
        if (!$user?->admin) {
            $register->city_id = $cityIdForRegister ?? (int) $user->city_id;
        } elseif ($cityIdForRegister) {
            $register->city_id = $cityIdForRegister;
        }

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

        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');
        if ($canViewAny || $canViewOwn) {
            return redirect()->route('registers.index');
        }

        return redirect()->route('registers.create');
    }

    public function edit($id)
    {
        $q = Register::query()->where('id', $id);
        $q = $this->applyCityWriteRestriction($q, 'edit_any_city_registers');
        $register = $q->firstOrFail();

        $user = Auth::user();

        $city = null;
        try {
            $city = $register->city_id ? City::query()->where('id', (int) $register->city_id)->first() : null;
        } catch (\Throwable $e) {
            $city = null;
        }
        if (!$city) {
            $city = $this->getUserCityForMap();
        }

        $allowOutsideCity = (bool) ($user?->admin ?? false) || $user->can('edit_any_city_registers');

        $canViewPage =
            $user->can('view_page_registers') ||
            $user->can('view_any_registers') ||
            $user->can('view_all_registers') ||
            $user->can('view_own_registers') ||
            $user->can('edit_any_registers') ||
            $user->can('edit_all_registers') ||
            $user->can('edit_own_registers');

        if (!$canViewPage) {
            abort(403);
        }

        $isOwner = Auth::id() === ($register->user_id ?? null);
        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');
        $canEditAny = $user->can('edit_any_registers') || $user->can('edit_all_registers');
        $canEditOwn = $user->can('edit_own_registers');
        if (!(($canViewAny && $canEditAny) || ($isOwner && $canViewOwn && $canEditOwn))) {
            abort(403);
        }

        return view('registers.edit', ['register' => $register, 'city' => $city, 'allowOutsideCity' => $allowOutsideCity]);
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

        $q = Register::query()->where('id', $id);
        $q = $this->applyCityWriteRestriction($q, 'edit_any_city_registers');
        $register = $q->firstOrFail();

        $user = Auth::user();
        $canViewPage =
            $user->can('view_page_registers') ||
            $user->can('view_any_registers') ||
            $user->can('view_all_registers') ||
            $user->can('view_own_registers') ||
            $user->can('edit_any_registers') ||
            $user->can('edit_all_registers') ||
            $user->can('edit_own_registers');

        if (!$canViewPage) {
            abort(403);
        }

        $isOwner = Auth::id() === ($register->user_id ?? null);
        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');
        $canEditAny = $user->can('edit_any_registers') || $user->can('edit_all_registers');
        $canEditOwn = $user->can('edit_own_registers');
        if (!(($canViewAny && $canEditAny) || ($isOwner && $canViewOwn && $canEditOwn))) {
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

        $this->assertPointAllowedForExistingRegisterOrFail(
            $register,
            !is_null($lat) ? (float) $lat : null,
            !is_null($lng) ? (float) $lng : null
        );

        if (!is_null($lat) && !is_null($lng)) {
            DB::update('UPDATE registers SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?', [$lng, $lat, $register->id]);
        }

        $register->save();

        if ($canViewAny || $canViewOwn) {
            return redirect()->route('registers.index');
        }

        return redirect()->route('registers.edit', ['id' => $register->id]);
    }

    public function delete($id, Request $request)
    {
        $user = Auth::user();
        $q = Register::query()->where('id', $id);
        $q = $this->applyCityWriteRestriction($q, 'delete_any_city_registers');
        $register = $q->firstOrFail();

        $canViewPage =
            $user->can('view_page_registers') ||
            $user->can('view_any_registers') ||
            $user->can('view_all_registers') ||
            $user->can('view_own_registers') ||
            $user->can('delete_any_registers') ||
            $user->can('delete_own_registers') ||
            $user->can('delete_registers');

        if (!$canViewPage) {
            abort(403);
        }

        $isOwner = Auth::id() === ($register->user_id ?? null);
        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');
        $canDeleteAny = $user->can('delete_any_registers') || $user->can('delete_registers');
        $canDeleteOwn = $user->can('delete_own_registers');
        if (!(($canViewAny && $canDeleteAny) || ($isOwner && $canViewOwn && $canDeleteOwn))) {
            abort(403);
        }

        if ($request->input('confirm') === 'yes') {
            $register->delete();
        }

        $canViewAnyAfter = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwnAfter = $user->can('view_own_registers');
        if ($canViewAnyAfter || $canViewOwnAfter) {
            return redirect()->route('registers.index');
        }

        return redirect('/');
    }

    public function map()
    {
        if (!Auth::check()) {
            abort(401);
        }

        $user = Auth::user();
        $canViewPage =
            $user->can('view_page_registers') ||
            $user->can('view_any_registers') ||
            $user->can('view_all_registers') ||
            $user->can('view_own_registers');

        if (!$canViewPage) {
            abort(403);
        }

        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');

        // Always pass default city for map centering (non-admin must have one).
        $city = $this->getUserCityForMap();

        if ($canViewAny) {
            $q = Register::with('images')->select('*', DB::raw('ST_Y(location) as lat_from_location'), DB::raw('ST_X(location) as lng_from_location'))
                ->orderBy('created_at', 'DESC');
            $q = $this->applyCityReadRestriction($q);
            $registers = $q->get();
        } elseif ($canViewOwn) {
            $q = Register::with('images')->where('user_id', Auth::id())
                ->select('*', DB::raw('ST_Y(location) as lat_from_location'), DB::raw('ST_X(location) as lng_from_location'))
                ->orderBy('created_at', 'DESC');
            $q = $this->applyCityReadRestriction($q);
            $registers = $q->get();
        } else {
            abort(403);
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
            $usersQ = User::select('id', 'name', 'email')->orderBy('name');
            if (!$user?->admin) {
                $usersQ->where('city_id', $user?->city_id);
            }
            $users = $usersQ->get();
        } catch (\Throwable $e) {
            $users = [];
        }

        return view('registers.map', ['mapData' => $mapData, 'categoryLabels' => $categoryLabels, 'users' => $users, 'city' => $city]);
    }

    private function getCityForWriteRestriction(): ?City
    {
        $user = Auth::user();
        if (!$user || (bool) ($user->admin ?? false)) {
            return null;
        }

        try {
            $city = $user->city()->first();
        } catch (\Throwable $e) {
            $city = null;
        }

        if (!$city) {
            abort(403, 'City not assigned.');
        }

        return $city;
    }

    private function getCityForReadRestriction(): ?City
    {
        $user = Auth::user();
        if (!$user || (bool) ($user->admin ?? false) || $user->can('view_any_city_registers') || $user->can('view_any_city')) {
            return null;
        }

        return $this->getCityForWriteRestriction();
    }

    private function getUserCityForMap(): ?City
    {
        $user = Auth::user();
        if (!$user || (bool) ($user->admin ?? false)) {
            return null;
        }

        try {
            $city = $user->city()->first();
        } catch (\Throwable $e) {
            $city = null;
        }

        if (!$city) {
            abort(403, 'City not assigned.');
        }

        return $city;
    }

    private function applyCityReadRestriction($query)
    {
        $city = $this->getCityForReadRestriction();
        if (!$city) {
            return $query;
        }

        $pointExpr = "COALESCE(location, CASE WHEN longitude IS NOT NULL AND latitude IS NOT NULL THEN ST_SetSRID(ST_MakePoint(longitude, latitude), 4326) END)";

        return $query->whereRaw(
            "ST_DWithin(($pointExpr)::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)",
            [(float) $city->center_lng, (float) $city->center_lat, (int) $city->radius_m]
        );
    }

    private function applyCityWriteRestriction($query, string $anyCityPermission)
    {
        $user = Auth::user();
        if ($user && ((bool) ($user->admin ?? false) || $user->can($anyCityPermission))) {
            return $query;
        }

        $city = $this->getCityForWriteRestriction();
        if (!$city) {
            return $query;
        }

        $pointExpr = "COALESCE(location, CASE WHEN longitude IS NOT NULL AND latitude IS NOT NULL THEN ST_SetSRID(ST_MakePoint(longitude, latitude), 4326) END)";

        return $query->whereRaw(
            "ST_DWithin(($pointExpr)::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)",
            [(float) $city->center_lng, (float) $city->center_lat, (int) $city->radius_m]
        );
    }

    private function assertPointAllowedAndGetCityId(?float $lat, ?float $lng, string $anyCityPermission): ?int
    {
        $user = Auth::user();
        if (!$user || (bool) ($user->admin ?? false)) {
            // Admin: no enforcement; if point is provided, best-effort assign a city_id.
            if ($lat === null || $lng === null || !is_finite($lat) || !is_finite($lng)) {
                return null;
            }

            return $this->findCityIdForPoint($lat, $lng);
        }

        if ($lat === null || $lng === null || !is_finite($lat) || !is_finite($lng)) {
            throw ValidationException::withMessages([
                'latitude' => __('Location is required and must be inside your city.'),
            ]);
        }

        // If allowed, accept any city that contains the point.
        if ($user->can($anyCityPermission)) {
            $cityId = $this->findCityIdForPoint($lat, $lng);
            if (!$cityId) {
                throw ValidationException::withMessages([
                    'latitude' => __('The selected point is outside any configured city area.'),
                ]);
            }
            return $cityId;
        }

        // Default: must be inside user's city.
        $city = $this->getCityForWriteRestriction();
        if (!$city) {
            return null;
        }

        $row = DB::selectOne(
            'SELECT ST_DWithin(' .
            'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,' .
            'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,' .
            '?) AS ok',
            [(float) $lng, (float) $lat, (float) $city->center_lng, (float) $city->center_lat, (int) $city->radius_m]
        );

        $ok = (bool) (($row->ok ?? false) ? true : false);
        if (!$ok) {
            throw ValidationException::withMessages([
                'latitude' => __('The selected point is outside your city area.'),
            ]);
        }

        return (int) $city->id;
    }

    private function assertPointAllowedForExistingRegisterOrFail(Register $register, ?float $lat, ?float $lng): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if ($lat === null || $lng === null || !is_finite($lat) || !is_finite($lng)) {
            throw ValidationException::withMessages([
                'latitude' => __('Location is required and must be inside your city.'),
            ]);
        }

        $registerCityId = (int) ($register->city_id ?? 0);

        // City is fixed: if register has a city, the point must remain inside it.
        if ($registerCityId > 0) {
            $this->assertPointInsideCityIdOrFail($registerCityId, (float) $lat, (float) $lng);
            return;
        }

        // Legacy/admin register without city: only enforce for non-admins (use user's default city).
        if (!(bool) ($user->admin ?? false)) {
            $city = $this->getCityForWriteRestriction();
            if (!$city) {
                abort(403, 'City not assigned.');
            }
            $this->assertPointInsideCityIdOrFail((int) $city->id, (float) $lat, (float) $lng);
        }
    }

    private function assertPointInsideCityIdOrFail(int $cityId, float $lat, float $lng): void
    {
        $city = null;
        try {
            $city = City::query()->where('id', $cityId)->first();
        } catch (\Throwable $e) {
            $city = null;
        }

        if (!$city) {
            throw ValidationException::withMessages([
                'latitude' => __('City not found.'),
            ]);
        }

        $row = DB::selectOne(
            'SELECT ST_DWithin(' .
            'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,' .
            'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,' .
            '?) AS ok',
            [(float) $lng, (float) $lat, (float) $city->center_lng, (float) $city->center_lat, (int) $city->radius_m]
        );

        $ok = (bool) (($row->ok ?? false) ? true : false);
        if (!$ok) {
            throw ValidationException::withMessages([
                'latitude' => __('The selected point is outside the register city area.'),
            ]);
        }
    }

    private function findCityIdForPoint(float $lat, float $lng): ?int
    {
        try {
            $row = DB::selectOne(
                'SELECT id FROM cities ' .
                'WHERE ST_DWithin(' .
                'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,' .
                'ST_SetSRID(ST_MakePoint(center_lng, center_lat), 4326)::geography,' .
                'radius_m' .
                ') ' .
                'ORDER BY ST_Distance(' .
                'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,' .
                'ST_SetSRID(ST_MakePoint(center_lng, center_lat), 4326)::geography' .
                ') ASC ' .
                'LIMIT 1',
                [(float) $lng, (float) $lat, (float) $lng, (float) $lat]
            );
        } catch (\Throwable $e) {
            $row = null;
        }

        $id = (int) (($row->id ?? 0) ? $row->id : 0);
        return $id > 0 ? $id : null;
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
