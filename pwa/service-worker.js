/**
 * Junxtion Service Worker
 *
 * Handles caching, offline support, and push notifications
 */

const CACHE_VERSION = 'v1.0.0';
const CACHE_NAME = `junxtion-${CACHE_VERSION}`;

// Assets to cache on install (shell)
const SHELL_ASSETS = [
  '/',
  '/app/index.php',
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/pwa/manifest.json',
  '/assets/img/icon-192x192.png',
  '/offline.html'
];

// Cache strategies
const CACHE_STRATEGIES = {
  // Cache first, then network
  cacheFirst: async (request) => {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    const networkResponse = await fetch(request);
    cache.put(request, networkResponse.clone());
    return networkResponse;
  },

  // Network first, fall back to cache
  networkFirst: async (request) => {
    try {
      const networkResponse = await fetch(request);
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
      return networkResponse;
    } catch (error) {
      const cachedResponse = await caches.match(request);
      if (cachedResponse) {
        return cachedResponse;
      }
      throw error;
    }
  },

  // Stale while revalidate
  staleWhileRevalidate: async (request) => {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);

    const fetchPromise = fetch(request).then((networkResponse) => {
      cache.put(request, networkResponse.clone());
      return networkResponse;
    });

    return cachedResponse || fetchPromise;
  }
};

// Install event - cache shell assets
self.addEventListener('install', (event) => {
  console.log('[SW] Install');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW] Caching shell assets');
        return cache.addAll(SHELL_ASSETS.filter(url => !url.endsWith('.php')));
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[SW] Activate');
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((name) => name.startsWith('junxtion-') && name !== CACHE_NAME)
            .map((name) => {
              console.log('[SW] Deleting old cache:', name);
              return caches.delete(name);
            })
        );
      })
      .then(() => self.clients.claim())
  );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip cross-origin requests
  if (url.origin !== location.origin) {
    return;
  }

  // API requests - network first
  if (url.pathname.startsWith('/api/')) {
    // Don't cache API requests by default
    // Menu API can be cached for short duration
    if (url.pathname === '/api/menu') {
      event.respondWith(CACHE_STRATEGIES.staleWhileRevalidate(request));
    }
    return;
  }

  // Static assets - cache first
  if (url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/)) {
    event.respondWith(CACHE_STRATEGIES.cacheFirst(request));
    return;
  }

  // HTML/PHP pages - network first with offline fallback
  event.respondWith(
    CACHE_STRATEGIES.networkFirst(request)
      .catch(() => {
        // Return offline page if we can't get the page
        return caches.match('/offline.html');
      })
  );
});

// Push notification event
self.addEventListener('push', (event) => {
  console.log('[SW] Push received');

  let data = {
    title: 'Junxtion',
    body: 'You have a new notification',
    icon: '/assets/img/icon-192x192.png',
    badge: '/assets/img/badge-72x72.png',
    tag: 'default',
    data: {}
  };

  if (event.data) {
    try {
      const payload = event.data.json();
      data = { ...data, ...payload };
    } catch (e) {
      data.body = event.data.text();
    }
  }

  const options = {
    body: data.body,
    icon: data.icon,
    badge: data.badge,
    tag: data.tag,
    data: data.data,
    vibrate: [200, 100, 200],
    actions: data.actions || [],
    requireInteraction: data.requireInteraction || false
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
  console.log('[SW] Notification clicked:', event.notification.tag);
  event.notification.close();

  const data = event.notification.data || {};
  let targetUrl = '/';

  // Handle deep links
  if (data.deeplink) {
    targetUrl = data.deeplink;
  } else if (data.orderId) {
    targetUrl = `/app/track.php?order=${data.orderId}`;
  }

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // If app is open, focus it
        for (const client of clientList) {
          if (client.url.includes(location.origin) && 'focus' in client) {
            client.navigate(targetUrl);
            return client.focus();
          }
        }
        // Otherwise open new window
        if (clients.openWindow) {
          return clients.openWindow(targetUrl);
        }
      })
  );
});

// Background sync event (for offline orders)
self.addEventListener('sync', (event) => {
  console.log('[SW] Background sync:', event.tag);

  if (event.tag === 'sync-orders') {
    event.waitUntil(syncPendingOrders());
  }
});

// Sync pending orders
async function syncPendingOrders() {
  const cache = await caches.open(CACHE_NAME);
  const pendingOrders = await cache.match('/pending-orders');

  if (!pendingOrders) return;

  const orders = await pendingOrders.json();

  for (const order of orders) {
    try {
      const response = await fetch('/api/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(order)
      });

      if (response.ok) {
        // Remove from pending
        orders.splice(orders.indexOf(order), 1);
      }
    } catch (e) {
      console.error('[SW] Failed to sync order:', e);
    }
  }

  // Update pending orders cache
  await cache.put('/pending-orders', new Response(JSON.stringify(orders)));
}

// Message event (for communication with main app)
self.addEventListener('message', (event) => {
  console.log('[SW] Message received:', event.data);

  if (event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: CACHE_VERSION });
  }
});
