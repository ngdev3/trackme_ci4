<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Config\Database;

/**
 * Web_push — CI4 port of admin/Web_push. Browser (FCM) web-push registration for
 * the admin panel: config, save/delete token (aa_whitelist_device keyed per
 * user+browser, users.fcm_token fallback) and the dynamic service worker JS.
 * FCM_WEB_* constants are read if defined; otherwise push reports "disabled"
 * (graceful) until they're added to the CI4 config.
 */
class Web_push extends BaseController
{
    protected $helpers = ['url', 'app'];

    protected function db()
    {
        return Database::connect();
    }

    public function config()
    {
        return $this->response->setJSON([
            'status'   => 'success',
            'config'   => $this->firebaseConfig(),
            'vapidKey' => defined('FCM_WEB_VAPID_KEY') ? FCM_WEB_VAPID_KEY : '',
            'enabled'  => $this->isConfigured(),
        ]);
    }

    public function save_token()
    {
        $uid   = (int) (currentuserinfo()->id ?? 0);
        $token = trim((string) $this->request->getPost('token'));

        if (! $uid || strlen($token) < 20) {
            return $this->response->setJSON(['status' => 'error', 'error_msg' => 'Invalid push token.', 'csrfHash' => csrf_hash()]);
        }

        if ($this->db()->tableExists('aa_whitelist_device')) {
            $this->saveWebDeviceToken($uid, $token);
        } else {
            $this->db()->table('users')->where('id', $uid)->update(['fcm_token' => $token]);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Web push enabled.', 'csrfHash' => csrf_hash()]);
    }

    public function delete_token()
    {
        $uid   = (int) (currentuserinfo()->id ?? 0);
        $token = trim((string) $this->request->getPost('token'));

        if ($uid) {
            if ($this->db()->tableExists('aa_whitelist_device') && $token !== '') {
                $this->db()->table('aa_whitelist_device')
                    ->where('user_id', $uid)->where('device_type', 'web')->where('push_token', $token)
                    ->update(['status' => 'Delete', 'updated_at' => date('Y-m-d')]);
            } else {
                $this->db()->table('users')->where('id', $uid)->update(['fcm_token' => null]);
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Web push disabled.', 'csrfHash' => csrf_hash()]);
    }

    public function service_worker()
    {
        $scope = parse_url(base_url(), PHP_URL_PATH) ?: '/';

        $config      = json_encode($this->firebaseConfig());
        $enabled     = $this->isConfigured() ? 'true' : 'false';
        $icon        = json_encode(base_url('assets/images/logo.png'));
        $fallbackUrl = json_encode(base_url('task/task'));
        $appSdk      = json_encode(base_url('assets/firebase/firebase-app-compat.js'));
        $msgSdk      = json_encode(base_url('assets/firebase/firebase-messaging-compat.js'));

        $js = <<<JS
if (typeof window === 'undefined') { self.window = self; }

importScripts({$appSdk});
importScripts({$msgSdk});

var trackmeFirebaseConfig = {$config};
var trackmePushEnabled = {$enabled};

if (trackmePushEnabled && self.firebase && firebase.apps.length === 0) {
    firebase.initializeApp(trackmeFirebaseConfig);
}

if (trackmePushEnabled && self.firebase && firebase.messaging) {
    var messaging = firebase.messaging();
    messaging.onBackgroundMessage(function (payload) {
        var data = payload.data || {};
        var notification = payload.notification || {};
        var title = notification.title || data.title || 'TrackMe';
        var options = {
            body: notification.body || data.body || '',
            icon: {$icon},
            badge: {$icon},
            data: { url: data.url || {$fallbackUrl} }
        };
        self.registration.showNotification(title, options);
    });
}

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var targetUrl = event.notification && event.notification.data && event.notification.data.url
        ? event.notification.data.url
        : {$fallbackUrl};
    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
        for (var i = 0; i < clientList.length; i++) {
            var client = clientList[i];
            if (client.url === targetUrl && 'focus' in client) { return client.focus(); }
        }
        if (clients.openWindow) { return clients.openWindow(targetUrl); }
    }));
});
JS;

        return $this->response
            ->setHeader('Content-Type', 'application/javascript; charset=UTF-8')
            ->setHeader('Service-Worker-Allowed', $scope)
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setBody($js);
    }

    private function firebaseConfig(): array
    {
        return [
            'apiKey'            => defined('FCM_WEB_API_KEY') ? FCM_WEB_API_KEY : '',
            'authDomain'        => defined('FCM_WEB_AUTH_DOMAIN') ? FCM_WEB_AUTH_DOMAIN : '',
            'projectId'         => defined('FCM_WEB_PROJECT_ID') ? FCM_WEB_PROJECT_ID : '',
            'storageBucket'     => defined('FCM_WEB_STORAGE_BUCKET') ? FCM_WEB_STORAGE_BUCKET : '',
            'messagingSenderId' => defined('FCM_WEB_MESSAGING_SENDER_ID') ? FCM_WEB_MESSAGING_SENDER_ID : '',
            'appId'             => defined('FCM_WEB_APP_ID') ? FCM_WEB_APP_ID : '',
        ];
    }

    private function isConfigured(): bool
    {
        $c = $this->firebaseConfig();
        return ! empty($c['apiKey']) && ! empty($c['projectId']) && ! empty($c['messagingSenderId'])
            && ! empty($c['appId']) && defined('FCM_WEB_VAPID_KEY') && FCM_WEB_VAPID_KEY !== '';
    }

    /** Upsert the web device token keyed on user+browser; retire the user's other web tokens. */
    private function saveWebDeviceToken(int $userId, string $token): void
    {
        $ua        = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceId  = 'web_' . sha1($userId . '|' . $ua);
        $now       = date('Y-m-d H:i:s');
        $today     = date('Y-m-d');
        $db        = $this->db();

        $data = [
            'device_name' => 'Web Browser', 'device_id' => $deviceId, 'device_type' => 'web', 'platform' => 'web',
            'user_id' => $userId, 'manufacturer' => '', 'model' => mb_substr($ua, 0, 120),
            'os_version' => '', 'app_version' => '', 'web_view_version' => '', 'uuid' => $deviceId,
            'is_virtual' => 0, 'push_token' => $token, 'push_provider' => 'fcm', 'status' => 'Active',
            'updated_at' => $today, 'last_seen_at' => $now,
        ];

        $exists = $db->table('aa_whitelist_device')->where('device_id', $deviceId)->get()->getRow();
        if ($exists) {
            $db->table('aa_whitelist_device')->where('device_id', $deviceId)->update([
                'user_id' => $userId, 'push_token' => $token, 'status' => 'Active',
                'model' => $data['model'], 'updated_at' => $today, 'last_seen_at' => $now,
            ]);
        } else {
            $db->table('aa_whitelist_device')->insert($data);
        }

        // Retire this user's other web tokens (closed browsers / rotated tokens).
        $db->table('aa_whitelist_device')
            ->where('user_id', $userId)->where('device_type', 'web')->where('push_token !=', $token)->where('status', 'Active')
            ->update(['status' => 'Delete', 'updated_at' => $today]);
    }
}
