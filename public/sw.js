const cacheName = "rocher-academie-v1.0.0";

const contentToCache = [
  "/",
  "/index.html",
  "/build/",
  "/images/",
  "/images/*.jpg",
  "/images/*.png",
  "/images/*.svg",
  "/icons/",
  "/icons/*.png",
  "/icons/*.svg",
  "/site.webmanifest",
  "/build/bundle.js",
  "/build/bundle.css",
  "/images/favicon/android-chrome-192x192.png",
  "/images/favicon/android-chrome-512x512.png",
  "/images/favicon/apple-touch-icon.png",
  "/images/favicon/favicon-32x32.png",
  "/images/favicon/favicon-16x16.png",
];

self.addEventListener("install", (e) => {
  e.waitUntil(
    caches.open(cacheName).then((cache) => cache.addAll(contentToCache))
  );
});

// Fetching content using Service Worker
self.addEventListener("fetch", (e) => {
  console.log("[Service Worker] Fetch", e.request.url);

  // Cache http and https only, skip unsupported chrome-extension:// and file://...
  if (
    !(e.request.url.startsWith("http:") || e.request.url.startsWith("https:"))
  ) {
    return;
  }

  e.respondWith(
    (async () => {
      const r = await caches.match(e.request);
      console.log(`[Service Worker] Fetching resource: ${e.request.url}`);
      if (r) return r;
      const response = await fetch(e.request);
      const cache = await caches.open(cacheName);
      console.log(`[Service Worker] Caching new resource: ${e.request.url}`);
      cache.put(e.request, response.clone());
      return response;
    })()
  );
});
