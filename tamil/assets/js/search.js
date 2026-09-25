(function () {
  "use strict";

  var searchForm = document.querySelector(".search-bar");
  if (searchForm) {
    searchForm.addEventListener("submit", function () {
      var btn = searchForm.querySelector("button[type='submit']");
      if (btn) {
        btn.textContent = "தேடுகிறது...";
        btn.disabled = true;
      }
    });
  }
})();
