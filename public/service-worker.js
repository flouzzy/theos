// Nom du cache
// cache à la date du jour au format YYYY-MM-DD
// On garantie qu'il sera renouvelé quotidiennement
// const CACHE_VERSION = "0.0.4";
const CACHE_VERSION = new Date().toISOString().split("T")[0];
const CACHE_NAME = "pepiteclub-cache-v" + CACHE_VERSION;

// Liste des fichiers à mettre en cache
// const URLS_TO_CACHE = [
//   "/",
//   "/courses",
//   "/assets/styles/",
//   "/assets/styles/*.css",
//   "/assets/images/*.jpg",
//   "/build/",
//   "/images/",
//   "/images/*",
//   "/images/favicon/",
//   "/images/favicon/*.png",
//   "/images/favicon/apple-touch-icon.png",
//   "/images/favicon/favicon-32x32.png",
//   "/images/favicon/favicon-16x16.png",
//   "/images/favicon/safari-pinned-tab.svg",
//   "/images/screens/",
//   "/images/screens/*.png",
//   "/site.webmanifest",
// ];

// URLs des pages statiques (qui ne changent pratiquement jamais)
const STATIC_URLS = [
  "/",
  "/images/favicon/",
  "/images/favicon/*.png",
  "/images/favicon/apple-touch-icon.png",
  "/images/favicon/favicon-32x32.png",
  "/images/favicon/favicon-16x16.png",
  "/images/favicon/safari-pinned-tab.svg",
  "/images/screens/",
  "/images/screens/*.png",
  "/site.webmanifest",
];

// URLs des pages dynamiques ou moins souvent modifiées
const DYNAMIC_URLS = [
  "/assets/images/*.jpg",
  "/assets/styles/",
  "/assets/styles/*.css",
  "/build/",
  "/courses",
  "/images/",
  "/images/*",
];

self.addEventListener("install", (event) => {
  console.log("SW::install :: event", event);

  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log("SW::install :: Caching files");
      return Promise.all(
        // URLS_TO_CACHE.map((url) => {
        [...STATIC_URLS, ...DYNAMIC_URLS].map((url) => {
          console.log("Caching:", url);
          return cache.add(url).catch((error) => {
            console.error(`Failed to cache ${url}:`, error);
          });
        })
      );
    })
  );
});

self.addEventListener("activate", (event) => {
  console.log("SW::activate :: event", event);

  const cacheWhitelist = [CACHE_NAME];

  // Suppression des anciens caches qui ne sont pas dans la liste blanche
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (navigator.onLine && !cacheWhitelist.includes(cacheName)) {
            console.log(`SW::activate :: Deleting old cache: ${cacheName}`);
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
      // Vérifier si l'utilisateur est en ligne
      const isConnected = navigator.onLine;

      if (isConnected) {
        // Si l'utilisateur est en ligne, on essaie d'abord de récupérer la ressource depuis le réseau
        try {
          const response = await fetch(event.request);

          if (response.status === 404) {
            console.error(
              `[Service Worker] Page non trouvée: ${event.request.url}`
            );
            return new Response("Page non trouvée", { status: 404 });
          }

          // Si la requête réussit, mettre à jour le cache
          const cache = await caches.open(CACHE_NAME);
          console.log(
            `[Service Worker] Caching new resource: ${event.request.url}`
          );
          cache.put(event.request, response.clone());

          return response;
        } catch (error) {
          console.error(
            `[Service Worker] Network fetch failed: ${event.request.url}`,
            error
          );
          // Si la requête réseau échoue, utiliser la réponse en cache (s'il existe)
          const cachedResponse = await caches.match(event.request);
          if (cachedResponse) {
            console.log(
              `[Service Worker] Serving cached resource (fallback): ${event.request.url}`
            );
            return cachedResponse;
          }

          return new Response(
            "Service non disponible. Merci de recommencer dans quelques instants ou vérifier la connexion internet",
            { status: 503 }
          );
        }
      } else {
        // Si l'utilisateur est hors ligne, on sert directement la réponse du cache
        const cachedResponse = await caches.match(event.request);
        if (cachedResponse) {
          console.log(
            `[Service Worker] Serving cached resource (offline): ${event.request.url}`
          );
          return cachedResponse;
        } else {
          console.error(
            `[Service Worker] Resource not found in cache while offline: ${event.request.url}`
          );
          return new Response(
            "Vous êtes hors ligne. Cette ressource n'est pas disponible.",
            { status: 503 }
          );
        }
      }
    })()
  );
});
