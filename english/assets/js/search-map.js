/* ==========================================================================
   SkillBridge.lk -- Search page: "Nearby" geolocation + results map toggle
   Leaflet.js + OpenStreetMap tiles (free, no API key). Two independent
   features:
     1. "Search Near Me" -- browser Geolocation API (always free, nothing
        to do with any map provider), then reload the page with
        ?lat=..&lng=.. so distance sorting/filtering (done server-side in
        search.php, plain SQL) kicks in.
     2. Map View -- plots every result that has a saved location as a
        pin, using the JSON search.php embedded in #resultsMapData.
   ========================================================================== */

var skillbridgeResultsMap = null;

var DARK_TILE_URL_SEARCH = "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png";
var DARK_TILE_ATTRIBUTION_SEARCH =
  '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors ' +
  '&copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>';

document.addEventListener("DOMContentLoaded", function () {

  // ---- 1. "Search Near Me" ----
  var useMyLocationBtn = document.getElementById("useMyLocationBtn");
  if (useMyLocationBtn) {
    useMyLocationBtn.addEventListener("click", function () {
      if (!navigator.geolocation) {
        alert("Your browser doesn't support location search.");
        return;
      }
      useMyLocationBtn.textContent = "Locating...";
      useMyLocationBtn.disabled = true;

      navigator.geolocation.getCurrentPosition(
        function (position) {
          var params = new URLSearchParams(window.location.search);
          params.set("lat", position.coords.latitude.toFixed(7));
          params.set("lng", position.coords.longitude.toFixed(7));
          params.set("sort", "nearby");
          params.delete("page");
          window.location.href = "search.php?" + params.toString();
        },
        function () {
          alert("Couldn't get your location — check your browser's location permission and try again.");
          useMyLocationBtn.textContent = "📍 Search Near Me";
          useMyLocationBtn.disabled = false;
        }
      );
    });
  }

  // ---- 2. Map View / List View toggle ----
  var viewToggleBtn = document.getElementById("viewToggleBtn");
  var resultGrid = document.getElementById("resultGrid");
  var resultsMapEl = document.getElementById("resultsMap");
  if (!viewToggleBtn || !resultGrid || !resultsMapEl) return;

  viewToggleBtn.addEventListener("click", function () {
    var showingMap = viewToggleBtn.getAttribute("data-mode") === "map";
    if (showingMap) {
      resultsMapEl.hidden = true;
      resultGrid.hidden = false;
      viewToggleBtn.setAttribute("data-mode", "list");
      viewToggleBtn.textContent = "🗺️ Map View";
      return;
    }

    viewToggleBtn.setAttribute("data-mode", "map");
    viewToggleBtn.textContent = "☰ List View";
    resultGrid.hidden = true;
    resultsMapEl.hidden = false;
    renderResultsMap();
  });

  function renderResultsMap() {
    if (skillbridgeResultsMap) {
      // Already built once -- just make sure Leaflet recalculates the
      // container size now that it's visible again (it can't measure a
      // hidden element correctly).
      setTimeout(function () { skillbridgeResultsMap.invalidateSize(); }, 0);
      return;
    }

    var dataEl = document.getElementById("resultsMapData");
    var points = [];
    try {
      points = dataEl ? JSON.parse(dataEl.textContent) : [];
    } catch (e) {
      points = [];
    }

    if (points.length === 0) {
      resultsMapEl.textContent = "None of the freelancers on this page have a saved location yet.";
      return;
    }

    resultsMapEl.textContent = "";
    skillbridgeResultsMap = L.map(resultsMapEl);
    L.tileLayer(DARK_TILE_URL_SEARCH, {
      attribution: DARK_TILE_ATTRIBUTION_SEARCH,
      subdomains: "abcd",
      maxZoom: 19
    }).addTo(skillbridgeResultsMap);

    var markerBounds = [];

    points.forEach(function (point) {
      var priceText = "LKR " + Math.round(point.price).toLocaleString();
      var popupHtml =
        '<div style="font-family:sans-serif;font-size:13px;max-width:180px;">' +
        '<strong>' + escapeHtml(point.name) + '</strong><br>' +
        escapeHtml(point.service) + '<br>' +
        priceText + '<br>' +
        '<a href="' + point.url + '">View profile →</a>' +
        '</div>';

      L.marker([point.lat, point.lng])
        .addTo(skillbridgeResultsMap)
        .bindPopup(popupHtml);

      markerBounds.push([point.lat, point.lng]);
    });

    if (markerBounds.length === 1) {
      skillbridgeResultsMap.setView(markerBounds[0], 13);
    } else {
      skillbridgeResultsMap.fitBounds(markerBounds, { padding: [30, 30] });
    }

    // The map was built while its container was hidden (display:none via
    // [hidden] until the toggle just now un-hid it) -- Leaflet needs a
    // nudge once it's actually visible to size the tiles correctly.
    setTimeout(function () { skillbridgeResultsMap.invalidateSize(); }, 0);
  }

  function escapeHtml(str) {
    var div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }
});
