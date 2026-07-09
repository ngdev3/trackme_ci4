/* ERP Web Push service worker.
 * Receives push events from the browser's push service and displays a
 * notification; focuses/opens the app when the notification is clicked. */
self.addEventListener('push', function (event) {
    var data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { title: 'Notification', body: event.data ? event.data.text() : '' }; }
    var title = data.title || 'ERP Admin';
    var options = {
        body: data.body || '',
        icon: data.icon || '/ERP/assets/img/favicon.svg',
        badge: data.badge || '/ERP/assets/img/favicon.svg',
        data: { url: data.url || '/ERP/notifications' }
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.url) || '/ERP/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
            for (var i = 0; i < list.length; i++) {
                if (list[i].url.indexOf(url) !== -1 && 'focus' in list[i]) { return list[i].focus(); }
            }
            if (clients.openWindow) { return clients.openWindow(url); }
        })
    );
});
