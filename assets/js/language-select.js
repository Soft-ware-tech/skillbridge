(function () {
  "use strict";

  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var cards = document.querySelectorAll("[data-tilt]");
  var modal = document.getElementById("langModal");

  // ---- 3D tilt on hover (desktop / pointer devices only) ----
  if (!reduceMotion && window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
    cards.forEach(function (card) {
      var bounds;

      card.addEventListener("pointerenter", function () {
        bounds = card.getBoundingClientRect();
      });

      card.addEventListener("pointermove", function (e) {
        if (!bounds) bounds = card.getBoundingClientRect();
        var x = (e.clientX - bounds.left) / bounds.width;   // 0 -> 1
        var y = (e.clientY - bounds.top) / bounds.height;   // 0 -> 1
        var rotateY = (x - 0.5) * 14;   // left/right tilt
        var rotateX = (0.5 - y) * 14;   // up/down tilt

        card.style.transform =
          "translateY(-4px) rotateX(" + rotateX + "deg) rotateY(" + rotateY + "deg)";
      });

      card.addEventListener("pointerleave", function () {
        card.style.transform = "translateY(0) rotateX(0) rotateY(0)";
      });
    });
  }

  // ---- Selection transition before navigating to the clicked card's href ----
  cards.forEach(function (card) {
    card.addEventListener("click", function (e) {
      if (reduceMotion) return; // let the native navigation happen immediately

      var destination = card.getAttribute("href");
      if (!destination) return;

      e.preventDefault();

      cards.forEach(function (c) {
        if (c !== card) {
          c.style.opacity = "0.35";
          c.style.transform = "scale(0.98)";
        }
      });
      card.style.transform = "scale(1.03)";
      if (modal) {
        modal.style.transition = "opacity 320ms ease, transform 420ms ease";
        modal.style.transform = "scale(0.98)";
        modal.style.opacity = "0";
      }

      window.setTimeout(function () {
        window.location.href = destination;
      }, 320);
    });
  });
})();
