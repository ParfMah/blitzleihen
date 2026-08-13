(function() {
  "use strict";
  var header, navToggle, navMobile, navOverlay, navClose;
  function init() {
    header = document.getElementById("siteHeader");
    navToggle = document.getElementById("navToggle");
    navMobile = document.getElementById("navMobile");
    navOverlay = document.getElementById("navOverlay");
    navClose = document.getElementById("navClose");
    function handleScroll() {
      if (!header) return;
      if (window.scrollY > 60) {
        header.classList.add("scrolled");
      } else {
        header.classList.remove("scrolled");
      }
    }
    let ticking = false;
    window.addEventListener("scroll", function() {
      if (!ticking) {
        requestAnimationFrame(function() {
          handleScroll();
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
    handleScroll();
    function openMenu() {
      if (!navMobile || !navOverlay) return;
      navMobile.classList.add("open");
      navOverlay.classList.add("visible");
      document.body.classList.add("menu-open");
      const firstLink = navMobile.querySelector(".nav-mobile__link, .nav-mobile__close");
      if (firstLink) {
        setTimeout(function() {
          firstLink.focus();
        }, 100);
      }
      if (navToggle) navToggle.setAttribute("aria-expanded", "true");
      navToggle.classList.add("open");
    }
    function closeMenu2() {
      if (!navMobile || !navOverlay) return;
      navMobile.classList.remove("open");
      navOverlay.classList.remove("visible");
      document.body.classList.remove("menu-open");
      if (navToggle) {
        navToggle.setAttribute("aria-expanded", "false");
        navToggle.classList.remove("open");
      }
    }
    if (navToggle) {
      navToggle.addEventListener("click", function() {
        const isOpen = navMobile && navMobile.classList.contains("open");
        isOpen ? closeMenu2() : openMenu();
      });
    }
    if (navClose) {
      navClose.addEventListener("click", closeMenu2);
    }
    if (navOverlay) {
      navOverlay.addEventListener("click", closeMenu2);
    }
    document.addEventListener("keydown", function(e) {
      if (e.key === "Escape") closeMenu2();
    });
    function setActiveLinks() {
      const currentPath = window.location.pathname;
      const allNavLinks = document.querySelectorAll(
        ".nav-desktop__link, .nav-mobile__link"
      );
      allNavLinks.forEach(function(link) {
        const linkPath = link.getAttribute("href");
        if (!linkPath) return;
        const linkFile = linkPath.split("/").pop() || "index.html";
        const currentFile = currentPath.split("/").pop() || "index.html";
        const isHome = currentFile === "" || currentFile === "index.html";
        const linkIsHome = linkFile === "index.html" || linkFile === "";
        if (isHome && linkIsHome) {
          link.classList.add("active");
        } else if (!isHome && linkFile === currentFile) {
          link.classList.add("active");
        } else {
          link.classList.remove("active");
        }
      });
    }
    setActiveLinks();
    if (navMobile) {
      navMobile.querySelectorAll(".nav-mobile__link").forEach(function(link) {
        link.addEventListener("click", function() {
          closeMenu2();
        });
      });
    }
  }
  window.addEventListener("resize", function() {
    if (window.innerWidth > 1024) {
      closeMenu();
    }
  });
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
