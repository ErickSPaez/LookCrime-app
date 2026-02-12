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
		/* Make register "read more" view more compact */
		:root {
			--register-line-width: 720px;
			--register-media-width: 680px;
		}

		.main-website-interior .font-title-for-customization.register-title {
			font-size: 1.6rem;
			margin-top: 6px;
			margin-bottom: 0;
		}
		.main-website-interior .interior-title-line.register-line-title {
			width: min(60%, var(--register-line-width));
			margin-left: auto;
			margin-right: auto;
			margin-bottom: 18px;
		}
		.register-narrow {
			width: min(60%, var(--register-line-width));
			margin-left: auto;
			margin-right: auto;
		}

		#register-show-map {
			height: 180px;
			width: min(60%, var(--register-line-width));
			margin: 10px auto 0 auto;
			border-radius: 4px;
			overflow: hidden;
		}

		.register-meta-bar {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			flex-wrap: nowrap;
			gap: 10px;
			margin-top: 10px;
		}
		@media (max-width: 720px) {
			.register-meta-bar { flex-wrap: wrap; }
			.register-meta-right { text-align: left; }
		}
		.register-author {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.register-author-photo {
			width: 34px;
			height: 34px;
			border-radius: 50%;
			object-fit: cover;
			flex: 0 0 auto;
		}
		.register-author-name { font-weight: 600; font-size: 0.95rem; }
		.register-meta-right { text-align: right; }
		.register-category { font-weight: 600; font-size: 0.9rem; }
		.register-date { margin-top: 6px; font-size: 0.85rem; }

		.register-description { margin-top: 10px; font-size: 0.95rem; line-height: 1.55; }
		.register-media-center { margin-top: 12px; display: flex; justify-content: center; }
		.register-map-block { margin-top: 14px; }

		.register-gallery {
			position: relative;
			width: min(60%, var(--register-media-width));
			max-width: var(--register-media-width);
		}
		.register-gallery-stage {
			position: relative;
			width: 100%;
			border-radius: 4px;
			overflow: hidden;
		}
		.register-gallery-slide {
			display: none;
			width: 100%;
			height: auto;
			max-height: 340px;
			object-fit: contain;
		}
		.register-gallery-slide.is-active { display: block; }
		.register-gallery-nav {
			position: absolute;
			top: 50%;
			transform: translateY(-50%);
			z-index: 2;
			width: 34px;
			height: 34px;
			border-radius: 17px;
			border: 1px solid rgba(0,0,0,0.2);
			background: rgba(255,255,255,0.9);
			line-height: 1;
			font-size: 22px;
			padding: 0;
		}
		.register-gallery-prev { left: 10px; }
		.register-gallery-next { right: 10px; }
		.register-gallery-nav:disabled { opacity: 0.35; cursor: not-allowed; }
		.register-gallery-counter {
			text-align: center;
			margin-top: 8px;
			font-size: 0.85rem;
			color: #333;
		}
	</style>
@endsection

@section('conteudo')
@include('partials.registers.show')
@endsection

@section('pagescripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// Gallery
			(function initRegisterGallery() {
				var root = document.querySelector('.js-register-gallery');
				if (!root) return;

				var slides = Array.prototype.slice.call(root.querySelectorAll('.js-register-gallery-slide'));
				var prevBtn = root.querySelector('.js-register-gallery-prev');
				var nextBtn = root.querySelector('.js-register-gallery-next');
				var counter = root.querySelector('.js-register-gallery-counter');
				var count = slides.length;
				var index = 0;

				function setActive(nextIndex) {
					index = nextIndex;
					slides.forEach(function (el, i) {
						if (i === index) el.classList.add('is-active');
						else el.classList.remove('is-active');
					});
					if (counter) counter.textContent = (index + 1) + ' / ' + count;
				}

				if (count <= 1) {
					if (prevBtn) prevBtn.disabled = true;
					if (nextBtn) nextBtn.disabled = true;
				}

				if (prevBtn) {
					prevBtn.addEventListener('click', function () {
						if (count <= 1) return;
						setActive((index - 1 + count) % count);
					});
				}
				if (nextBtn) {
					nextBtn.addEventListener('click', function () {
						if (count <= 1) return;
						setActive((index + 1) % count);
					});
				}

				setActive(0);
			})();

			// Map
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
