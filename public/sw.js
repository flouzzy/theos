const PREFIX = "v1.0.1";

const cacheName = "rocher-academie-" + PREFIX;

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
  // "/styles/ionic.bundle.css",
  // "/js/tinymce.min.js",
];

self.addEventListener("install", (event) => {
  console.log(`[Service Worker] Install ${PREFIX}`);
  self.skipWaiting();

  // event.waitUntil(
  //   caches
  //     .open(cacheName)
  //     .then((cache) => cache.addAll(contentToCache))
  //     .catch((err) => console.log("install:error", err))
  // );
});

// Fetching content using Service Worker
self.addEventListener("fetch", (event) => {
  console.log(`[Service Worker] Fetch ${PREFIX} :: ${event.request.url}`);

  // Cache http and https only, skip unsupported chrome-extension:// and file://...
  // if (
  //   !(e.request.url.startsWith("http:") || e.request.url.startsWith("https:"))
  // ) {
  //   return;
  // }

  if (event.request.mode === "navigate") {
    event.respondWith(
      (async () => {
        try {
          const preloadResponse = await event.preloadResponse;
          if (preloadResponse) {
            console.log("preloadResponse", preloadResponse);
            return preloadResponse;
          }

          return await fetch(event.request.url)
            .then((resp) => {
              console.log("fetch::resp", resp);
              return caches.open(cacheName).then((cache) => {
                cache.put(event.request.url, resp.clone());
                return resp;
              });
            })
            .catch(() => {
              return caches.match("/offline");
            });
        } catch (error) {
          return new Response("Erreur");
        }
      })()
    );
  }
});

self.addEventListener("activate", (event) => {
  console.log(`[Service Worker] activate ${PREFIX}`);
  clients.claim();
});

// Vider le cache
// self.addEventListener("activate", (event) => {
//   console.log(`[Service Worker] activate ${PREFIX}`, event.request.url);
//   event.waitUntil(
//     caches.keys().then((keyList) => {
//       return Promise.all(
//         keyList.map((key) => {
//           if (key === cacheName) {
//             return;
//           }
//           return caches.delete(key);
//         })
//       );
//     })
//   );
// });
