const CACHE_NAME = 'Big Wallet Lounge Management information System-v1';
const ASSETS = [
  './',
  './index.html',
  './logo.png',
  './manifest.json'
];

// Install event - cache files
self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
    })
  );
});

// Fetch event - serve from cache if offline, otherwise fetch from network
self.addEventListener('fetch', (e) => {
  // Only cache GET requests (ignore POST requests for login/API)
  if (e.request.method !== 'GET') return;
  
  e.respondWith(
    caches.match(e.request).then((response) => {
      return response || fetch(e.request);
    })
  );
});