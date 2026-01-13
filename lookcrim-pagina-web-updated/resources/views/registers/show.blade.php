@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('layout.registers'). ' - LookCrim')

@php
	$lat = $register->lat_from_location ?? $register->latitude ?? null;
	$lng = $register->lng_from_location ?? $register->longitude ?? null;
	$authorName = $register->user->name
		?? $register->user->email
		?? null;
@endphp

@section('pagestyles')
	<style>
		.publication-show-grid { margin-top: 12px; }
		.publication-media img { width: 100%; height: auto; max-height: 420px; object-fit: contain; }
		.publication-media .youtube-video iframe { width: 100% !important; max-width: 100%; height: 340px; }
		#register-show-map { height: 260px; width: 100%; margin-top: 10px; border-radius: 4px; overflow: hidden; }
		.publication-meta { font-size: 0.95rem; color: #333; }
		.publication-meta .meta-row { margin-bottom: 6px; }
	</style>
@endsection

@section('conteudo')
@include('partials.registers.show')
@endsection

@section('pagescripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			var mapEl = document.getElementById('register-show-map');
			if (!mapEl) return;
			if (!window.L) return;

			var lat = {{ json_encode($lat) }};
			var lng = {{ json_encode($lng) }};

			lat = (lat === null || lat === undefined) ? NaN : parseFloat(lat);
			lng = (lng === null || lng === undefined) ? NaN : parseFloat(lng);

			if (!isFinite(lat) || !isFinite(lng)) {
				return;
			}

			var map = L.map('register-show-map').setView([lat, lng], 14);
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 19,
				attribution: '&copy; OpenStreetMap contributors'
			}).addTo(map);

			L.marker([lat, lng]).addTo(map);
		});
	</script>
@endsection
