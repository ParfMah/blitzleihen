(function() {
  "use strict";
  var MEDIA = {

    offersGallery: [
      { src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1784145824/privatkredit-blitz-leihen.webp", alt: "Privatkredit f\xFCr pers\xF6nliche Projekte", eyebrow: "Privatkredit", title: "F\xFCr Ihre pers\xF6nlichen Projekte", desc: "Client heureux, projet personnel (voyage, achat) \u2014 1600\xD7900px" },
      { src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1784145821/immobilienfinanzierung-traumhaus.webp", alt: "Immobilienfinanzierung f\xFCr das Eigenheim", eyebrow: "Immobilienfinanzierung", title: "Ihr Traumhaus wird Realit\xE4t", desc: "Maison individuelle moderne \u2014 1600\xD7900px" },
      { src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1784145820/autokredit-neuwagen-finanzierung.webp", alt: "Autofinanzierung f\xFCr den Neuwagenkauf", eyebrow: "Autofinanzierung", title: "Ihr neues Auto, sofort verf\xFCgbar", desc: "V\xE9hicule neuf, concession moderne \u2014 1600\xD7900px" },
      { src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1784145825/renovierungskredit-modernisierung-kueche.webp", alt: "Renovierungskredit f\xFCr die Modernisierung", eyebrow: "Renovierungskredit", title: "Investieren Sie in Ihr Zuhause", desc: "R\xE9novation int\xE9rieure moderne, artisan au travail \u2014 1600\xD7900px" },
      { src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1784145820/hypothekenkredit-immobiliensicherheit.webp", alt: "Hypothekenkredit mit Immobiliensicherheit", eyebrow: "Hypothekenkredit", title: "Gro\xDFz\xFCgiger Kapitalspielraum", desc: "Grande propri\xE9t\xE9, ambiance patrimoniale \u2014 1600\xD7900px" },
      { src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1784145825/umschuldung-kredit-zusammenfassen.webp", alt: "Umschuldung mehrerer bestehender Kredite", eyebrow: "Umschuldung", title: "Eine einzige, g\xFCnstigere Rate", desc: "Personne sereine consultant ses finances \u2014 1600\xD7900px" }
    ]

  };
  function estUrlReelle(src) {
    return !!src && src.indexOf("http") === 0;
  }
  function creerPlaceholder(desc, label) {
    var ph = document.createElement("div");
    ph.className = "media-ph";
    ph.innerHTML = '<span class="media-ph__icon" aria-hidden="true">\u{1F5BC}\uFE0F</span><strong class="media-ph__label">' + (label || "Cloudinary-Bild") + '</strong><span class="media-ph__desc">' + desc + '</span><code class="media-ph__code">\u2192 src Cloudinary in js/bg-effects.js eintragen</code>';
    return ph;
  }
  function initDistortion(containerId, images) {
    var container = document.getElementById(containerId);
    if (!container || !images || !images.length) return;
    var dots = document.createElement("div");
    dots.className = "distort-gallery__dots";
    images.forEach(function(img, i) {
      var slide = document.createElement("div");
      slide.className = "distort-gallery__slide" + (i === 0 ? " is-active" : "");
      if (estUrlReelle(img.src)) {
        slide.style.backgroundImage = "url(" + img.src + ")";
      } else {
        slide.appendChild(creerPlaceholder(img.desc, img.alt));
      }
      slide.setAttribute("role", "img");
      slide.setAttribute("aria-label", img.alt);
      container.appendChild(slide);
      var dot = document.createElement("button");
      dot.type = "button";
      dot.className = "distort-gallery__dot" + (i === 0 ? " active" : "");
      dot.setAttribute("aria-label", img.eyebrow + " anzeigen");
      dot.addEventListener("click", function() {
        goTo(i);
      });
      dots.appendChild(dot);
    });
    var caption = document.createElement("div");
    caption.className = "distort-gallery__caption";
    container.appendChild(caption);
    container.appendChild(dots);
    var slides = container.querySelectorAll(".distort-gallery__slide");
    var dotEls = dots.querySelectorAll(".distort-gallery__dot");
    var current = 0;
    var DIRECTIONS = ["top", "right", "bottom", "left"];
    var dirIndex = 0;
    var DIR_CLASSES = ["enter-from-top", "enter-from-bottom", "enter-from-left", "enter-from-right"];
    function updateCaption(i) {
      caption.innerHTML = '<span class="distort-gallery__caption-eyebrow">' + images[i].eyebrow + '</span><span class="distort-gallery__caption-title">' + images[i].title + "</span>";
    }
    updateCaption(0);
    function goTo(next) {
      if (next === current) return;
      var prevEl = slides[current];
      var nextEl = slides[next];
      var direction = DIRECTIONS[dirIndex % DIRECTIONS.length];
      dirIndex++;
      nextEl.classList.remove.apply(nextEl.classList, DIR_CLASSES.concat(["is-entering"]));
      nextEl.classList.add("enter-from-" + direction);
      void nextEl.offsetWidth;
      requestAnimationFrame(function() {
        nextEl.classList.add("is-entering");
      });
      dotEls[current].classList.remove("active");
      dotEls[next].classList.add("active");
      setTimeout(function() {
        prevEl.classList.remove("is-active");
        nextEl.classList.remove.apply(nextEl.classList, DIR_CLASSES.concat(["is-entering"]));
        nextEl.classList.add("is-active");
      }, 980);
      current = next;
      updateCaption(current);
    }
    setInterval(function() {
      goTo((current + 1) % slides.length);
    }, 5500);
  }
  function init() {
    initDistortion("offersDistortionGallery", MEDIA.offersGallery);
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
  window.BlitzBgEffects = {
    initDistortion
  };
})();
