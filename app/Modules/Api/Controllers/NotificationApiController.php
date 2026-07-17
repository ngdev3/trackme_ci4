<?php

namespace Modules\Api\Controllers;

use App\Models\NotificationModel;

/**
 * Notification feed for the mobile app (bearer-token auth). Returns the user's
 * notifications (own + global) newest-first with an unread count, and lets the
 * app mark them read. Company scope isn't relevant — notifications are per user.
 */
class NotificationApiController extends BaseApiController
{
    /** GET /api/v1/notifications — list + unread count. */
    public function index()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $uid = (int) $user['id'];

        $rows = (new NotificationModel())
            ->visibleForUser($uid)
            ->orderBy('notifications.is_read', 'ASC')
            ->orderBy('notifications.id', 'DESC')
            ->findAll(100);

        $unread = (new NotificationModel())
            ->visibleForUser($uid)
            ->where('notifications.is_read', 0)
            ->countAllResults();

        $out = array_map(static fn ($n) => [
            'id'         => (int) $n['id'],
            'type'       => $n['type'] ?? 'info',
            'title'      => $n['title'],
            'message'    => $n['message'] ?? null,
            'module'     => $n['module'] ?? null,
            'priority'   => $n['priority'] ?? 'normal',
            'action_url' => $n['action_url'] ?? null,
            'is_read'    => (int) $n['is_read'] === 1,
            'created_at' => $n['created_at'] ?? null,
        ], $rows);

        return $this->respond(['status' => 'ok', 'notifications' => $out, 'unread_count' => $unread]);
    }

    /** POST /api/v1/notifications/{id}/read — mark one read (scoped to the user). */
    public function markRead($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $uid = (int) $user['id'];
        $id  = (int) $id;

        $owned = (new NotificationModel())
            ->visibleForUser($uid)
            ->where('notifications.id', $id)
            ->countAllResults() > 0;

        if (! $owned) {
            return $this->failNotFound('Notification not found.');
        }

        (new NotificationModel())->update($id, ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);

        return $this->respond(['status' => 'ok', 'message' => 'Marked read.']);
    }

    /** POST /api/v1/notifications/read-all — mark every unread notification read. */
    public function markAllRead()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $uid = (int) $user['id'];

        $ids = array_column(
            (new NotificationModel())->visibleForUser($uid)->where('notifications.is_read', 0)->findAll(500),
            'id'
        );
        if ($ids !== []) {
            (new NotificationModel())
                ->whereIn('id', array_map('intval', $ids))
                ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
                ->update();
        }

        return $this->respond(['status' => 'ok', 'message' => 'All marked read.', 'count' => count($ids)]);
    }
}
