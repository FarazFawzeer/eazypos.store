// EazyPOS PWA service worker. No business records, auth tokens, or API responses are cached.
self.addEventListener('install', (event) => { self.skipWaiting(); });
self.addEventListener('activate', (event) => { event.waitUntil(self.clients.claim()); });
