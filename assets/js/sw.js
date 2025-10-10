// Basic service worker for cart persistence
const CACHE_NAME = 'homewareontap-v1';
const urlsToCache = [
    '/',
    '/assets/css/style.css',
    '/assets/js/cart.js',
    '/assets/js/main.js'
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            }
        )
    );
});