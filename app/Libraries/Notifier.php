<?php

namespace App\Libraries;

use App\Models\NotificationModel;
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

        return $id ? (int) $id : null;
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
