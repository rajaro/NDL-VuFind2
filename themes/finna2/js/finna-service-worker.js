const cacheName = "finna-sw-v%%service_worker_version%%";
const fallbackUrl = '%%fallback_url%%';
const offlineImageUrl = '%%offline_image_url%%';

self.addEventListener('install', function installSw(event) {
  event.waitUntil(
    caches.open(cacheName).then(function onCacheOpen(cache) {
      cache.addAll(
        [
          fallbackUrl,
          offlineImageUrl
        ]
      )
    })
  );
});

self.addEventListener('activate', function activateEvent(event) {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== cacheName) {
            return caches.delete(cache);
          }
        })
      );
    })
  );
});

self.addEventListener("fetch", function fetchEvent(event) {
  if (event.request.mode === 'navigate' || event.request.destination === 'image') {
    event.respondWith(
      fetch(event.request)
        .then(function responseFromNetwork(response) {
          return response;
        })
        .catch(function onError() {
          if (event.request.mode === "navigate") {
            return caches.match(fallbackUrl).then(function responseFromCache(cachedResponse) {
              return cachedResponse;
            });
          } else if (event.request.destination === 'image') {
            return caches.match(event.request).then(function imageFromCache(cachedImage) {
              return cachedImage;
            });
          }
        })
    );
  }
});
