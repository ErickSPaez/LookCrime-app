@extends('layouts.legacy')

@section('titulo_browser','Map - LookCrim')

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
    <h1 class="font-title-for-customization interior-title">{{ __('Map of publications') }}</h1>
    <hr class="interior-title-line">

    <div id="publications-map"></div>
    <div id="publications-legend" style="display:none"></div>
</div>

@section('pagescripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    // publications data prepared in controller
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
    publications.forEach(function(pub){
        if(!pub.lat || !pub.lng) return;
        const cat = pub.category || '';
        const color = categoryColors[cat] || '#2e7d32';
        const marker = L.circleMarker([pub.lat, pub.lng], {
            radius: 8,
            color: '#333',
            weight: 1,
            fillColor: color,
            fillOpacity: 1
        }).addTo(map);

        let popupHtml = '<div style="min-width:180px">';
        if(pub.image) popupHtml += '<a href="'+pub.url+'"><img src="'+pub.image+'" alt="'+pub.title+'"></a>';
        popupHtml += '<div><a href="'+pub.url+'"><strong>'+ (pub.title || 'Publication') +'</strong></a></div>';
        if(cat) popupHtml += '<div style="margin-top:6px;font-size:0.9rem;color:#444"><em>'+ (categoryLabels[cat] || cat) +'</em></div>';
        popupHtml += '</div>';
        marker.bindPopup(popupHtml);
    });

    // Add legend control
    const legend = L.control({position: 'topright'});
    legend.onAdd = function () {
        const div = L.DomUtil.create('div', 'map-legend');
        const legendTitle = '{{ addslashes(__('pages.categories')) }}';
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
});
</script>
@endsection

@endsection
