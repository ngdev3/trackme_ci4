<?php

namespace Modules\Notifications\Controllers;

use App\Controllers\BaseController;
use App\Models\NotificationModel;

class NotificationController extends BaseController
{
    public function index()
    {
        $model = new NotificationModel();
        $search = trim((string) $this->request->getGet('q'));
        $type = trim((string) $this->request->getGet('type'));
        $module = trim((string) $this->request->getGet('module'));
        $status = trim((string) $this->request->getGet('status'));

        $builder = $model->visibleForCurrentUser()
            ->orderBy('notifications.is_read', 'ASC')
            ->orderBy('notifications.id', 'DESC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('notifications.title', $search)
                ->orLike('notifications.message', $search)
                ->orLike('notifications.module', $search)
                ->groupEnd();
        }
        if ($type !== '') {
            $builder->where('notifications.type', $type);
        }
        if ($module !== '') {
            $builder->where('notifications.module', $module);
        }
        if ($status === 'read') {
            $builder->where('notifications.is_read', 1);
        } elseif ($status === 'unread') {
            $builder->where('notifications.is_read', 0);
        }

        return $this->render('index', [
            'title'      => 'Notifications',
            'breadcrumb' => [['label' => 'Notifications']],
            'rows'       => $builder->paginate(15),
            'pager'      => $model->pager,
            'search'     => $search,
            'type'       => $type,
            'module'     => $module,
            'status'     => $status,
            'types'      => NotificationModel::TYPES,
        ]);
    }

    public function markRead()
    {
        $ids = (array) $this->request->getPost('ids');
        if ($id = $this->request->getPost('id')) {
            $ids[] = $id;
        }
        $count = (new NotificationModel())->markRead($ids);
        return redirect()->back()->with('success', $count . ' notification(s) marked as read.');
    }

    public function markAllRead()
    {
        $count = (new NotificationModel())->markAllRead();
        return redirect()->back()->with('success', $count . ' notification(s) marked as read.');
    }

    public function delete()
    {
        $ids = (array) $this->request->getPost('ids');
        if ($id = $this->request->getPost('id')) {
            $ids[] = $id;
        }
        $count = (new NotificationModel())->deleteVisible($ids);
        return redirect()->back()->with('success', $count . ' notification(s) deleted.');
    }

    public function apiIndex()
    {
        $model = new NotificationModel();
        return $this->response->setJSON([
            'unread' => $model->unreadCount(),
            'items'  => $model->latestVisible(10),
        ]);
    }

    public function apiMarkRead()
    {
        $ids = (array) ($this->request->getJSON(true)['ids'] ?? []);
        return $this->response->setJSON(['updated' => (new NotificationModel())->markRead($ids)]);
    }

    public function apiMarkAllRead()
    {
        return $this->response->setJSON(['updated' => (new NotificationModel())->markAllRead()]);
    }

    public function apiDelete()
    {
        $ids = (array) ($this->request->getJSON(true)['ids'] ?? []);
        return $this->response->setJSON(['deleted' => (new NotificationModel())->deleteVisible($ids)]);
    }
}
