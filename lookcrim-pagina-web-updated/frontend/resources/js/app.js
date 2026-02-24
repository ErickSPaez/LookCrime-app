// Vite entry file for legacy JS + bootstrap integration
// Bootstrap v4 requires jQuery to be available globally.
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import 'bootstrap/dist/js/bootstrap.bundle';

// Import local legacy scripts (copied to resources/js/vendor)
import './vendor/jquery.put-delete.js';

// Application entry (can add modern JS here)
window.LC = window.LC || {};

console.log('LookCrim app.js loaded');
import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Leaflet: import CSS and expose L globally so blades can use it in inline scripts
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
window.L = L;

// Fix Leaflet default marker icon URLs when bundled (Vite).
// Without this, Leaflet may request `/marker-icon.png` and `/marker-shadow.png`.
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
	iconRetinaUrl,
	iconUrl,
	shadowUrl,
});
