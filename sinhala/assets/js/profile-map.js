/* ==========================================================================
   SkillBridge.lk -- Read-only freelancer location map (profile.php)
   Leaflet.js + OpenStreetMap tiles -- free, no API key. Shows a single
   pin at the freelancer's saved coordinates. No editing -- that only
   happens on profile-edit.php's location picker.
   ========================================================================== */

var DARK_TILE_URL_PROFILE = "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png";
var DARK_TILE_ATTRIBUTION_PROFILE =
  '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors ' +
  '&copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>';

function initProfileMap() {
  var el = document.getElementById("profileMap");
  if (!el) return;

  var lat = parseFloat(el.getAttribute("data-lat"));
  var lng = parseFloat(el.getAttribute("data-lng"));
  var name = el.getAttribute("data-name") || "මෙම නිදහස් වෘත්තිකයා";
  if (isNaN(lat) || isNaN(lng)) return;

  var map = L.map(el, {
    center: [lat, lng],
    zoom: 13,
    scrollWheelZoom: false, // don't hijack page scroll while browsing a profile
    zoomControl: true
  });

  L.tileLayer(DARK_TILE_URL_PROFILE, {
    attribution: DARK_TILE_ATTRIBUTION_PROFILE,
    subdomains: "abcd",
    maxZoom: 19
  }).addTo(map);

  L.marker([lat, lng]).addTo(map).bindPopup(name);
}
