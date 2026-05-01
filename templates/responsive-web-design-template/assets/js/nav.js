const body = document.querySelector("body");
const nav_menu = document.querySelector(".nav-menu");

// Cambiar el color al hacer scroll //
window.addEventListener("scroll", function (e) {
  const last_position = window.scrollY;
  if (last_position > 100) {
    nav_menu.classList.add("scroll-active");
  } else if (nav_menu.classList.contains("scroll-active")) {
    nav_menu.classList.remove("scroll-active");
  } else {
    nav_menu.classList.remove("scroll-active");
  }
});
