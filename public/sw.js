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
    // Stratégie : Cache First for static assets, Network First for others
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request).then((fetchResponse) => {
                // Optionnel : mettre en cache dynamiquement certaines requêtes
                return fetchResponse;
            });
        }).catch(() => {
            // Fallback hors-ligne
            if (event.request.mode === 'navigate') {
                return caches.match('/');
            }
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
