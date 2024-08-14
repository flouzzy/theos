// Nom du cache
const CACHE_VERSION = "0.0.1";
const CACHE_NAME = "pepiteclub-cache-v" + CACHE_VERSION;

// Liste des fichiers à mettre en cache
const URLS_TO_CACHE = [
  "/",
  "/images/",
  "/images/*",
  "/images/favicon/",
  "/images/favicon/*.png",
  "images/favicon/favicon.ico",
  "/images/favicon/apple-touch-icon.png",
  "/images/favicon/favicon-32x32.png",
  "/images/favicon/favicon-16x16.png",
  "/images/favicon/safari-pinned-tab.svg",
  "/images/screens/",
  "/images/screens/*.png",
  "/site.webmanifest",
  "/assets/styles/",
  "/assets/styles/*.css",
  "/assets/images/*.jpg",
];

self.addEventListener("install", (event) => {
  console.log("SW::install :: event", event);

  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(URLS_TO_CACHE);
    })
  );
});

self.addEventListener("activate", (event) => {
  console.log("SW::activate :: event", event);

  const cacheWhitelist = [CACHE_NAME];

  // Suppression des autres caches
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

self.addEventListener("fetch", (event) => {
  console.log(`SW::fetch :: event for ${event.request.url}`, event);

  event.respondWith(
    (async () => {
      const cachedResponse = await caches.match(event.request);
      if (cachedResponse) {
        console.log(
          `[Service Worker] Serving cached resource: ${event.request.url}`
        );
        return cachedResponse;
      }
      try {
        const response = await fetch(event.request);
        if (response.status === 404) {
          console.error(
            `[Service Worker] Resource not found: ${event.request.url}`
          );
          return new Response("Resource not found", { status: 404 });
        }
        const cache = await caches.open(CACHE_NAME);
        console.log(
          `[Service Worker] Caching new resource: ${event.request.url}`
        );
        cache.put(event.request, response.clone());
        return response;
      } catch (error) {
        console.error(
          `[Service Worker] Fetch failed; returning offline page instead: ${event.request.url}`,
          error
        );
        return new Response(
          "Service non disponible. Merci de recommencer dans quelques instants ou vérifier la connexion internet",
          { status: 503 }
        );
      }
    })()
  );
});
