/**
 * MicroClimeet Service Worker — sw.js
 * Menangani Web Push Notifications dari server VAPID
 * File ini HARUS berada di root domain (diakses via /sw.js)
 */

const CACHE_NAME = 'microclimeet-v1';

// Install event
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

// Push event — terima notifikasi dari server
self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'MicroClimeet', body: event.data ? event.data.text() : 'Ada notifikasi baru' };
    }

    const title   = data.title || 'MicroClimeet Alert';
    const options = {
        body:   data.body   || 'Sensor greenhouse Anda memerlukan perhatian.',
        icon:   data.icon   || '/assets/images/brand/mini_logo.png',
        badge:  data.badge  || '/assets/images/brand/mini_logo.png',
        tag:    'mcm-alert',
        renotify: true,
        data: {
            url: data.url || '/petani/alerts.php',
        },
        actions: [
            { action: 'view',    title: '👁 Lihat Dashboard' },
            { action: 'dismiss', title: '✖ Tutup'            },
        ],
        vibrate: [200, 100, 200],
        requireInteraction: true,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// NotificationClick event — navigasi saat notifikasi diklik
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'dismiss') return;

    const targetUrl = event.notification.data?.url || '/petani/alerts.php';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Fokus ke tab yang sudah ada jika ada
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.focus();
                    client.navigate(targetUrl);
                    return;
                }
            }
            // Buka tab baru jika belum ada
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
