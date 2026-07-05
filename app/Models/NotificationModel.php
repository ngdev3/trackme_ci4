<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Services;

class NotificationModel extends Model
{
    protected $table          = 'notifications';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'type', 'title', 'message', 'module', 'user_id', 'role_id', 'priority',
        'action_url', 'is_read', 'read_at', 'created_by',
    ];

    public const TYPES = ['success', 'error', 'warning', 'info', 'system_update', 'user_activity'];
    public const PRIORITIES = ['low', 'normal', 'high', 'critical'];

    public function visibleForCurrentUser()
    {
        $session = Services::session();
        $userId = (int) $session->get('user_id');
        $roleIds = array_map('intval', (array) ($session->get('role_ids') ?? []));
        $moduleCodes = Services::acl()->isSuperAdmin()
            ? null
            : Services::acl()->viewableModuleCodes();

        $builder = $this->select('notifications.*, users.name AS user_name, roles.name AS role_name')
            ->join('users', 'users.id = notifications.user_id', 'left')
            ->join('roles', 'roles.id = notifications.role_id', 'left')
            ->groupStart()
                ->where('notifications.user_id', $userId)
                ->orGroupStart()
                    ->where('notifications.user_id', null)
                    ->where('notifications.role_id', null)
                ->groupEnd();

        if ($roleIds !== []) {
            $builder->orWhereIn('notifications.role_id', $roleIds);
        }

        $builder->groupEnd();

        if ($moduleCodes !== null) {
            $builder->groupStart()
                ->where('notifications.module', null);
            if ($moduleCodes !== []) {
                $builder->orWhereIn('notifications.module', $moduleCodes);
            }
            $builder->groupEnd();
        }

        return $builder;
    }

    public function unreadCount(): int
    {
        return (int) $this->visibleForCurrentUser()
            ->where('notifications.is_read', 0)
            ->countAllResults();
    }

    public function latestVisible(int $limit = 8): array
    {
        return $this->visibleForCurrentUser()
            ->orderBy('notifications.is_read', 'ASC')
            ->orderBy('notifications.id', 'DESC')
            ->findAll($limit);
    }

    public function markRead(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return 0;
        }

        $visibleIds = array_column(
            $this->visibleForCurrentUser()->whereIn('notifications.id', $ids)->findAll(),
            'id'
        );

        if ($visibleIds === []) {
            return 0;
        }

        $this->whereIn('id', $visibleIds)->set([
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ])->update();

        return count($visibleIds);
    }

    public function markAllRead(): int
    {
        $ids = array_column(
            $this->visibleForCurrentUser()->where('notifications.is_read', 0)->findAll(500),
            'id'
        );

        return $this->markRead($ids);
    }

    public function deleteVisible(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return 0;
        }

        $visibleIds = array_column(
            $this->visibleForCurrentUser()->whereIn('notifications.id', $ids)->findAll(),
            'id'
        );

        if ($visibleIds === []) {
            return 0;
        }

        $this->delete($visibleIds);
        return count($visibleIds);
    }
}
