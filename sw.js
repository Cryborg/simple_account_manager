const CACHE_NAME = 'mes-comptes-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/css/style.css',
  '/js/app.js',
  '/js/sidebar.js',
  '/js/recurring.js',
  '/js/periodicity.js',
  '/favicon.svg'
];

// Installation du Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

// Activation et nettoyage des anciens caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Stratégie : Network First, Cache Fallback
self.addEventListener('fetch', event => {
  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Cloner la réponse car elle ne peut être utilisée qu'une fois
        const responseToCache = response.clone();

        caches.open(CACHE_NAME)
          .then(cache => {
            cache.put(event.request, responseToCache);
          });

        return response;
      })
      .catch(() => {
        // En cas d'échec, utiliser le cache
        return caches.match(event.request);
      })
  );
});
