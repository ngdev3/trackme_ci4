/* ------------------------------------------------------------------
 * ERP Web Push client.
 * Registers the service worker and subscribes the browser to push,
 * sending the subscription to the server (session + CSRF protected).
 *
 * Public API:
 *   window.erpEnablePush()   -> request permission + subscribe (returns Promise)
 *   window.erpDisablePush()  -> unsubscribe this browser
 *   window.erpPushSupported()-> boolean
 * ------------------------------------------------------------------ */
(function () {
    'use strict';

    var S = window.ERP_SETTINGS || {};
    var BASE = window.APP_BASE_URL || '';

    function supported() {
        return 'serviceWorker' in navigator && 'PushManager' in window;
    }
    window.erpPushSupported = supported;

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = window.atob(base64);
        var out = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; ++i) { out[i] = raw.charCodeAt(i); }
        return out;
    }

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? { header: 'X-CSRF-TOKEN', value: m.getAttribute('content') } : null;
    }

    function postJSON(path, body) {
        var headers = { 'Content-Type': 'application/json' };
        var c = csrf();
        if (c) { headers[c.header] = c.value; }
        return fetch(BASE + '/' + path, {
            method: 'POST', headers: headers, credentials: 'same-origin', body: JSON.stringify(body)
        });
    }

    function register() {
        return navigator.serviceWorker.register(BASE + '/sw.js');
    }

    window.erpEnablePush = function () {
        if (!supported()) {
            if (window.erpNotify) { erpNotify('warning', 'Push notifications are not supported by this browser.'); }
            return Promise.reject(new Error('unsupported'));
        }
        if (!S.vapidPublicKey) {
            if (window.erpNotify) { erpNotify('warning', 'Web push is not configured. Ask an admin to generate VAPID keys.'); }
            return Promise.reject(new Error('no-vapid'));
        }
        return Notification.requestPermission().then(function (perm) {
            if (perm !== 'granted') { throw new Error('denied'); }
            return register();
        }).then(function (reg) {
            return reg.pushManager.getSubscription().then(function (sub) {
                return sub || reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(S.vapidPublicKey)
                });
            });
        }).then(function (sub) {
            var json = sub.toJSON();
            return postJSON('push/subscribe', {
                endpoint: sub.endpoint,
                p256dh: json.keys ? json.keys.p256dh : '',
                auth: json.keys ? json.keys.auth : ''
            });
        }).then(function () {
            if (window.erpNotify) { erpNotify('success', 'Notifications enabled on this browser.'); }
        }).catch(function (err) {
            if (window.erpNotify) { erpNotify('error', 'Could not enable notifications: ' + err.message); }
            throw err;
        });
    };

    window.erpDisablePush = function () {
        if (!supported()) { return Promise.resolve(); }
        return navigator.serviceWorker.ready.then(function (reg) {
            return reg.pushManager.getSubscription();
        }).then(function (sub) {
            if (!sub) { return; }
            var endpoint = sub.endpoint;
            return sub.unsubscribe().then(function () {
                return postJSON('push/unsubscribe', { endpoint: endpoint });
            });
        }).then(function () {
            if (window.erpNotify) { erpNotify('info', 'Notifications disabled on this browser.'); }
        });
    };
})();
