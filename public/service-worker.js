// ============================================================
// Service Worker — Académie Le Rocher
// Stratégie : Network-First avec timeout (adapté connexions 3G)
// ============================================================

const CACHE_VERSION = "1.0.0";

// 3 caches séparés pour des stratégies différentes
const STATIC_CACHE = `lr-static-v${CACHE_VERSION}`;   // Cache-first, longue durée
const PAGE_CACHE = `lr-pages-v${CACHE_VERSION}`;    // Network-first, fallback cache
const ASSET_CACHE = `lr-assets-v${CACHE_VERSION}`;   // Stale-while-revalidate

const ALL_CACHES = [STATIC_CACHE, PAGE_CACHE, ASSET_CACHE];

// Timeout réseau en ms — 4s permet à la 3G de répondre dans la plupart des cas
const NETWORK_TIMEOUT_MS = 4000;

// Max entrées dans le cache page (LRU eviction)
const PAGE_CACHE_MAX = 30;
const ASSET_CACHE_MAX = 80;

// Assets statiques immuables — logos, favicons, offline page
const STATIC_ASSETS = [
  "/offline.html",
  "/images/favicon/android-chrome-192x192.png",
  "/images/favicon/android-chrome-512x512.png",
  "/images/favicon/apple-touch-icon.png",
  "/images/favicon/favicon-32x32.png",
  "/images/logo.svg",
  "/images/logo_white.svg",
  "/images/default-course.png",
  "/images/default-user.png",
  "/site.webmanifest",
];

// Pages/routes à ne JAMAIS mettre en cache
const BYPASS_PATTERNS = [
  /^\/login/,
  /^\/logout/,
  /^\/register/,
  /^\/admin/,
  /^\/verify/,
  /^\/reset-password/
];

// Extensions vidéo — exclues car gérées par OfflineVideoManager (IndexedDB)
const VIDEO_PATTERN = /\.(mp4|webm|ogg|mov)$/i;

// ----------------------------------------------------------------
// INSTALL — préchargement des assets statiques
// ----------------------------------------------------------------
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => {
      return cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn("[SW] Certains assets statiques n'ont pas pu être chargés :", err);
      });
    })
  );
  // Activation immédiate sans attendre que les anciens clients ferment
  self.skipWaiting();
});

// ----------------------------------------------------------------
// ACTIVATE — nettoyage des anciens caches
// ----------------------------------------------------------------
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(
        cacheNames
          .filter((name) => !ALL_CACHES.includes(name))
          .map((name) => {
            console.log("[SW] Suppression ancien cache :", name);
            return caches.delete(name);
          })
      )
    ).then(() => self.clients.claim())
  );
});

// ----------------------------------------------------------------
// FETCH — routing des stratégies
// ----------------------------------------------------------------
self.addEventListener("fetch", (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Ne traiter que les requêtes internes (même origine)
  if (url.origin !== location.origin) {
    return; // Laisser passer les CDN, API externes, etc.
  }

  // Bypass total pour certaines routes sensibles
  if (BYPASS_PATTERNS.some((p) => p.test(url.pathname + url.search))) {
    return fetch(req);
  }

  // Vidéos — gérées par IndexedDB
  if (VIDEO_PATTERN.test(url.pathname) || req.destination === "video") {
    return;
  }

  // Assets avec fingerprint (hash dans l'URL = immuables)
  // ex: /assets/app-abc123.js → cache-first agressif
  if (url.pathname.startsWith("/assets/")) {
    event.respondWith(assetStrategy(req));
    return;
  }

  // Navigation HTML → network-first avec timeout
  if (req.mode === "navigate" || req.headers.get("accept")?.includes("text/html")) {
    event.respondWith(pageStrategy(req));
    return;
  }

  // Autres ressources internes (CSS non-fingerprintées, images statiques)
  event.respondWith(assetStrategy(req));
});

// ----------------------------------------------------------------
// Stratégie NETWORK-FIRST avec timeout (pour les pages HTML)
// Optimisée pour 3G : si le réseau prend >4s, on sert le cache.
// ----------------------------------------------------------------
async function pageStrategy(req) {
  const cache = await caches.open(PAGE_CACHE);

  try {
    const networkResponse = await Promise.race([
      fetch(req.clone()),
      new Promise((_, reject) =>
        setTimeout(() => reject(new Error("network-timeout")), NETWORK_TIMEOUT_MS)
      ),
    ]);

    if (networkResponse.ok) {
      // Mettre à jour le cache et appliquer LRU
      cache.put(req, networkResponse.clone());
      await enforceCacheLimit(PAGE_CACHE, PAGE_CACHE_MAX);
    }

    return networkResponse;
  } catch (err) {
    // Réseau trop lent ou hors ligne → fallback cache
    const cached = await cache.match(req);
    if (cached) {
      console.log("[SW] Réseau indisponible, cache servi :", req.url);
      return cached;
    }

    // Aucun cache → page offline
    const offlinePage = await caches.match("/offline.html", { cacheName: STATIC_CACHE });
    return offlinePage || new Response("Hors connexion", { status: 503, statusText: "Service Unavailable" });
  }
}

// ----------------------------------------------------------------
// Stratégie STALE-WHILE-REVALIDATE (pour les assets)
// Sert immédiatement depuis le cache et met à jour en arrière-plan.
// Idéal pour CSS/JS avec fingerprint.
// ----------------------------------------------------------------
async function assetStrategy(req) {
  const cache = await caches.open(ASSET_CACHE);
  const cached = await cache.match(req);

  const fetchAndUpdate = fetch(req.clone())
    .then((response) => {
      if (response.ok) {
        cache.put(req, response.clone());
        enforceCacheLimit(ASSET_CACHE, ASSET_CACHE_MAX);
      }
      return response;
    })
    .catch(() => null);

  // Si en cache → répondre immédiatement et mettre à jour en fond
  return cached || fetchAndUpdate;
}

// ----------------------------------------------------------------
// LRU Cache Eviction — supprime les entrées les plus anciennes
// ----------------------------------------------------------------
async function enforceCacheLimit(cacheName, maxEntries) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();

  if (keys.length > maxEntries) {
    const toDelete = keys.slice(0, keys.length - maxEntries);
    await Promise.all(toDelete.map((key) => cache.delete(key)));
  }
}

// ----------------------------------------------------------------
// MESSAGES — communication avec la page principale
// ----------------------------------------------------------------
self.addEventListener("message", (event) => {
  if (!event.data) return;

  switch (event.data.type) {
    case "SKIP_WAITING":
      self.skipWaiting();
      break;

    case "CLEAR_PAGE_CACHE":
      caches.delete(PAGE_CACHE).then(() => {
        event.source?.postMessage({ type: "PAGE_CACHE_CLEARED" });
      });
      break;

    default:
      // Ignore les messages inconnus silencieusement
      break;
  }
});
