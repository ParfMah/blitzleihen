const CACHE_NAME = "blitz-admin-v2";
const STATIC_CACHE = "blitz-admin-static-v2";
const PRECACHE_URLS = [
  "./index.html",
  "./manifest.json"
];
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting())
  );
});
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((k) => k !== CACHE_NAME && k !== STATIC_CACHE).map((k) => caches.delete(k))
    )).then(() => self.clients.claim())
  );
});
self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);
  if (url.pathname.startsWith("/api/")) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
    return;
  }
  event.respondWith(
    caches.match(event.request).then(
      (cached) => cached || fetch(event.request).then((response) => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        return response;
      })
    )
  );
});
self.addEventListener("push", (event) => {
  let data = { title: "Blitz Leihen Admin", body: "Neue Benachrichtigung" };
  try {
    if (event.data) data = event.data.json();
  } catch (e) {
  }
  event.waitUntil(
    self.registration.showNotification(data.title || "Blitz Leihen Admin", {
      body: data.body || "",
      icon: "./icon-192.png",
      badge: "./icon-192.png",
      tag: data.tag || "blitz-admin",
      data,
      vibrate: [200, 100, 200],
      actions: data.actions || [
        { action: "open", title: "\xD6ffnen" },
        { action: "dismiss", title: "Schlie\xDFen" }
      ]
    })
  );
});
self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  if (event.action === "dismiss") return;
  const targetUrl = event.notification.data && event.notification.data.url || "./index.html#demandes";
  event.waitUntil(
    self.clients.matchAll({ type: "window", includeUncontrolled: true }).then((clients) => {
      const existing = clients.find((c) => c.url.includes("admin-mobile"));
      if (existing) {
        existing.focus();
        existing.navigate(targetUrl);
      } else {
        self.clients.openWindow(targetUrl);
      }
    })
  );
});
self.addEventListener("sync", (event) => {
  if (event.tag === "sync-statuts") {
    event.waitUntil(syncPendingUpdates());
  }
});
async function syncPendingUpdates() {
  const clients = await self.clients.matchAll();
  clients.forEach((c) => c.postMessage({ type: "SYNC_COMPLETE" }));
}
