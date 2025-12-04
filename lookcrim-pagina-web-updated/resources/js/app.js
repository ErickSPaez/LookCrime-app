// Vite entry file for legacy JS + bootstrap integration
import 'bootstrap/dist/js/bootstrap.bundle';

// Import local legacy scripts (copied to resources/js/vendor)
import './vendor/jquery.put-delete.js';
import './vendor/newsletter.js';

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
