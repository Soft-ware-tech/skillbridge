(function () {
  var burger = document.getElementById("dashNavBurger");
  var links = document.getElementById("dashNavLinks");
  if (!burger || !links) return;

  burger.addEventListener("click", function () {
    var isOpen = links.classList.toggle("is-open");
    burger.classList.toggle("is-open", isOpen);
    burger.setAttribute("aria-expanded", isOpen ? "true" : "false");
  });

  links.addEventListener("click", function (e) {
    if (e.target.tagName === "A") {
      links.classList.remove("is-open");
      burger.classList.remove("is-open");
      burger.setAttribute("aria-expanded", "false");
    }
  });
})();
