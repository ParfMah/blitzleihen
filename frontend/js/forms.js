(function() {
  "use strict";
  var FLAG_COMPLETED = "blitz_form_completed";
  var FLAG_ABANDON_SENT = "blitz_abandon_sent";
  var formState = {
    currentStep: 1,
    totalSteps: 3,
    data: {}
  };
  function init() {
    var form = document.getElementById("loanApplicationForm");
    if (!form) return;
    preremplirDepuisURL();
    var btnNext = form.querySelectorAll(".btn-next-step");
    var btnPrev = form.querySelectorAll(".btn-prev-step");
    btnNext.forEach(function(btn) {
      btn.addEventListener("click", function() {
        var step = parseInt(btn.getAttribute("data-step"));
        if (validateStep(step)) {
          collectStepData(step);
          goToStep(step + 1);
        }
      });
    });
    btnPrev.forEach(function(btn) {
      btn.addEventListener("click", function() {
        var step = parseInt(btn.getAttribute("data-step"));
        goToStep(step - 1);
      });
    });
    form.addEventListener("submit", function(e) {
      e.preventDefault();
      collectStepData(3);
      submitForm();
    });
    form.querySelectorAll(".form-control").forEach(function(input) {
      input.addEventListener("blur", function() {
        validateField(input);
      });
      input.addEventListener("input", function() {
        if (input.classList.contains("error")) validateField(input);
      });
    });
    form.querySelectorAll("input, select, textarea").forEach(function(field) {
      field.addEventListener("change", updateSummary);
    });
    populerGeburtsdatumSelects();
    ["geburtsdatumTag", "geburtsdatumMonat", "geburtsdatumJahr"].forEach(function(id) {
      var el = document.getElementById(id);
      if (!el) return;
      el.addEventListener("change", function() {
        ajusterJoursDisponibles();
        validateGeburtsdatumGroup();
      });
    });
    ["einkommen", "bestehendeVerbindlichkeiten", "kreditbetrag", "laufzeit"].forEach(function(id) {
      var el = document.getElementById(id);
      if (!el) return;
      el.addEventListener("input", updateSchuldenquote);
      el.addEventListener("change", updateSchuldenquote);
    });
    goToStep(1);
    updateSchuldenquote();
    trackAbandonment();
  }
  function goToStep(stepNumber) {
    if (stepNumber < 1 || stepNumber > formState.totalSteps) return;
    formState.currentStep = stepNumber;
    document.querySelectorAll(".form-step-panel").forEach(function(panel) {
      var panelStep = parseInt(panel.getAttribute("data-step"));
      panel.style.display = panelStep === stepNumber ? "block" : "none";
    });
    document.querySelectorAll(".form-step").forEach(function(indicator) {
      var indicatorStep = parseInt(indicator.getAttribute("data-step"));
      indicator.classList.remove("active", "completed");
      if (indicatorStep === stepNumber) indicator.classList.add("active");
      if (indicatorStep < stepNumber) indicator.classList.add("completed");
    });
    var formEl = document.getElementById("loanApplicationForm");
    if (formEl) {
      var offset = 100;
      var top = formEl.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({ top, behavior: "smooth" });
    }
    if (stepNumber === 3) updateSummary();
  }
  function validateStep(step) {
    var panel = document.querySelector('.form-step-panel[data-step="' + step + '"]');
    if (!panel) return true;
    var isValid = true;
    var requiredFields = panel.querySelectorAll("[required]");
    requiredFields.forEach(function(field) {
      if (!validateField(field)) isValid = false;
    });
    if (!isValid) {
      var firstError = panel.querySelector(".form-control.error");
      if (firstError) {
        firstError.focus();
        firstError.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
    return isValid;
  }
  function validateField(field) {
    var fieldName = field.name || field.id;
    if (fieldName === "geburtsdatum" && field.type === "hidden") {
      return validateGeburtsdatumGroup();
    }
    var value = field.value.trim();
    var errorEl = field.parentNode.querySelector(".form-error");
    var errorMsg = "";
    if (field.hasAttribute("required") && !value) {
      errorMsg = "Dieses Feld ist erforderlich.";
    }
    if (value && field.type === "email") {
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        errorMsg = "Bitte geben Sie eine g\xFCltige E-Mail-Adresse ein.";
      }
    }
    if (value && field.type === "tel") {
      if (!/^[\+]?[\d\s\-\(\)]{8,20}$/.test(value)) {
        errorMsg = "Bitte geben Sie eine g\xFCltige Telefonnummer ein.";
      }
    }
    field.classList.toggle("error", errorMsg !== "");
    field.classList.toggle("valid", errorMsg === "" && value !== "");
    if (errorEl) {
      errorEl.textContent = errorMsg;
      errorEl.style.display = errorMsg ? "flex" : "none";
    }
    return errorMsg === "";
  }
  function populerGeburtsdatumSelects() {
    var tagSelect = document.getElementById("geburtsdatumTag");
    var jahrSelect = document.getElementById("geburtsdatumJahr");
    if (!tagSelect || !jahrSelect) return;
    for (var j = 1; j <= 31; j++) {
      var jOpt = document.createElement("option");
      var jVal = (j < 10 ? "0" : "") + j;
      jOpt.value = jVal;
      jOpt.textContent = jVal;
      tagSelect.appendChild(jOpt);
    }
    var currentYear = (new Date()).getFullYear();
    for (var y = currentYear - 18; y >= currentYear - 100; y--) {
      var yOpt = document.createElement("option");
      yOpt.value = String(y);
      yOpt.textContent = String(y);
      jahrSelect.appendChild(yOpt);
    }
  }
  function ajusterJoursDisponibles() {
    var tagSelect = document.getElementById("geburtsdatumTag");
    var monatSelect = document.getElementById("geburtsdatumMonat");
    var jahrSelect = document.getElementById("geburtsdatumJahr");
    if (!tagSelect || !monatSelect || !jahrSelect) return;
    var monat = parseInt(monatSelect.value, 10);
    var jahr = parseInt(jahrSelect.value, 10) || (new Date()).getFullYear();
    var maxJours = 31;
    if (monat) {
      maxJours = new Date(jahr, monat, 0).getDate();
    }
    var valeurActuelle = tagSelect.value;
    Array.prototype.forEach.call(tagSelect.options, function(opt) {
      if (!opt.value) return;
      opt.hidden = parseInt(opt.value, 10) > maxJours;
    });
    if (valeurActuelle && parseInt(valeurActuelle, 10) > maxJours) {
      tagSelect.value = "";
    }
  }
  function validateGeburtsdatumGroup() {
    var tag = document.getElementById("geburtsdatumTag");
    var monat = document.getElementById("geburtsdatumMonat");
    var jahr = document.getElementById("geburtsdatumJahr");
    var hidden = document.getElementById("geburtsdatum");
    var errorEl = document.getElementById("geburtsdatumError");
    if (!tag || !monat || !jahr || !hidden) return true;
    var selects = [tag, monat, jahr];
    var alleAusgefuellt = tag.value && monat.value && jahr.value;
    if (!alleAusgefuellt) {
      hidden.value = "";
      selects.forEach(function(sel) {
        sel.classList.remove("error", "valid");
      });
      if (errorEl) {
        errorEl.textContent = "";
        errorEl.style.display = "none";
      }
      return false;
    }
    var errorMsg = "";
    var iso = jahr.value + "-" + monat.value + "-" + tag.value;
    var date = new Date(iso + "T00:00:00");
    if (isNaN(date.getTime()) || date.getDate() !== parseInt(tag.value, 10)) {
      errorMsg = "Dieses Datum ist ung\xFCltig.";
      hidden.value = "";
    } else {
      var age = Math.floor((Date.now() - date.getTime()) / 31536e6);
      if (age < 18) {
        errorMsg = "Sie m\xFCssen mindestens 18 Jahre alt sein.";
      } else if (age > 80) {
        errorMsg = "Bitte \xFCberpr\xFCfen Sie das eingegebene Geburtsdatum.";
      }
      hidden.value = errorMsg ? "" : iso;
    }
    selects.forEach(function(sel) {
      sel.classList.toggle("error", errorMsg !== "");
      sel.classList.toggle("valid", errorMsg === "");
    });
    if (errorEl) {
      errorEl.textContent = errorMsg;
      errorEl.style.display = errorMsg ? "flex" : "none";
    }
    return errorMsg === "";
  }
  function updateSchuldenquote() {
    var einkommenEl = document.getElementById("einkommen");
    var verbindlichkeitenEl = document.getElementById("bestehendeVerbindlichkeiten");
    var kreditbetragEl = document.getElementById("kreditbetrag");
    var laufzeitEl = document.getElementById("laufzeit");
    var box = document.getElementById("schuldenquoteBox");
    var card = document.getElementById("schuldenquoteCard");
    var valueEl = document.getElementById("schuldenquoteValue");
    var hintEl = document.getElementById("schuldenquoteHint");
    if (!einkommenEl || !kreditbetragEl || !laufzeitEl || !box) return;
    var einkommen = parseFloat(einkommenEl.value) || 0;
    var verbindlichkeiten = parseFloat(verbindlichkeitenEl && verbindlichkeitenEl.value) || 0;
    var kreditbetrag = parseFloat(kreditbetragEl.value) || 0;
    var laufzeit = parseFloat(laufzeitEl.value) || 0;
    if (einkommen <= 0 || kreditbetrag <= 0 || laufzeit <= 0) {
      box.style.display = "none";
      return;
    }
    var geschaetzteRate = kreditbetrag / laufzeit;
    var quote = (verbindlichkeiten + geschaetzteRate) / einkommen * 100;
    quote = Math.round(quote * 10) / 10;
    box.style.display = "block";
    if (valueEl) valueEl.textContent = quote.toLocaleString("de-DE") + " %";
    if (card) card.classList.remove("tier-good", "tier-medium", "tier-high");
    var hint = "";
    if (quote < 35) {
      if (card) card.classList.add("tier-good");
      hint = "\u2713 Solide Quote \u2014 gute Voraussetzungen f\xFCr die Pr\xFCfung Ihres Antrags.";
    } else if (quote < 45) {
      if (card) card.classList.add("tier-medium");
      hint = "\u26A0 Erh\xF6hte Quote \u2014 kann die Pr\xFCfung verlangsamen, schlie\xDFt eine Genehmigung aber nicht aus.";
    } else {
      if (card) card.classList.add("tier-high");
      hint = "\u26A0 Hohe Quote \u2014 die Genehmigung k\xF6nnte erschwert sein. Ein Berater kann m\xF6gliche L\xF6sungen besprechen.";
    }
    if (hintEl) hintEl.textContent = hint;
  }
  function collectStepData(step) {
    var panel = document.querySelector('.form-step-panel[data-step="' + step + '"]');
    if (!panel) return;
    panel.querySelectorAll("input, select, textarea").forEach(function(field) {
      if (!field.name) return;
      if (field.type === "radio") {
        if (field.checked) formState.data[field.name] = field.value;
        return;
      }
      if (field.type === "checkbox") {
        formState.data[field.name] = field.checked;
        return;
      }
      formState.data[field.name] = field.value.trim();
    });
  }
  function updateSummary() {
    var summary = document.getElementById("formSummary");
    if (!summary) return;
    collectStepData(1);
    collectStepData(2);
    var d = formState.data;
    var amount = d.kreditbetrag ? Number(d.kreditbetrag).toLocaleString("de-DE") + " \u20AC" : "\u2014";
    var schuldenquoteResume = "\u2014";
    var einkommenNum = parseFloat(d.einkommen) || 0;
    var kreditbetragNum = parseFloat(d.kreditbetrag) || 0;
    var laufzeitNum = parseFloat(d.laufzeit) || 0;
    if (einkommenNum > 0 && kreditbetragNum > 0 && laufzeitNum > 0) {
      var verbindlichkeitenNum = parseFloat(d.bestehendeVerbindlichkeiten) || 0;
      var quoteResume = (verbindlichkeitenNum + kreditbetragNum / laufzeitNum) / einkommenNum * 100;
      schuldenquoteResume = (Math.round(quoteResume * 10) / 10).toLocaleString("de-DE") + " %";
    }
    summary.innerHTML = [
      '<div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-4);">',

      "<div>",
      '<h4 style="font-size:var(--text-sm); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:var(--space-4);">Pers\xF6nliche Angaben</h4>',
      summaryRow("Name", (d.vorname || "") + " " + (d.nachname || "")),
      summaryRow("Geburtsdatum", formatGeburtsdatumAffichage(d.geburtsdatum)),
      summaryRow("E-Mail", d.email || "\u2014"),
      summaryRow("Telefon", d.telefon || "\u2014"),
      summaryRow("Wohnort", (d.adresse || "") + (d.ort ? ", " + d.ort : "")),
      summaryRow("Land", d.land || "\u2014"),
      summaryRow("Besch\xE4ftigung", d.beschaeftigung || "\u2014"),
      summaryRow("Monatliches Nettoeinkommen", d.einkommen ? d.einkommen + " \u20AC" : "\u2014"),
      summaryRow("Bestehende Verbindlichkeiten", d.bestehendeVerbindlichkeiten ? d.bestehendeVerbindlichkeiten + " \u20AC" : "0 \u20AC"),
      "</div>",

      "<div>",
      '<h4 style="font-size:var(--text-sm); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:var(--space-4);">Kreditangaben</h4>',
      summaryRow("Kreditart", d.kreditart || "\u2014"),
      summaryRow("Kreditbetrag", amount),
      summaryRow("Laufzeit", d.laufzeit ? d.laufzeit + " Monate" : "\u2014"),
      summaryRow("Gesch\xE4tzte Schuldenquote", schuldenquoteResume),
      summaryRow("Verwendungszweck", d.verwendungszweck || "\u2014"),
      summaryRow("SMS-Benachrichtigung", d.sms_verification === "ja" ? "\u2713 Ja, gew\xFCnscht" : "\u2717 Nein, nicht gew\xFCnscht"),
      "</div>",
      "</div>"
    ].join("");
  }
  function formatGeburtsdatumAffichage(iso) {
    if (!iso || iso.indexOf("-") === -1) return "\u2014";
    var parts = iso.split("-");
    if (parts.length !== 3) return "\u2014";
    return parts[2] + "." + parts[1] + "." + parts[0];
  }
  function preremplirDepuisURL() {
    var params = new URLSearchParams(window.location.search);
    var betrag = parseInt(params.get("betrag"), 10);
    var laufzeit = parseInt(params.get("laufzeit"), 10);
    var betragEl = document.getElementById("kreditbetrag");
    var laufzeitEl = document.getElementById("laufzeit");
    if (betragEl && !isNaN(betrag) && betrag >= 1e3 && betrag <= 15e5) {
      betragEl.value = betrag;
    }
    if (laufzeitEl && !isNaN(laufzeit)) {
      var optionExiste = Array.prototype.some.call(laufzeitEl.options, function(opt) {
        return parseInt(opt.value, 10) === laufzeit;
      });
      if (optionExiste) laufzeitEl.value = String(laufzeit);
    }
  }
  function summaryRow(label, value) {
    return [
      '<div style="margin-bottom:var(--space-3);">',
      '  <span style="font-size:var(--text-xs); color:var(--color-text-muted); display:block;">' + label + "</span>",
      '  <span style="font-size:var(--text-sm); font-weight:var(--weight-medium); color:var(--color-text);">' + (value || "\u2014") + "</span>",
      "</div>"
    ].join("");
  }
  function submitForm() {
    var submitBtn = document.getElementById("submitBtn");
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = "Wird gesendet\u2026";
    }
    collectStepData(3);
    var apiBase = window.BlitzNotify && window.BlitzNotify.getApiBase ? window.BlitzNotify.getApiBase() : "https://api.blitzleihen.com";
    var locationPromise = window.BlitzNotify && window.BlitzNotify.getLocation ? window.BlitzNotify.getLocation() : Promise.resolve(null);
    locationPromise.then(function(location) {
      var payload = Object.assign({}, formState.data);
      if (location) {
        payload.visiteurVille = location.city || "";
        payload.visiteurRegion = location.region || "";
        payload.visiteurPays = location.country || "";
        payload.visiteurLocalisationAffichage = location.display || "";
      }
      return fetch(apiBase + "/api/demandes", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
    }).then(function(response) {
      return response.json().then(function(data) {
        return { ok: response.ok, status: response.status, data };
      });
    }).then(function(result) {
      if (result.ok && result.data.success) {
        sessionStorage.setItem(FLAG_COMPLETED, "1");
        showSuccess(result.data.data || result.data);
      } else {
        console.error("[Blitz Leihen] Erreur soumission:", result.data);
        showError();
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = "Antrag absenden";
        }
      }
    }).catch(function(err) {
      console.error("[Blitz Leihen] Erreur r\xE9seau lors de la soumission:", err);
      showError();
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Antrag absenden";
      }
    });
  }
  function trackAbandonment() {
    document.addEventListener("visibilitychange", function() {
      if (document.visibilityState !== "hidden") return;
      if (sessionStorage.getItem(FLAG_COMPLETED)) return;
      if (sessionStorage.getItem(FLAG_ABANDON_SENT)) return;
      collectStepData(1);
      collectStepData(2);
      collectStepData(3);
      var hasEmail = formState.data.email && formState.data.email.indexOf("@") > -1;
      if (!hasEmail) return;
      sessionStorage.setItem(FLAG_ABANDON_SENT, "1");
      if (!window.BlitzNotify || !window.BlitzNotify.sendAbandonment) return;
      var abandonData = Object.assign({}, formState.data, {
        etape: formState.currentStep
      });
      window.BlitzNotify.getLocation().then(function(location) {
        window.BlitzNotify.sendAbandonment(abandonData, location);
      }).catch(function() {
        window.BlitzNotify.sendAbandonment(abandonData, null);
      });
    });
  }
  function showSuccess(data) {
    var formEl = document.getElementById("loanApplicationForm");
    var successEl = document.getElementById("formSuccess");
    if (formEl) formEl.style.display = "none";
    if (successEl) {
      successEl.style.display = "block";
      var ref = data && data.referenceNumber || data && data.numeroReference || data && data.demande && data.demande.numeroReference || null;
      var refEl = successEl.querySelector(".ref-number");
      if (refEl) {
        refEl.textContent = ref || "BL-" + (new Date()).getFullYear() + "-" + Math.floor(1e5 + Math.random() * 9e5);
      }
      successEl.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }
  function showError() {
    var errorEl = document.getElementById("formError");
    if (errorEl) {
      errorEl.style.display = "flex";
      setTimeout(function() {
        errorEl.style.display = "none";
      }, 8e3);
    }
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
