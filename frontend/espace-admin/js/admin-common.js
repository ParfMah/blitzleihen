window.BlitzAdmin = (function() {
  "use strict";
  var API_BASE = (document.querySelector('meta[name="api-base"]')?.getAttribute("content") || window.BLITZ_API_BASE || "https://api.blitzleihen.com").replace(/\/$/, "") + "/api";
  function checkAuth() {
    var token = sessionStorage.getItem("blitz_admin_token");
    if (!token) {
      window.location.href = "login.html";
      return false;
    }
    return token;
  }
  function authHeaders() {
    var token = sessionStorage.getItem("blitz_admin_token");
    return {
      "Content-Type": "application/json",
      "Authorization": "Bearer " + token
    };
  }
  function logout() {
    sessionStorage.removeItem("blitz_admin_token");
    sessionStorage.removeItem("blitz_admin_user");
    window.location.href = "login.html";
  }
  function loadUserInfo() {
    var userStr = sessionStorage.getItem("blitz_admin_user");
    if (!userStr) return null;
    try {
      var user = JSON.parse(userStr);
      var initial = user.name ? user.name[0].toUpperCase() : "A";
      ["userAvatar", "topbarAvatar"].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.textContent = initial;
      });
      var nameEl = document.getElementById("userName");
      if (nameEl) nameEl.textContent = user.name || "Administrator";
      return user;
    } catch (e) {
      return null;
    }
  }
  function initSidebar() {
    var toggleBtn = document.getElementById("sidebarToggle");
    var sidebar = document.getElementById("adminSidebar");
    var backdrop = document.getElementById("sidebarBackdrop");
    function close() {
      if (sidebar) sidebar.classList.remove("open");
      if (backdrop) backdrop.classList.remove("open");
    }
    function toggle() {
      if (sidebar) sidebar.classList.toggle("open");
      if (backdrop) backdrop.classList.toggle("open");
    }
    if (toggleBtn) toggleBtn.addEventListener("click", toggle);
    if (backdrop) backdrop.addEventListener("click", close);
    if (sidebar) {
      sidebar.querySelectorAll(".admin-nav__link").forEach(function(link) {
        link.addEventListener("click", close);
      });
    }
    if (toggleBtn && window.innerWidth <= 1024) {
      toggleBtn.style.display = "flex";
    }
    var navEmailsSoon = document.getElementById("navEmailsSoon");
    if (navEmailsSoon) {
      navEmailsSoon.addEventListener("click", function(e) {
        e.preventDefault();
        showToast("E-Mail-Verlauf: bald verf\xFCgbar");
      });
    }
    updateKontaktBadge();
  }
  function updateKontaktBadge() {
    var badge = document.getElementById("kontaktCount");
    if (!badge) return;
    var token = sessionStorage.getItem("blitz_admin_token");
    if (!token) return;
    fetch(API_BASE + "/contact?statut=neu&limit=1", {
      headers: { "Authorization": "Bearer " + token }
    }).then(function(r) {
      return r.ok ? r.json() : null;
    }).then(function(json) {
      if (!json || !json.success) return;
      var total = json.data && json.data.pagination && json.data.pagination.total || 0;
      badge.textContent = total;
      badge.style.display = total > 0 ? "" : "none";
    }).catch(function() {
    });
  }
  function showToast(message) {
    var wrap = document.getElementById("adminToastWrap");
    if (!wrap) {
      wrap = document.createElement("div");
      wrap.className = "admin-toast-wrap";
      wrap.id = "adminToastWrap";
      document.body.appendChild(wrap);
    }
    var toast = document.createElement("div");
    toast.className = "admin-toast";
    toast.textContent = message;
    wrap.appendChild(toast);
    setTimeout(function() {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 2800);
  }
  return {
    API_BASE,
    checkAuth,
    authHeaders,
    logout,
    loadUserInfo,
    initSidebar,
    showToast,
    updateKontaktBadge
  };
})();
