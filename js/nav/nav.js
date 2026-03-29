const toggle = document.querySelector(".menu-toggle");
const menu = document.querySelector(".menu");

toggle.addEventListener("click", () => {

  toggle.classList.toggle("active");
  menu.classList.toggle("active");

});


