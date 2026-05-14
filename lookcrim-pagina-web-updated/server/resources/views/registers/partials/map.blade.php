<div id="register-map" style="height: 400px; width: 100%; margin-bottom:0.75rem;"></div>

@php
    $userCity = (isset($city) && $city) ? [
        'center_lat' => (float) $city->center_lat,
        'center_lng' => (float) $city->center_lng,
        'radius_m' => (int) $city->radius_m,
        'name' => (string) ($city->name ?? ''),
    ] : null;

    $initialAddress = old('address', isset($register) ? ($register->address ?? '') : '');
@endphp

<input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', isset($register) ? $register->latitude : '') }}">
<input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', isset($register) ? $register->longitude : '') }}">
<input type="hidden" name="address" id="address" value="{{ $initialAddress }}">

<div id="lc-selected-address-box" style="{{ trim($initialAddress) !== '' ? '' : 'display:none;' }} background:#F5ECEC;border-radius:10px;padding:12px 14px;margin-bottom:1rem;">
    <div style="color:#6B6B6B;font-weight:600;margin-bottom:6px;">Your location</div>
    <div style="display:flex;align-items:flex-start;gap:8px;color:#000;font-weight:600;">
        <span style="color:#820000;font-size:18px;line-height:1;">
            <i class="fa fa-map-marker"></i>
        </span>
        <span id="lc-selected-address-text">{{ $initialAddress }}</span>
    </div>
</div>

<div id="lc-city-popup" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:8px;max-width:520px;width:100%;padding:18px 16px;box-shadow:0 10px 30px rgba(0,0,0,0.25);text-align:center;">
        <div id="lc-city-popup-msg" style="font-size:1rem;color:#222;margin-bottom:14px;"></div>
        <button type="button" id="lc-city-popup-ok" class="btn btn-lookcrim btn-sm">OK</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const addressInput = document.getElementById('address');
    const addressBox = document.getElementById('lc-selected-address-box');
    const addressText = document.getElementById('lc-selected-address-text');

    if (!latInput || !lngInput) return;

    const popupEl = document.getElementById('lc-city-popup');
    const popupMsgEl = document.getElementById('lc-city-popup-msg');
    const popupOkEl = document.getElementById('lc-city-popup-ok');

    const userCity = @json($userCity);
    const allowOutsideCity = @json($allowOutsideCity ?? false);

    const msgOutsideBlocked = @json(__('pages.register_point_outside_city_blocked'));
    const msgOutsideAllowed = @json(__('pages.register_point_outside_city_allowed'));

    const initialLat = parseFloat(latInput.value) || null;
    const initialLng = parseFloat(lngInput.value) || null;

    const defaultCenter = [40.4168, -3.7038];
    const cityCenter = userCity ? [userCity.center_lat, userCity.center_lng] : null;
    const startCenter = (initialLat && initialLng)
        ? [initialLat, initialLng]
        : (cityCenter || defaultCenter);
    const startZoom = (initialLat && initialLng) ? 13 : (userCity ? 12 : 5);

    const map = L.map('register-map').setView(startCenter, startZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = null;
    if (initialLat && initialLng) {
        marker = L.marker([initialLat, initialLng]).addTo(map);
    }

    if (userCity && cityCenter) {
        const cityCircle = L.circle(cityCenter, {
            radius: userCity.radius_m,
            color: '#0d6efd',
            weight: 2,
            fillOpacity: 0.05,
            dashArray: '6,6',
        }).addTo(map);

        if (!(initialLat && initialLng)) {
            try {
                map.fitBounds(cityCircle.getBounds(), { padding: [20, 20] });
            } catch (e) {
                // ignore
            }
        }
    }

    function showPopup(message) {
        if (!popupEl || !popupMsgEl) return;
        popupMsgEl.textContent = message || '';
        popupEl.style.display = 'flex';
    }

    function hidePopup() {
        if (!popupEl) return;
        popupEl.style.display = 'none';
    }

    if (popupOkEl) {
        popupOkEl.addEventListener('click', hidePopup);
    }

    if (popupEl) {
        popupEl.addEventListener('click', function (e) {
            if (e.target === popupEl) hidePopup();
        });
    }

    function isInsideCity(lat, lng) {
        if (!userCity) return true;
        const p = L.latLng(lat, lng);
        const c = L.latLng(userCity.center_lat, userCity.center_lng);
        return p.distanceTo(c) <= userCity.radius_m;
    }

    function fallbackAddress(lat, lng) {
        return Number(lat).toFixed(6) + ', ' + Number(lng).toFixed(6);
    }

    function setAddress(value) {
        const cleaned = (value || '').trim();

        if (addressInput) {
            addressInput.value = cleaned;
        }

        if (addressText) {
            addressText.textContent = cleaned;
        }

        if (addressBox) {
            addressBox.style.display = cleaned ? 'block' : 'none';
        }
    }

    async function reverseGeocode(lat, lng) {
        setAddress('Loading address...');

        try {
            const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='
                + encodeURIComponent(lat)
                + '&lon='
                + encodeURIComponent(lng)
                + '&zoom=18&addressdetails=1';

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Reverse geocoding failed');
            }

            const data = await response.json();
            const displayName = data && data.display_name ? String(data.display_name) : '';

            setAddress(displayName || fallbackAddress(lat, lng));
        } catch (e) {
            setAddress(fallbackAddress(lat, lng));
        }
    }

    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        if (userCity && !isInsideCity(lat, lng)) {
            if (!allowOutsideCity) {
                showPopup(msgOutsideBlocked);
                return;
            }

            showPopup(msgOutsideAllowed);
        }

        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }

        latInput.value = lat;
        lngInput.value = lng;

        reverseGeocode(lat, lng);
    });
});
</script>