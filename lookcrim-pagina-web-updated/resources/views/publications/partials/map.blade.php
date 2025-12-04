<div id="publication-map" style="height: 400px; width: 100%; margin-bottom:1rem;"></div>

<input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', isset($publications) ? $publications->latitude : '') }}">
<input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', isset($publications) ? $publications->longitude : '') }}">

<script>
document.addEventListener('DOMContentLoaded', function () {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    const initialLat = parseFloat(latInput.value) || null;
    const initialLng = parseFloat(lngInput.value) || null;

    const defaultCenter = [40.4168, -3.7038]; // default center: Madrid (change as needed)
    const map = L.map('publication-map').setView(initialLat && initialLng ? [initialLat, initialLng] : defaultCenter, initialLat && initialLng ? 13 : 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = null;
    if (initialLat && initialLng) {
        marker = L.marker([initialLat, initialLng]).addTo(map);
    }

    map.on('click', function(e) {
        const { lat, lng } = e.latlng;
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }
        latInput.value = lat;
        lngInput.value = lng;
    });

});
</script>