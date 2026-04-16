<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Register;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $user = Auth::user();
        if (!$user || !($user->can('view_page_statistics') || $user->can('admin'))) {
            abort(403);
        }

        $totalRegisters = Register::count();

        $categoryCounts = Register::query()
            ->select('category', DB::raw('count(*) as total'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $categoryLabels = $categoryCounts->map(function ($row) {
            $key = (string) $row->category;
            $translated = __('pages.' . $key);
            return $translated === ('pages.' . $key) ? $key : $translated;
        })->values();

        $topCategory = $categoryCounts->first();
        $topCategoryLabel = null;
        if ($topCategory) {
            $key = (string) $topCategory->category;
            $translated = __('pages.' . $key);
            $topCategoryLabel = $translated === ('pages.' . $key) ? $key : $translated;
        }

        $topCity = City::query()
            ->select('cities.id', 'cities.name', DB::raw('count(registers.id) as total'))
            ->join('registers', 'registers.city_id', '=', 'cities.id')
            ->groupBy('cities.id', 'cities.name')
            ->orderByDesc('total')
            ->first();

        $topUsers = User::query()
            ->select('users.id', 'users.name', 'users.email', DB::raw('count(registers.id) as total'))
            ->join('registers', 'registers.user_id', '=', 'users.id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $usersPerCity = City::query()
            ->leftJoin('users', 'users.city_id', '=', 'cities.id')
            ->select('cities.id', 'cities.name', DB::raw('count(users.id) as total'))
            ->groupBy('cities.id', 'cities.name')
            ->orderByDesc('total')
            ->get();

        $usersWithoutCity = User::whereNull('city_id')->count();

        $categoryChart = [
            'labels' => $categoryLabels,
            'data' => $categoryCounts->pluck('total')->values(),
        ];

        $usersCityLabels = $usersPerCity->pluck('name')->values();
        $usersCityData = $usersPerCity->pluck('total')->values();
        if ($usersWithoutCity > 0) {
            $usersCityLabels = $usersCityLabels->push(__('pages.users_without_city'));
            $usersCityData = $usersCityData->push($usersWithoutCity);
        }

        $usersCityChart = [
            'labels' => $usersCityLabels,
            'data' => $usersCityData,
        ];

        return view('settings.statistics.index', compact(
            'totalRegisters',
            'topCategory',
            'topCategoryLabel',
            'topCity',
            'topUsers',
            'usersPerCity',
            'usersWithoutCity',
            'categoryChart',
            'usersCityChart'
        ));
    }
}
