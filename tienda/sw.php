<?php
// Service Worker servido como PHP para poder usar URLs de entorno
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');
$iconUrl  = URL_ASSETS . '/img/Logo_Canetto_Cookie.png';
$pedidosUrl = URL_TIENDA . '/mis-pedidos.php';
$tiendaUrl  = URL_TIENDA . '/';
?>
// Service Worker — Canetto Push Notifications

self.addEventListener('push', event => {
    event.waitUntil(handlePush());
});

async function handlePush() {
    let epHash = '';
    try {
        const sub = await self.registration.pushManager.getSubscription();
        if (sub) {
            const ep = sub.endpoint;
            const buffer = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(ep));
            epHash = Array.from(new Uint8Array(buffer)).map(b => b.toString(16).padStart(2, '0')).join('');
        }
    } catch (_) {}

    try {
        const res  = await fetch('./api/get_notif_push.php?h=' + epHash, { credentials: 'omit' });
        const data = await res.json();
        await self.registration.showNotification(data.titulo || 'Canetto 🍪', {
            body:    data.cuerpo || 'Tu pedido fue actualizado',
            icon:    '<?= $iconUrl ?>',
            badge:   '<?= $iconUrl ?>',
            vibrate: [200, 100, 200],
            data:    { url: data.url || '<?= $pedidosUrl ?>' },
        });
    } catch (_) {
        await self.registration.showNotification('Canetto 🍪', {
            body:  'Tu pedido fue actualizado — tocá para ver el estado',
            icon:  '<?= $iconUrl ?>',
            data:  { url: '<?= $pedidosUrl ?>' },
        });
    }
}

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = event.notification.data?.url || '<?= $pedidosUrl ?>';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
            for (const client of list) {
                if (client.url.includes('mis-pedidos') && 'focus' in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});

self.addEventListener('activate', e => e.waitUntil(clients.claim()));
