/* ==========================================================================
   SkillBridge.lk -- Search page interactions
   Filters/sort work via plain links + <select onchange> (no JS required
   for them to function). This just adds a small polish touch.
   ========================================================================== */
(function () {
  "use strict";

  var searchForm = document.querySelector(".search-bar");
  if (searchForm) {
    searchForm.addEventListener("submit", function () {
      var btn = searchForm.querySelector("button[type='submit']");
      if (btn) {
        btn.textContent = "Searching...";
        btn.disabled = true;
      }
    });
  }
})();
