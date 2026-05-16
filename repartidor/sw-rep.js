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
    let cuerpo = 'Confirmá que seguís en el turno. Tocá para abrir la app.';
    let url    = '/canetto/repartidor/';

    // Obtener hash del endpoint para identificarse
    let epHash = '';
    try {
        const sub = await self.registration.pushManager.getSubscription();
        if (sub) {
            const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(sub.endpoint));
            epHash = Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2,'0')).join('');
        }
    } catch (_) {}

    // Pedir contenido al servidor
    try {
        const res  = await fetch('./api/get_notif_push_rep.php?h=' + epHash, { credentials: 'omit' });
        const data = await res.json();
        titulo = data.titulo || titulo;
        cuerpo = data.cuerpo || cuerpo;
        url    = data.url    || url;
    } catch (_) {}

    // Avisar a la app abierta para que toque el sonido personalizado
    const clientList = await self.clients.matchAll({ type: 'window' });
    clientList.forEach(c => c.postMessage({ type: 'PLAY_SOUND' }));

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

// ── Tap en la notificación → confirmar actividad ────────────────────
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const esCheckActividad = event.notification.tag === 'rep-actividad';
    const url = event.notification.data?.url || '/canetto/repartidor/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
            // Si es el check de actividad, mandar mensaje a la app para confirmar
            if (esCheckActividad) {
                list.forEach(c => c.postMessage({ type: 'CONFIRMAR_ACTIVO' }));
            }
            // Enfocar o abrir la app
            for (const c of list) {
                if (c.url.includes('/repartidor/') && 'focus' in c) return c.focus();
            }
            return clients.openWindow(url);
        })
    );
});
