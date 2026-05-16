// Service Worker — Canetto Repartidor
// Scope: /canetto/repartidor/

self.addEventListener('install',  () => self.skipWaiting());
self.addEventListener('activate', e  => e.waitUntil(clients.claim()));

// ── Push desde servidor ──────────────────────────────────────────────
self.addEventListener('push', event => {
    event.waitUntil(mostrarPush(event));
});

async function mostrarPush(event) {
    let titulo = '👋 ¿Seguís activo?';
    let cuerpo = 'Confirmá que seguís en la app. Tu sesión se cerrará pronto.';
    let url    = '/canetto/repartidor/';

    // Si el push trae datos JSON, usarlos
    if (event.data) {
        try {
            const d = event.data.json();
            titulo = d.titulo || titulo;
            cuerpo = d.cuerpo || cuerpo;
            url    = d.url    || url;
        } catch (_) {}
    }

    await self.registration.showNotification(titulo, {
        body:             cuerpo,
        icon:             '/canetto/assets/img/Logo_Canetto_Cookie.png',
        badge:            '/canetto/assets/img/Logo_Canetto_Cookie.png',
        vibrate:          [400, 150, 400],
        requireInteraction: true,
        tag:              'rep-actividad',
        renotify:         true,
        data:             { url },
    });
}

// ── Tap en la notificación → abrir/enfocar la app ───────────────────
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = event.notification.data?.url || '/canetto/repartidor/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
            for (const c of list) {
                if (c.url.includes('/repartidor/') && 'focus' in c) return c.focus();
            }
            return clients.openWindow(url);
        })
    );
});
