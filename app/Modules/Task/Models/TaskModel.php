<?php

namespace App\Modules\Task\Models;

use Config\Database;

/**
 * TaskModel — CI4 port of task/models/Task_mod. Task CRUD (aa_task) + Jira-style
 * threaded comments (aa_task_comment) with attachments (aa_task_comment_attachment)
 * and per-user notifications (aa_task_notification). Soft delete (is_deleted).
 */
class TaskModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function add(array $data): int
    {
        $this->db()->table('aa_task')->insert($data);
        return (int) $this->db()->insertID();
    }

    public function update(int $taskId, array $data): bool
    {
        $this->db()->table('aa_task')->where('task_id', $taskId)->update($data);
        return true;
    }

    public function view(int $taskId)
    {
        return $this->db()->table('aa_task t')
            ->select('t.*, cu.first_name as creator_first, cu.last_name as creator_last, au.first_name as assignee_first, au.last_name as assignee_last')
            ->join('users cu', 'cu.id = t.created_by', 'left')
            ->join('users au', 'au.id = t.assigned_to', 'left')
            ->where('t.task_id', $taskId)->get()->getRow();
    }

    public function softDelete(int $taskId): bool
    {
        $this->db()->table('aa_task')->where('task_id', $taskId)->update(['is_deleted' => 1, 'updated_date' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function getUsers(): array
    {
        return $this->db()->table('users')->select('id, first_name, last_name, user_type')
            ->where('status', 'Active')->orderBy('first_name', 'asc')->get()->getResult();
    }

    private function applyFilters($b): void
    {
        $req = service('request');
        $b->where('t.is_deleted', 0);
        if ($req->getGet('status') !== null && $req->getGet('status') !== '') { $b->where('t.status', $req->getGet('status')); }
        $post = $req->getPost();
        if (! empty($post['search']['value'])) {
            $s = $post['search']['value'];
            $b->groupStart()->like('t.title', $s)->orLike('t.description', $s)->orLike('t.status', $s)->orLike('t.priority', $s)->groupEnd();
        }
    }

    public function countTaskData(): int
    {
        $b = $this->db()->table('aa_task t');
        $this->applyFilters($b);
        return $b->countAllResults();
    }

    public function getTaskData(): array
    {
        $post = service('request')->getPost();
        $cols = [1 => 't.title', 2 => 't.status', 3 => 't.priority', 4 => 't.added_date'];
        $b = $this->db()->table('aa_task t')
            ->select('t.*, au.first_name as assignee_first, au.last_name as assignee_last,
                (SELECT COUNT(*) FROM aa_task_comment c WHERE c.task_id = t.task_id AND c.is_deleted = 0) as comment_count', false)
            ->join('users au', 'au.id = t.assigned_to', 'left');
        $this->applyFilters($b);
        if (! empty($post['order'][0]['column']) && isset($cols[$post['order'][0]['column']])) {
            $b->orderBy($cols[$post['order'][0]['column']], $post['order'][0]['dir']);
        } else {
            $b->orderBy('t.task_id', 'desc');
        }
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    public function addComment(int $taskId, int $userId, string $text, int $parentId = 0): int
    {
        $this->db()->table('aa_task_comment')->insert([
            'task_id' => $taskId, 'parent_id' => $parentId, 'user_id' => $userId,
            'comment_text' => $text, 'is_deleted' => 0, 'added_date' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db()->insertID();
    }

    public function getComment(int $id)
    {
        return $this->db()->table('aa_task_comment')->where('comment_id', $id)->get()->getRow();
    }

    public function getCommentsTree(int $taskId): array
    {
        $rows = $this->db()->table('aa_task_comment c')
            ->select('c.comment_id, c.task_id, c.parent_id, c.user_id, c.comment_text, c.added_date, u.first_name, u.last_name, u.user_type')
            ->join('users u', 'u.id = c.user_id', 'left')
            ->where('c.task_id', $taskId)->where('c.is_deleted', 0)->orderBy('c.added_date', 'asc')->get()->getResult();

        $attachments = $this->attachmentsByComment($taskId);
        $map = [];
        foreach ($rows as $r) {
            $r->role = $this->roleLabel($r->user_type);
            $r->name = trim($r->first_name . ' ' . $r->last_name);
            $r->attachments = $attachments[$r->comment_id] ?? [];
            $r->replies = [];
            $map[$r->comment_id] = $r;
        }
        $tree = [];
        foreach ($map as $r) {
            if ($r->parent_id && isset($map[$r->parent_id])) {
                $map[$r->parent_id]->replies[] = $r;
            } else {
                $tree[] = $r;
            }
        }
        return $tree;
    }

    private function attachmentsByComment(int $taskId): array
    {
        $out = [];
        foreach ($this->db()->table('aa_task_comment_attachment')->where('task_id', $taskId)->get()->getResult() as $a) {
            $a->file_url = base_url($a->file_path);
            $out[$a->comment_id][] = $a;
        }
        return $out;
    }

    public function addAttachment(array $data): int
    {
        $this->db()->table('aa_task_comment_attachment')->insert($data);
        return (int) $this->db()->insertID();
    }

    public function deleteComment(int $commentId, int $actorId, string $actorType): array
    {
        $c = $this->getComment($commentId);
        if (! $c || $c->is_deleted) { return ['status' => 'error', 'error_msg' => 'Comment not found.']; }
        $isAdmin = in_array($actorType, ['1', '2'], true);
        if (! $isAdmin && (int) $c->user_id !== $actorId) {
            return ['status' => 'error', 'error_msg' => 'You are not allowed to delete this comment.'];
        }
        $ids = $this->descendantIds($commentId);
        $ids[] = $commentId;
        $this->db()->table('aa_task_comment')->whereIn('comment_id', $ids)->update(['is_deleted' => 1, 'updated_date' => date('Y-m-d H:i:s')]);
        return ['status' => 'success'];
    }

    private function descendantIds(int $commentId): array
    {
        $out = [];
        $stack = [$commentId];
        while ($stack) {
            $pid = array_pop($stack);
            foreach ($this->db()->table('aa_task_comment')->select('comment_id')->where('parent_id', $pid)->get()->getResult() as $r) {
                $out[] = (int) $r->comment_id;
                $stack[] = (int) $r->comment_id;
            }
        }
        return $out;
    }

    public function roleLabel($userType): string
    {
        $map = ['1' => 'Super Admin', '2' => 'Admin', '3' => 'Accounts', '4' => 'Accountant', '5' => 'Support'];
        return $map[(string) $userType] ?? 'User';
    }

    public function unreadNotificationCount(int $userId): int
    {
        return $this->db()->table('aa_task_notification')->where('user_id', $userId)->where('is_read', 0)->countAllResults();
    }

    public function listNotifications(int $userId, int $limit = 30): array
    {
        return $this->db()->table('aa_task_notification n')
            ->select('n.*, t.title as task_title, a.first_name as actor_first, a.last_name as actor_last')
            ->join('aa_task t', 't.task_id = n.task_id', 'left')
            ->join('users a', 'a.id = n.actor_id', 'left')
            ->where('n.user_id', $userId)->orderBy('n.id', 'desc')->limit($limit)->get()->getResult();
    }

    public function markNotificationsRead(int $userId, int $notificationId = 0): bool
    {
        $b = $this->db()->table('aa_task_notification')->where('user_id', $userId);
        if ($notificationId) { $b->where('id', $notificationId); }
        $b->update(['is_read' => 1]);
        return true;
    }
}
