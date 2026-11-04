const CACHE_NAME = 'medixa-tracker-v1';
const STATIC_ASSETS = [
    '/tracker',
    '/tracker/offline',
    '/manifest-tracker.json',
    '/tracker-assets/icons/icon-192.svg',
    '/tracker-assets/icons/icon-512.svg',
    '/tracker-assets/icons/maskable-512.svg'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // SECURITY: NEVER cache sensitive API responses, auth endpoints, or patient records
    if (url.pathname.startsWith('/api/') || url.pathname.includes('/auth') || request.method !== 'GET') {
        event.respondWith(fetch(request));
        return;
    }

    // Static assets & App Shell: Stale-while-revalidate or Network-first with offline fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => {
                return caches.match('/tracker/offline') || caches.match('/tracker');
            })
        );
        return;
    }

    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, networkResponse));
                    }
                }).catch(() => {});
                return cachedResponse;
            }

            return fetch(request).then((networkResponse) => {
                if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                    return networkResponse;
                }

                // Cache static assets only (CSS, JS, fonts, images)
                if (url.pathname.match(/\.(css|js|svg|png|jpg|woff2?)$/)) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, responseToCache));
                }

                return networkResponse;
            }).catch(() => {
                return caches.match(request);
            });
        })
    );
});
