// sidebar.js
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar");
  const toggleMenu = document.getElementById("toggle-btn-menu");
  if (toggleMenu) {
    toggleMenu.addEventListener("click", () => sidebar.classList.toggle("collapsed"));
  }
});