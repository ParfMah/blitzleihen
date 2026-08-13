(function() {
  "use strict";
  var CONFIG = {
    autoplay: true,
    panelInterval: 7e3,

    photoInterval: 2800,

    transitionMs: 480,

    panels: [

      {
        direction: "ltr",
        eyebrow: "Blitz Leihen \u2014 Ihr Finanzpartner",
        title: "Schnelle Kredite f\xFCr jede Lebenslage",
        text: "Bis zu 150.000 \u20AC in weniger als 48 Stunden. Einfacher Antrag, transparente Zinsen, pers\xF6nlicher Berater.",
        btnPrimary: "Jetzt Kredit beantragen \u26A1",
        btnPrimaryHref: "kreditantrag.html",
        btnSecondary: "Alle Kreditprodukte",
        btnSecondaryHref: "kredite.html",
        photos: [
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849033/blitz-leihen-hero-kunde-kreditvertrag-lachend_hvnefd.webp",
            alt: "Gl\xFCcklicher Kunde mit Kreditvertrag",
            caption: "Kredit in 48 Stunden",
            desc: "CLIENT SATISFAIT tenant un contrat de cr\xE9dit \u2014 ambiance chaleureuse et professionnelle \u2014 800\xD7600px"
          },
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849033/blitz-leihen-hero-finanzberater-beratungsgespraech_nhkcni.webp",
            alt: "Professioneller Finanzberater",
            caption: "Pers\xF6nliche Beratung",
            desc: "CONSEILLER FINANCIER souriant face au client, bureau moderne \u2014 800\xD7600px"
          },
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849033/blitz-leihen-hero-familie-wohnzimmer-gluecklich_ya6ki8.webp",
            alt: "Familie mit finanziertem Wunsch",
            caption: "Finanzielle Freiheit",
            desc: "FAMILLE HEUREUSE dans leur salon, ambiance de r\xE9ussite et s\xE9r\xE9nit\xE9 \u2014 800\xD7600px"
          }
        ]
      },

      {
        direction: "rtl",
        eyebrow: "Immobilienfinanzierung",
        title: "Ihr Traumhaus \u2014 finanziert mit Top-Konditionen",
        text: "Baufinanzierungen ab 1,8% eff. p.a. Von der Eigentumswohnung bis zum Einfamilienhaus.",
        btnPrimary: "Immobilienkredit anfragen",
        btnPrimaryHref: "immobilien.html",
        btnSecondary: "Konditionen ansehen",
        btnSecondaryHref: "immobilien.html",
        photos: [
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849033/blitz-leihen-hero-einfamilienhaus-garten-modern_rglahy.webp",
            alt: "Modernes Einfamilienhaus in Deutschland",
            caption: "Traumhaus verwirklichen",
            desc: "MAISON INDIVIDUELLE MODERNE avec jardin, style contemporain, ciel bleu \u2014 800\xD7600px"
          },
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849032/blitz-leihen-hero-eigentumswohnung-interieur_bbtfus.webp",
            alt: "Elegante Eigentumswohnung",
            caption: "Eigentumswohnung",
            desc: "INT\xC9RIEUR APPARTEMENT \xE9l\xE9gant avec lumi\xE8re naturelle, mobilier design \u2014 800\xD7600px"
          },
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849033/blitz-leihen-hero-paar-hausschluessel-uebergabe1_mikuxd.webp",
            alt: "Paar vor neuem Haus",
            caption: "Schl\xFCssel\xFCbergabe",
            desc: "COUPLE souriant devant leur nouvelle maison, tenant les cl\xE9s \u2014 800\xD7600px"
          }
        ]
      },

      {
        direction: "ttb",
        eyebrow: "Sicher & Transparent",
        title: "Ihre Vorteile bei Blitz Leihen",
        text: "BaFin-reguliert, DSGVO-konform, 15.000+ zufriedene Kunden. Kostenlose Erstberatung ohne Verpflichtungen.",
        btnPrimary: "Kostenlos beraten lassen",
        btnPrimaryHref: "kreditantrag.html",
        btnSecondary: "\xDCber uns",
        btnSecondaryHref: "ueber-uns.html",
        photos: [
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849033/blitz-leihen-hero-smartphone-kredit-app_yzlmin.webp",
            alt: "Digitale Kreditanwendung auf Smartphone",
            caption: "100% digital & sicher",
            desc: "SMARTPHONE avec app de cr\xE9dit sur \xE9cran, fond lumineux moderne \u2014 800\xD7600px"
          },
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849032/blitz-leihen-hero-bafin-sicherheit-tresor_zxtkwd.webp",
            alt: "BaFin Sicherheit und Zertifizierung",
            caption: "BaFin-reguliert",
            desc: "S\xC9CURIT\xC9 FINANCI\xC8RE \u2014 coffre fort, bouclier ou fa\xE7ade banque allemande \u2014 800\xD7600px"
          },
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849037/blitz-leihen-hero-team-buero-modern_k5iek0.webp",
            alt: "Professionelles Team Blitz Leihen",
            caption: "Unser Expertenteam",
            desc: "\xC9QUIPE BUREAU MODERNE \u2014 conseillers en open space, ambiance dynamique \u2014 800\xD7600px"
          }
        ]
      },

      {
        direction: "btu",
        eyebrow: "Deutsche Qualit\xE4t seit 2015",
        title: "Vertrauen Sie auf 10 Jahre Erfahrung",
        text: "98% Kundenzufriedenheit, \xFCber 150 Mio. \u20AC ausgezahlte Kredite. Ihr langfristiger Finanzpartner.",
        btnPrimary: "Unsere Geschichte",
        btnPrimaryHref: "ueber-uns.html",
        btnSecondary: "Kundenstimmen",
        btnSecondaryHref: "ueber-uns.html",
        photos: [
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849032/blitz-leihen-hero-berlin-skyline-panorama_gqw7uk.webp",
            alt: "Berlin Stadtansicht Deutschland",
            caption: "Gegr\xFCndet in Berlin 2015",
            desc: "PANORAMA BERLIN ou grande ville allemande, skyline moderne, ciel bleu \u2014 800\xD7600px"
          },
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849033/blitz-leihen-hero-familie-mehrgenerationen-zuhause_jbrasy.webp",
            alt: "Gl\xFCckliche Familie zu Hause",
            caption: "15.000+ zufriedene Kunden",
            desc: "FAMILLE MULTIG\xC9N\xC9RATIONNELLE dans leur maison, ambiance chaleureuse \u2014 800\xD7600px"
          },
          {
            src: "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto/v1783849033/blitz-leihen-hero-geschaeftshandschlag-partnerschaft_jqgame.webp",
            alt: "Gesch\xE4ftshandschlag Partnerschaft",
            caption: "Ihr Partner f\xFCr die Zukunft",
            desc: "POIGN\xC9E DE MAIN professionnelle, bureau \xE9l\xE9gant en arri\xE8re-plan \u2014 800\xD7600px"
          }
        ]
      }
    ]
  };
  var state = {
    currentPanel: 0,
    panelTimer: null,
    photoTimers: [],

    photoIndex: [0, 0, 0, 0],
    animating: false,
    touchStartX: 0,
    touchStartY: 0
  };
  function build() {
    var hero = document.getElementById("heroMega");
    if (!hero) return;
    var track = document.createElement("div");
    track.className = "hero-panels-track";
    track.id = "heroPanelsTrack";
    CONFIG.panels.forEach(function(panel, i) {
      track.appendChild(buildPanel(panel, i));
    });
    var nav = document.createElement("div");
    nav.className = "hero-mega__nav";
    nav.innerHTML = '<button class="hero-mega__arrow" id="heroPrev" aria-label="Zur\xFCck">&#8592;</button><div class="hero-mega__dots" id="heroDots">' + CONFIG.panels.map(function(_, i) {
      return '<button class="hero-mega__dot' + (i === 0 ? " active" : "") + '" data-target="' + i + '" aria-label="Abschnitt ' + (i + 1) + '"></button>';
    }).join("") + '</div><button class="hero-mega__arrow" id="heroNext" aria-label="Weiter">&#8594;</button>';
    var progress = document.createElement("div");
    progress.className = "hero-mega__progress";
    progress.innerHTML = '<div class="hero-mega__progress-bar" id="heroProgressBar"></div>';
    hero.appendChild(track);
    hero.appendChild(nav);
    hero.appendChild(progress);
    bindEvents(hero);
    activatePanel(0, true);
    startPanelTimer();
    startAllPhotoTimers();
  }
  function buildPanel(panel, idx) {
    var el = document.createElement("div");
    el.className = "hero-panel hero-panel--" + panel.direction;
    el.setAttribute("data-panel", idx);
    var zone = document.createElement("div");
    zone.className = "hero-panel__photos-zone";
    panel.photos.forEach(function(photo, pi) {
      var item = document.createElement("div");
      item.className = "hero-photo-item hero-photo-item--" + panel.direction + (pi === 0 ? " active" : "");
      item.setAttribute("data-photo", pi);
      if (photo.src && photo.src.indexOf("http") === 0) {
        item.innerHTML = '<img src="' + photo.src + '" alt="' + photo.alt + '" class="hero-photo-img" loading="' + (pi === 0 ? "eager" : "lazy") + '"><div class="hero-photo-caption"><span>' + photo.caption + "</span></div>";
      } else {
        item.innerHTML = '<div class="hero-photo-placeholder"><span class="hero-photo-placeholder__icon">\u{1F4F8}</span><strong class="hero-photo-placeholder__label">' + photo.caption + '</strong><span class="hero-photo-placeholder__desc">' + photo.desc + '</span><code class="hero-photo-placeholder__code">\u2192 Remplacer ce div par: &lt;img src="URL_CLOUDINARY" class="hero-photo-img"&gt;</code></div><div class="hero-photo-caption"><span>' + photo.caption + "</span></div>";
      }
      zone.appendChild(item);
    });
    var content = document.createElement("div");
    content.className = "hero-panel__content";
    content.innerHTML = '<span class="hero-panel__eyebrow">' + panel.eyebrow + '</span><h2 class="hero-panel__title">' + panel.title + '</h2><p class="hero-panel__text">' + panel.text + '</p><div class="hero-panel__actions"><a href="' + panel.btnPrimaryHref + '" class="btn btn-accent btn-lg">' + panel.btnPrimary + "</a>" + (panel.btnSecondary ? '<a href="' + panel.btnSecondaryHref + '" class="btn btn-outline-white">' + panel.btnSecondary + "</a>" : "") + "</div>";
    el.appendChild(zone);
    el.appendChild(content);
    return el;
  }
  function nextPhoto(panelIdx) {
    var panelEl = document.querySelector('.hero-panel[data-panel="' + panelIdx + '"]');
    if (!panelEl) return;
    var items = panelEl.querySelectorAll(".hero-photo-item");
    if (items.length < 2) return;
    var prev = state.photoIndex[panelIdx];
    var next = (prev + 1) % items.length;
    state.photoIndex[panelIdx] = next;
    items[prev].classList.add("exiting");
    items[prev].classList.remove("active");
    items[next].classList.add("active");
    setTimeout(function() {
      items[prev].classList.remove("exiting");
    }, CONFIG.transitionMs + 50);
  }
  function startAllPhotoTimers() {
    CONFIG.panels.forEach(function(_, i) {
      var offset = i * 900;
      state.photoTimers[i] = setInterval(function() {
        nextPhoto(i);
      }, CONFIG.photoInterval + offset);
    });
  }
  function stopAllPhotoTimers() {
    state.photoTimers.forEach(function(t) {
      clearInterval(t);
    });
    state.photoTimers = [];
  }
  function activatePanel(n, immediate) {
    var total = CONFIG.panels.length;
    n = (n % total + total) % total;
    if (!immediate && n === state.currentPanel) return;
    if (state.animating && !immediate) return;
    state.animating = true;
    state.currentPanel = n;
    var track = document.getElementById("heroPanelsTrack");
    if (track) {
      track.style.transform = "translateX(-" + n * 25 + "%)";
    }
    document.querySelectorAll(".hero-panel").forEach(function(p, i) {
      p.setAttribute("aria-hidden", i !== n ? "true" : "false");
    });
    document.querySelectorAll(".hero-mega__dot").forEach(function(dot, i) {
      dot.classList.toggle("active", i === n);
    });
    restartProgress();
    setTimeout(function() {
      state.animating = false;
    }, 900);
  }
  function goToPanel(n) {
    var total = CONFIG.panels.length;
    n = (n % total + total) % total;
    if (n === state.currentPanel || state.animating) return;
    state.animating = true;
    state.currentPanel = n;
    var track = document.getElementById("heroPanelsTrack");
    if (track) track.style.transform = "translateX(-" + n * 25 + "%)";
    document.querySelectorAll(".hero-panel").forEach(function(p, i) {
      p.setAttribute("aria-hidden", i !== n ? "true" : "false");
    });
    document.querySelectorAll(".hero-mega__dot").forEach(function(dot, i) {
      dot.classList.toggle("active", i === n);
    });
    restartProgress();
    setTimeout(function() {
      state.animating = false;
    }, 900);
  }
  function startPanelTimer() {
    clearInterval(state.panelTimer);
    state.panelTimer = setInterval(function() {
      goToPanel(state.currentPanel + 1);
    }, CONFIG.panelInterval);
  }
  function resetPanelTimer() {
    startPanelTimer();
    restartProgress();
  }
  function restartProgress() {
    var bar = document.getElementById("heroProgressBar");
    if (!bar) return;
    bar.style.animation = "none";
    bar.offsetHeight;
    bar.style.animation = "heroProgressBar " + CONFIG.panelInterval / 1e3 + "s linear forwards";
  }
  function bindEvents(hero) {
    var btnPrev = document.getElementById("heroPrev");
    var btnNext = document.getElementById("heroNext");
    if (btnPrev) btnPrev.addEventListener("click", function() {
      goToPanel(state.currentPanel - 1);
      resetPanelTimer();
    });
    if (btnNext) btnNext.addEventListener("click", function() {
      goToPanel(state.currentPanel + 1);
      resetPanelTimer();
    });
    document.querySelectorAll(".hero-mega__dot").forEach(function(dot) {
      dot.addEventListener("click", function() {
        goToPanel(parseInt(dot.getAttribute("data-target"), 10));
        resetPanelTimer();
      });
    });
    hero.addEventListener("mouseenter", function() {
      clearInterval(state.panelTimer);
    });
    hero.addEventListener("mouseleave", function() {
      if (CONFIG.autoplay) startPanelTimer();
    });
    hero.addEventListener("touchstart", function(e) {
      state.touchStartX = e.changedTouches[0].screenX;
      state.touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });
    hero.addEventListener("touchend", function(e) {
      var dx = e.changedTouches[0].screenX - state.touchStartX;
      var dy = e.changedTouches[0].screenY - state.touchStartY;
      if (Math.abs(dy) > Math.abs(dx) || Math.abs(dx) < 50) return;
      goToPanel(dx < 0 ? state.currentPanel + 1 : state.currentPanel - 1);
      resetPanelTimer();
    }, { passive: true });
  }
  function appliquerLayoutMobileForce() {
    var hero = document.getElementById("heroMega");
    if (!hero) return;
    var estVraiTelephone = !!(window.screen && window.screen.width && window.screen.width <= 600);
    hero.classList.toggle("hero-mega--force-mobile", estVraiTelephone);
  }
  function init() {
    var hero = document.getElementById("heroMega");
    if (!hero) return;
    build();
    state.photoIndex = CONFIG.panels.map(function() {
      return 0;
    });
    appliquerLayoutMobileForce();
  }
  window.addEventListener("resize", appliquerLayoutMobileForce, { passive: true });
  window.addEventListener("orientationchange", appliquerLayoutMobileForce, { passive: true });
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
