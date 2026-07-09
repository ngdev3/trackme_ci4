<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\PushSubscriptionModel;
use App\Models\UserModel;
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

        // Best-effort browser push for user-targeted notifications. Never let a
        // push failure affect the calling action.
        if ($payload['user_id']) {
            try {
                $this->webPush((int) $payload['user_id'], $payload['title'], $payload['message'], $payload['action_url']);
            } catch (\Throwable $e) {
                log_message('error', 'Web push failed: ' . $e->getMessage());
            }
        }

        return $id ? (int) $id : null;
    }

    /**
     * Deliver a Web Push message to every browser a user has subscribed, subject
     * to automatically managed VAPID keys and the user's own web_push flag.
     * Prunes subscriptions the push service reports as gone (404/410).
     */
    protected function webPush(int $userId, string $title, string $message, ?string $url): void
    {
        $user = (new UserModel())->find($userId);
        if (! $user || (int) ($user['web_push_enabled'] ?? 1) !== 1) {
            return;
        }

        $subs = new PushSubscriptionModel();
        $rows = $subs->forUser($userId);
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
