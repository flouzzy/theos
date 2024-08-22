// Nom du cache
const CACHE_VERSION = "0.0.2";
const CACHE_NAME = "pepiteclub-cache-v" + CACHE_VERSION;
const MAX_CACHE_AGE = 1 * 60 * 60 * 1000; // 1 heure en millisecondes

// Liste des fichiers à mettre en cache
const URLS_TO_CACHE = [
  "/",
  "/courses",
  "/assets/styles/",
  "/assets/styles/*.css",
  "/assets/images/*.jpg",
  "/build/",
  "/images/",
  "/images/*",
  "/images/favicon/",
  "/images/favicon/*.png",
  "/images/favicon/apple-touch-icon.png",
  "/images/favicon/favicon-32x32.png",
  "/images/favicon/favicon-16x16.png",
  "/images/favicon/favicon.ico",
  "/images/favicon/safari-pinned-tab.svg",
  "/images/screens/",
  "/images/screens/*.png",
  "/site.webmanifest",
];

self.addEventListener("install", (event) => {
  console.log("SW::install :: event", event);

  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log("SW::install :: Caching files");
      return Promise.all(
        URLS_TO_CACHE.map((url) => {
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

      // Si une réponse en cache existe
      if (cachedResponse) {
        const cachedDate = new Date(cachedResponse.headers.get("date"));
        const now = new Date();
        const isConnected = navigator.onLine; // Vérifie si l'utilisateur est en ligne

        // Si l'utilisateur est en ligne et le cache est plus vieux que la durée de cache max, on le rafraîchit
        if (isConnected && now - cachedDate > MAX_CACHE_AGE) {
          console.log(
            `[Service Worker] Cache too old, refreshing: ${event.request.url}`
          );
          return fetchAndUpdateCache(event.request, cachedResponse);
        }

        console.log(
          `[Service Worker] Serving cached resource: ${event.request.url}`
        );
        return cachedResponse;
      }

      // Si aucune réponse en cache, on va chercher la ressource en ligne
      return fetchAndUpdateCache(event.request);
    })()
  );
});

// Fonction pour récupérer une ressource en ligne et mettre à jour le cache
async function fetchAndUpdateCache(request, fallbackResponse) {
  try {
    const response = await fetch(request);

    if (response.status === 404) {
      console.error(`[Service Worker] Page non trouvée: ${request.url}`);
      return (
        fallbackResponse || new Response("Page non trouvée", { status: 404 })
      );
    }

    const cache = await caches.open(CACHE_NAME);
    console.log(`[Service Worker] Caching new resource: ${request.url}`);
    cache.put(request, response.clone());

    return response;
  } catch (error) {
    console.error(`[Service Worker] Fetch failed: ${request.url}`, error);
    return (
      fallbackResponse ||
      new Response(
        "Service non disponible. Merci de recommencer dans quelques instants ou vérifier la connexion internet",
        { status: 503 }
      )
    );
  }
}
