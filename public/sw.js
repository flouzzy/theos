self.addEventListener("install", (e) => {
  e.waitUntil(
    caches
      .open("rocher-academie-store")
      .then((cache) => cache.addAll(["/build/", "/images/", "/images/*.jpg"]))
  );
});

self.addEventListener("fetch", (e) => {
  console.log(e.request.url);
  e.respondWith(
    caches.match(e.request).then((response) => response || fetch(e.request))
  );
});
