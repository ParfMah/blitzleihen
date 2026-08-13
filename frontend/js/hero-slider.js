(function() {
  "use strict";
  var HERO_SLIDES = [
    {
      image: "LIEN_CLOUDINARY_IMAGE_1",
      eyebrow: "Ihr Finanzpartner in Deutschland",
      title: "Schnelle Kredite, die Ihr Leben ver\xE4ndern",
      text: "Bis zu 150.000 \u20AC in weniger als 48 Stunden. Einfache Konditionen, transparente Zinsen und ein pers\xF6nlicher Berater an Ihrer Seite.",
      btnPrimary: "Jetzt Kredit beantragen",
      btnPrimaryHref: "kreditantrag.html",
      btnSecondary: "Unsere Angebote entdecken",
      btnSecondaryHref: "kredite.html"
    },
    {
      image: "LIEN_CLOUDINARY_IMAGE_2",
      eyebrow: "Immobilienfinanzierung",
      title: "Ihr Traumhaus mit der richtigen Finanzierung",
      text: "G\xFCnstige Baufinanzierungen mit Top-Konditionen. Wir begleiten Sie von der ersten Idee bis zum Einzug in Ihr neues Zuhause.",
      btnPrimary: "Immobilienkredit anfragen",
      btnPrimaryHref: "immobilien.html",
      btnSecondary: "Mehr erfahren",
      btnSecondaryHref: "ueber-uns.html"
    },
    {
      image: "LIEN_CLOUDINARY_IMAGE_3",
      eyebrow: "Flexibel & Sicher",
      title: "Finanzl\xF6sungen f\xFCr jeden Bedarf",
      text: "Ob Privatkredit, Autofinanzierung oder Umschuldung \u2014 bei Blitz Leihen finden Sie die passende L\xF6sung f\xFCr Ihre finanzielle Situation.",
      btnPrimary: "Kostenlose Beratung starten",
      btnPrimaryHref: "kreditantrag.html",
      btnSecondary: "Alle Kredite anzeigen",
      btnSecondaryHref: "kredite.html"
    }
  ];
  var currentIndex = 0;
  var autoTimer = null;
  var isAnimating = false;
  var SLIDE_DURATION = 5e3;
  function init() {
    var container = document.getElementById("heroSlider");
    if (!container) return;
    buildSlides(container);
    var dots = container.querySelectorAll(".hero-dot");
    var btnPrev = container.querySelector("#heroPrev");
    var btnNext = container.querySelector("#heroNext");
    var progress = container.querySelector(".hero-progress");
    showSlide(0, container);
    if (btnPrev) {
      btnPrev.addEventListener("click", function() {
        var prev = (currentIndex - 1 + HERO_SLIDES.length) % HERO_SLIDES.length;
        goToSlide(prev, container);
        resetAutoplay(container);
      });
    }
    if (btnNext) {
      btnNext.addEventListener("click", function() {
        var next = (currentIndex + 1) % HERO_SLIDES.length;
        goToSlide(next, container);
        resetAutoplay(container);
      });
    }
    dots.forEach(function(dot, i) {
      dot.addEventListener("click", function() {
        if (i !== currentIndex) {
          goToSlide(i, container);
          resetAutoplay(container);
        }
      });
    });
    container.addEventListener("mouseenter", function() {
      pauseAutoplay();
    });
    container.addEventListener("mouseleave", function() {
      startAutoplay(container);
    });
    startAutoplay(container);
  }
  function buildSlides(container) {
    var slidesWrap = container.querySelector(".hero-slides");
    if (!slidesWrap) return;
    HERO_SLIDES.forEach(function(slide, i) {
      var el = document.createElement("div");
      el.className = "hero-slide";
      el.setAttribute("aria-label", "Slide " + (i + 1));
      el.innerHTML = [
        '<img class="hero-slide__bg" src="' + slide.image + '" alt="' + slide.eyebrow + '" loading="' + (i === 0 ? "eager" : "lazy") + '">',
        '<div class="hero-slide__overlay"></div>',
        '<div class="hero-slide__content">',
        '  <div class="container">',
        '    <span class="hero-slide__eyebrow">' + slide.eyebrow + "</span>",
        '    <h1 class="hero-slide__title">' + slide.title + "</h1>",
        '    <p class="hero-slide__text">' + slide.text + "</p>",
        '    <div class="hero-slide__actions">',
        '      <a href="' + slide.btnPrimaryHref + '" class="btn btn-accent btn-lg">' + slide.btnPrimary + "</a>",
        slide.btnSecondary ? '      <a href="' + slide.btnSecondaryHref + '" class="btn btn-outline-white btn-lg">' + slide.btnSecondary + "</a>" : "",
        "    </div>",
        "  </div>",
        "</div>"
      ].join("\n");
      slidesWrap.appendChild(el);
    });
  }
  function showSlide(index, container) {
    var slides = container.querySelectorAll(".hero-slide");
    var dots = container.querySelectorAll(".hero-dot");
    slides.forEach(function(s, i) {
      s.classList.toggle("active", i === index);
    });
    dots.forEach(function(d, i) {
      d.classList.toggle("active", i === index);
    });
    currentIndex = index;
  }
  function goToSlide(index, container) {
    if (isAnimating || index === currentIndex) return;
    isAnimating = true;
    showSlide(index, container);
    setTimeout(function() {
      isAnimating = false;
    }, 800);
  }
  function startAutoplay(container) {
    pauseAutoplay();
    autoTimer = setInterval(function() {
      var next = (currentIndex + 1) % HERO_SLIDES.length;
      goToSlide(next, container);
      restartProgress(container);
    }, SLIDE_DURATION);
  }
  function pauseAutoplay() {
    if (autoTimer) {
      clearInterval(autoTimer);
      autoTimer = null;
    }
  }
  function resetAutoplay(container) {
    pauseAutoplay();
    restartProgress(container);
    startAutoplay(container);
  }
  function restartProgress(container) {
    var bar = container.querySelector(".hero-progress");
    if (!bar) return;
    bar.style.animation = "none";
    bar.offsetHeight;
    bar.style.animation = "heroProgress " + SLIDE_DURATION / 1e3 + "s linear";
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
