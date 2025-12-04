@extends('layouts.legacy')

@section('titulo_browser','Map - LookCrim')

@section('pagestyles')
    <style>
        #publications-map { height: 600px; width: 100%; margin-bottom: 1rem; }
        .leaflet-popup-content img { max-width: 200px; height: auto; display:block; margin-bottom:6px; }
    </style>
@endsection

@section('conteudo')
<div class="main-website-interior">
    <h1 class="font-title-for-customization interior-title">{{ __('Map of publications') }}</h1>
    <hr class="interior-title-line">

    <div id="publications-map"></div>
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

    publications.forEach(function(pub){
        if(!pub.lat || !pub.lng) return;
        const marker = L.marker([pub.lat, pub.lng]).addTo(map);
        let popupHtml = '<div style="min-width:180px">';
        if(pub.image) popupHtml += '<a href="'+pub.url+'"><img src="'+pub.image+'" alt="'+pub.title+'"></a>';
        popupHtml += '<div><a href="'+pub.url+'"><strong>'+ (pub.title || 'Publication') +'</strong></a></div>';
        popupHtml += '</div>';
        marker.bindPopup(popupHtml);
    });
});
</script>
@endsection

@endsection
