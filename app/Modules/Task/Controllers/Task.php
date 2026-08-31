<?php

namespace App\Modules\Task\Controllers;

use App\Controllers\BaseController;
use App\Modules\Task\Models\TaskModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Task — CI4 port of the top-level `task` module controller.
 *
 * Web admin for the Task module: task CRUD plus the Jira-style threaded
 * discussion (comments, replies, attachments, notifications). The mobile app
 * talks to the mirror endpoints in the webservices module.
 *
 * Auth + RBAC are enforced by the route filter chain (adminAuth+fyContext+rbac)
 * on `task/*` (Config\Filters) — the CI3 manual guards are no longer needed.
 * RBAC module key = 2nd URI segment = 'task'.
 */
class Task extends BaseController
{
    protected $helpers = ['url', 'form', 'text', 'app', 'cr_cache', 'task_notify'];

    private function model(): TaskModel
    {
        return new TaskModel();
    }

    /** Current logged-in user id from the admin session. */
    private function _uid(): int
    {
        $info = currentuserinfo();
        return $info ? (int) $info->id : 0;
    }

    private function _utype(): string
    {
        return (string) ($this->session->get('user_type') ?? '');
    }

    private function _actor_name(): string
    {
        $info = currentuserinfo();
        return $info ? trim($info->first_name . ' ' . $info->last_name) : 'Someone';
    }

    /* =====================================================================
     * LISTING / CRUD
     * ===================================================================== */

    public function index()
    {
        return _layout('\App\Modules\Task\Views\tasks\listing', [
            'title'      => 'Track | Tasks',
            'page_title' => 'Tasks',
            'breadcum'   => ['dashboard/' => 'Home', '' => 'Tasks'],
        ]);
    }

    /** DataTables server-side feed. */
    public function view_all()
    {
        $req         = $this->request;
        $requestData = $req->getPost();
        $totalData   = $this->model()->count_task_data();
        $rows        = $this->model()->get_task_data();

        $data = [];
        $j = (int) ($requestData['start'] ?? 0);
        foreach ((array) $rows as $row) {
            $j++;
            $assignee = trim($row->assignee_first . ' ' . $row->assignee_last);
            $nested   = [];
            $nested[] = $j;
            $nested[] = '<a href="' . base_url('task/task/view/' . ID_encode($row->task_id)) . '">' . esc($row->title) . '</a>';
            $nested[] = $this->_status_badge($row->status);
            $nested[] = ucfirst($row->priority);
            $nested[] = $assignee !== '' ? esc($assignee) : '<span class="text-muted">Unassigned</span>';
            $nested[] = '<i class="ti-comment-alt"></i> ' . (int) $row->comment_count;
            $nested[] = date('d-M-Y', strtotime($row->added_date));
            $nested[] = view('\App\Modules\Task\Views\tasks\_action', ['row' => $row]);
            $data[]   = $nested;
        }

        return $this->response->setJSON([
            'draw'            => (int) ($requestData['draw'] ?? 0),
            'recordsTotal'    => (int) $totalData,
            'recordsFiltered' => (int) $totalData,
            'data'            => $data,
        ]);
    }

    private function _status_badge(string $status): string
    {
        $map = [
            'open'        => 'badge-secondary',
            'in_progress' => 'badge-info',
            'done'        => 'badge-success',
            'closed'      => 'badge-dark',
        ];
        $cls = $map[$status] ?? 'badge-secondary';
        return '<span class="badge ' . $cls . '">' . ucwords(str_replace('_', ' ', $status)) . '</span>';
    }

