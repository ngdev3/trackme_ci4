<?php

namespace Modules\Api\Controllers;

use App\Models\PushSubscriptionModel;

/**
 * Web-Push subscription endpoints. Used by both the browser (service worker
 * subscribe) via a bearer token and the mobile app. The subscription is always
 * tied to the authenticated user and their email.
 */
class PushApiController extends BaseApiController
{
    public function subscribe()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        // Native (mobile) path: a single FCM/APNs device token. Stored in the same
        // push_subscriptions table so one user↔email can map to many devices — the
        // token goes in `endpoint`, with p256dh='fcm' marking it as a native token
        // and `auth` carrying the platform (android/ios).
        $token = (string) $this->input('token', '');
        if ($token !== '') {
            $platform = (string) ($this->input('platform', '') ?: 'android');
            (new PushSubscriptionModel())->store(
                (int) $user['id'],
                (string) $user['email'],
                $token,
                'fcm',
                $platform,
                $this->request->getUserAgent() ? (string) $this->request->getUserAgent() : null
            );

            return $this->respond(['status' => 'success', 'message' => 'Device registered.']);
        }

        // Web-Push path: browser service-worker subscription.
        $endpoint = (string) $this->input('endpoint', '');
        $p256dh   = (string) $this->input('p256dh', '');
        $auth     = (string) $this->input('auth', '');
        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            return $this->failValidationErrors('Provide an FCM `token` (native) or `endpoint`, `p256dh` and `auth` (web).');
        }

        (new PushSubscriptionModel())->store(
            (int) $user['id'],
            (string) $user['email'],
            $endpoint,
            $p256dh,
            $auth,
            $this->request->getUserAgent() ? (string) $this->request->getUserAgent() : null
        );

        return $this->respond(['status' => 'success', 'message' => 'Subscription stored.']);
    }

    public function unsubscribe()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $endpoint = (string) $this->input('endpoint', '');
        if ($endpoint === '') {
            return $this->failValidationErrors('endpoint is required.');
        }
        (new PushSubscriptionModel())->deleteByEndpoint($endpoint);
        return $this->respond(['status' => 'success', 'message' => 'Subscription removed.']);
    }
}
