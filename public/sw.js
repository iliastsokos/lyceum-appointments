// Service worker for installability + a friendly offline fallback.
//
// Deliberately does NOT cache dashboard/booking/availability pages or any
// dynamic data — this app's whole point is showing live slot availability,
// and a cached "snapshot" of it would be actively misleading (a guardian
// could try to book a slot that's already gone, or a teacher could see
// stale appointments). Only build-hashed static assets (safe: their URL
// changes whenever their content does) and a static offline page are
// cached.

const CACHE_NAME = 'lyceum-static-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll([OFFLINE_URL])),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)),
        )),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Full-page navigations: always go to the network for current data;
    // only fall back to the offline page when there's truly no connection.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL)),
        );
        return;
    }

    // Vite-built, content-hashed assets: safe to cache indefinitely.
    if (url.origin === self.location.origin && url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                return response;
            })),
        );
    }
});
