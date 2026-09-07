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
      title: "Sign Up Account",
      sub: "Enter your details to create your account.",
      submit: "Sign Up",
      prompt: "Already have an account?",
      linkText: "Log in",
      linkHref: "login.php",
      action: "register-process.php"
    },
    login: {
      title: "Welcome Back",
      sub: "Log in to manage your bookings or services.",
      submit: "Log In",
      prompt: "Don't have an account?",
      linkText: "Sign up",
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

  /* ---- Role toggle: "Find a Freelancer" vs "Offer Services" ---------- */
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
      passwordToggle.textContent = isHidden ? "Hide" : "Show";
      passwordToggle.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
    });
  }
})();
