self.addEventListener('push', (event) => {
    if (!event.data) return;
    const payload = event.data.json();
    const title   = payload.title || 'New Order';
    const options = {
        body:    payload.body || '',
        icon:    '/favicon.ico',
        badge:   '/favicon.ico',
        tag:     'customer-order-' + (payload.data?.sale_id || Date.now()),
        renotify: true,
        data:    payload.data || {},
        actions: [{ action: 'open', title: 'View Order' }],
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const saleId = event.notification.data?.sale_id;
    const url    = saleId ? `${self.location.origin}/sales/new?draft=${saleId}` : self.location.origin;
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const client of list) {
                if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                    client.focus();
                    client.navigate(url);
                    return;
                }
            }
            return clients.openWindow(url);
        })
    );
});
