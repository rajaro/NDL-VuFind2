const cacheName = "finna-sw-v%%service_worker_version%%";
const fallbackUrl = '%%fallback_url%%';
const offlineImageUrl = '%%offline_image_url%%';

self.addEventListener('install', function installSw(e) {
  e.waitUntil(
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

self.addEventListener('activate', event => {
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

const fetchRequest = async ({request}) => {
  try {
    const responseFromNetwork = await fetch(request);
    return responseFromNetwork;
  } catch (error) {
    if (request.mode === "navigate") {
      const fallbackResponse = await caches.match(fallbackUrl);
      if (fallbackResponse) {
        return fallbackResponse;
      }
    } else if (request.destination === "image") {
      const fallbackImage = await caches.match(request);
      if (fallbackImage) {
        return fallbackImage;
      }
    }
  }
};

self.addEventListener("fetch", (event) => {
  if (event.request.mode === 'navigate' || event.request.destination === 'image') {
    event.respondWith(
      fetchRequest({request: event.request})
    );
  }
});
