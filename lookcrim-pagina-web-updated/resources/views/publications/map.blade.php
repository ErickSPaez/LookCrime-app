@extends('layouts.legacy')

@section('titulo_browser', __('pages.map_title'))

@section('pagestyles')
    <style>
        #publications-map { height: 600px; width: 100%; margin-bottom: 1rem; }
        .leaflet-popup-content img { max-width: 200px; height: auto; display:block; margin-bottom:6px; }
        .map-legend { background: #fff; padding: 8px 10px; border-radius:4px; box-shadow: 0 1px 4px rgba(0,0,0,0.3); }
        .map-legend .item { display:flex; align-items:center; margin-bottom:6px; font-size:0.9rem; }
        .map-legend .swatch { width:14px; height:14px; border-radius:3px; display:inline-block; margin-right:8px; border:1px solid #3333; }
    </style>
@endsection

@section('conteudo')
<div class="main-website-interior">
    <h1 class="font-title-for-customization interior-title">{{ __('pages.map_title') }}</h1>
    <hr class="interior-title-line">

    <div style="margin-bottom:0.75rem">
        <div id="map-filters" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <label style="font-size:0.9rem">{{ __('pages.radius_km') }}:
                <input id="filter-radius" type="number" step="0.5" min="0" value="5" style="width:80px;margin-left:6px"> 
            </label>
            <label style="font-size:0.9rem">{{ __('pages.types') }}:
                <div id="filter-types-container" style="display:inline-block;min-width:180px;max-width:420px;margin-left:6px;vertical-align:middle">
                    @foreach($categoryLabels as $k => $v)
                        <label style="display:inline-block;margin-right:8px;white-space:nowrap"><input class="filter-type" type="checkbox" value="{{ $k }}"> {{ $v }}</label>
                    @endforeach
                </div>
                <button id="select-all-types" type="button" style="margin-left:6px">{{ __('pages.select_all') }}</button>
            </label>
            <label style="font-size:0.9rem">
                <input type="checkbox" id="use-bbox"> {{ __('pages.search_in_map_view') }}
            </label>
            <label style="font-size:0.9rem">
                <input type="checkbox" id="use-my-location"> {{ __('pages.use_my_location') }}
            </label>
            <button id="apply-filters" class="btn-lookcrim">{{ __('pages.apply') }}</button>
            <button id="clear-filters" class="btn-secondary">{{ __('pages.clear') }}</button>
            <div id="map-info" style="margin-left:auto;font-size:0.9rem;color:#666"></div>
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
            'server_error' => __('pages.server_error')
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
        let popupHtml = '<div style="min-width:180px">';
        if(pub.image) popupHtml += '<a href="'+pub.url+'"><img src="'+pub.image+'" alt="'+pub.title+'"></a>';
        popupHtml += '<div><a href="'+(pub.url||'#')+'"><strong>'+ (pub.title || (pub.properties && pub.properties.title) || TRANSLATIONS.publication) +'</strong></a></div>';
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
        // choose center: user-provided currentCenter or map center
        const center = currentCenter || map.getCenter();
        if(searchCircle) map.removeLayer(searchCircle);
        if(meters > 0){
            searchCircle = L.circle([center.lat, center.lng], { radius: meters, color: '#d9534f', weight: 1, fillOpacity: 0.08 }).addTo(map);
        }
    }

    // update circle when radius input changes or map moved (if using center)
    document.getElementById('filter-radius').addEventListener('input', updateSearchCircle);
    map.on('move', function(){ if(!document.getElementById('use-my-location').checked){ currentCenter = null; updateSearchCircle(); } });

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

    // Search helper: call API and render GeoJSON results
    async function performSearch(){
        const useBbox = document.getElementById('use-bbox').checked;
        const radiusKm = parseFloat(document.getElementById('filter-radius').value) || 0;
        const checked = Array.from(document.querySelectorAll('#filter-types-container input.filter-type:checked'));
        const selected = checked.map(c=>c.value);

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
                    addPublicationMarker({lat: lat, lng: lon, title: f.properties.title, category: f.properties.category});
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
        markersLayer.clearLayers();
        if(searchCircle) { map.removeLayer(searchCircle); searchCircle = null; }
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
                    // DO NOT draw a circle or run a search automatically
                }, function(err){ console.warn('Geolocation denied or unavailable', err); });
            }
        } else {
            if(userLocationMarker) { map.removeLayer(userLocationMarker); userLocationMarker = null; }
            currentCenter = null;
        }
    });
});
</script>
@endsection

@endsection
