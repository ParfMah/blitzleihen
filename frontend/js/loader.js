(function() {
  "use strict";
  function hideLoader() {
    var loader = document.getElementById("pageLoader");
    if (!loader) return;
    loader.classList.add("fade-out");
    setTimeout(function() {
      var el = document.getElementById("pageLoader");
      if (el && el.parentNode) {
        el.parentNode.removeChild(el);
      }
    }, 520);
  }
  function forceRemoveLoader() {
    var loader = document.getElementById("pageLoader");
    if (loader && loader.parentNode) {
      loader.parentNode.removeChild(loader);
    }
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function() {
      setTimeout(hideLoader, 300);
    });
  } else {
    setTimeout(hideLoader, 300);
  }
  window.addEventListener("pageshow", function(event) {
    if (event.persisted) {
      forceRemoveLoader();
    }
  });
  setTimeout(forceRemoveLoader, 2500);
  document.addEventListener("click", function(e) {
    var link = e.target.closest("a[href]");
    if (!link) return;
    var href = link.getAttribute("href");
    if (!href || href.startsWith("#") || href.startsWith("mailto:") || href.startsWith("tel:") || href.startsWith("http") || link.target === "_blank" || e.ctrlKey || e.metaKey || e.shiftKey) return;
    e.preventDefault();
    var isAdmin = window.location.pathname.indexOf("/admin/") !== -1;
    var logo = isAdmin ? "../blitz_leihen_logo.webp" : "blitz_leihen_logo.webp";
    var overlay = document.createElement("div");
    overlay.id = "pageLoader";
    overlay.className = "page-loader";
    overlay.innerHTML = '<div class="page-loader__inner"><div class="page-loader__ring"></div><img src="' + logo + '" alt="Blitz Leihen" class="page-loader__logo-img"></div>';
    document.body.appendChild(overlay);
    setTimeout(function() {
      window.location.href = href;
    }, 280);
  });
  window.BlitzLoader = { hide: hideLoader, forceRemove: forceRemoveLoader };
})();
