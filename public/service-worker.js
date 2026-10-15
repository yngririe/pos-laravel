// Twist & Roll POS — Service Worker
// v4 fixes stale CSS/JS after deployments: styles/scripts are network-first.
const CACHE_NAME = 'tnr-pos-v5';
const OFFLINE_URL = '/offline.html';
const CORE_ASSETS = ['/assets/images/logo.png','/offline.html','/manifest.json'];

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => Promise.allSettled(CORE_ASSETS.map(url => cache.add(url)))).then(() => self.skipWaiting()));
});

self.addEventListener('activate', event => {
  event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (!url.protocol.startsWith('http')) return;

  const sameOrigin = url.origin === self.location.origin;
  const isCodeAsset = sameOrigin && (request.destination === 'style' || request.destination === 'script' || url.pathname.endsWith('.css') || url.pathname.endsWith('.js'));
  const isPage = sameOrigin && request.mode === 'navigate';

  // Pages, CSS and JS must prefer the server so a normal reload gets the latest deployment.
  if (isPage || isCodeAsset) {
    event.respondWith(fetch(request).then(response => {
      if (response && response.ok) caches.open(CACHE_NAME).then(cache => cache.put(request, response.clone()));
      return response;
    }).catch(async () => (await caches.match(request)) || (isPage ? caches.match(OFFLINE_URL) : new Response('', {status:503}))));
    return;
  }

  // Images/manifest/fonts: cached for fast POS loading, refreshed in the background.
  event.respondWith(caches.match(request).then(cached => {
    const network = fetch(request).then(response => {
      if (response && response.status === 200 && response.type !== 'opaque') caches.open(CACHE_NAME).then(cache => cache.put(request, response.clone()));
      return response;
    }).catch(() => cached);
    return cached || network;
  }));
});
