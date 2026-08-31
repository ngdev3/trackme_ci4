<?php

namespace App\Modules\Task\Controllers;

use App\Controllers\BaseController;
use App\Modules\Task\Models\TaskModel;

/**
 * Task — CI4 port of task/Task (top-level module). Task CRUD + Jira-style
 * threaded comments (replies, attachments) + per-user notifications. FCM
 * assignment/participant push is guarded (task_notify helper not yet ported).
 * Gated by adminAuth+fyContext+rbac on the task/* group (Config\Filters).
 */
class Task extends BaseController
{
    protected $helpers = ['url', 'app', 'form'];
    private const UPDIR = 'uploads/task_attachments/';
    private const ALLOWED = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

    private function uid(): int
    {
        return (int) (currentuserinfo()->id ?? 0);
    }

    private function utype(): string
    {
        return (string) (currentuserinfo()->user_type ?? '');
    }

    private function actorName(): string
    {
        $u = currentuserinfo();
        return $u ? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) : 'Someone';
    }

    public function index()
    {
        return _layout('\App\Modules\Task\Views\tasks\listing', ['title' => 'Tasks · C R Industries ERP']);
    }

    public function view_all()
    {
        $model = new TaskModel();
        $rows  = $model->getTaskData();
        $data = [];
        $j = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $row) {
            $j++;
            $assignee = trim(($row->assignee_first ?? '') . ' ' . ($row->assignee_last ?? ''));
            $data[] = [
                $j,
                '<a href="' . base_url('task/task/view/' . ID_encode((int) $row->task_id)) . '">' . esc($row->title) . '</a>',
                $this->statusBadge((string) $row->status),
                ucfirst((string) $row->priority),
                $assignee !== '' ? esc($assignee) : '<span class="text-muted">Unassigned</span>',
                '<i class="fa fa-comment-o"></i> ' . (int) $row->comment_count,
                ! empty($row->added_date) ? date('d-M-Y', strtotime($row->added_date)) : '-',
                $this->rowActions($row),
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $model->countTaskData(), 'recordsFiltered' => $model->countTaskData(), 'data' => $data]);
    }

    public function add()
    {
        $model = new TaskModel();
        if (strtoupper($this->request->getMethod()) === 'POST') {
            if (trim((string) $this->request->getPost('title')) !== '') {
                $taskId = $model->add([
                    'title' => $this->request->getPost('title'), 'description' => $this->request->getPost('description'),
                    'status' => $this->request->getPost('status') ?: 'open', 'priority' => $this->request->getPost('priority') ?: 'medium',
                    'assigned_to' => $this->request->getPost('assigned_to') ?: null, 'created_by' => $this->uid(),
                    'due_date' => $this->request->getPost('due_date') ?: null, 'added_date' => date('Y-m-d H:i:s'),
                ]);
                if ($taskId && $this->request->getPost('assigned_to') && function_exists('task_notify_assignment')) {
                    task_notify_assignment($taskId, $this->uid(), (int) $this->request->getPost('assigned_to'));
                }
                return redirect()->to(base_url('task/task'))->with('success', 'Task created successfully');
            }
            session()->setFlashdata('error', 'Title is required.');
        }
        return _layout('\App\Modules\Task\Views\tasks\add', ['title' => 'Add Task', 'result' => false, 'users' => $model->getUsers()]);
    }

    public function edit($id = '')
    {
        $model  = new TaskModel();
        $taskId = (int) ID_decode($id);
        if (! $taskId) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }

        if (strtoupper($this->request->getMethod()) === 'POST') {
            if (trim((string) $this->request->getPost('title')) !== '') {
                $model->update($taskId, [
                    'title' => $this->request->getPost('title'), 'description' => $this->request->getPost('description'),
                    'status' => $this->request->getPost('status'), 'priority' => $this->request->getPost('priority'),
                    'assigned_to' => $this->request->getPost('assigned_to') ?: null,
                    'due_date' => $this->request->getPost('due_date') ?: null, 'updated_date' => date('Y-m-d H:i:s'),
                ]);
                return redirect()->to(base_url('task/task'))->with('success', 'Task updated successfully');
            }
            session()->setFlashdata('error', 'Title is required.');
        }
        return _layout('\App\Modules\Task\Views\tasks\add', ['title' => 'Edit Task', 'result' => $model->view($taskId), 'users' => $model->getUsers()]);
    }

    public function view($id = '')
    {
        $model  = new TaskModel();
        $taskId = (int) ID_decode($id);
        $task   = $model->view($taskId);
        if (! $task || $task->is_deleted) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        return _layout('\App\Modules\Task\Views\tasks\view', [
            'title' => 'Task #' . $taskId, 'result' => $task,
            'comments' => $model->getCommentsTree($taskId), 'current_uid' => $this->uid(),
        ]);
    }

    public function delete()
    {
        $id = (int) ID_decode($this->request->getPost('id'));
        if ($id) { (new TaskModel())->softDelete($id); }
        return $this->response->setJSON(['status' => 'success']);
    }

    public function set_status()
    {
        $id = (int) ID_decode($this->request->getPost('id'));
        $status = (string) $this->request->getPost('status');
        if (! $id || ! in_array($status, ['open', 'in_progress', 'done', 'closed'], true)) {
            return $this->response->setJSON(['status' => 'error', 'error_msg' => 'Invalid task or status.']);
        }
        (new TaskModel())->update($id, ['status' => $status, 'updated_date' => date('Y-m-d H:i:s')]);
        if (in_array($status, ['done', 'closed'], true) && function_exists('task_notify_participants')) {
            task_notify_participants($id, $this->uid(), 'status', $this->actorName() . ' marked the task as ' . ucfirst($status));
        }
        return $this->response->setJSON(['status' => 'success']);
    }

    public function comment_add()
    {
        $model   = new TaskModel();
        $taskId  = (int) $this->request->getPost('task_id');
        $parent  = (int) $this->request->getPost('parent_id');
        $text    = trim((string) $this->request->getPost('comment_text'));
        $files   = $this->request->getFileMultiple('attachment') ?: [];
        $hasFile = false;
        foreach ($files as $f) { if ($f && $f->isValid()) { $hasFile = true; break; } }

        if (! $taskId || ($text === '' && ! $hasFile)) {
            return $this->response->setJSON(['status' => 'error', 'error_msg' => 'Comment text or attachment is required.']);
        }
        $commentId = $model->addComment($taskId, $this->uid(), $text, $parent);
        $this->saveAttachments($model, $taskId, $commentId, $files);

        if (function_exists('task_notify_participants')) {
            $type = $parent ? 'reply' : 'comment';
            task_notify_participants($taskId, $this->uid(), $type, $this->actorName() . ($parent ? ' replied: ' : ' commented: ') . ($text ?: 'shared an attachment'), $commentId);
        }
        return $this->response->setJSON(['status' => 'success', 'message' => 'Comment posted', 'data' => $model->getCommentsTree($taskId)]);
    }

    public function comment_delete()
    {
        $res = (new TaskModel())->deleteComment((int) $this->request->getPost('comment_id'), $this->uid(), $this->utype());
        return $this->response->setJSON($res);
    }

    public function get_comments($id = '')
    {
        $taskId = $id ? (int) ID_decode($id) : (int) $this->request->getPost('task_id');
        return $this->response->setJSON(['status' => 'success', 'data' => (new TaskModel())->getCommentsTree($taskId)]);
    }

    public function notifications()
    {
        $model = new TaskModel();
        return $this->response->setJSON([
            'status' => 'success',
            'unread_count' => $model->unreadNotificationCount($this->uid()),
            'data' => $model->listNotifications($this->uid()),
        ]);
    }

    public function mark_read()
    {
        (new TaskModel())->markNotificationsRead($this->uid(), (int) $this->request->getPost('notification_id'));
        return $this->response->setJSON(['status' => 'success']);
    }

    private function saveAttachments(TaskModel $model, int $taskId, int $commentId, array $files): void
    {
        if (! $files) { return; }
        $dir = FCPATH . self::UPDIR . $taskId . '/';
        if (! is_dir($dir)) { @mkdir($dir, 0755, true); }
        foreach ($files as $i => $f) {
            if (! $f || ! $f->isValid() || $f->getError() === UPLOAD_ERR_NO_FILE) { continue; }
            $ext = strtolower($f->getClientExtension() ?: '');
            if (! in_array($ext, self::ALLOWED, true)) { continue; }
            $orig = $f->getClientName();
            $type = $f->getClientMimeType();
            $size = $f->getSize();
            $stored = 'cmt_' . $commentId . '_' . $i . '_' . time() . '.' . $ext;
            $f->move($dir, $stored);
            $model->addAttachment([
                'comment_id' => $commentId, 'task_id' => $taskId, 'file_name' => $orig,
                'file_path' => self::UPDIR . $taskId . '/' . $stored, 'file_type' => $type, 'file_size' => $size,
                'uploaded_by' => $this->uid(), 'added_date' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function statusBadge(string $status): string
    {
        $map = ['open' => 'default', 'in_progress' => 'info', 'done' => 'success', 'closed' => 'primary'];
        return '<span class="label label-' . ($map[$status] ?? 'default') . '">' . ucwords(str_replace('_', ' ', $status)) . '</span>';
    }

    private function rowActions($row): string
    {
        $enc = ID_encode((int) $row->task_id);
        return '<div class="text-nowrap">'
            . '<a class="btn btn-xs btn-default" href="' . base_url('task/task/view/' . $enc) . '"><i class="fa fa-eye"></i></a> '
            . '<a class="btn btn-xs btn-primary" href="' . base_url('task/task/edit/' . $enc) . '"><i class="fa fa-edit"></i></a> '
            . '<button class="btn btn-xs btn-danger tsk-del" data-id="' . esc($enc, 'attr') . '"><i class="fa fa-trash"></i></button></div>';
    }
}
