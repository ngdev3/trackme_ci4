<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\PushSubscriptionModel;
use Config\Services;

class Notifier
{
    protected NotificationModel $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationModel();
    }

    public function send(array $data): ?int
    {
        $type = $data['type'] ?? 'info';
        $priority = $data['priority'] ?? 'normal';

        if (! in_array($type, NotificationModel::TYPES, true)) {
            $type = 'info';
        }
        if (! in_array($priority, NotificationModel::PRIORITIES, true)) {
            $priority = 'normal';
        }

        $payload = [
            'type'       => $type,
            'title'      => trim((string) ($data['title'] ?? 'Notification')),
            'message'    => trim((string) ($data['message'] ?? '')),
            'module'     => $data['module'] ?? null,
            'user_id'    => isset($data['user_id']) ? (int) $data['user_id'] : null,
            'role_id'    => isset($data['role_id']) ? (int) $data['role_id'] : null,
            'priority'   => $priority,
            'action_url' => $data['action_url'] ?? null,
            'is_read'    => 0,
            'created_by' => $data['created_by'] ?? Services::session()->get('user_id'),
        ];

        if ($payload['title'] === '') {
            $payload['title'] = 'Notification';
        }

        // A non-critical audit notification must never break the calling
        // action (login, user-create, etc.). If the notifications table is
        // missing or the insert fails for any reason, log and carry on.
        try {
            $id = $this->notifications->insert($payload);
        } catch (\Throwable $e) {
            log_message('error', 'Notification insert failed: ' . $e->getMessage());
            return null;
        }

        // Best-effort push for user-targeted notifications (browser Web-Push +
        // native FCM). Never let a push failure affect the calling action.
        if ($payload['user_id']) {
            try {
                $this->push((int) $payload['user_id'], $payload['title'], $payload['message'], $payload['action_url']);
            } catch (\Throwable $e) {
                log_message('error', 'Push delivery failed: ' . $e->getMessage());
            }
        }

        return $id ? (int) $id : null;
    }

    /**
     * Deliver a push to every device a user has registered. Two transports share
     * the push_subscriptions table:
     *   - native app installs  → FCM device token (p256dh = 'fcm'), sent via FCM
     *   - browsers             → Web-Push subscription, sent via VAPID Web-Push
     * Dead subscriptions the services report as gone are pruned.
     */
    protected function push(int $userId, string $title, string $message, ?string $url): void
    {
        $subs = new PushSubscriptionModel();
        $rows = $subs->forUser($userId);
        if ($rows === []) {
            return;
        }

        // Partition by transport.
        $native = [];
        $web    = [];
        foreach ($rows as $row) {
            if (($row['p256dh'] ?? '') === 'fcm') {
                $native[] = $row;
            } else {
                $web[] = $row;
            }
        }

        $this->fcmPush($subs, $native, $title, $message, $url);
        $this->webPush($subs, $web, $title, $message, $url);
    }

    /**
     * Send to native device tokens via FCM (no-op unless a Firebase service
     * account is configured). Prunes tokens FCM reports as invalid/expired.
     *
     * @param list<array> $rows push_subscriptions rows with an FCM token in `endpoint`
     */
    protected function fcmPush(PushSubscriptionModel $subs, array $rows, string $title, string $message, ?string $url): void
    {
        if ($rows === []) {
            return;
        }
        $fcm = new \App\Libraries\Fcm();
        if (! $fcm->isConfigured()) {
            return;
        }

        $byToken = [];
        foreach ($rows as $row) {
            $byToken[$row['endpoint']] = (int) $row['id'];
        }

        $invalid = $fcm->sendToTokens(array_keys($byToken), $title, $message, $url ?: site_url('notifications'));
        foreach ($invalid as $token) {
            if (isset($byToken[$token])) {
                $subs->delete($byToken[$token]);
            }
        }
    }

    /**
     * Deliver a Web Push message to browser subscriptions with app-managed VAPID
     * keys. Prunes subscriptions the push service reports as gone (404/410).
     *
     * @param list<array> $rows browser Web-Push subscription rows
     */
    protected function webPush(PushSubscriptionModel $subs, array $rows, string $title, string $message, ?string $url): void
    {
        if ($rows === []) {
            return;
        }

        $vapid = \App\Libraries\WebPush::ensureVapidKeys();
        $payload = json_encode([
            'title' => $title,
            'body'  => $message,
            'url'   => $url ?: site_url('notifications'),
        ]);

        foreach ($rows as $row) {
            $res = \App\Libraries\WebPush::send(
                ['endpoint' => $row['endpoint'], 'p256dh' => $row['p256dh'], 'auth' => $row['auth']],
                (string) $payload,
                $vapid
            );
            if (in_array($res['status'], [404, 410], true)) {
                $subs->delete($row['id']); // subscription expired/unsubscribed
            }
        }
    }

    public function user(int $userId, string $title, string $message, array $options = []): ?int
    {
        return $this->send($options + [
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
        ]);
    }

    public function broadcast(string $title, string $message, array $options = []): ?int
    {
        return $this->send($options + [
            'title'   => $title,
            'message' => $message,
        ]);
    }
}
