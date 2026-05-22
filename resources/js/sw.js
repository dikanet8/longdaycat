import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching';
import { NavigationRoute, registerRoute } from 'workbox-routing';
import { createHandlerBoundToURL } from 'workbox-precaching';

// Workbox precache manifest (auto-injected by VitePWA)
precacheAndRoute(self.__WB_MANIFEST);
cleanupOutdatedCaches();

// SPA Navigation fallback
registerRoute(
    new NavigationRoute(createHandlerBoundToURL('/'))
);

// Handle skip waiting
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// ─── Web Push Notification Handler ─────────────────────────────────────────

self.addEventListener('push', (event) => {
    if (!event.data) return;

    let data;
    try {
        data = event.data.json();
    } catch (e) {
        data = {
            title: 'Longdaycat.Co',
            message: event.data.text(),
            url: '/',
        };
    }

    const title   = data.title   || 'Longdaycat.Co';
    const options = {
        body:    data.message || data.body || '',
        icon:    data.icon    || '/logo.svg',
        badge:   data.badge   || '/logo.svg',
        tag:     'longdaycat-notif',
        renotify: true,
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/',
        },
        actions: [
            { action: 'open',    title: 'Buka' },
            { action: 'dismiss', title: 'Tutup' },
        ],
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// ─── Notification Click Handler ─────────────────────────────────────────────

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'dismiss') return;

    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Kalau sudah ada tab, fokuskan
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(urlToOpen);
                    return client.focus();
                }
            }
            // Kalau belum ada tab, buka baru
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});
