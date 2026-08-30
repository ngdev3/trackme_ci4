<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\NotificationModel;

/**
 * Notification — CI4 port of admin/Notification (the notification-centre).
 * Lists the current user's activity notifications (+ global broadcasts) with
 * read/unread state, mark-one / mark-all, and the AJAX "mark seen" the top-nav
 * bell dropdown calls to clear its badge. Gated rbac('notification').
 *
 * Ported: listing, view_all, mark_all_read, mark_seen, read, updatenotificationStatus.
 * Skipped: add/edit/GeneratePdf/delete/account_name/getSOBDate — dead copy-paste
 * boilerplate (they reference tax-invoice/rokad columns, not notifications).
 */
class Notification extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\notification\listing', [
            'title'  => 'Notifications · C R Industries ERP',
            'counts' => (new NotificationModel())->getCounts(),
        ]);
    }

    /** DataTables JSON feed. */
    public function viewAll()
    {
        $model = new NotificationModel();
        $total = $model->countBillingData();
        $rows  = $model->getBillingData();

        $data = [];
        $j    = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $r) {
            $row = (array) $r;
            $j++;

            $name = (string) ($row['name'] ?? '');
            $activity = ! empty($row['action'])
                ? '<a href="' . esc($row['action'], 'attr') . '" style="color:#26374f;">' . $name . '</a>'
                : $name;

            $status = ((int) ($row['is_seen'] ?? 0) === 0)
                ? '<span class="badge" style="background:#1769c2;color:#fff;">Unread</span>'
                : '<span class="badge" style="background:#a0aec0;color:#fff;">Read</span>';

            $data[] = [
                $j,
                $activity,
                $status,
                ! empty($row['user_name']) ? esc($row['user_name']) : '-',
                ! empty($row['added_date']) ? date('d M Y, h:i A', strtotime($row['added_date'])) : '-',
                $this->timeAgo($row['added_date'] ?? null),
                $this->rowAction((int) $row['id'], (int) ($row['is_seen'] ?? 0)),
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    /** Mark all read (page action → redirect). */
    public function mark_all_read()
    {
        (new NotificationModel())->markAllRead();
        return redirect()->to(base_url('admin/notification/listing'))->with('success', 'All notifications marked as read');
    }

    /** AJAX: mark all unread read — called when the bell dropdown opens. */
    public function mark_seen()
    {
        $updated = (new NotificationModel())->markAllRead();
        return $this->response->setJSON(['status' => 'success', 'updated' => (int) $updated]);
    }

    /** Mark one read, then forward to its target page. */
    public function read($id = null)
    {
        $nid = (int) $id;
        if ($nid) {
            $model = new NotificationModel();
            $note  = $model->getOne($nid);
            $model->markRead($nid);
            if ($note && ! empty($note->action)) {
                return redirect()->to($note->action);
            }
        }
        return redirect()->to(base_url('admin/notification/listing'));
    }

    /** AJAX per-row read/unread toggle. */
    public function updatenotificationStatus()
    {
        $id   = (int) $this->request->getPost('id');
        $flag = (string) $this->request->getPost('status');
        (new NotificationModel())->toggleSeen($id, $flag);
        return $this->response->setJSON(['status' => 'success']);
    }

    /** Compact "x minutes ago" (port of notif_time_ago). */
    private function timeAgo($datetime): string
    {
        if (empty($datetime)) {
            return '-';
        }
        $ts   = strtotime($datetime);
        $diff = time() - $ts;
        if ($diff < 60)      { return 'just now'; }
        if ($diff < 3600)    { $m = floor($diff / 60);   return $m . ' min' . ($m > 1 ? 's' : '') . ' ago'; }
        if ($diff < 86400)   { $h = floor($diff / 3600); return $h . ' hour' . ($h > 1 ? 's' : '') . ' ago'; }
        if ($diff < 604800)  { $d = floor($diff / 86400); return $d . ' day' . ($d > 1 ? 's' : '') . ' ago'; }
        return date('d M Y', $ts);
    }

    private function rowAction(int $id, int $isSeen): string
    {
        if ($isSeen === 0) {
            return '<button class="btn btn-xs btn-success ntf-toggle" data-id="' . $id . '" data-flag="Notchecked"><i class="fa fa-check"></i> Mark read</button>';
        }
        return '<button class="btn btn-xs btn-default ntf-toggle" data-id="' . $id . '" data-flag="checked"><i class="fa fa-undo"></i> Mark unread</button>';
    }
}
