var skillbridgeMap = null;
var skillbridgeMarker = null;

// Default center when the freelancer has no saved pin yet: Sri Lanka,
// zoomed out enough to show the whole island.
var SRI_LANKA_CENTER = [7.8731, 80.7718];
var DEFAULT_ZOOM = 8;
var PIN_ZOOM = 15;

// CARTO's free "dark matter" tiles (built on OpenStreetMap data) --
// matches the site's dark theme instead of a bright white map dropped
// into a black page. No key required; attribution is required and kept
// visible below, per both OSM's and CARTO's usage terms.
var DARK_TILE_URL = "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png";
var DARK_TILE_ATTRIBUTION =
  '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors ' +
  '&copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>';

function skillbridgeSetStatus(message) {
  var el = document.getElementById("locationStatus");
  if (el) el.textContent = message;
}

function skillbridgeSetCoords(lat, lng) {
  var latInput = document.getElementById("latitude");
  var lngInput = document.getElementById("longitude");
  if (latInput) latInput.value = lat.toFixed(7);
  if (lngInput) lngInput.value = lng.toFixed(7);
}

function skillbridgeClearCoords() {
  var latInput = document.getElementById("latitude");
  var lngInput = document.getElementById("longitude");
  if (latInput) latInput.value = "";
  if (lngInput) lngInput.value = "";
}

function skillbridgeDropPin(lat, lng, recenter) {
  if (!skillbridgeMarker) {
    skillbridgeMarker = L.marker([lat, lng], { draggable: true }).addTo(skillbridgeMap);
    skillbridgeMarker.on("dragend", function () {
      var pos = skillbridgeMarker.getLatLng();
      skillbridgeSetCoords(pos.lat, pos.lng);
      skillbridgeSetStatus("பின் அமைக்கப்பட்டது — அதை நகர்த்த இழுக்கவும் அல்லது வரைபடத்தில் வேறு இடத்தில் கிளிக் செய்யவும்.");
    });
  } else {
    skillbridgeMarker.setLatLng([lat, lng]);
  }
  if (recenter) {
    skillbridgeMap.setView([lat, lng], PIN_ZOOM);
  }
  skillbridgeSetCoords(lat, lng);
  skillbridgeSetStatus("பின் அமைக்கப்பட்டது — அதை நகர்த்த இழுக்கவும் அல்லது வரைபடத்தில் வேறு இடத்தில் கிளிக் செய்யவும்.");
}

function initLocationPicker() {
  var mapEl = document.getElementById("locationMap");
  if (!mapEl) return; // this script is only wired up on profile-edit.php

  var latInput = document.getElementById("latitude");
  var lngInput = document.getElementById("longitude");
  var hasSavedPin = latInput && lngInput && latInput.value !== "" && lngInput.value !== "";

  var startCenter = hasSavedPin
    ? [parseFloat(latInput.value), parseFloat(lngInput.value)]
    : SRI_LANKA_CENTER;

  skillbridgeMap = L.map(mapEl).setView(startCenter, hasSavedPin ? PIN_ZOOM : DEFAULT_ZOOM);
  L.tileLayer(DARK_TILE_URL, {
    attribution: DARK_TILE_ATTRIBUTION,
    subdomains: "abcd",
    maxZoom: 19
  }).addTo(skillbridgeMap);

  if (hasSavedPin) {
    skillbridgeDropPin(startCenter[0], startCenter[1], false);
  }

  // Click anywhere on the map -> drop/move the pin there.
  skillbridgeMap.on("click", function (e) {
    skillbridgeDropPin(e.latlng.lat, e.latlng.lng, false);
  });

  // Address search box -> Nominatim -> drop the pin at the top result.
  var searchBtn = document.getElementById("locationSearchBtn");
  var searchBox = document.getElementById("locationSearchBox");
  function runSearch() {
    var query = searchBox.value.trim();
    if (!query) return;
    skillbridgeSetStatus("அந்த முகவரியைத் தேடுகிறது...");
    searchBtn.disabled = true;

    var url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=lk&q=" + encodeURIComponent(query);
    fetch(url, { headers: { "ஏற்றுக்கொள்": "application/json" } })
      .then(function (res) { return res.json(); })
      .then(function (results) {
        searchBtn.disabled = false;
        if (results && results.length > 0) {
          skillbridgeDropPin(parseFloat(results[0].lat), parseFloat(results[0].lon), true);
        } else {
          skillbridgeSetStatus("Couldn't find that address — try a nearby town or landmark instead.");
        }
      })
      .catch(function () {
        searchBtn.disabled = false;
        skillbridgeSetStatus("Address lookup failed — check your connection and try again.");
      });
  }
  if (searchBtn) searchBtn.addEventListener("click", runSearch);
  if (searchBox) {
    searchBox.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        runSearch();
      }
    });
  }

  // "Use My Location" -> browser Geolocation API -> drop the pin there.
  // Nothing to do with Google -- this has always been a free browser API.
  var useMyLocationBtn = document.getElementById("useMyLocationBtn");
  if (useMyLocationBtn) {
    useMyLocationBtn.addEventListener("click", function () {
      if (!navigator.geolocation) {
        skillbridgeSetStatus("Your browser doesn't support location — search an address instead.");
        return;
      }
      skillbridgeSetStatus("உங்கள் தற்போதைய இருப்பிடத்தைப் பெறுகிறது...");
      navigator.geolocation.getCurrentPosition(
        function (position) {
          skillbridgeDropPin(position.coords.latitude, position.coords.longitude, true);
        },
        function () {
          skillbridgeSetStatus("Couldn't get your location — check your browser's location permission, or search an address instead.");
        }
      );
    });
  }

  // Clear location -> remove the pin and blank the hidden inputs.
  var clearBtn = document.getElementById("clearLocationBtn");
  if (clearBtn) {
    clearBtn.addEventListener("click", function () {
      if (skillbridgeMarker) {
        skillbridgeMap.removeLayer(skillbridgeMarker);
        skillbridgeMarker = null;
      }
      skillbridgeClearCoords();
      skillbridgeSetStatus("இதுவரை இருப்பிடம் அமைக்கப்படவில்லை. வரைபடத்தைக் கிளிக் செய்யவும், முகவரியைத் தேடவும், அல்லது உங்கள் தற்போதைய இருப்பிடத்தைப் பயன்படுத்தவும்.");
    });
  }
}
