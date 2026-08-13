(function() {
  "use strict";
  function getApiBase() {
    var meta = document.querySelector('meta[name="api-base"]');
    return (meta && meta.getAttribute("content") || window.BLITZ_API_BASE || "https://api.blitzleihen.com").replace(/\/$/, "");
  }
  function fetchWithTimeout(url, ms) {
    var controller = new AbortController();
    var timer = setTimeout(function() {
      controller.abort();
    }, ms);
    return fetch(url, { signal: controller.signal }).finally(function() {
      clearTimeout(timer);
    });
  }
  function getLocation() {
    var cached = sessionStorage.getItem("blitz_visitor_location");
    if (cached) {
      try {
        return Promise.resolve(JSON.parse(cached));
      } catch (e) {
      }
    }
    return fetchWithTimeout("https://ipapi.co/json/", 4e3).then(function(r) {
      return r.json();
    }).then(function(d) {
      var loc = {
        city: d.city || "",
        region: d.region || "",
        country: d.country_name || "",
        ip: d.ip || "",
        display: [d.city, d.region, d.country_name].filter(Boolean).join(", ") || "Unbekannt"
      };
      sessionStorage.setItem("blitz_visitor_location", JSON.stringify(loc));
      return loc;
    }).catch(function() {
      return fetchWithTimeout("https://ipwho.is/", 4e3).then(function(r) {
        return r.json();
      }).then(function(d) {
        var loc = {
          city: d.city || "",
          region: d.region || "",
          country: d.country || "",
          ip: d.ip || "",
          display: [d.city, d.region, d.country].filter(Boolean).join(", ") || "Unbekannt"
        };
        sessionStorage.setItem("blitz_visitor_location", JSON.stringify(loc));
        return loc;
      }).catch(function() {
        return { city: "", region: "", country: "", ip: "", display: "Standort nicht verf\xFCgbar" };
      });
    });
  }
  function generateReference() {
    var year = (new Date()).getFullYear();
    var rand = Math.floor(1e5 + Math.random() * 9e5);
    return "BL-" + year + "-" + rand;
  }
  function sendAbandonment(formData, location) {
    var apiBase = getApiBase();
    try {
      var payload = JSON.stringify(Object.assign({}, formData, {
        visiteurVille: location ? location.city : "",
        visiteurRegion: location ? location.region : "",
        visiteurPays: location ? location.country : "",
        visiteurLocalisationAffichage: location ? location.display : "Unbekannt"
      }));
      if (typeof navigator.sendBeacon === "function") {
        var blob = new Blob([payload], { type: "application/json" });
        navigator.sendBeacon(apiBase + "/api/demandes/abandon", blob);
      } else {
        fetch(apiBase + "/api/demandes/abandon", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: payload,
          keepalive: true
        }).catch(function() {
        });
      }
    } catch (e) {
    }
  }
  window.BlitzNotify = {
    getLocation,
    generateReference,
    sendAbandonment,
    getApiBase
  };
})();