    public function add()
    {
        if ($this->request->is('post')) {
            if ($this->validate(['title' => 'trim|required'])) {
                $uid     = $this->_uid();
                $assigned = $this->request->getPost('assigned_to') ?: null;
                $task_id = $this->model()->add([
                    'title'       => $this->request->getPost('title'),
                    'description' => $this->request->getPost('description'),
                    'status'      => $this->request->getPost('status') ?: 'open',
                    'priority'    => $this->request->getPost('priority') ?: 'medium',
                    'assigned_to' => $assigned,
                    'created_by'  => $uid,
                    'due_date'    => $this->request->getPost('due_date') ?: null,
                    'added_date'  => date('Y-m-d H:i:s'),
                ]);

                if ($task_id && $assigned) {
                    task_notify_assignment($task_id, $uid, (int) $assigned);
                }
                session()->setFlashdata('success', 'Task created successfully');
                return redirect()->to('/task/task');
            }
        }

        return _layout('\App\Modules\Task\Views\tasks\add', [
            'users'      => $this->model()->get_users(),
            'result'     => false,
            'title'      => 'Track | Add Task',
            'page_title' => 'Add Task',
            'breadcum'   => ['dashboard/' => 'Home', 'task/task' => 'Tasks', '' => 'Add Task'],
            'validation' => $this->validator,
        ]);
    }

    public function edit($id = '')
    {
        $task_id = (int) ID_decode($id);
        if (empty($task_id)) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->request->is('post')) {
            if ($this->validate(['title' => 'trim|required'])) {
                $uid          = $this->_uid();
                $prev         = $this->model()->view($task_id);
                $old_assignee = $prev ? (int) $prev->assigned_to : 0;
                $new_assignee = (int) ($this->request->getPost('assigned_to') ?: 0);

                $this->model()->update($task_id, [
                    'title'        => $this->request->getPost('title'),
                    'description'  => $this->request->getPost('description'),
                    'status'       => $this->request->getPost('status'),
                    'priority'     => $this->request->getPost('priority'),
                    'assigned_to'  => $this->request->getPost('assigned_to') ?: null,
                    'due_date'     => $this->request->getPost('due_date') ?: null,
                    'updated_date' => date('Y-m-d H:i:s'),
                ]);

                $newly_assigned = ($new_assignee && $new_assignee !== $old_assignee);
                if ($newly_assigned) {
                    task_notify_assignment($task_id, $uid, $new_assignee);
                }

                $title = $this->request->getPost('title');
                task_notify_participants(
                    $task_id,
                    $uid,
                    'updated',
                    $this->_actor_name() . ' updated task: ' . $title,
                    0,
                    $newly_assigned ? [$new_assignee] : []
                );

                session()->setFlashdata('success', 'Task updated successfully');
                return redirect()->to('/task/task');
            }
        }

