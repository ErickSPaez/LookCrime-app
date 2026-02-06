@extends('layouts.legacy')

@section('pagestyles')
<style>
    #city-map{height:520px;width:100%;border-radius:6px;border:1px solid rgba(0,0,0,0.08);}
    .lc-help{font-size:0.9rem;color:#555;margin-top:6px;}
    .lc-center-icon{background:transparent;border:0;}
    .lc-crosshair{width:18px;height:18px;position:relative;pointer-events:none;opacity:0.95;filter:drop-shadow(0 1px 2px rgba(0,0,0,0.35));}
    .lc-crosshair::before,.lc-crosshair::after{content:'';position:absolute;left:50%;top:50%;background:rgb(123,30,33);transform:translate(-50%,-50%);border-radius:1px;}
    .lc-crosshair::before{width:18px;height:2px;}
    .lc-crosshair::after{width:2px;height:18px;}
</style>
@endsection

@section('conteudo')
<div class="main-website-interior user-management-panel">
    <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">{{ __('pages.edit_city') }}</h1>
    <hr class="interior-title-line register-line-title" style="margin-bottom:10px;">
    <div style="display:flex;justify-content:flex-end;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 18px 0;">
        <a class="btn btn-lookcrim-white btn-sm" href="{{ route('settings.city.index') }}">{{ __('pages.back') }}</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">
        <form method="POST" action="{{ route('settings.city.update', $city->slug) }}">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group" style="width:100%">
                    <label class="form-label">{{ __('pages.city_name') }}</label>
                    <input class="form-input" type="text" name="name" value="{{ old('name', $city->name) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">{{ __('pages.city_radius_km') }}</label>
                    <input class="form-input" id="radius-km" type="number" name="radius_km" step="0.1" min="0.1" value="{{ old('radius_km', round(($city->radius_m ?? 0) / 1000, 1)) }}" required>
                    <div class="lc-help">{{ __('pages.city_radius_help') }}</div>
                </div>
                <div class="form-group" style="flex:1">
                    <label class="form-label">{{ __('buttons.created-at') }}</label>
                    <input class="form-input" type="text" value="{{ $city->created_at?->format('Y-m-d') ?? '-' }}" readonly>
                </div>
                <div class="form-group" style="flex:1">
                    <label class="form-label">{{ __('pages.city_center') }}</label>
                    <div class="lc-help">{{ __('pages.city_center_help') }}</div>
                    <input type="hidden" name="center_lat" id="center-lat" value="{{ old('center_lat', $city->center_lat) }}">
                    <input type="hidden" name="center_lng" id="center-lng" value="{{ old('center_lng', $city->center_lng) }}">
                </div>
            </div>

            <div id="city-map"></div>

            <div class="form-actions" style="margin-top:14px;">
                <button class="btn-lookcrim" type="submit">{{ __('pages.save') }}</button>
                <a class="btn-secondary" href="{{ route('settings.city.index') }}">{{ __('pages.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('pagescripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const lat0 = parseFloat(document.getElementById('center-lat').value);
    const lng0 = parseFloat(document.getElementById('center-lng').value);
    const defaultCenter = (isFinite(lat0) && isFinite(lng0)) ? [lat0, lng0] : [41.1579, -8.6291];

    const map = L.map('city-map').setView(defaultCenter, 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const centerIcon = L.divIcon({
        className: 'lc-center-icon',
        html: '<div class="lc-crosshair" aria-hidden="true"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 9],
    });

    let centerMarker = null;
    let circle = null;

    function getRadiusM(){
        const km = parseFloat(document.getElementById('radius-km').value) || 0;
        return Math.round(km * 1000);
    }

    function redraw(){
        const lat = parseFloat(document.getElementById('center-lat').value);
        const lng = parseFloat(document.getElementById('center-lng').value);
        if(!isFinite(lat) || !isFinite(lng)) return;

        const r = getRadiusM();
        if(centerMarker){ centerMarker.setLatLng([lat,lng]); }
        else { centerMarker = L.marker([lat,lng], { icon: centerIcon, interactive:false }).addTo(map); }

        if(circle){ map.removeLayer(circle); circle = null; }
        if(r > 0){
            circle = L.circle([lat,lng], { radius: r, color: 'rgb(123,30,33)', weight: 2, fillOpacity: 0.06, interactive:false }).addTo(map);
            map.fitBounds(circle.getBounds(), { padding: [20, 20] });
        }
    }

    redraw();

    map.on('click', function(e){
        document.getElementById('center-lat').value = e.latlng.lat;
        document.getElementById('center-lng').value = e.latlng.lng;
        redraw();
    });

    document.getElementById('radius-km').addEventListener('input', function(){
        redraw();
    });
});
</script>
@endsection
