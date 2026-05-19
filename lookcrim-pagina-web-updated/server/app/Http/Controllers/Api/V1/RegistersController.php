<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Register;
use App\Models\RegisterImage;
use App\Models\User;
use App\Http\Controllers\Api\V1\MetaController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistersController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');

        if (!$canViewAny && !$canViewOwn) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $q = Register::with('images')->orderBy('created_at', 'DESC');

        if (!$canViewAny) {
            $q->where('user_id', $user->id);
        }

        $category = $request->query('category');
        if (is_string($category) && $category !== '') {
            $q->where('category', $category);
        }

        $term = $request->query('q');
        if (is_string($term) && trim($term) !== '') {
            $term = trim($term);
            $q->where(function (Builder $sub) use ($term) {
                $like = '%' . str_replace('%', '\\%', $term) . '%';
                $sub->where('title_pt', 'ILIKE', $like)
                    ->orWhere('content_pt', 'ILIKE', $like)
                    ->orWhere('title_en', 'ILIKE', $like)
                    ->orWhere('content_en', 'ILIKE', $like);
            });
        }

        $from = $request->query('from');
        if (is_string($from) && $from !== '') {
            $q->whereDate('created_at', '>=', $from);
        }
        $to = $request->query('to');
        if (is_string($to) && $to !== '') {
            $q->whereDate('created_at', '<=', $to);
        }

        $q = $this->applyCityReadRestriction($q);

        $p = $q->paginate($perPage);

        return response()->json([
            'data' => collect($p->items())->map(fn (Register $r) => $this->serializeRegister($r))->values(),
            'meta' => [
                'page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');

        if (!$canViewAny && !$canViewOwn) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $q = Register::with(['user', 'images'])
            ->select('*', DB::raw('ST_Y(location) as lat_from_location'), DB::raw('ST_X(location) as lng_from_location'))
            ->where('id', $id);

        $q = $this->applyCityReadRestriction($q);

        /** @var Register $register */
        $register = $q->firstOrFail();

        $isOwner = (int) $register->user_id === (int) $user->id;

        if ($canViewAny) {
            return response()->json(['data' => $this->serializeRegister($register)], 200);
        }

        if ($isOwner && $canViewOwn) {
            return response()->json(['data' => $this->serializeRegister($register)], 200);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $canCreate = $user->can('create_own_registers') || $user->can('create_registers');
        if (!$canCreate) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $categoryKeys = MetaController::registerCategoryKeys();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'in:' . implode(',', $categoryKeys)],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'address' => ['nullable', 'string', 'max:500'],
            'image' => ['required', 'image', 'max:8192'],
        ]);

        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];

        $cityIdForRegister = $this->assertPointAllowedAndGetCityId($lat, $lng, 'create_any_city_registers');

        $register = new Register();
        $register->user_id = $user->id;

        // City assignment is based on the creator account, not on the picked point.
        $register->city_id = $cityIdForRegister;

        $register->title_pt = $data['title'];
        $register->title_en = $data['title'];
        $register->content_pt = $data['description'];
        $register->content_en = $data['description'];
        $register->category = $data['category'];
        $register->latitude = $lat;
        $register->longitude = $lng;
        $register->address = $data['address'] ?? null;
        $register->save();

        DB::update(
            'UPDATE registers SET location = ST_SetSRID(ST_MakePoint(?::double precision, ?::double precision), 4326) WHERE id = ?',
            [$lng, $lat, $register->id]
        );

        $this->replaceRegisterImages($register, [$request->file('image')]);

        $register->load('images');

        return response()->json(['data' => $this->serializeRegister($register)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $categoryKeys = MetaController::registerCategoryKeys();

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'category' => ['sometimes', 'required', 'string', 'in:' . implode(',', $categoryKeys)],
            'latitude' => ['sometimes', 'required', 'numeric'],
            'longitude' => ['sometimes', 'required', 'numeric'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'image' => ['sometimes', 'image', 'max:8192'],
        ]);

        // If body parsing fails (common with PATCH multipart in PHP) or the client sends an empty update,
        // return a validation error instead of a misleading 200.
        $hasAnyField =
            array_key_exists('title', $data) ||
            array_key_exists('description', $data) ||
            array_key_exists('category', $data) ||
            array_key_exists('latitude', $data) ||
            array_key_exists('longitude', $data) ||
            array_key_exists('address', $data) ||
            $request->hasFile('image');

        if (!$hasAnyField) {
            throw ValidationException::withMessages([
                'update' => __('No fields provided to update.'),
            ]);
        }

        /** @var User $user */
        $user = $request->user();

        $q = Register::query()->where('id', $id);
        $q = $this->applyCityWriteRestriction($q, 'edit_any_city_registers');
        /** @var Register $register */
        $register = $q->firstOrFail();

        $isOwner = (int) $register->user_id === (int) $user->id;

        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');
        $canEditAny = $user->can('edit_any_registers') || $user->can('edit_all_registers');
        $canEditOwn = $user->can('edit_own_registers');

        if (!(($canViewAny && $canEditAny) || ($isOwner && $canViewOwn && $canEditOwn))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (array_key_exists('title', $data)) {
            $register->title_pt = $data['title'];
            $register->title_en = $data['title'];
        }
        if (array_key_exists('description', $data)) {
            $register->content_pt = $data['description'];
            $register->content_en = $data['description'];
        }
        if (array_key_exists('category', $data)) {
            $register->category = $data['category'];
        }

        if (array_key_exists('address', $data)) {
            $register->address = $data['address'];
        }

        $hasLat = array_key_exists('latitude', $data);
        $hasLng = array_key_exists('longitude', $data);
        if ($hasLat xor $hasLng) {
            throw ValidationException::withMessages([
                'latitude' => __('Latitude and longitude must be provided together.'),
            ]);
        }

        if ($hasLat && $hasLng) {
            $lat = (float) $data['latitude'];
            $lng = (float) $data['longitude'];

            $this->assertPointAllowedForExistingRegisterOrFail($register, $lat, $lng);

            $register->latitude = $lat;
            $register->longitude = $lng;

            DB::update(
                'UPDATE registers SET location = ST_SetSRID(ST_MakePoint(?::double precision, ?::double precision), 4326) WHERE id = ?',
                [$lng, $lat, $register->id]
            );
        }

        if ($request->hasFile('image')) {
            $this->replaceRegisterImages($register, [$request->file('image')]);
        }

        $register->save();
        $register->load('images');

        return response()->json(['data' => $this->serializeRegister($register)], 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $q = Register::query()->where('id', $id);
        $q = $this->applyCityWriteRestriction($q, 'delete_any_city_registers');
        /** @var Register $register */
        $register = $q->firstOrFail();

        $isOwner = (int) $register->user_id === (int) $user->id;

        $canViewAny = $user->can('view_any_registers') || $user->can('view_all_registers');
        $canViewOwn = $user->can('view_own_registers');
        $canDeleteAny = $user->can('delete_any_registers') || $user->can('delete_registers');
        $canDeleteOwn = $user->can('delete_own_registers');

        if (!(($canViewAny && $canDeleteAny) || ($isOwner && $canViewOwn && $canDeleteOwn))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $register->delete();

        return response()->json(null, 204);
    }

    private function serializeRegister(Register $r): array
    {
        $lat = $r->lat_from_location ?? $r->latitude;
        $lng = $r->lng_from_location ?? $r->longitude;

        $images = [];

        try {
            if ($r->relationLoaded('images') && $r->images) {
                $images = $r->images
                    ->map(fn ($img) => $img->url())
                    ->filter()
                    ->values()
                    ->all();
            }
        } catch (\Throwable $e) {
            $images = [];
        }

        return [
            'id' => $r->id,
            'title' => $r->title_pt,
            'description' => $r->content_pt,
            'category' => $r->category,
            'latitude' => $lat,
            'longitude' => $lng,
            'address' => $r->address,
            'city_id' => $r->city_id,
            'user_id' => $r->user_id,
            'author_name' => $r->user?->name,
            'image_url' => $r->image_url(),
            'images' => $images,
            'created_at' => optional($r->created_at)->toISOString(),
            'updated_at' => optional($r->updated_at)->toISOString(),
        ];
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

    private function applyCityReadRestriction($query)
    {
        $city = $this->getCityForReadRestriction();
        if (!$city) {
            return $query;
        }

        $pointExpr = "COALESCE(location, CASE WHEN longitude IS NOT NULL AND latitude IS NOT NULL THEN ST_SetSRID(ST_MakePoint(longitude, latitude), 4326) END)";

        return $query->whereRaw(
            "ST_DWithin(($pointExpr)::geography, ST_SetSRID(ST_MakePoint(?::double precision, ?::double precision), 4326)::geography, ?)",
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
            "ST_DWithin(($pointExpr)::geography, ST_SetSRID(ST_MakePoint(?::double precision, ?::double precision), 4326)::geography, ?)",
            [(float) $city->center_lng, (float) $city->center_lat, (int) $city->radius_m]
        );
    }

    private function assertPointAllowedAndGetCityId(?float $lat, ?float $lng, string $anyCityPermission): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $userCityId = $user->city_id !== null ? (int) $user->city_id : null;
        if (!$userCityId) {
            return null;
        }

        if ($lat === null || $lng === null || !is_finite($lat) || !is_finite($lng)) {
            throw ValidationException::withMessages([
                'latitude' => __('Location is required and must be inside your city.'),
            ]);
        }

        $city = null;
        try {
            $city = City::query()->where('id', $userCityId)->first();
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
            'ST_SetSRID(ST_MakePoint(?::double precision, ?::double precision), 4326)::geography,' .
            'ST_SetSRID(ST_MakePoint(?::double precision, ?::double precision), 4326)::geography,' .
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
        if ($registerCityId > 0) {
            $this->assertPointInsideCityIdOrFail($registerCityId, (float) $lat, (float) $lng);
            return;
        }

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
            'ST_SetSRID(ST_MakePoint(?::double precision, ?::double precision), 4326)::geography,' .
            'ST_SetSRID(ST_MakePoint(?::double precision, ?::double precision), 4326)::geography,' .
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
                'ST_SetSRID(ST_MakePoint(?::double precision, ?::double precision), 4326)::geography,' .
                'ST_SetSRID(ST_MakePoint(center_lng, center_lat), 4326)::geography,' .
                'radius_m' .
                ') ' .
                'ORDER BY ST_Distance(' .
                'ST_SetSRID(ST_MakePoint(?::double precision, ?::double precision), 4326)::geography,' .
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

        $validFiles = array_slice($validFiles, 0, 3);

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

        if ($firstPath) {
            try {
                $register->image = Storage::disk($disk)->url($firstPath);
            } catch (\Throwable $e) {
                $register->image = 'storage/' . $firstPath;
            }
            $register->save();
        }
    }
}
