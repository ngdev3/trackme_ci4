<?php

namespace App\Modules\Task\Models;

use Config\Database;

/**
 * TaskModel — CI4 port of task/models/Task_mod.
 *
 * Task CRUD plus the Jira-style threaded comment / attachment / notification
 * reads and writes shared by the web admin views and mirrored by the mobile
 * webservice APIs.
 *
 * Tables: aa_task, aa_task_assignee, aa_task_comment,
 *         aa_task_comment_attachment, aa_task_notification
 */
class TaskModel
{
    protected function db()
    {
        return Database::connect();
    }

    /* =====================================================================
     * TASK CRUD
     * ===================================================================== */

    public function add(array $data): int
    {
        $this->db()->table('aa_task')->insert($data);
        return (int) $this->db()->insertID();
    }

    public function update(int $task_id, array $data): bool
    {
        $this->db()->table('aa_task')->where('task_id', $task_id)->update($data);
        return $this->db()->affectedRows() >= 0;
    }

    public function view(int $task_id)
    {
        return $this->db()->table('aa_task as t')
            ->select('t.*, cu.first_name as creator_first, cu.last_name as creator_last,
                      au.first_name as assignee_first, au.last_name as assignee_last')
            ->join('users as cu', 'cu.id = t.created_by', 'left')
            ->join('users as au', 'au.id = t.assigned_to', 'left')
            ->where('t.task_id', $task_id)
            ->get()->getRow();
    }

    public function soft_delete(int $task_id): bool
    {
        $this->_delete_attachment_files_by_task($task_id);
        $this->db()->table('aa_task')->where('task_id', $task_id)
            ->update(['is_deleted' => 1, 'updated_date' => date('Y-m-d H:i:s')]);
        return true;
    }

    /**
     * Flat task list for the mobile API. Optionally scoped to a user (tasks
     * they created or are assigned to) and/or a status.
     */
    public function api_list(int $user_id = 0, string $status = '', int $limit = 100): array
    {
        $b = $this->db()->table('aa_task as t')
            ->select('t.task_id, t.title, t.description, t.status, t.priority, t.due_date,
                      t.created_by, t.assigned_to, t.added_date,
                      au.first_name as assignee_first, au.last_name as assignee_last,
                      (SELECT COUNT(*) FROM aa_task_comment c WHERE c.task_id = t.task_id AND c.is_deleted = 0) as comment_count')
            ->join('users as au', 'au.id = t.assigned_to', 'left')
            ->where('t.is_deleted', 0);
        if ($status !== '') {
            $b->where('t.status', $status);
        }
        if ($user_id) {
            $b->groupStart()
                ->where('t.created_by', $user_id)
                ->orWhere('t.assigned_to', $user_id)
                ->orWhere("t.task_id IN (SELECT task_id FROM aa_task_assignee WHERE user_id = " . (int) $user_id . ")", null, false)
                ->groupEnd();
        }
        $rows = $b->orderBy('t.task_id', 'desc')->limit($limit)->get()->getResult();
        foreach ($rows as $r) {
            $r->assignee_name = trim($r->assignee_first . ' ' . $r->assignee_last);
            unset($r->assignee_first, $r->assignee_last);
        }
        return $rows;
    }

    /** Active users for the assignee dropdown. */
    public function get_users(): array
    {
        return $this->db()->table('users')
            ->select('id, first_name, last_name, user_type')
            ->where('status', 'Active')
            ->orderBy('first_name', 'asc')
            ->get()->getResult();
    }

    /* =====================================================================
     * DATATABLES (web listing)
     * ===================================================================== */

    public function count_task_data(): int
    {
        $b = $this->db()->table('aa_task as t');
        $this->_listing_filters($b);
        return $b->countAllResults();
    }

    public function get_task_data(): array
    {
        $req  = service('request');
        $post = $req->getPost();
        $columns = [1 => 't.title', 2 => 't.status', 3 => 't.priority', 4 => 't.added_date'];

        $b = $this->db()->table('aa_task as t')
            ->select('t.*, au.first_name as assignee_first, au.last_name as assignee_last,
                      (SELECT COUNT(*) FROM aa_task_comment c WHERE c.task_id = t.task_id AND c.is_deleted = 0) as comment_count')
            ->join('users as au', 'au.id = t.assigned_to', 'left');
        $this->_listing_filters($b);

        $ordCol = $post['order'][0]['column'] ?? null;
        if ($ordCol !== null && isset($columns[$ordCol])) {
            $b->orderBy($columns[$ordCol], $post['order'][0]['dir'] ?? 'asc');
        } else {
            $b->orderBy('t.task_id', 'desc');
        }
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    /** Shared WHERE clause for the listing count + data queries. */
    private function _listing_filters($b): void
    {
        $req  = service('request');
        $post = $req->getPost();
        $b->where('t.is_deleted', 0);

        $status = $req->getGet('status');
        if ($status !== null && $status !== '') {
            $b->where('t.status', $status);
        }
        if (! empty($post['search']['value'])) {
            $s = $this->db()->escapeLikeString($post['search']['value']);
            $b->where("(t.title LIKE '%$s%' OR t.description LIKE '%$s%' OR t.status LIKE '%$s%' OR t.priority LIKE '%$s%')");
        }
    }

    /* =====================================================================
     * COMMENTS
     * ===================================================================== */

    /** Insert a comment or reply (parent_id = 0 = top-level). Returns new id. */
    public function add_comment(int $task_id, int $user_id, string $text, int $parent_id = 0): int
    {
        $this->db()->table('aa_task_comment')->insert([
            'task_id'      => $task_id,
            'parent_id'    => $parent_id,
            'user_id'      => $user_id,
            'comment_text' => $text,
            'is_deleted'   => 0,
            'added_date'   => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db()->insertID();
    }

    public function get_comment(int $comment_id)
    {
        return $this->db()->table('aa_task_comment')->where('comment_id', $comment_id)->get()->getRow();
    }

    /**
     * All (non-deleted) comments for a task as a nested thread: top-level
     * comments in chronological order, each with a `replies` array.
     */
    public function get_comments_tree(int $task_id): array
    {
        $rows = $this->db()->table('aa_task_comment as c')
            ->select('c.comment_id, c.task_id, c.parent_id, c.user_id, c.comment_text,
                      c.added_date, u.first_name, u.last_name, u.user_type')
            ->join('users as u', 'u.id = c.user_id', 'left')
            ->where('c.task_id', $task_id)
            ->where('c.is_deleted', 0)
            ->orderBy('c.added_date', 'asc')
            ->get()->getResult();

        $attachments = $this->_attachments_by_comment($task_id);

        $map = [];
        foreach ($rows as $r) {
            $r->role        = $this->role_label($r->user_type);
            $r->name        = trim($r->first_name . ' ' . $r->last_name);
            $r->attachments = $attachments[$r->comment_id] ?? [];
            $r->replies     = [];
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

    /** Map of comment_id => attachments[] for a whole task (one query). */
    private function _attachments_by_comment(int $task_id): array
    {
        $out  = [];
        $rows = $this->db()->table('aa_task_comment_attachment')->where('task_id', $task_id)->get()->getResult();
        foreach ($rows as $a) {
            $a->file_url = base_url($a->file_path);
            $out[$a->comment_id][] = $a;
        }
        return $out;
    }

    public function add_attachment(array $data): int
    {
        $this->db()->table('aa_task_comment_attachment')->insert($data);
        return (int) $this->db()->insertID();
    }

    /**
     * Soft-delete a comment if the actor is allowed: the comment author, an
     * admin (user_type 1/2), or a super admin.
     */
    public function delete_comment(int $comment_id, int $actor_id, string $actor_type): array
    {
        $comment = $this->get_comment($comment_id);
        if (! $comment || $comment->is_deleted) {
            return ['status' => 'error', 'error_msg' => 'Comment not found.'];
        }
        $is_admin = in_array($actor_type, ['1', '2'], true);
        if (! $is_admin && (int) $comment->user_id !== $actor_id) {
            return ['status' => 'error', 'error_msg' => 'You are not allowed to delete this comment.'];
        }

        $delete_ids   = $this->_comment_descendant_ids($comment_id);
        $delete_ids[] = $comment_id;

        $this->_delete_attachment_files_by_comments($delete_ids);

        $this->db()->table('aa_task_comment')->whereIn('comment_id', $delete_ids)
            ->update(['is_deleted' => 1, 'updated_date' => date('Y-m-d H:i:s')]);
        return ['status' => 'success'];
    }

    /** All nested child comment ids below a parent comment. */
    private function _comment_descendant_ids(int $comment_id): array
    {
        $ids     = [];
        $parents = [$comment_id];

        while (! empty($parents)) {
            $rows = $this->db()->table('aa_task_comment')
                ->select('comment_id')
                ->whereIn('parent_id', $parents)
                ->where('is_deleted', 0)
                ->get()->getResult();

            $parents = [];
            foreach ($rows as $row) {
                $id        = (int) $row->comment_id;
                $ids[]     = $id;
                $parents[] = $id;
            }
        }
        return $ids;
    }

    /** Delete attachment files and DB rows for every attachment on a task. */
    private function _delete_attachment_files_by_task(int $task_id): void
    {
        if (! $task_id) {
            return;
        }
        $rows = $this->db()->table('aa_task_comment_attachment')->where('task_id', $task_id)->get()->getResult();
        $this->_unlink_attachment_rows($rows);
        $this->_delete_all_files_in_task_attachment_dir($task_id);

        $this->db()->table('aa_task_comment_attachment')->where('task_id', $task_id)->delete();
        $this->_remove_empty_task_attachment_dir($task_id);
    }

    /** Delete attachment files and DB rows for one or more comments. */
    private function _delete_attachment_files_by_comments($comment_ids): void
    {
        $comment_ids = array_filter(array_map('intval', (array) $comment_ids));
        if (empty($comment_ids)) {
            return;
        }

        $rows = $this->db()->table('aa_task_comment_attachment')->whereIn('comment_id', $comment_ids)->get()->getResult();
        $comment_rows = $this->db()->table('aa_task_comment')->select('task_id')->whereIn('comment_id', $comment_ids)->get()->getResult();

        $task_ids = [];
        foreach ($rows as $row) {
            $task_ids[] = (int) $row->task_id;
        }
        foreach ($comment_rows as $row) {
            $task_ids[] = (int) $row->task_id;
        }

        $this->_unlink_attachment_rows($rows);
        $this->_delete_comment_files_by_pattern($task_ids, $comment_ids);

        $this->db()->table('aa_task_comment_attachment')->whereIn('comment_id', $comment_ids)->delete();

        foreach (array_unique($task_ids) as $task_id) {
            $this->_remove_empty_task_attachment_dir($task_id);
        }
    }

    /** Unlink physical files, but only when the resolved path stays inside FCPATH. */
    private function _unlink_attachment_rows($rows): void
    {
        $base = realpath(FCPATH);
        if ($base === false) {
            return;
        }
        foreach ((array) $rows as $row) {
            if (empty($row->file_path)) {
                continue;
            }
            $path = FCPATH . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $row->file_path), DIRECTORY_SEPARATOR);
            $real = realpath($path);
            if ($real === false || stripos($real, $base . DIRECTORY_SEPARATOR) !== 0 || ! is_file($real)) {
                continue;
            }
            @unlink($real);
        }
    }

    /** Delete orphaned files for specific comments by their filename prefix. */
    private function _delete_comment_files_by_pattern($task_ids, $comment_ids): void
    {
        $task_ids    = array_unique(array_filter(array_map('intval', (array) $task_ids)));
        $comment_ids = array_unique(array_filter(array_map('intval', (array) $comment_ids)));
        if (empty($task_ids) || empty($comment_ids)) {
            return;
        }
        foreach ($task_ids as $task_id) {
            $dir = $this->_safe_task_attachment_dir($task_id);
            if ($dir === false) {
                continue;
            }
            foreach ($comment_ids as $comment_id) {
                $files = glob($dir . DIRECTORY_SEPARATOR . 'cmt_' . $comment_id . '_*');
                if ($files === false) {
                    continue;
                }
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }
    }

    /** Delete all files under a task attachment directory. */
    private function _delete_all_files_in_task_attachment_dir(int $task_id): void
    {
        $dir = $this->_safe_task_attachment_dir($task_id);
        if ($dir === false) {
            return;
        }
        $files = glob($dir . DIRECTORY_SEPARATOR . '*');
        if ($files === false) {
            return;
        }
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /** Remove the task attachment directory if it is now empty. */
    private function _remove_empty_task_attachment_dir(int $task_id): void
    {
        $real = $this->_safe_task_attachment_dir($task_id);
        if ($real === false) {
            return;
        }
        $items = array_diff(scandir($real), ['.', '..']);
        if (empty($items)) {
            @rmdir($real);
        }
    }

    /** Resolve a task attachment dir only when inside uploads/task_attachments. */
    private function _safe_task_attachment_dir(int $task_id)
    {
        $dir  = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'task_attachments' . DIRECTORY_SEPARATOR . $task_id;
        $real = realpath($dir);
        $base = realpath(FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'task_attachments');
        if ($real === false || $base === false || stripos($real, $base . DIRECTORY_SEPARATOR) !== 0 || ! is_dir($real)) {
            return false;
        }
        return $real;
    }

    /** Human-readable role from the users.user_type enum. */
    public function role_label($user_type): string
    {
        $map = ['1' => 'Super Admin', '2' => 'Admin', '3' => 'Accounts', '4' => 'Accountant', '5' => 'Support'];
        return $map[(string) $user_type] ?? 'User';
    }

    /* =====================================================================
     * NOTIFICATIONS
     * ===================================================================== */

    public function unread_notification_count(int $user_id): int
    {
        return $this->db()->table('aa_task_notification')
            ->where('user_id', $user_id)->where('is_read', 0)->countAllResults();
    }

    public function list_notifications(int $user_id, int $limit = 30): array
    {
        return $this->db()->table('aa_task_notification as n')
            ->select('n.*, t.title as task_title, a.first_name as actor_first, a.last_name as actor_last')
            ->join('aa_task as t', 't.task_id = n.task_id', 'left')
            ->join('users as a', 'a.id = n.actor_id', 'left')
            ->where('n.user_id', $user_id)
            ->orderBy('n.id', 'desc')
            ->limit($limit)
            ->get()->getResult();
    }

    public function mark_notifications_read(int $user_id, int $notification_id = 0): bool
    {
        $b = $this->db()->table('aa_task_notification')->where('user_id', $user_id);
        if ($notification_id) {
            $b->where('id', $notification_id);
        }
        $b->update(['is_read' => 1]);
        return true;
    }
}
