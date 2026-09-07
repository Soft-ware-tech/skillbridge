/* ==========================================================================
   SkillBridge.lk -- Login / Register interactions
   - Toggle between login/register without a page reload (smooth swap)
   - Register-only name fields fade/collapse when switching to login
   - Password show/hide
   ========================================================================== */
(function () {
  "use strict";

  var stage = document.getElementById("authStage");
  var title = document.getElementById("authTitle");
  var sub = document.getElementById("authSub");
  var nameRow = document.getElementById("nameRow");
  var roleToggle = document.getElementById("roleToggle");
  var authForm = document.getElementById("authForm");
  var submitBtn = document.getElementById("authSubmit");
  var switchPrompt = document.getElementById("switchPrompt");
  var toggleLink = document.getElementById("toggleModeLink");

  var copy = {
    register: {
      title: "ගිණුම ලියාපදිංචි කරන්න",
      sub: "ඔබේ ගිණුම සෑදීමට විස්තර ඇතුළත් කරන්න.",
      submit: "ලියාපදිංචි වන්න",
      prompt: "දැනටමත් ගිණුමක් තිබේද?",
      linkText: "පිවිසෙන්න",
      linkHref: "login.php",
      action: "register-process.php"
    },
    login: {
      title: "නැවත සාදරයෙන් පිළිගනිමු",
      sub: "ඔබේ වෙන්කිරීම් හෝ සේවා කළමනාකරණය කිරීමට පිවිසෙන්න.",
      submit: "පිවිසෙන්න",
      prompt: "ගිණුමක් නැද්ද?",
      linkText: "ලියාපදිංචි වන්න",
      linkHref: "register.php",
      action: "login-process.php"
    }
  };

  function applyMode(mode, pushState) {
    if (!stage) return;
    stage.setAttribute("data-mode", mode);

    var c = copy[mode];
    if (title) title.textContent = c.title;
    if (sub) sub.textContent = c.sub;
    if (submitBtn) submitBtn.textContent = c.submit;
    if (switchPrompt) switchPrompt.textContent = c.prompt;
    if (toggleLink) {
      toggleLink.textContent = c.linkText;
      toggleLink.setAttribute("href", c.linkHref);
    }
    if (authForm) authForm.setAttribute("action", c.action);

    if (nameRow) nameRow.classList.toggle("is-collapsed", mode === "login");
    if (roleToggle) roleToggle.classList.toggle("is-collapsed", mode === "login");

    if (pushState && window.history && window.history.pushState) {
      var redirectInput = document.getElementById("redirectInput");
      var redirectVal = redirectInput ? redirectInput.value : "";
      var redirectQS = redirectVal ? "&redirect=" + encodeURIComponent(redirectVal) : "";
      var url = mode === "register"
        ? "login.php?mode=register" + redirectQS
        : "login.php" + (redirectQS ? "?" + redirectQS.slice(1) : "");
      window.history.pushState({ mode: mode }, "", url);
    }
  }

  if (toggleLink) {
    toggleLink.addEventListener("click", function (e) {
      e.preventDefault();
      var current = stage.getAttribute("data-mode");
      var next = current === "register" ? "login" : "register";
      applyMode(next, true);
    });
  }

  window.addEventListener("popstate", function (e) {
    var mode = (e.state && e.state.mode) || "login";
    applyMode(mode, false);
  });

  // Sync the collapsed/expanded state of the name row + role toggle on
  // first load (in case the page opened directly in login mode).
  if (stage) {
    applyMode(stage.getAttribute("data-mode") || "login", false);
  }

  /* ---- Role toggle: "නිදහස් වෘත්තිකයෙකු සොයන්න" vs "සේවා පිරිනමන්න" ---------- */
  var roleInput = document.getElementById("roleInput");
  if (roleToggle && roleInput) {
    var roleButtons = roleToggle.querySelectorAll(".role-option");
    for (var r = 0; r < roleButtons.length; r++) {
      (function (btn) {
        btn.addEventListener("click", function () {
          for (var j = 0; j < roleButtons.length; j++) {
            roleButtons[j].classList.remove("is-active");
          }
          btn.classList.add("is-active");
          roleInput.value = btn.getAttribute("data-role");
        });
      })(roleButtons[r]);
    }
  }

  /* ---- Password show/hide -------------------------------------------- */
  var passwordInput = document.getElementById("password");
  var passwordToggle = document.getElementById("passwordToggle");

  if (passwordInput && passwordToggle) {
    passwordToggle.addEventListener("click", function () {
      var isHidden = passwordInput.getAttribute("type") === "password";
      passwordInput.setAttribute("type", isHidden ? "text" : "password");
      passwordToggle.textContent = isHidden ? "සඟවන්න" : "පෙන්වන්න";
      passwordToggle.setAttribute("aria-label", isHidden ? "මුරපදය සඟවන්න" : "මුරපදය පෙන්වන්න");
    });
  }
})();
