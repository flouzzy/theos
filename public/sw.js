const cacheName = "rocher-academie-v1.0.0";

const appCachedFiles = [
  "/",
  "/index.html",
  "/build/",
  "/images/",
  "/images/*.jpg",
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
    caches.open(cacheName).then((cache) => cache.addAll(appCachedFiles))
  );
});

self.addEventListener("fetch", (e) => {
  console.log(e.request.url);
  e.respondWith(
    caches.match(e.request).then((response) => response || fetch(e.request))
  );
});
