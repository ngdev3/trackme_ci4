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
        if ((int) ($user['web_push_enabled'] ?? 1) !== 1) {
            return $this->failForbidden('Web push is disabled for this account.');
        }

        $endpoint = (string) $this->input('endpoint', '');
        $p256dh   = (string) $this->input('p256dh', '');
        $auth     = (string) $this->input('auth', '');
        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            return $this->failValidationErrors('endpoint, p256dh and auth are required.');
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
