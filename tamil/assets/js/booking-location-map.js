/* ==========================================================================
   SkillBridge.lk -- Client location map (bookings.php, freelancer side)
   Leaflet.js + OpenStreetMap tiles -- free, no API key. Each booking row
   that has a saved client location gets a "View Client Location" button;
   clicking it reveals a small read-only map pinned at that booking's
   latitude/longitude. Maps are only initialised the first time a row is
   opened (lazy), since Leaflet can't measure a hidden (display:none) map
   container correctly.
   ========================================================================== */

var DARK_TILE_URL_BOOKING = "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png";
var DARK_TILE_ATTRIBUTION_BOOKING =
  '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors ' +
  '&copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>';

var skillbridgeBookingMapsInitialised = {};

function skillbridgeInitBookingMap(mapEl) {
  var lat = parseFloat(mapEl.getAttribute("data-lat"));
  var lng = parseFloat(mapEl.getAttribute("data-lng"));
  var name = mapEl.getAttribute("data-name") || "Client";
  if (isNaN(lat) || isNaN(lng)) return;

  var map = L.map(mapEl, {
    center: [lat, lng],
    zoom: 14,
    scrollWheelZoom: false,
    zoomControl: true
  });

  L.tileLayer(DARK_TILE_URL_BOOKING, {
    attribution: DARK_TILE_ATTRIBUTION_BOOKING,
    subdomains: "abcd",
    maxZoom: 19
  }).addTo(map);

  L.marker([lat, lng]).addTo(map).bindPopup(name + "'s location");
}

document.addEventListener("DOMContentLoaded", function () {
  var toggles = document.querySelectorAll(".booking-location-toggle");
  toggles.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var targetId = btn.getAttribute("data-target");
      var mapEl = document.getElementById(targetId);
      if (!mapEl) return;

      var isVisible = mapEl.classList.toggle("is-visible");
      btn.textContent = isVisible ? "📍 Hide Client Location" : "📍 வாடிக்கையாளர் இருப்பிடத்தைக் காண்க";

      if (isVisible && !skillbridgeBookingMapsInitialised[targetId]) {
        // Leaflet needs the container visible before it can size itself --
        // give the browser one paint tick after unhiding it.
        window.setTimeout(function () {
          skillbridgeInitBookingMap(mapEl);
          skillbridgeBookingMapsInitialised[targetId] = true;
        }, 0);
      } else if (isVisible) {
        // Already initialised once before (e.g. re-opened) -- Leaflet
        // sometimes needs a nudge to recompute size after being re-shown.
        window.setTimeout(function () {
          mapEl.dispatchEvent(new Event("resize"));
        }, 0);
      }
    });
  });
});
