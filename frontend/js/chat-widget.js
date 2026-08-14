(function () {
  "use strict";
  var STORAGE_VISITEUR_ID = "blitz_chat_visiteur_id";
  var STORAGE_NOM = "blitz_chat_nom";
  var POLL_INTERVAL_MS = 8e3;
  var state = {
    visiteurId: null,
    nom: "",
    conversationId: null,
    messagesCharges: false,
    dernierMessageId: null,
    pollTimer: null,
    unread: 0,
    fenetreOuverte: false,
    envoiEnCours: false
  };
  function getApiBase() {
    var meta = document.querySelector('meta[name="api-base"]');
    return (meta && meta.getAttribute("content") || window.BLITZ_API_BASE || "https://api.blitzleihen.com").replace(/\/$/, "");
  }
  function obtenirVisiteurId() {
    var id = localStorage.getItem(STORAGE_VISITEUR_ID);
    if (id) return id;
    if (window.crypto && typeof window.crypto.randomUUID === "function") {
      id = window.crypto.randomUUID();
    } else {
      id = "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function (c) {
        var r = Math.random() * 16 | 0;
        var v = c === "x" ? r : r & 3 | 8;
        return v.toString(16);
      });
    }
    localStorage.setItem(STORAGE_VISITEUR_ID, id);
    return id;
  }
  function obtenirLocalisation() {
    var cached = sessionStorage.getItem("blitz_visitor_location");
    if (cached) {
      try {
        return Promise.resolve(JSON.parse(cached));
      } catch (e) {
      }
    }
    function requeteAvecDelai(url, ms) {
      var controller = new AbortController();
      var timer = setTimeout(function () {
        controller.abort();
      }, ms);
      return fetch(url, { signal: controller.signal }).finally(function () {
        clearTimeout(timer);
      });
    }
    return requeteAvecDelai("https://ipapi.co/json/", 4e3).then(function (r) {
      return r.json();
    }).then(function (d) {
      var loc = {
        city: d.city || "",
        region: d.region || "",
        country: d.country_name || "",
        ip: d.ip || "",
        display: [d.city, d.region, d.country_name].filter(Boolean).join(", ") || "Unbekannt"
      };
      sessionStorage.setItem("blitz_visitor_location", JSON.stringify(loc));
      return loc;
    }).catch(function () {
      return requeteAvecDelai("https://ipwho.is/", 4e3).then(function (r) {
        return r.json();
      }).then(function (d) {
        var loc = {
          city: d.city || "",
          region: d.region || "",
          country: d.country || "",
          ip: d.ip || "",
          display: [d.city, d.region, d.country].filter(Boolean).join(", ") || "Unbekannt"
        };
        sessionStorage.setItem("blitz_visitor_location", JSON.stringify(loc));
        return loc;
      }).catch(function () {
        return { city: "", region: "", country: "", ip: "", display: "" };
      });
    });
  }
  function construireWidget() {
    var bubble = document.createElement("button");
    bubble.id = "blitzChatBubble";
    bubble.setAttribute("aria-label", "Live-Chat \xF6ffnen");
    bubble.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 4h16v12H7l-3 3V4z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="blitz-chat-bubble__badge" id="blitzChatBadge" data-count="0"></span>';
    var win = document.createElement("div");
    win.id = "blitzChatWindow";
    win.setAttribute("role", "dialog");
    win.setAttribute("aria-label", "Live-Chat mit Blitz Leihen");
    win.innerHTML = [
      '<div class="blitz-chat__header">',
      '  <div class="blitz-chat__header-avatar" id="blitzChatHeaderAvatar">\u26A1</div>',
      '  <div class="blitz-chat__header-info">',
      '    <div class="blitz-chat__header-title" id="blitzChatHeaderTitle">Blitz Leihen \xB7 Live-Chat</div>',
      '    <div class="blitz-chat__header-status"><span class="blitz-chat__header-status-dot"></span>Wir sind f\xFCr Sie da</div>',
      "  </div>",
      '  <button class="blitz-chat__header-close" id="blitzChatClose" aria-label="Chat schlie\xDFen">\xD7</button>',
      "</div>",
      '<div class="blitz-chat__precha" id="blitzChatPrecha">',
      "  <p>Willkommen bei Blitz Leihen! Wie d\xFCrfen wir Sie ansprechen?</p>",
      '  <input type="text" id="blitzChatNomInput" placeholder="Ihr Vorname" maxlength="60" autocomplete="given-name">',
      '  <button id="blitzChatStart" type="button">Chat starten</button>',
      "</div>",
      '<div class="blitz-chat__messages" id="blitzChatMessages" hidden></div>',
      '<div class="blitz-chat__connstate" id="blitzChatConnState" hidden></div>',
      '<div class="blitz-chat__inputbar" id="blitzChatInputbar" hidden>',
      '  <textarea id="blitzChatInput" rows="1" maxlength="4000" placeholder="Nachricht schreiben\u2026" aria-label="Nachricht"></textarea>',
      '  <button id="blitzChatSend" type="button" aria-label="Senden">',
      '    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">',
      '      <path d="M20 12l-16-8 6 8-6 8 16-8z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
      "    </svg>",
      "  </button>",
      "</div>"
    ].join("");
    document.body.appendChild(bubble);
    document.body.appendChild(win);
  }
  function toggleFenetre() {
    var win = document.getElementById("blitzChatWindow");
    var bubble = document.getElementById("blitzChatBubble");
    if (!win) return;
    state.fenetreOuverte = !state.fenetreOuverte;
    win.classList.toggle("open", state.fenetreOuverte);
    if (bubble) bubble.classList.toggle("is-hidden", state.fenetreOuverte);
    if (state.fenetreOuverte) {
      state.unread = 0;
      majBadge();
      var nomConnu = localStorage.getItem(STORAGE_NOM);
      if (nomConnu && !state.messagesCharges) {
        state.nom = nomConnu;
        demarrerConversation();
      } else if (!nomConnu) {
        var input = document.getElementById("blitzChatNomInput");
        if (input) setTimeout(function () {
          input.focus();
        }, 150);
      }
      scrollVersLeBas();
    }
  }
  function majBadge() {
    var badge = document.getElementById("blitzChatBadge");
    if (!badge) return;
    badge.setAttribute("data-count", String(state.unread));
    badge.textContent = state.unread > 9 ? "9+" : state.unread > 0 ? String(state.unread) : "";
  }
  function mettreAJourConseiller(conseiller) {
    if (!conseiller || !conseiller.name) return;
    var avatarEl = document.getElementById("blitzChatHeaderAvatar");
    var titleEl = document.getElementById("blitzChatHeaderTitle");
    if (titleEl) titleEl.textContent = conseiller.name + " \xB7 Blitz Leihen";
    if (avatarEl) {
      avatarEl.innerHTML = conseiller.avatar ? '<img src="' + conseiller.avatar + '" alt="' + conseiller.name + '">' : "\u26A1";
    }
  }
  function demarrerConversation() {
    afficherEtatConnexion("Verbindung wird hergestellt\u2026");
    obtenirLocalisation().then(function (loc) {
      fetch(getApiBase() + "/api/chat/conversations", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          visiteurId: state.visiteurId,
          nom: state.nom,
          pageOrigine: window.location.pathname,
          visiteurVille: loc.city,
          visiteurRegion: loc.region,
          visiteurPays: loc.country,
          visiteurLocalisationAffichage: loc.display
        })
      }).then(function (r) {
        return r.json();
      }).then(function (json) {
        if (!json || !json.success) throw new Error("Antwort ung\xFCltig");
        state.conversationId = json.data.conversation._id;
        state.messagesCharges = true;
        basculerVersDiscussion();
        renderHistorique(json.data.messages || []);
        afficherEtatConnexion(null);
        if (json.data.conversation.adminAssigne) {
          mettreAJourConseiller(json.data.conversation.adminAssigne);
        }
        demarrerPolling();
      }).catch(function () {
        afficherEtatConnexion("Chat momentan nicht erreichbar. Bitte versuchen Sie es sp\xE4ter erneut.");
      });
    });
  }
  function basculerVersDiscussion() {
    var precha = document.getElementById("blitzChatPrecha");
    var messages = document.getElementById("blitzChatMessages");
    var inputbar = document.getElementById("blitzChatInputbar");
    if (precha) precha.style.display = "none";
    if (messages) messages.hidden = false;
    if (inputbar) inputbar.hidden = false;
  }
  function afficherEtatConnexion(texte) {
    var el = document.getElementById("blitzChatConnState");
    if (!el) return;
    if (!texte) {
      el.hidden = true;
      el.textContent = "";
    } else {
      el.hidden = false;
      el.textContent = texte;
    }
  }
  function demarrerPolling() {
    if (state.pollTimer) return;
    var echecsConsecutifs = 0;
    var cyclesASauter = 0;
    state.pollTimer = setInterval(function () {
      if (!state.visiteurId) return;
      // Ralentit automatiquement si le serveur ne répond plus, pour ne pas
      // le surcharger davantage (et reprend normalement dès que ça remarche).
      if (cyclesASauter > 0) {
        cyclesASauter -= 1;
        return;
      }
      fetch(getApiBase() + "/api/chat/conversations/" + state.visiteurId).then(function (r) {
        if (!r.ok) throw new Error("http_" + r.status);
        return r.json();
      }).then(function (json) {
        echecsConsecutifs = 0;
        if (!json || !json.success) return;
        if (json.data.conversation && json.data.conversation.adminAssigne) {
          mettreAJourConseiller(json.data.conversation.adminAssigne);
        }
        renderHistorique(json.data.messages || [], true);
      }).catch(function () {
        echecsConsecutifs = Math.min(echecsConsecutifs + 1, 6);
        cyclesASauter = echecsConsecutifs;
      });
    }, POLL_INTERVAL_MS);
  }
  function arreterPolling() {
    if (state.pollTimer) {
      clearInterval(state.pollTimer);
      state.pollTimer = null;
    }
  }
  function renderHistorique(messages, remplacementSeuleNouveautes) {
    var container = document.getElementById("blitzChatMessages");
    if (!container) return;
    if (remplacementSeuleNouveautes) {
      messages.forEach(function (m) {
        if (document.getElementById("blitz-msg-" + m._id)) return;
        afficherMessage(m);
        if (m.expediteur === "admin" && !state.fenetreOuverte) {
          state.unread += 1;
          majBadge();
        }
      });
      return;
    }
    container.innerHTML = "";
    messages.forEach(function (m) {
      afficherMessage(m);
    });
    scrollVersLeBas();
  }
  function afficherMessage(message) {
    var container = document.getElementById("blitzChatMessages");
    if (!container || !message || !message.texte) return;
    state.dernierMessageId = message._id;
    var bulle = document.createElement("div");
    bulle.id = "blitz-msg-" + message._id;
    bulle.className = "blitz-chat__msg blitz-chat__msg--" + (message.expediteur === "admin" ? "admin" : "visiteur");
    if (message.expediteur === "admin" && message.auteurNom) {
      var auteur = document.createElement("div");
      auteur.className = "blitz-chat__msg-author";
      if (message.auteurAvatar) {
        auteur.innerHTML = '<img src="' + message.auteurAvatar + '" alt="">';
      } else {
        var fallback = document.createElement("span");
        fallback.className = "blitz-chat__msg-author-fallback";
        fallback.textContent = message.auteurNom[0].toUpperCase();
        auteur.appendChild(fallback);
      }
      var nomSpan = document.createElement("span");
      nomSpan.textContent = message.auteurNom;
      auteur.appendChild(nomSpan);
      bulle.appendChild(auteur);
      if (message.auteurAdmin) {
        mettreAJourConseiller({ name: message.auteurNom, avatar: message.auteurAvatar });
      }
    }
    var texte = document.createElement("span");
    texte.textContent = message.texte;
    bulle.appendChild(texte);
    var heure = document.createElement("span");
    heure.className = "blitz-chat__msg-time";
    heure.textContent = formaterHeure(message.createdAt);
    bulle.appendChild(heure);
    container.appendChild(bulle);
    scrollVersLeBas();
  }
  function afficherMessageSysteme(texte) {
    var container = document.getElementById("blitzChatMessages");
    if (!container) return;
    var bulle = document.createElement("div");
    bulle.className = "blitz-chat__msg blitz-chat__msg--systeme";
    bulle.textContent = texte;
    container.appendChild(bulle);
    scrollVersLeBas();
  }
  function formaterHeure(iso) {
    try {
      var d = new Date(iso);
      return d.toLocaleTimeString("de-DE", { hour: "2-digit", minute: "2-digit" });
    } catch (e) {
      return "";
    }
  }
  function scrollVersLeBas() {
    var container = document.getElementById("blitzChatMessages");
    if (container) container.scrollTop = container.scrollHeight;
  }
  function envoyerMessage() {
    var input = document.getElementById("blitzChatInput");
    if (!input || state.envoiEnCours) return;
    var texte = input.value.trim();
    if (!texte || !state.conversationId) return;
    state.envoiEnCours = true;
    input.value = "";
    ajusterHauteurTextarea(input);
    fetch(getApiBase() + "/api/chat/conversations/" + state.visiteurId + "/message", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ texte })
    }).then(function (r) {
      return r.json();
    }).then(function (json) {
      if (json && json.success && json.data && json.data.message) {
        afficherMessage(json.data.message);
      }
    }).catch(function () {
      afficherMessageSysteme("Nachricht konnte nicht gesendet werden. Bitte versuchen Sie es erneut.");
    }).finally(function () {
      state.envoiEnCours = false;
    });
  }
  function ajusterHauteurTextarea(el) {
    el.style.height = "auto";
    el.style.height = Math.min(el.scrollHeight, 88) + "px";
  }
  function bindEvents() {
    var bubble = document.getElementById("blitzChatBubble");
    var close = document.getElementById("blitzChatClose");
    var start = document.getElementById("blitzChatStart");
    var nomInp = document.getElementById("blitzChatNomInput");
    var input = document.getElementById("blitzChatInput");
    var send = document.getElementById("blitzChatSend");
    if (bubble) bubble.addEventListener("click", toggleFenetre);
    if (close) close.addEventListener("click", toggleFenetre);
    function validerPrecha() {
      var nom = (nomInp && nomInp.value || "").trim();
      if (!nom) {
        if (nomInp) nomInp.focus();
        return;
      }
      state.nom = nom;
      localStorage.setItem(STORAGE_NOM, nom);
      demarrerConversation();
    }
    if (start) start.addEventListener("click", validerPrecha);
    if (nomInp) nomInp.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        validerPrecha();
      }
    });
    if (send) send.addEventListener("click", envoyerMessage);
    if (input) {
      input.addEventListener("input", function () {
        ajusterHauteurTextarea(input);
      });
      input.addEventListener("keydown", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
          e.preventDefault();
          envoyerMessage();
        }
      });
    }
  }
  function init() {
    if (window.location.pathname.indexOf("/admin/") !== -1) return;
    state.visiteurId = obtenirVisiteurId();
    fetch(getApiBase() + "/api/settings/public").then(function (r) {
      return r.ok ? r.json() : null;
    }).then(function (json) {
      var chatActif = !json || json.success === false ? true : json.data.chatActif !== false;
      if (!chatActif) return;
      construireWidget();
      bindEvents();
    }).catch(function () {
      construireWidget();
      bindEvents();
    });
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

// Ouvre automatiquement la bulle de chat si l'URL contient ?chat=open
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('chat') === 'open') {
        const chatBtn = document.querySelector('#chat-widget-toggle') || document.querySelector('.chat-toggle') || document.querySelector('#chat-button');
        if (chatBtn) {
            chatBtn.click();
        }
    }
});
