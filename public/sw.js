const cacheName = "rocher-academie-v1.0.0";

const contentToCache = [
  "/",
  "/offline.html",
  "/offline",
  "/images/",
  "/images/default-course.png",
  "/images/default-user.png",
  "/images/logo.svg",
  "/images/logo_black.svg",
  "/images/logo_white.svg",
  "/images/favicon/favicon.ico",
  "/icons/",
  "/site.webmanifest",
  "/images/favicon/android-chrome-192x192.png",
  "/images/favicon/android-chrome-512x512.png",
  "/images/favicon/apple-touch-icon.png",
  "/images/favicon/favicon-32x32.png",
  "/images/favicon/favicon-16x16.png",
  "/styles/app.css",
  "/styles/color.css",
  "/styles/form.css",
  "/styles/utilities.css",
  "/styles/ionic.bundle.css",
  "/js/tinymce.min.js",
];

self.addEventListener("install", (e) => {
  e.waitUntil(
    caches
      .open(cacheName)
      .then((cache) => cache.addAll(contentToCache))
      .catch((err) => console.log("install:error", err))
  );
});

// Fetching content using Service Worker
// self.addEventListener("fetch", (e) => {
//   console.log("[Service Worker] Fetch", e.request.url);

//   // Cache http and https only, skip unsupported chrome-extension:// and file://...
//   if (
//     !(e.request.url.startsWith("http:") || e.request.url.startsWith("https:"))
//   ) {
//     return;
//   }

//   e.respondWith(
//     (async () => {
//       const r = await caches.match(e.request).catch(() => {
//         // Retourner une ressource par défaut ou une page hors ligne spécifique si l'utilisateur est hors ligne et que la ressource n'est pas en cache
//         return caches.match("/offline.html");
//       });
//       console.log(`[Service Worker] Fetching resource: ${e.request.url}`);
//       if (r) return r;
//       const response = await fetch(e.request);
//       const cache = await caches.open(cacheName);
//       console.log(`[Service Worker] Caching new resource: ${e.request.url}`);
//       cache.put(e.request, response.clone());
//       return response;
//     })()
//   );
// });

// Vider le cache
self.addEventListener("activate", (e) => {
  e.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key === cacheName) {
            return;
          }
          return caches.delete(key);
        })
      );
    })
  );
});
