import { ref } from 'vue';

/**
 * Manages browser/OS push notifications backed by the Web Push API.
 * Registers the service worker, requests permission, and syncs the
 * PushSubscription with the server (laravel-notification-channels/webpush).
 */

const isSupported = ref(
    typeof window !== 'undefined' &&
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window,
);

const permission = ref<NotificationPermission>(
    typeof Notification !== 'undefined' ? Notification.permission : 'default',
);

const isSubscribed = ref(false);

/** Read the Laravel XSRF-TOKEN cookie for fetch-based POSTs. */
function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

/** Convert a base64url VAPID public key into the Uint8Array the PushManager expects. */
function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function post(url: string, body: unknown): Promise<void> {
    await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    });
}

export function usePushNotifications() {
    /** Ensure the service worker is registered, returning its registration. */
    async function registerServiceWorker(): Promise<ServiceWorkerRegistration | null> {
        if (!isSupported.value) return null;
        try {
            return await navigator.serviceWorker.register('/sw.js');
        } catch (e) {
            console.warn('[push] service worker registration failed', e);
            return null;
        }
    }

    /**
     * Subscribe the current browser to push. Requests permission if needed and
     * sends the subscription to the server. Safe to call on every page load —
     * an existing subscription is simply refreshed.
     */
    async function subscribe(vapidPublicKey?: string | null): Promise<boolean> {
        if (!isSupported.value || !vapidPublicKey) return false;

        const registration = await registerServiceWorker();
        if (!registration) return false;

        if (Notification.permission === 'default') {
            permission.value = await Notification.requestPermission();
        } else {
            permission.value = Notification.permission;
        }

        if (permission.value !== 'granted') return false;

        try {
            let subscription = await registration.pushManager.getSubscription();
            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                });
            }

            const json = subscription.toJSON();
            await post('/notifications/subscribe', {
                endpoint: json.endpoint,
                keys: json.keys,
            });

            isSubscribed.value = true;
            return true;
        } catch (e) {
            console.warn('[push] subscription failed', e);
            return false;
        }
    }

    /** Unsubscribe this browser and tell the server to forget it. */
    async function unsubscribe(): Promise<void> {
        if (!isSupported.value) return;
        const registration = await navigator.serviceWorker.getRegistration();
        const subscription = await registration?.pushManager.getSubscription();
        if (subscription) {
            const endpoint = subscription.endpoint;
            await subscription.unsubscribe();
            await post('/notifications/unsubscribe', { endpoint });
        }
        isSubscribed.value = false;
    }

    return { isSupported, permission, isSubscribed, subscribe, unsubscribe };
}
