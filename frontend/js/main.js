(function() {
  "use strict";
  function initScrollAnimations() {
    if (!("IntersectionObserver" in window)) return;
    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.12,

      rootMargin: "0px 0px -40px 0px"

    });
    document.querySelectorAll(".animate-on-scroll").forEach(function(el) {
      observer.observe(el);
    });
  }
  function initAccordions() {
    document.querySelectorAll(".accordion-trigger").forEach(function(trigger) {
      trigger.addEventListener("click", function() {
        var item = trigger.closest(".accordion-item");
        if (!item) return;
        var isOpen = item.classList.contains("open");
        var group = item.closest(".accordion-group");
        if (group) {
          group.querySelectorAll(".accordion-item.open").forEach(function(openItem) {
            if (openItem !== item) {
              openItem.classList.remove("open");
              openItem.querySelector(".accordion-trigger").setAttribute("aria-expanded", "false");
            }
          });
        }
        item.classList.toggle("open", !isOpen);
        trigger.setAttribute("aria-expanded", !isOpen ? "true" : "false");
      });
    });
  }
  function initCounters() {
    if (!("IntersectionObserver" in window)) return;
    var counters = document.querySelectorAll("[data-count]");
    if (counters.length === 0) return;
    function formatNombreAllemand(n, decimals, useGrouping) {
      return n.toLocaleString("de-DE", {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
        useGrouping
      });
    }
    var counterObserver = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        var target = parseFloat(el.getAttribute("data-count"));
        var suffix = el.getAttribute("data-suffix") || "";
        var prefix = el.getAttribute("data-prefix") || "";
        var decimals = el.getAttribute("data-decimals") ? parseInt(el.getAttribute("data-decimals")) : 0;
        var useGrouping = el.getAttribute("data-no-grouping") !== "true";
        var duration = 1800;
        var start = null;
        function animate(timestamp) {
          if (!start) start = timestamp;
          var progress = Math.min((timestamp - start) / duration, 1);
          var eased = 1 - Math.pow(1 - progress, 3);
          var current = target * eased;
          el.textContent = prefix + formatNombreAllemand(current, decimals, useGrouping) + suffix;
          if (progress < 1) {
            requestAnimationFrame(animate);
          } else {
            el.textContent = prefix + formatNombreAllemand(target, decimals, useGrouping) + suffix;
          }
        }
        requestAnimationFrame(animate);
        counterObserver.unobserve(el);
      });
    }, { threshold: 0.5 });
    counters.forEach(function(counter) {
      counterObserver.observe(counter);
    });
  }
  function initBackToTop() {
    var btn = document.getElementById("backToTop");
    if (!btn) return;
    window.addEventListener("scroll", function() {
      btn.classList.toggle("visible", window.scrollY > 400);
    }, { passive: true });
    btn.addEventListener("click", function() {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }
  function updateRangeFill(range) {
    var min = parseFloat(range.min) || 0;
    var max = parseFloat(range.max) || 100;
    var val = parseFloat(range.value) || 0;
    var percent = (val - min) / (max - min) * 100;
    range.style.setProperty("--range-fill", percent + "%");
  }
  function initLoanCalculator() {
    var calc = document.getElementById("loanCalculator");
    if (!calc) return;
    var amountInput = calc.querySelector("#calcAmount");
    var durationInput = calc.querySelector("#calcDuration");
    var rateInput = calc.querySelector("#calcRate");
    var resultEl = calc.querySelector("#calcResult");
    var monthlyEl = calc.querySelector("#calcMonthly");
    var totalEl = calc.querySelector("#calcTotal");
    var interestEl = calc.querySelector("#calcInterest");
    function calculate() {
      var amount = parseFloat(amountInput ? amountInput.value : 0) || 0;
      var months = parseInt(durationInput ? durationInput.value : 0) || 0;
      var rateYear = parseFloat(rateInput ? rateInput.value : 0) || 0;
      if (amount <= 0 || months <= 0 || rateYear <= 0) {
        if (resultEl) resultEl.style.display = "none";
        return;
      }
      var r = rateYear / 100 / 12;
      var monthly = amount * r * Math.pow(1 + r, months) / (Math.pow(1 + r, months) - 1);
      var total = monthly * months;
      var interest = total - amount;
      if (monthlyEl) monthlyEl.textContent = formatEuro(monthly);
      if (totalEl) totalEl.textContent = formatEuro(total);
      if (interestEl) interestEl.textContent = formatEuro(interest);
      if (resultEl) resultEl.style.display = "block";
    }
    calc.querySelectorAll('input[type="range"]').forEach(function(range) {
      var display = document.getElementById(range.id + "Display");
      range.addEventListener("input", function() {
        var unit2 = range.getAttribute("data-unit") || "";
        if (display) display.textContent = range.value + unit2;
        updateRangeFill(range);
      });
      var unit = range.getAttribute("data-unit") || "";
      if (display) display.textContent = range.value + unit;
      updateRangeFill(range);
    });
    calculate();
  }
  function formatEuro(value) {
    return new Intl.NumberFormat("de-DE", {
      style: "currency",
      currency: "EUR",
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  }
  window.BlitzUtils = { formatEuro };
  function initRadioOptions() {
    document.querySelectorAll(".form-radio-option").forEach(function(option) {
      var radio = option.querySelector('input[type="radio"]');
      if (!radio) return;
      option.addEventListener("click", function() {
        var groupName = radio.name;
        document.querySelectorAll('input[name="' + groupName + '"]').forEach(function(r) {
          var parent = r.closest(".form-radio-option");
          if (parent) parent.classList.remove("selected");
        });
        radio.checked = true;
        option.classList.add("selected");
      });
      if (radio.checked) option.classList.add("selected");
    });
  }
  function initSmoothAnchors() {
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
      anchor.addEventListener("click", function(e) {
        var targetId = this.getAttribute("href").slice(1);
        var target = document.getElementById(targetId);
        if (!target) return;
        e.preventDefault();
        var offset = 96;
        var top = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top, behavior: "smooth" });
      });
    });
  }
  function onReady() {
    initScrollAnimations();
    initAccordions();
    initCounters();
    initBackToTop();
    initLoanCalculator();
    initRadioOptions();
    initSmoothAnchors();
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", onReady);
  } else {
    onReady();
  }
})();
