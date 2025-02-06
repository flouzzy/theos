// Nom du cache
// cache à la date du jour au format YYYY-MM-DD
// On garantie qu'il sera renouvelé quotidiennement
// const CACHE_VERSION = "0.0.4";
const CACHE_VERSION = "0.0.2";
const CACHE_NAME = "academie-lerocher-cache-v" + CACHE_VERSION;

// URLs des pages statiques (qui ne changent pratiquement jamais)
const URLS_TO_CACHE = [
  "/",
  "/images/favicon/",
  "/images/favicon/*.png",
  "/images/favicon/apple-touch-icon.png",
  "/images/favicon/favicon-32x32.png",
  "/images/favicon/favicon-16x16.png",
  "/images/favicon/safari-pinned-tab.svg",
  "/site.webmanifest",
];

// Exclude certain pages from caching (e.g., /login, /admin)
const noCachePages = ["/login", "/admin"];

// Install event: Caches essential resources
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(URLS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// Activate event: Deletes old caches
self.addEventListener("activate", (event) => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (!cacheWhitelist.includes(cacheName)) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event: Handles all internal and external requests
self.addEventListener("fetch", (event) => {
  const requestUrl = new URL(event.request.url);

  console.log(
    "Fetch event for:",
    event.request.url,
    "from:",
    requestUrl.origin,
    requestUrl.origin === location.origin
  );

  // Handle internal requests only
  if (requestUrl.origin === location.origin) {
    if (noCachePages.some((page) => requestUrl.pathname.startsWith(page))) {
      return fetch(event.request); // Always fetch from network
    }

    // Check if the URL contains '?pageview=page_editor'
    if (requestUrl.search.includes("pageview=page_editor")) {
      // Always bypass the cache for these pages
      return fetch(event.request);
    }

    // Cache, then Network strategy for internal resources
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        const fetchPromise = fetch(event.request)
          .then((networkResponse) => {
            // Update the cache with the latest response
            return caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, networkResponse.clone());
              return networkResponse;
            });
          })
          .catch(() => {
            // Return offline page for navigation requests if network fails
            return caches.match("/offline.html");
          });

        // Return cached response if available, else wait for network response
        return cachedResponse || fetchPromise;
      })
    );
  } else {
    // External resources (e.g., fonts, APIs)
    event.respondWith(
      fetch(event.request).catch(() => {
        // Ne rien faire pour les échecs externes, ou loguer l'erreur
        console.error("Network request failed for:", event.request.url);
      })
    );
  }
});

// Message event: Ecoute le message de la page pour forcer une mise à jour
self.addEventListener("message", (event) => {
  if (event.data && event.data.type === "SKIP_WAITING") {
    self.skipWaiting();
  }
});

// Message event: Ecoute le message de la page pour forcer une mise à jour
self.addEventListener("message", (event) => {
  if (event.data) {
    switch (event.data.type) {
      case "SKIP_WAITING":
        self.skipWaiting();
        break;
      case "CACHE_UPDATED":
        caches.keys().then((cacheNames) => {
          return Promise.all(
            cacheNames.map((cacheName) => {
              if (cacheName !== CACHE_NAME) {
                return caches.delete(cacheName);
              }
            })
          );
        });
        break;
      default:
        console.log("Unknown message type:", event.data.type);
    }
  }
});
