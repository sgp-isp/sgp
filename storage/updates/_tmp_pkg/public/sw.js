// SGP ISPEB Service Worker — Habilita instalación PWA en Teléfonos Móviles
const CACHE_NAME = 'sgp-pwa-v2';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        }).then(() => clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    // Para llamadas no GET (POST/PUT), dejar pasar directo a la red
    if (event.request.method !== 'GET') return;

    // Network-first con fallback
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
