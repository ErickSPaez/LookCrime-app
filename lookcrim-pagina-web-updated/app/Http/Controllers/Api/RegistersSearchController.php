<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $userId = $req->input('user_id');
        $fromDate = $req->input('from_date');
        $toDate = $req->input('to_date');
        $hasImage = $req->input('has_image'); // expected: 'with' | 'without' | null
        $order = $req->input('order'); // expected: 'newest'|'oldest' or null
        $q = $req->input('q'); // full-text / substring search

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

        // filter by user (only if the column exists in the table)
        if(!empty($userId)){
            try {
                if (Schema::hasColumn('registers', 'user_id')) {
                    $whereClauses[] = "user_id = ?";
                    $bindings[] = $userId;
                } else {
                    // column not present - log and ignore the user filter
                    \Log::warning('RegistersSearchController: user_id filter requested but column does not exist on registers table');
                }
            } catch (\Throwable $e) {
                // In case schema inspection fails, log and continue without user filter
                \Log::warning('RegistersSearchController: error checking user_id column: '.$e->getMessage());
            }
        }

        // created_at range
        if(!empty($fromDate)){
            $whereClauses[] = "created_at >= ?";
            $bindings[] = $fromDate;
        }
        if(!empty($toDate)){
            $whereClauses[] = "created_at <= ?";
            $bindings[] = $toDate;
        }

        // has image filter
        if($hasImage === 'with'){
            $whereClauses[] = "image IS NOT NULL AND image <> ''";
        } elseif($hasImage === 'without'){
            $whereClauses[] = "(image IS NULL OR image = '')";
        }

        // text search (simple ILIKE search across title/content)
        if(!empty($q) && is_string($q)){
            $qLike = '%' . str_replace('%','\%',$q) . '%';
            $whereClauses[] = "(title_en ILIKE ? OR content_en ILIKE ? OR title_pt ILIKE ? OR content_pt ILIKE ?)";
            $bindings[] = $qLike; $bindings[] = $qLike; $bindings[] = $qLike; $bindings[] = $qLike;
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
            // allow overriding order by created_at
            if($order === 'oldest'){
                $sql .= " ORDER BY created_at ASC ";
            } else {
                $sql .= " ORDER BY created_at DESC ";
            }
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
