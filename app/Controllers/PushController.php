<?php

namespace App\Controllers;

use App\Models\PushSubscriptionModel;

/**
 * Session-authenticated Web Push subscription endpoints for the browser. The
 * mobile app uses the token-based equivalents under /api/v1/push/*.
 */
class PushController extends BaseController
{
    public function subscribe()
    {
        if (! auth()->check()) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Not authenticated.']);
        }
        $user = current_user();
        $body     = $this->request->getJSON(true) ?: [];
        $endpoint = (string) ($body['endpoint'] ?? '');
        $p256dh   = (string) ($body['p256dh'] ?? '');
        $auth     = (string) ($body['auth'] ?? '');
        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Missing subscription fields.']);
        }

        (new PushSubscriptionModel())->store(
            (int) $user['id'],
            (string) $user['email'],
            $endpoint,
            $p256dh,
            $auth,
            $this->request->getUserAgent() ? (string) $this->request->getUserAgent() : null
        );

        return $this->response->setJSON(['status' => 'success', 'message' => 'Subscribed.']);
    }

    public function unsubscribe()
    {
        if (! auth()->check()) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Not authenticated.']);
        }
        $body     = $this->request->getJSON(true) ?: [];
        $endpoint = (string) ($body['endpoint'] ?? '');
        if ($endpoint !== '') {
            (new PushSubscriptionModel())->deleteByEndpoint($endpoint);
        }
        return $this->response->setJSON(['status' => 'success', 'message' => 'Unsubscribed.']);
    }
}
