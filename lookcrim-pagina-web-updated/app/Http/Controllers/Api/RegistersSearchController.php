<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistersSearchController extends Controller
{
    /**
     * Search registers by radius (meters) or bbox with optional filters.
     * Expects JSON: { lat, lng, radius_m, bbox: [lngMin, latMin, lngMax, latMax], types: [], limit }
     */
    public function search(Request $req)
    {
        $lat = $req->input('lat');
        $lng = $req->input('lng');
        $radius = (int) $req->input('radius_m', 0);
        $bbox = $req->input('bbox');
        $types = $req->input('types', []);
        $limit = min((int)$req->input('limit', 200), 1000);

        // Basic validation
        if ($radius > 0 && (!is_numeric($lat) || !is_numeric($lng))) {
            return response()->json(['error' => 'lat and lng required when using radius'], 422);
        }

        if ($bbox && (!is_array($bbox) || count($bbox) !== 4)) {
            return response()->json(['error' => 'bbox must be array [lngMin, latMin, lngMax, latMax]'], 422);
        }

        $bindings = [];
        $whereClauses = [];

        if ($radius > 0 && is_numeric($lat) && is_numeric($lng)) {
            $whereClauses[] = "ST_DWithin(location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ? )";
            $bindings[] = $lng;
            $bindings[] = $lat;
            $bindings[] = $radius;
        } elseif (is_array($bbox) && count($bbox) === 4) {
            // bbox: [lngMin, latMin, lngMax, latMax]
            $whereClauses[] = "location && ST_MakeEnvelope(?, ?, ?, ?, 4326)";
            $bindings[] = $bbox[0];
            $bindings[] = $bbox[1];
            $bindings[] = $bbox[2];
            $bindings[] = $bbox[3];
        }

        if (!empty($types) && is_array($types)) {
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            // filter by category column
            $whereClauses[] = "category IN ($placeholders)";
            foreach ($types as $t) $bindings[] = $t;
        }

        $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

        // choose localized title column (model stores title_en/title_pt)
        $locale = app()->getLocale();
        $titleCol = $locale === 'en' ? 'title_en' : 'title_pt';
        $sql = "SELECT id, {$titleCol} AS title, category, ST_AsGeoJSON(location) AS geo, created_at FROM registers $whereSql ";

        if ($radius > 0 && is_numeric($lat) && is_numeric($lng)) {
            $sql .= " ORDER BY ST_Distance(location::geography, ST_SetSRID(ST_MakePoint(?, ?),4326)::geography) ";
            $bindings[] = $lng;
            $bindings[] = $lat;
        } else {
            $sql .= " ORDER BY created_at DESC ";
        }

        $sql .= " LIMIT ?";
        $bindings[] = $limit;

        try {
            $rows = DB::select($sql, $bindings);
        } catch (\Throwable $e) {
            \Log::error('RegistersSearchController search error: '.$e->getMessage(), ['sql'=>$sql,'bindings'=>$bindings]);
            return response()->json(['error' => 'Internal query error'], 500);
        }

        $features = array_map(function($r){
            return [
                'type' => 'Feature',
                'geometry' => json_decode($r->geo, true),
                'properties' => [
                    'id' => $r->id,
                    'title' => $r->title,
                    'category' => $r->category,
                    'created_at' => $r->created_at,
                ]
            ];
        }, $rows);

        return response()->json(['type'=>'FeatureCollection','features'=>$features]);
    }
}
