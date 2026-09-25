const sidebar = document.getElementById("sidebar");
const sidebarToggle = document.getElementById("sidebarToggle");
const mobileBurger = document.getElementById("mobileBurger");
const SB_KEY = "sb_sidebar_collapsed";

if (localStorage.getItem(SB_KEY) === "1") {
  sidebar.classList.add("is-collapsed");
}

sidebarToggle.addEventListener("click", () => {
  sidebar.classList.toggle("is-collapsed");
  localStorage.setItem(
    SB_KEY,
    sidebar.classList.contains("is-collapsed") ? "1" : "0",
  );
});

mobileBurger.addEventListener("click", () => {
  sidebar.classList.toggle("is-open");
  mobileBurger.classList.toggle("is-open");
});
(function () {
  "use strict";

  var reduceMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
  ).matches;

  /* ---- Scroll progress bar ------------------------------------------- */
  var progressBar = document.getElementById("bridgeProgress");
  function updateProgress() {
    var scrollTop = window.scrollY;
    var docHeight = document.documentElement.scrollHeight - window.innerHeight;
    var pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
    if (progressBar) progressBar.style.width = pct + "%";
  }

  /* ---- Sticky nav: blur + hide on scroll down ------------------------- */
  var nav = document.getElementById("siteNav");
  var lastScroll = window.scrollY;

  function updateNav() {
    var current = window.scrollY;
    if (!nav) return;

    if (current > 40) nav.classList.add("is-scrolled");
    else nav.classList.remove("is-scrolled");

    if (current > lastScroll && current > 160) {
      nav.classList.add("is-hidden");
    } else {
      nav.classList.remove("is-hidden");
    }
    lastScroll = current;
  }

  var ticking = false;
  window.addEventListener("scroll", function () {
    updateProgress();
    if (!ticking) {
      window.requestAnimationFrame(function () {
        updateNav();
        ticking = false;
      });
      ticking = true;
    }
  });
  updateProgress();
  updateNav();

  /* ---- Reveal-on-scroll ------------------------------------------------ */
  var revealEls = document.querySelectorAll("[data-reveal]");
  if ("IntersectionObserver" in window && !reduceMotion) {
    var revealObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("in-view");
            revealObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15, rootMargin: "0px 0px -40px 0px" },
    );
    revealEls.forEach(function (el) {
      revealObserver.observe(el);
    });
  } else {
    revealEls.forEach(function (el) {
      el.classList.add("in-view");
    });
  }

  /* ---- Animated stat counters ------------------------------------------ */
  var statEls = document.querySelectorAll("[data-count]");
  function animateCount(el) {
    var target = parseInt(el.getAttribute("data-count"), 10) || 0;
    var duration = 1400;
    var start = null;

    function step(timestamp) {
      if (!start) start = timestamp;
      var progress = Math.min((timestamp - start) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      el.textContent = Math.floor(eased * target).toLocaleString();
      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        el.textContent = target.toLocaleString();
      }
    }
    window.requestAnimationFrame(step);
  }

  if ("IntersectionObserver" in window) {
    var statObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            animateCount(entry.target);
            statObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.5 },
    );
    statEls.forEach(function (el) {
      statObserver.observe(el);
    });
  } else {
    statEls.forEach(function (el) {
      el.textContent = el.getAttribute("data-count");
    });
  }

  /* ---- 3D tilt on hover (pointer devices only) -------------------------- */
  if (
    !reduceMotion &&
    window.matchMedia("(hover: hover) and (pointer: fine)").matches
  ) {
    document.querySelectorAll("[data-tilt]").forEach(function (card) {
      var bounds;

      card.addEventListener("pointerenter", function () {
        bounds = card.getBoundingClientRect();
      });

      card.addEventListener("pointermove", function (e) {
        if (!bounds) bounds = card.getBoundingClientRect();
        var x = (e.clientX - bounds.left) / bounds.width;
        var y = (e.clientY - bounds.top) / bounds.height;
        var rotateY = (x - 0.5) * 10;
        var rotateX = (0.5 - y) * 10;
        card.style.transform =
          "translateY(-4px) rotateX(" +
          rotateX +
          "deg) rotateY(" +
          rotateY +
          "deg)";
      });

      card.addEventListener("pointerleave", function () {
        card.style.transform = "translateY(0) rotateX(0) rotateY(0)";
      });
    });
  }

  /* ---- Parallax orbs ------------------------------------------------- */
  if (!reduceMotion) {
    var parallaxEls = document.querySelectorAll("[data-parallax]");
    window.addEventListener("scroll", function () {
      var y = window.scrollY;
      parallaxEls.forEach(function (el) {
        var speed = parseFloat(el.getAttribute("data-parallax")) || 0.05;
        el.style.transform = "translate3d(0," + y * speed + "px,0)";
      });
    });
  }

  /* ---- Language pill dropdown ------------------------------------------ */
  var langBtn = document.getElementById("langPillBtn");
  var langMenu = document.getElementById("langPillMenu");

  if (langBtn && langMenu) {
    langBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      var isOpen = langMenu.classList.toggle("is-open");
      langBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
    document.addEventListener("click", function () {
      langMenu.classList.remove("is-open");
      langBtn.setAttribute("aria-expanded", "false");
    });
  }

  /* ---- Mobile nav burger ------------------------------------------------ */
  var burger = document.getElementById("navBurger");
  var navLinks = document.getElementById("navLinks");

  if (burger && navLinks) {
    burger.addEventListener("click", function () {
      var isOpen = navLinks.classList.toggle("is-open");
      burger.classList.toggle("is-open", isOpen);
      burger.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
  }

  /* ---- Logged-in nav swap --------------------------------------------
     index(TAM).html is a static file, so it can't check PHP's session
     itself. This asks session-status.php (which can) and swaps the
     Log In / Join Free buttons for a "Hi, {name}" + Dashboard + Log Out
     once we know someone is actually logged in. --------------------- */
  var loggedOutNav = document.getElementById("navLoggedOut");
  var loggedInNav = document.getElementById("navLoggedIn");
  var helloName = document.getElementById("navHelloName");
  var dashboardLink = document.getElementById("navDashboardLink");
  var profileLink = document.getElementById("navProfileLink");

  var dashboardHrefByRole = {
    freelancer: "dashboard.php",
    admin: "admin-dashboard.php",
    client: "client-dashboard.php",
  };

  if (loggedOutNav && loggedInNav) {
    fetch("session-status.php")
      .then(function (response) {
        return response.text();
      })
      .then(function (text) {
        var parts = text.split("|");
        var isLoggedIn = parts[0] === "1";
        if (isLoggedIn) {
          var role = parts[2] || "client";
          var photoPath = parts[3] || "";
          if (helloName) helloName.textContent = parts[1] || "";
          if (dashboardLink) {
            dashboardLink.setAttribute(
              "href",
              dashboardHrefByRole[role] || "client-dashboard.php",
            );
          }
          if (profileLink) {
            // profile-edit.php now works for every role.
            profileLink.hidden = false;
          }
          var avatarEl = document.getElementById("navHelloAvatar");
          if (avatarEl) {
            if (photoPath) {
              avatarEl.innerHTML =
                '<img src="' + photoPath + '" alt="" class="sidebar-avatar-img">';
            } else {
              avatarEl.textContent = (parts[1] || "?").charAt(0).toUpperCase();
            }
          }
          loggedOutNav.hidden = true;
          loggedInNav.hidden = false;
        }
      })
      .catch(function () {
        // If the check fails, just leave the logged-out nav showing --
        // same as before this feature existed.
      });
  }

  /* ---- Team cards: glow border pulls flush when "விவரங்களைக் காண்க" opens - */
  document.querySelectorAll(".tcg-card").forEach(function (card) {
    var details = card.querySelector(".tcg-details");
    if (!details) return;
    details.addEventListener("toggle", function () {
      card.classList.toggle("is-active", details.open);
    });
  });
})();
