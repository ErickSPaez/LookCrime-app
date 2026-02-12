<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    private const REGISTER_CATEGORIES = [
        'robo',
        'poco_iluminacion',
        'zona_insegura',
        'zona_transitada',
        'construccion',
        'otro',
    ];

    public static function registerCategoryKeys(): array
    {
        return self::REGISTER_CATEGORIES;
    }

    public function registerCategories(Request $request): JsonResponse
    {
        $lang = $request->query('lang');
        if (is_string($lang) && in_array($lang, ['en', 'pt'], true)) {
            app()->setLocale($lang);
        }

        $items = array_map(function (string $key) {
            return [
                'key' => $key,
                'label' => trans('pages.' . $key),
            ];
        }, self::REGISTER_CATEGORIES);

        return response()->json([
            'data' => $items,
        ]);
    }
}