        return _layout('\App\Modules\Task\Views\tasks\add', [
            'result'     => $this->model()->view($task_id),
            'users'      => $this->model()->get_users(),
            'title'      => 'Track | Edit Task',
            'page_title' => 'Update Task',
            'breadcum'   => ['dashboard/' => 'Home', 'task/task' => 'Tasks', '' => 'Update Task'],
            'validation' => $this->validator,
        ]);
    }

    /** Task detail with the Jira-style activity / comments thread. */
    public function view($id = '')
    {
        $task_id = (int) ID_decode($id);
        $task    = $this->model()->view($task_id);
        if (! $task || $task->is_deleted) {
            throw PageNotFoundException::forPageNotFound();
        }

        return _layout('\App\Modules\Task\Views\tasks\view', [
            'result'      => $task,
            'comments'    => $this->model()->get_comments_tree($task_id),
            'current_uid' => $this->_uid(),
            'title'       => 'Track | Task #' . $task_id,
            'page_title'  => $task->title,
            'breadcum'    => ['dashboard/' => 'Home', 'task/task' => 'Tasks', '' => 'View'],
        ]);
    }

    public function delete()
    {
        $id = (int) ID_decode($this->request->getPost('id'));
        if (! empty($id) && $this->model()->soft_delete($id)) {
            session()->setFlashdata('success', 'Task deleted successfully');
        }
        return $this->response->setJSON(['status' => 'success']);
    }

    /** Change a task's status (e.g. "Mark as Done") via AJAX. */
    public function set_status()
    {
        $id      = (int) ID_decode($this->request->getPost('id'));
        $status  = (string) $this->request->getPost('status');
        $allowed = ['open', 'in_progress', 'done', 'closed'];

        if (empty($id) || ! in_array($status, $allowed, true)) {
            return $this->response->setJSON(['status' => 'error', 'error_msg' => 'Invalid task or status.']);
        }

        $this->model()->update($id, ['status' => $status, 'updated_date' => date('Y-m-d H:i:s')]);

        if ($status === 'done' || $status === 'closed') {
            task_notify_participants($id, $this->_uid(), 'status',
                $this->_actor_name() . ' marked the task as ' . ucfirst($status));
        }
        return $this->response->setJSON(['status' => 'success']);
    }

    /* =====================================================================
     * COMMENTS (AJAX)
     * ===================================================================== */

    /** Add a comment or reply. Optional multipart file attachments. */
    public function comment_add()
    {
        $task_id   = (int) $this->request->getPost('task_id');
        $parent_id = (int) $this->request->getPost('parent_id');
        $text      = trim((string) $this->request->getPost('comment_text'));
        $uid       = $this->_uid();

        $hasFile = ! empty($_FILES['attachment']['name'][0]) || (! empty($_FILES['attachment']['name']) && ! is_array($_FILES['attachment']['name']));
        if (! $task_id || ($text === '' && ! $hasFile)) {
            return $this->response->setJSON(['status' => 'error', 'error_msg' => 'Comment text or attachment is required.']);
        }

        $comment_id = $this->model()->add_comment($task_id, $uid, $text, $parent_id);

        $this->_save_uploaded_attachments($task_id, $comment_id, $uid);

        $type    = $parent_id ? 'reply' : 'comment';
        $snippet = $text !== '' ? $text : 'shared an attachment';
        task_notify_participants($task_id, $uid, $type,
            $this->_actor_name() . ($parent_id ? ' replied: ' : ' commented: ') . $snippet, $comment_id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Comment posted',
            'data'    => $this->model()->get_comments_tree($task_id),
        ]);
    }

    public function comment_delete()
    {
        $comment_id = (int) $this->request->getPost('comment_id');
        return $this->response->setJSON(
            $this->model()->delete_comment($comment_id, $this->_uid(), $this->_utype())
        );
    }

    public function get_comments($id = '')
    {
        $task_id = $id ? (int) ID_decode($id) : (int) $this->request->getPost('task_id');
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $this->model()->get_comments_tree($task_id),
        ]);
    }

    /**
     * Persist files posted under the `attachment` input (single or multiple)
     * into uploads/task_attachments/<task_id>/ and record them.
     */
    private function _save_uploaded_attachments($task_id, $comment_id, $uid): void
    {
        if (empty($_FILES['attachment']['name'])) {
            return;
        }

        $rel_dir = 'uploads/task_attachments/' . (int) $task_id . '/';
        $abs_dir = FCPATH . $rel_dir;
        if (! is_dir($abs_dir)) {
            @mkdir($abs_dir, 0755, true);
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
        $names   = (array) $_FILES['attachment']['name'];
        $tmps    = (array) $_FILES['attachment']['tmp_name'];
        $sizes   = (array) $_FILES['attachment']['size'];
        $types   = (array) $_FILES['attachment']['type'];

        foreach ($names as $i => $name) {
            if ($name === '' || empty($tmps[$i]) || ! is_uploaded_file($tmps[$i])) {
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (! in_array($ext, $allowed, true)) {
                continue;
            }
            $filename = 'cmt_' . $comment_id . '_' . $i . '_' . time() . '.' . $ext;
            if (@move_uploaded_file($tmps[$i], $abs_dir . $filename)) {
                $this->model()->add_attachment([
                    'comment_id'  => (int) $comment_id,
                    'task_id'     => (int) $task_id,
                    'file_name'   => $name,
                    'file_path'   => $rel_dir . $filename,
                    'file_type'   => $types[$i] ?? '',
                    'file_size'   => isset($sizes[$i]) ? (int) $sizes[$i] : 0,
                    'uploaded_by' => (int) $uid,
                    'added_date'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /* =====================================================================
     * NOTIFICATIONS (AJAX)
     * ===================================================================== */

    public function notifications()
    {
        $uid = $this->_uid();
        return $this->response->setJSON([
            'status'       => 'success',
            'unread_count' => $this->model()->unread_notification_count($uid),
            'data'         => $this->model()->list_notifications($uid),
        ]);
    }

    public function mark_read()
    {
        $this->model()->mark_notifications_read($this->_uid(), (int) $this->request->getPost('notification_id'));
        return $this->response->setJSON(['status' => 'success']);
    }
}
