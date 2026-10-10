document.addEventListener("DOMContentLoaded", () => {
  "use strict";

  const sidebar = document.getElementById("appSidebar");
  const toggle = document.getElementById("sidebarToggle");
  const overlay = document.getElementById("sidebarOverlay");

  // Required sidebar elements না থাকলে execution বন্ধ হবে।
  if (!sidebar || !toggle || !overlay) {
    return;
  }

  const closeSidebar = () => {
    sidebar.classList.remove("sidebar-open");
    overlay.classList.remove("show");
    toggle.setAttribute("aria-expanded", "false");
    document.body.classList.remove("sidebar-visible");
  };

  const openSidebar = () => {
    sidebar.classList.add("sidebar-open");
    overlay.classList.add("show");
    toggle.setAttribute("aria-expanded", "true");
    document.body.classList.add("sidebar-visible");
  };

  // Mobile menu toggle
  toggle.addEventListener("click", () => {
    const isOpen = sidebar.classList.contains("sidebar-open");

    if (isOpen) {
      closeSidebar();
    } else {
      openSidebar();
    }
  });

  // Overlay click করলে sidebar বন্ধ হবে।
  overlay.addEventListener("click", closeSidebar);

  // Escape key চাপলে sidebar বন্ধ হবে।
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeSidebar();
    }
  });

  // Mobile screen-এ navigation link click করলে sidebar বন্ধ হবে।
  sidebar.querySelectorAll(".sidebar-link").forEach((link) => {
    link.addEventListener("click", () => {
      if (window.innerWidth <= 900) {
        closeSidebar();
      }
    });
  });

  // Desktop-এ resize করলে mobile sidebar-এর stale state পরিষ্কার হবে।
  window.addEventListener("resize", () => {
    if (window.innerWidth > 900) {
      closeSidebar();
    }
  });
});
