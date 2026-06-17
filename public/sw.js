// PeoplePulse service worker — handles native browser/OS push notifications.
// Delivered by laravel-notification-channels/webpush (minishlink/web-push) using
// the WebPushMessage payload defined in App\Notifications\NewLeaveRequest.

self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let payload = {};
    try {
        payload = event.data.json();
    } catch (e) {
        payload = { title: 'PeoplePulse', body: event.data.text() };
    }

    const title = payload.title || 'PeoplePulse';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/favicon.ico',
        badge: payload.badge || '/favicon.ico',
        data: payload.data || {},
        tag: payload.tag || undefined,
        renotify: !!payload.tag,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) || '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Focus an existing tab if one is already open.
            for (const client of clientList) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            // Otherwise open a new window.
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
