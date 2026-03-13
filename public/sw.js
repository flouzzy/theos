const CACHE_NAME = 'le-rocher-cache-v1';
const ASSETS_TO_CACHE = [
    '/',
    '/manifest.json',
    '/assets/images/logo-z6LkZ5A.png',
    // On ajoutera ici les assets compilés via AssetMapper si nécessaire
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.filter((name) => name !== CACHE_NAME).map((name) => caches.delete(name))
            );
        })
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Stratégie "Cache First" pour les assets statiques (fonts, images, assets mapper CSS/JS)
    if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/images/') || url.hostname.includes('fonts.googleapis.com') || url.hostname.includes('fonts.gstatic.com')) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then((networkResponse) => {
                    return caches.open(CACHE_NAME).then((cache) => {
                        // Cache la réponse pour les futures requêtes
                        cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    });
                });
            })
        );
        return;
    }

    // Stratégie "Network First, fallback to Cache" pour le reste (HTML, API, etc.)
    event.respondWith(
        fetch(event.request).then((response) => {
            if (event.request.method === 'GET' && response.ok) {
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, responseClone);
                });
            }
            return response;
        }).catch(() => {
            // Fallback vers le cache hors-ligne
            return caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                if (event.request.mode === 'navigate') {
                    return caches.match('/');
                }
            });
        })
    );
});

self.addEventListener('push', (event) => {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Le Rocher Académie';
    const options = {
        body: data.body || 'Nouvelle notification',
        icon: '/assets/images/logo.png',
        badge: '/assets/images/logo.png',
        data: {
            url: data.url || '/'
        }
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});