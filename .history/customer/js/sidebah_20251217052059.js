// sidebar.js - Legacy support
// Main sidebar functionality is now in header_nav.js
// This file is kept for backward compatibility

document.addEventListener("DOMContentLoaded", () => {
  // Only run if header_nav.js hasn't already set up sidebar
  if (window.sidebarSetupComplete) return;
  
  const sidebar = document.getElementById("sidebar");
  const toggleMenu = document.getElementById("toggle-btn-menu");
  
  if (toggleMenu && sidebar && !toggleMenu._sidebarBound) {
    toggleMenu._sidebarBound = true;
    toggleMenu.addEventListener("click", () => {
      if (window.innerWidth <= 768) {
        sidebar.classList.toggle("show");
        const overlay = document.getElementById("sidebar-overlay");
        if (overlay) overlay.classList.toggle("show");
      } else {
        sidebar.classList.toggle("collapsed");
      }
    });
  }
});