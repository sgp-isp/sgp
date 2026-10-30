// SGP ISPEB Service Worker — Habilita instalación PWA sin interferir con PHP/PJAX
const CACHE_NAME = 'sgp-pwa-v4';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => caches.delete(key))
            );
        }).then(() => clients.claim())
    );
});

// Passthrough fetch: cumple el criterio de instalabilidad PWA en navegadores
// pero deja que el navegador maneje de forma 100% nativa las rutas PHP y PJAX.
self.addEventListener('fetch', (event) => {
    // Intencionalmente vacío: nunca llamar event.respondWith() para evitar
    // interceptar peticiones dinámicas, abortadas o con cookies de sesión.
});
