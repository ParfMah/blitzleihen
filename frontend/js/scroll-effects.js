(function() {
  "use strict";
  function init() {
    if (!window.gsap || !window.ScrollTrigger) return;
    gsap.registerPlugin(ScrollTrigger);
    var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var elements = document.querySelectorAll(".animate-on-scroll");
    if (!elements.length) return;
    if (reduceMotion) {
      elements.forEach(function(el) {
        el.classList.add("is-visible");
      });
      return;
    }
    var groups = new Map();
    elements.forEach(function(el) {
      var parent = el.parentElement;
      if (!groups.has(parent)) groups.set(parent, []);
      groups.get(parent).push(el);
    });
    groups.forEach(function(group) {
      gsap.set(group, { opacity: 0, y: 24 });
      ScrollTrigger.batch(group, {
        start: "top 88%",
        once: true,
        onEnter: function(batch) {
          gsap.to(batch, {
            opacity: 1,
            y: 0,
            duration: 0.65,
            ease: "power2.out",
            stagger: 0.12,
            onComplete: function() {
              batch.forEach(function(el) {
                el.classList.add("is-visible");
              });
            }
          });
        }
      });
    });
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
