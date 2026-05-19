<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');
$iconUrl  = URL_ASSETS . '/img/Logo_Canetto_Cookie.png';
$repUrl   = URL_REPARTIDOR . '/';
?>
// Service Worker — Canetto Repartidor

self.addEventListener('install',  () => self.skipWaiting());
self.addEventListener('activate', e  => e.waitUntil(clients.claim()));

self.addEventListener('push', event => {
    event.waitUntil(mostrarPush(event));
});

async function mostrarPush(event) {
    let titulo = '👋 ¿Seguís activo?';
    let cuerpo = 'Confirmá que seguís en el turno. Tocá para abrir la app.';
    let url    = '<?= $repUrl ?>';

    let epHash = '';
    try {
        const sub = await self.registration.pushManager.getSubscription();
        if (sub) {
            const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(sub.endpoint));
            epHash = Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2,'0')).join('');
        }
    } catch (_) {}

    try {
        const res  = await fetch('./api/get_notif_push_rep.php?h=' + epHash, { credentials: 'omit' });
        const data = await res.json();
        titulo = data.titulo || titulo;
        cuerpo = data.cuerpo || cuerpo;
        url    = data.url    || url;
    } catch (_) {}

    const clientList = await self.clients.matchAll({ type: 'window' });
    clientList.forEach(c => c.postMessage({ type: 'PLAY_SOUND' }));

    await self.registration.showNotification(titulo, {
        body:               cuerpo,
        icon:               '<?= $iconUrl ?>',
        badge:              '<?= $iconUrl ?>',
        vibrate:            [400, 150, 400],
        requireInteraction: true,
        tag:                'rep-actividad',
        renotify:           true,
        data:               { url },
    });
}

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const esCheckActividad = event.notification.tag === 'rep-actividad';
    const url = event.notification.data?.url || '<?= $repUrl ?>';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
            if (esCheckActividad) {
                list.forEach(c => c.postMessage({ type: 'CONFIRMAR_ACTIVO' }));
            }
            for (const c of list) {
                if (c.url.includes('/repartidor') && 'focus' in c) return c.focus();
            }
            return clients.openWindow(url);
        })
    );
});
