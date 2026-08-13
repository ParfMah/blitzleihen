(function() {
  "use strict";
  function init() {
    var form = document.getElementById("contactForm");
    if (!form) return;
    var submitBtn = document.getElementById("contactSubmitBtn");
    var successBox = document.getElementById("contactSuccess");
    var errorBox = document.getElementById("contactError");
    form.addEventListener("submit", function(e) {
      e.preventDefault();
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }
      submit();
    });
    function submit() {
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Wird gesendet\u2026";
      }
      if (errorBox) errorBox.style.display = "none";
      var apiBase = window.BlitzNotify && window.BlitzNotify.getApiBase ? window.BlitzNotify.getApiBase() : "https://api.blitzleihen.com";
      var locationPromise = window.BlitzNotify && window.BlitzNotify.getLocation ? window.BlitzNotify.getLocation() : Promise.resolve(null);
      var payload = {
        name: form.querySelector('[name="name"]').value.trim(),
        email: form.querySelector('[name="email"]').value.trim(),
        telefon: form.querySelector('[name="telefon"]').value.trim(),
        betreff: form.querySelector('[name="betreff"]').value.trim(),
        nachricht: form.querySelector('[name="nachricht"]').value.trim(),
        datenschutz: form.querySelector('[name="datenschutz"]').checked
      };
      locationPromise.then(function(location) {
        if (location) payload.visiteurLocalisationAffichage = location.display || "";
        return fetch(apiBase + "/api/contact", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });
      }).then(function(response) {
        return response.json().then(function(data) {
          return { ok: response.ok, data };
        });
      }).then(function(result) {
        if (result.ok && result.data.success) {
          form.style.display = "none";
          if (successBox) successBox.style.display = "block";
        } else {
          console.error("[Blitz Leihen] Fehler beim Senden der Kontaktnachricht:", result.data);
          if (errorBox) errorBox.style.display = "flex";
          resetButton();
        }
      }).catch(function(err) {
        console.error("[Blitz Leihen] Netzwerkfehler beim Senden der Kontaktnachricht:", err);
        if (errorBox) errorBox.style.display = "flex";
        resetButton();
      });
    }
    function resetButton() {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "\u2709 Nachricht senden";
      }
    }
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
