(function() {
  "use strict";
  var TAUX_EXEMPLE_ANNUEL = 0.036;
  var TAUX_MENSUEL = TAUX_EXEMPLE_ANNUEL / 12;
  var state = {
    betrag: 1e4,
    laufzeit: 48
  };
  function formatEuro(n) {
    return Math.round(n).toLocaleString("de-DE") + " \u20AC";
  }
  function formatMonate(n) {
    var jahre = n / 12;
    if (Number.isInteger(jahre) && jahre >= 1) {
      return n + " Monate (" + jahre + (jahre === 1 ? " Jahr" : " Jahre") + ")";
    }
    return n + " Monate";
  }
  function calculerRate(betrag, laufzeit) {
    if (TAUX_MENSUEL === 0) return betrag / laufzeit;
    var facteur = Math.pow(1 + TAUX_MENSUEL, laufzeit);
    return betrag * TAUX_MENSUEL * facteur / (facteur - 1);
  }
  function update() {
    var rate = calculerRate(state.betrag, state.laufzeit);
    var rateArrondie = Math.round(rate);
    var gesamt = rateArrondie * state.laufzeit;
    var kosten = gesamt - state.betrag;
    var betragDisplay = document.getElementById("betragDisplay");
    var laufzeitDisplay = document.getElementById("laufzeitDisplay");
    var rateValue = document.getElementById("rateValue");
    var gesamtValue = document.getElementById("gesamtValue");
    var kostenValue = document.getElementById("kostenValue");
    var applyBtn = document.getElementById("applyBtn");
    if (betragDisplay) betragDisplay.textContent = formatEuro(state.betrag);
    if (laufzeitDisplay) laufzeitDisplay.textContent = formatMonate(state.laufzeit);
    if (rateValue) rateValue.textContent = Math.round(rate).toLocaleString("de-DE");
    if (gesamtValue) gesamtValue.textContent = formatEuro(gesamt);
    if (kostenValue) kostenValue.textContent = formatEuro(kosten);
    if (applyBtn) {
      applyBtn.href = "kreditantrag.html?betrag=" + state.betrag + "&laufzeit=" + state.laufzeit;
    }
  }
  function majBoutonsActifs(groupe, valeur, dataAttr) {
    document.querySelectorAll(groupe).forEach(function(btn) {
      btn.classList.toggle("active", parseInt(btn.getAttribute(dataAttr), 10) === valeur);
    });
  }
  function lireParametresURL() {
    var params = new URLSearchParams(window.location.search);
    var betragParam = parseInt(params.get("betrag"), 10);
    var laufzeitParam = parseInt(params.get("laufzeit"), 10);
    if (!isNaN(betragParam) && betragParam >= 1e3 && betragParam <= 1e5) {
      state.betrag = betragParam;
    }
    if (!isNaN(laufzeitParam) && laufzeitParam >= 6 && laufzeitParam <= 360) {
      state.laufzeit = laufzeitParam;
    }
  }
  function updateRangeFill(range) {
    var min = parseFloat(range.min) || 0;
    var max = parseFloat(range.max) || 100;
    var val = parseFloat(range.value) || 0;
    var percent = (val - min) / (max - min) * 100;
    range.style.setProperty("--range-fill", percent + "%");
  }
  function init() {
    var betragRange = document.getElementById("betragRange");
    var laufzeitRange = document.getElementById("laufzeitRange");
    if (!betragRange || !laufzeitRange) return;
    lireParametresURL();
    betragRange.value = state.betrag;
    laufzeitRange.value = state.laufzeit;
    updateRangeFill(betragRange);
    updateRangeFill(laufzeitRange);
    betragRange.addEventListener("input", function() {
      state.betrag = parseInt(betragRange.value, 10);
      majBoutonsActifs("[data-betrag]", state.betrag, "data-betrag");
      updateRangeFill(betragRange);
      update();
    });
    laufzeitRange.addEventListener("input", function() {
      state.laufzeit = parseInt(laufzeitRange.value, 10);
      majBoutonsActifs("[data-laufzeit]", state.laufzeit, "data-laufzeit");
      updateRangeFill(laufzeitRange);
      update();
    });
    document.querySelectorAll("[data-betrag]").forEach(function(btn) {
      btn.addEventListener("click", function() {
        state.betrag = parseInt(btn.getAttribute("data-betrag"), 10);
        betragRange.value = state.betrag;
        majBoutonsActifs("[data-betrag]", state.betrag, "data-betrag");
        updateRangeFill(betragRange);
        update();
      });
    });
    document.querySelectorAll("[data-laufzeit]").forEach(function(btn) {
      btn.addEventListener("click", function() {
        state.laufzeit = parseInt(btn.getAttribute("data-laufzeit"), 10);
        laufzeitRange.value = state.laufzeit;
        majBoutonsActifs("[data-laufzeit]", state.laufzeit, "data-laufzeit");
        updateRangeFill(laufzeitRange);
        update();
      });
    });
    majBoutonsActifs("[data-betrag]", state.betrag, "data-betrag");
    majBoutonsActifs("[data-laufzeit]", state.laufzeit, "data-laufzeit");
    update();
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
