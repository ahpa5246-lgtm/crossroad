/* Service Worker — مفترق الطرق v6 */
'use strict';

const CACHE_VERSION = 'v6';
const CACHE_STATIC  = 'mftaraq-static-' + CACHE_VERSION;
const CACHE_PAGES   = 'mftaraq-pages-' + CACHE_VERSION;
const OFFLINE_URL   = '/offline.html';

const PRECACHE_ASSETS = [
  OFFLINE_URL,
  '/public/css/style.css',
  '/public/js/main.js',
  '/public/js/offline.js',
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_STATIC)
      .then(cache => cache.addAll(PRECACHE_ASSETS.map(u => new Request(u, { cache: 'reload' }))))
      .then(() => self.skipWaiting())
      .catch(err => console.warn('[SW] Pre-cache failed:', err))
  );
});

self.addEventListener('activate', event => {
  const valid = [CACHE_STATIC, CACHE_PAGES];
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => !valid.includes(k)).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const req = event.request;
  if (req.method !== 'GET') return;

  // Only handle same-origin requests
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Never cache admin, auth, api responses
  const blocked = ['/admin', '/auth', '/api', '/account'];
  if (blocked.some(p => url.pathname.startsWith(p))) return;

  const isStatic = /\.(css|js|png|jpg|jpeg|webp|ico|woff2?|ttf|gif)(\?.*)?$/.test(url.pathname);
  const isHTML   = (req.headers.get('accept') || '').includes('text/html');

  if (isStatic) {
    event.respondWith(
      caches.match(req).then(cached => {
        if (cached) return cached;
        return fetch(req).then(res => {
          if (res.ok) caches.open(CACHE_STATIC).then(c => c.put(req, res.clone()));
          return res;
        }).catch(() => new Response('', { status: 404 }));
      })
    );
    return;
  }

  if (isHTML) {
    event.respondWith(
      fetch(req)
        .then(res => {
          if (res.ok) caches.open(CACHE_PAGES).then(c => c.put(req, res.clone()));
          return res;
        })
        .catch(() => caches.match(req).then(cached => cached || caches.match(OFFLINE_URL)))
    );
    return;
  }

  event.respondWith(
    fetch(req).catch(() =>
      new Response('{"error":"offline"}', { status: 503, headers: { 'Content-Type': 'application/json' } })
    )
  );
});

// Message handler for online-check from offline page
self.addEventListener('message', event => {
  if (event.data === 'check_online') {
    fetch('/').then(() => {
      self.clients.matchAll().then(clients => {
        clients.forEach(c => c.postMessage({ type: 'online' }));
      });
    }).catch(() => {});
  }
});
