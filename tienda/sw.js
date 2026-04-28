// Service Worker — Canetto Push Notifications
// Registrado en /canetto/tienda/sw.js → scope /canetto/tienda/

self.addEventListener('push', event => {
    event.waitUntil(handlePush());
});

async function handlePush() {
    // Obtener suscripción propia para identificarse ante el servidor
    let epHash = '';
    try {
        const sub = await self.registration.pushManager.getSubscription();
        if (sub) {
            // Hash simple del endpoint para pasar como parámetro
            const ep = sub.endpoint;
            const buffer = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(ep));
            epHash = Array.from(new Uint8Array(buffer)).map(b => b.toString(16).padStart(2, '0')).join('');
        }
    } catch (_) {}

    // Pedir contenido de la notificación al servidor
    try {
        const res  = await fetch('./api/get_notif_push.php?h=' + epHash, { credentials: 'omit' });
        const data = await res.json();
        await self.registration.showNotification(data.titulo || 'Canetto 🍪', {
            body:    data.cuerpo || 'Tu pedido fue actualizado',
            icon:    '/canetto/assets/img/Logo_Canetto_Cookie.png',
            badge:   '/canetto/assets/img/Logo_Canetto_Cookie.png',
            vibrate: [200, 100, 200],
            data:    { url: data.url || '/canetto/tienda/mis-pedidos.php' },
        });
    } catch (_) {
        // Fallback si la red falla
        await self.registration.showNotification('Canetto 🍪', {
            body:  'Tu pedido fue actualizado — tocá para ver el estado',
            icon:  '/canetto/assets/img/Logo_Canetto_Cookie.png',
            data:  { url: '/canetto/tienda/mis-pedidos.php' },
        });
    }
}

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = event.notification.data?.url || '/canetto/tienda/mis-pedidos.php';
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

// Activar de inmediato sin esperar tabs antiguas
self.addEventListener('activate', e => e.waitUntil(clients.claim()));
