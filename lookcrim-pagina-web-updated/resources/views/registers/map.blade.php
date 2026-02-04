@extends('layouts.legacy')

@section('titulo_browser', __('pages.map_title'))

@section('pagestyles')
    <style>
        #publications-map { height: 600px; width: 100%; margin-bottom: 1rem; }
        .leaflet-popup-content img { max-width: 200px; height: auto; display:block; margin-bottom:6px; }
        .map-legend { background: #fff; padding: 8px 10px; border-radius:4px; box-shadow: 0 1px 4px rgba(0,0,0,0.3); }
        .map-legend .item { display:flex; align-items:center; margin-bottom:6px; font-size:0.9rem; }
        .map-legend .swatch { width:14px; height:14px; border-radius:3px; display:inline-block; margin-right:8px; border:1px solid #3333; }

        /* Filters panel (visual only) */
        .lc-map-panel {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 12px;
            margin-bottom: 14px;
        }
        .lc-map-panel-head {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 10px;
        }
        .lc-map-panel-head .view-toggle-wrap { margin-left: auto; }

        #map-filters {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .lc-map-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
            align-items: center;
        }
        .lc-map-row .lc-field {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .lc-types-field { align-items: flex-start; }
        .lc-label { font-size: 0.9rem; font-weight: 600; color: #333; }
        .lc-small { font-size: 0.9rem; }

        .lc-map-actions { margin-left: auto; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .lc-map-info { margin-left: auto; font-size: 0.9rem; color: #666; }

        /* Hide select-location button while in select mode (keep spacing) */
        .lc-btn-invisible {
            visibility: hidden;
        }

        /* Make type checkboxes wrap nicely */
        .lc-types-wrap {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
        }
        #filter-types-container {
            display: grid;
            grid-template-columns: repeat(3, max-content);
            column-gap: 14px;
            row-gap: 6px;
            align-items: center;
            min-width: 180px;
        }
        #filter-types-container label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 0;
            white-space: nowrap;
            font-size: 0.9rem;
        }

        /* Secondary button fallback (since markup uses class="btn-secondary" without bootstrap "btn") */
        .btn-secondary {
            display: inline-block;
            padding: 6px 10px;
            min-height: 34px;
            background: #6c757d;
            color: #fff;
            border: 1px solid #5a6268;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .btn-secondary:hover {
            background: rgb(123,30,33);
            border-color: rgb(123,30,33);
            color: #fff;
        }
        .btn-secondary:focus { outline: none; box-shadow: 0 0 0 0.2rem rgba(108,117,125,0.25); }

        @media (max-width: 768px) {
            .lc-map-actions, .lc-map-info { margin-left: 0; }
            #filter-types-container { grid-template-columns: repeat(2, max-content); }
        }
    </style>
@endsection

@section('conteudo')
<div class="main-website-interior">
    <h1 class="font-title-for-customization interior-title">{{ __('pages.map_title') }}</h1>
    <hr class="interior-title-line">


    <div class="lc-map-panel">
        <div class="lc-map-panel-head">
            <div class="view-toggle-wrap">
                @include('registers.partials.view-toggle')
            </div>
        </div>

        <div id="map-filters">
            <div class="lc-map-row">
                <div class="lc-field">
                    <span class="lc-label">{{ __('pages.radius_km') }}:</span>
                    <input id="filter-radius" type="number" step="0.5" min="0" value="5" style="width:90px">
                </div>

                <div class="lc-field">
                    <span class="lc-label">{{ __('pages.users') }}:</span>
                    <select id="filter-user" style="min-width:180px">
                        <option value="">{{ __('pages.all_users') }}</option>
                        @if(isset($users) && count($users))
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name ?? $u->email ?? ('User '+$u->id) }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="lc-field">
                    <span class="lc-label">{{ __('pages.filter_by_time') }}:</span>
                    <input id="filter-from" type="date">
                    <span class="lc-small">—</span>
                    <input id="filter-to" type="date">
                </div>
            </div>

            <div class="lc-map-row">
                <div class="lc-field lc-types-field">
                    <span class="lc-label">{{ __('pages.types') }}:</span>
                    <div class="lc-types-wrap">
                        <div id="filter-types-container">
                            @foreach($categoryLabels as $k => $v)
                                <label><input class="filter-type" type="checkbox" value="{{ $k }}"> {{ $v }}</label>
                            @endforeach
                        </div>
                        <button id="select-all-types" type="button" class="btn-secondary">{{ __('pages.select_all') }}</button>
                    </div>
                </div>
                <div class="lc-map-actions">
                    <button id="btn-select-location" type="button" class="btn-secondary" aria-label="{{ __('pages.select_location') }}">
                        {{ __('pages.select_location') }}
                    </button>
                    <button id="apply-filters" class="btn-lookcrim">{{ __('pages.apply') }}</button>
                    <button id="clear-filters" class="btn-secondary">{{ __('pages.clear') }}</button>
                </div>
            </div>

            <div class="lc-map-row">
                <div class="lc-field">
                    <label class="lc-small" style="margin:0">
                        <input type="checkbox" id="use-bbox"> {{ __('pages.search_in_map_view') }}
                    </label>
                    <label class="lc-small" style="margin:0">
                        <input type="checkbox" id="use-my-location"> {{ __('pages.use_my_location') }}
                    </label>
                </div>

                <div class="lc-map-info" id="map-info"></div>
            </div>
        </div>
    </div>

    <div id="publications-map"></div>
    <div id="publications-legend" style="display:none"></div>
</div>

@section('pagescripts')
<script>
    @php
        $__translations = [
            'map_title' => __('pages.map_title'),
            'radius_km' => __('pages.radius_km'),
            'types' => __('pages.types'),
            'select_all' => __('pages.select_all'),
            'select_location' => __('pages.select_location'),
            'select_location_mode' => __('pages.select_location_mode'),
            'search_in_map_view' => __('pages.search_in_map_view'),
            'use_my_location' => __('pages.use_my_location'),
            'apply' => __('pages.apply'),
            'clear' => __('pages.clear'),
            'categories' => __('pages.categories'),
            'you_are_here' => __('pages.you_are_here'),
            'confirm_use_location' => __('pages.confirm_use_location'),
            'searching' => __('pages.searching'),
            'no_publications' => __('pages.no_publications'),
            'error_network' => __('pages.error_network'),
            'results_suffix' => __('pages.results_suffix'),
            'porto' => __('pages.porto'),
            'braga' => __('pages.braga'),
            'publication' => __('pages.publication'),
            'server_error' => __('pages.server_error'),
            'users' => __('pages.users'),
            'all_users' => __('pages.all_users'),
            'filter_by_time' => __('pages.filter_by_time'),
            'from_date' => __('pages.from_date'),
            'to_date' => __('pages.to_date')
        ];
    @endphp
    const TRANSLATIONS = {!! json_encode($__translations) !!};

document.addEventListener('DOMContentLoaded', function(){
    // publications data prepared in controller (initial set)
    const publications = @json($mapData);

    const defaultCenter = (publications.length && publications[0].lat) ? [publications[0].lat, publications[0].lng] : [40.4168, -3.7038];
    const map = L.map('publications-map').setView(defaultCenter, publications.length ? 12 : 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Category -> color mapping
    const categoryColors = {
        'robo': '#e53935',              // rojo
        'poco_iluminacion': '#ffb300',  // amarillo
        'zona_insegura': '#6a1b9a',     // morado
        'zona_transitada': '#1e88e5',   // azul
        'construccion': '#fb8c00',      // naranja
        'otro': '#757575'               // gris
    };

    const categoryLabels = @json($categoryLabels);

    // Add markers as circleMarkers colored by category
    // we will manage markers in a layer group so we can replace them on searches
    const markersLayer = L.layerGroup().addTo(map);
    let searchCircle = null;
    let userLocationMarker = null;
    let centerMarker = null;
    let currentCenter = null; // store actual center (could be user location)
    function addPublicationMarker(pub){
        if(!pub.lat || !pub.lng) return null;
        const cat = pub.category || (pub.properties && pub.properties.category) || '';
        const color = categoryColors[cat] || '#2e7d32';
        const marker = L.circleMarker([pub.lat, pub.lng], {
            radius: 8,
            color: '#333',
            weight: 1,
            fillColor: color,
            fillOpacity: 1
        });

        const title = pub.title || (pub.properties && pub.properties.title) || TRANSLATIONS.publication;
        const detailUrl = pub.url || (pub.properties && pub.properties.url) || '#';
        const imageUrl = pub.image || (pub.properties && pub.properties.image) || null;

        let popupHtml = '<div style="min-width:180px">';
        if(imageUrl) popupHtml += '<a href="'+detailUrl+'"><img src="'+imageUrl+'" alt="'+title+'"></a>';
        popupHtml += '<div><a href="'+detailUrl+'"><strong>'+ title +'</strong></a></div>';
        if(cat) popupHtml += '<div style="margin-top:6px;font-size:0.9rem;color:#444"><em>'+ (categoryLabels[cat] || cat) +'</em></div>';
        popupHtml += '</div>';
        marker.bindPopup(popupHtml);
        markersLayer.addLayer(marker);
    }

    publications.forEach(function(pub){
        // legacy initial data may come with lat/lng fields
        if(pub.lat && pub.lng){
            addPublicationMarker(pub);
        } else if(pub.location){
            // if initial data included GeoJSON-like geometry
            const coords = pub.location.coordinates || null;
            if(coords) addPublicationMarker({lat: coords[1], lng: coords[0], title: pub.title, category: pub.category, image: pub.image, url: pub.url});
        }
    });


    // Add legend control
    const legend = L.control({position: 'topright'});
    legend.onAdd = function () {
        const div = L.DomUtil.create('div', 'map-legend');
        const legendTitle = TRANSLATIONS.categories;
        let html = '<strong>'+legendTitle+'</strong><div style="margin-top:6px">';
        Object.keys(categoryColors).forEach(function(k){
            const color = categoryColors[k];
            const label = categoryLabels[k] || k;
            html += '<div class="item"><span class="swatch" style="background:'+color+'"></span>' + label + '</div>';
        });
        html += '</div>';
        div.innerHTML = html;
        return div;
    };
    legend.addTo(map);

    // Ask user permission on open if checkbox is enabled
    (function promptForLocationOnOpen(){
        try{
            const ask = document.getElementById('use-my-location');
            if(ask && ask.checked){
                if(navigator.geolocation){
                    navigator.geolocation.getCurrentPosition(function(pos){
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        map.setView([lat,lng], 14);
                        currentCenter = {lat: lat, lng: lng};
                        if(userLocationMarker) map.removeLayer(userLocationMarker);
                        userLocationMarker = L.marker([lat,lng]).addTo(map).bindPopup(TRANSLATIONS.you_are_here).openPopup();
                        // DO NOT draw a circle or run a search automatically — user will set radius and click Apply
                    }, function(err){
                        console.warn('Geolocation denied or unavailable', err);
                    });
                }
            }
        }catch(e){ console.error(e); }
    })();

    // Additionally, offer a prompt when page opens so users can accept immediately
    try{
        if(navigator.geolocation){
            const want = confirm(TRANSLATIONS.confirm_use_location);
            if(want){
                document.getElementById('use-my-location').checked = true;
                navigator.geolocation.getCurrentPosition(function(pos){
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    map.setView([lat,lng], 14);
                    currentCenter = {lat: lat, lng: lng};
                    if(userLocationMarker) map.removeLayer(userLocationMarker);
                    userLocationMarker = L.marker([lat,lng]).addTo(map).bindPopup(TRANSLATIONS.you_are_here).openPopup();
                    // DO NOT draw circle or run search automatically — wait for user action
                }, function(err){
                    console.warn('Geolocation denied or unavailable', err);
                });
            }
        }
    }catch(e){ console.error(e); }

    // helper to draw/update circle overlay
    function updateSearchCircle(){
        const radiusKm = parseFloat(document.getElementById('filter-radius').value) || 0;
        const meters = Math.round(radiusKm * 1000);
        // Only draw a circle if the user explicitly selected a center (currentCenter)
        if(!currentCenter){
            if(searchCircle){ map.removeLayer(searchCircle); searchCircle = null; }
            return;
        }
        const center = currentCenter;
        if(searchCircle) map.removeLayer(searchCircle);
        if(meters > 0){
            searchCircle = L.circle([center.lat, center.lng], { radius: meters, color: '#d9534f', weight: 1, fillOpacity: 0.08, interactive: false }).addTo(map);
        }
        // update or add center marker visual
        if(centerMarker){ map.removeLayer(centerMarker); centerMarker = null; }
        centerMarker = L.marker([center.lat, center.lng], { opacity: 0.0, interactive: false }).addTo(map);
    }

    // update circle when radius input changes or map moved (if using center)
    document.getElementById('filter-radius').addEventListener('input', function(){ if(currentCenter) updateSearchCircle(); });
    // do not clear currentCenter on simple map move; only update circle when a center exists
    map.on('move', function(){ if(currentCenter) updateSearchCircle(); });

    // select-all types (checkboxes)
    document.getElementById('select-all-types').addEventListener('click', function(){
        const checks = document.querySelectorAll('#filter-types-container input.filter-type');
        checks.forEach(c=>c.checked = true);
    });

    // Predefined city buttons (Porto, Braga)
    const portoBtn = L.control({position: 'topleft'});
    portoBtn.onAdd = function(){
        const div = L.DomUtil.create('div', 'leaflet-bar');
        div.style.padding = '6px';
        div.innerHTML = '<button id="btn-porto" class="btn-lookcrim" style="font-size:0.85rem">'+TRANSLATIONS.porto+'</button> <button id="btn-braga" class="btn-lookcrim" style="font-size:0.85rem">'+TRANSLATIONS.braga+'</button>';
        return div;
    };
    portoBtn.addTo(map);
    document.getElementById('btn-porto').addEventListener('click', function(){
        // Porto center
        currentCenter = { lat: 41.1579, lng: -8.6291 };
        document.getElementById('filter-radius').value = 25;
        map.setView([currentCenter.lat, currentCenter.lng], 12);
        updateSearchCircle();
        performSearch();
    });
    document.getElementById('btn-braga').addEventListener('click', function(){
        // Braga center
        currentCenter = { lat: 41.5454, lng: -8.4265 };
        document.getElementById('filter-radius').value = 25;
        map.setView([currentCenter.lat, currentCenter.lng], 12);
        updateSearchCircle();
        performSearch();
    });

    // allow user to pick a center by clicking the map
    let selectMode = false; // when true, clicks set center; when false, clicks interact with markers
    const selectBtn = document.getElementById('btn-select-location');
    const mapInfoEl = document.getElementById('map-info');
    let previousMapInfoText = '';
    function setSelectMode(enabled){
        selectMode = !!enabled;
        if(selectMode){
            // Keep original button text; hide button while user selects on the map
            const lbl = selectBtn.querySelector('.btn-label');
            if(lbl) lbl.textContent = TRANSLATIONS.select_location; else selectBtn.textContent = TRANSLATIONS.select_location;
            selectBtn.classList.add('lc-btn-invisible');
            selectBtn.classList.add('active');

            // Show the hint on the right side (bottom) of the panel
            previousMapInfoText = mapInfoEl ? (mapInfoEl.textContent || '') : '';
            if(mapInfoEl) mapInfoEl.textContent = TRANSLATIONS.select_location_mode;
            map.getContainer().style.cursor = 'crosshair';
        } else {
            const lbl2 = selectBtn.querySelector('.btn-label');
            if(lbl2) lbl2.textContent = TRANSLATIONS.select_location; else selectBtn.textContent = TRANSLATIONS.select_location;
            selectBtn.classList.remove('lc-btn-invisible');
            selectBtn.classList.remove('active');

            // Restore previous info text if we were showing the select hint
            if(mapInfoEl && mapInfoEl.textContent === TRANSLATIONS.select_location_mode){
                mapInfoEl.textContent = previousMapInfoText;
            }
            map.getContainer().style.cursor = '';
        }
    }
    selectBtn.addEventListener('click', function(){
        // toggle selection mode
        setSelectMode(!selectMode);
    });

    map.on('click', function(e){
        if(!selectMode) return; // only act on clicks when in select mode
        currentCenter = { lat: e.latlng.lat, lng: e.latlng.lng };
        if(userLocationMarker){ map.removeLayer(userLocationMarker); userLocationMarker = null; }
        if(centerMarker){ map.removeLayer(centerMarker); centerMarker = null; }
        centerMarker = L.marker([currentCenter.lat, currentCenter.lng]).addTo(map);
        // draw circle if radius > 0
        updateSearchCircle();
        // once a center is selected, exit select mode so marker clicks behave normally
        setSelectMode(false);
    });

    // Search helper: call API and render GeoJSON results
    async function performSearch(){
        const useBbox = document.getElementById('use-bbox').checked;
        const radiusKm = parseFloat(document.getElementById('filter-radius').value) || 0;
        const checked = Array.from(document.querySelectorAll('#filter-types-container input.filter-type:checked'));
        const selected = checked.map(c=>c.value);
        // user filter
        const userEl = document.getElementById('filter-user');
        const selectedUser = userEl && userEl.value ? userEl.value : null;
        // time filters (YYYY-MM-DD)
        const fromDate = (document.getElementById('filter-from') && document.getElementById('filter-from').value) || null;
        const toDate = (document.getElementById('filter-to') && document.getElementById('filter-to').value) || null;

        const payload = {};
        if(useBbox){
            const b = map.getBounds();
            payload.bbox = [b.getWest(), b.getSouth(), b.getEast(), b.getNorth()];
            // remove circle if any
            if(searchCircle) { map.removeLayer(searchCircle); searchCircle = null; }
        } else if(radiusKm > 0){
            const c = currentCenter || map.getCenter();
            payload.lat = c.lat;
            payload.lng = c.lng;
            payload.radius_m = Math.round(radiusKm * 1000);
            // update visual circle to reflect center/radius
            updateSearchCircle();
        } else {
            // radius 0 -> search whole map
            const b = map.getBounds();
            payload.bbox = [b.getWest(), b.getSouth(), b.getEast(), b.getNorth()];
        }

        if(selected.length) payload.types = selected;
        if(selectedUser) payload.user_id = selectedUser;
        if(fromDate) payload.from_date = fromDate;
        if(toDate) payload.to_date = toDate;
        payload.limit = 500;

        document.getElementById('map-info').textContent = TRANSLATIONS.searching;
        // draw circle before query
        updateSearchCircle();
        try{
            const res = await fetch('/api/registers/search-radius', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            // clear existing markers
            markersLayer.clearLayers();
            let count = 0;
            if(!res.ok){
                const msg = json && json.error ? json.error : (json && json.message ? json.message : TRANSLATIONS.server_error);
                document.getElementById('map-info').textContent = 'Error: ' + msg;
                return;
            }
            if(json && json.features){
                json.features.forEach(function(f){
                    const coords = f.geometry && f.geometry.coordinates;
                    if(!coords) return;
                    const lon = coords[0], lat = coords[1];
                    addPublicationMarker({
                        lat: lat,
                        lng: lon,
                        title: f.properties.title,
                        category: f.properties.category,
                        image: f.properties.image,
                        url: f.properties.url,
                    });
                    count++;
                });
            }
            if(count === 0){
                document.getElementById('map-info').textContent = TRANSLATIONS.no_publications;
            } else {
                document.getElementById('map-info').textContent = count + ' ' + TRANSLATIONS.results_suffix;
            }
        }catch(err){
            console.error(err);
            document.getElementById('map-info').textContent = TRANSLATIONS.error_network;
        }
    }

    document.getElementById('apply-filters').addEventListener('click', function(){
        performSearch();
    });

    document.getElementById('clear-filters').addEventListener('click', function(){
        // clear selection and restore initial markers
        document.getElementById('filter-radius').value = 5;
        const checks = document.querySelectorAll('#filter-types-container input.filter-type');
        checks.forEach(c=>c.checked = false);
        document.getElementById('use-bbox').checked = false;
        // clear user/time filters
        try{ document.getElementById('filter-user').value = ''; }catch(e){}
        try{ document.getElementById('filter-from').value = ''; document.getElementById('filter-to').value = ''; }catch(e){}
        markersLayer.clearLayers();
        if(searchCircle) { map.removeLayer(searchCircle); searchCircle = null; }
        if(centerMarker) { map.removeLayer(centerMarker); centerMarker = null; }
        currentCenter = null;
        // ensure select mode is off and button remains visible after clear
        try{ setSelectMode(false); }catch(e){/* ignore if not initialized */}
        const selectBtnElem = document.getElementById('btn-select-location');
        if(selectBtnElem) selectBtnElem.style.display = '';
        publications.forEach(function(pub){ if(pub.lat && pub.lng) addPublicationMarker(pub); });
        document.getElementById('map-info').textContent = '';
    });

    // when user enables the 'use my location' checkbox, request location and set marker (no circle/search)
    document.getElementById('use-my-location').addEventListener('change', function(){
        if(this.checked){
            if(navigator.geolocation){
                navigator.geolocation.getCurrentPosition(function(pos){
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    map.setView([lat,lng], 14);
                    currentCenter = {lat: lat, lng: lng};
                    if(userLocationMarker) map.removeLayer(userLocationMarker);
                    userLocationMarker = L.marker([lat,lng]).addTo(map).bindPopup(TRANSLATIONS.you_are_here).openPopup();
                    // also add a center marker so updateSearchCircle will draw when radius set
                    if(centerMarker){ map.removeLayer(centerMarker); centerMarker = null; }
                    centerMarker = L.marker([lat,lng]).addTo(map);
                    // DO NOT draw a circle or run a search automatically
                }, function(err){ console.warn('Geolocation denied or unavailable', err); });
            }
        } else {
            if(userLocationMarker) { map.removeLayer(userLocationMarker); userLocationMarker = null; }
            if(centerMarker){ map.removeLayer(centerMarker); centerMarker = null; }
            currentCenter = null;
        }
    });
});
</script>
@endsection

@endsection
