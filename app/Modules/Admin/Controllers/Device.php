<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\DeviceModel;

/**
 * Device — CI4 port of admin/Device (registered devices). Lists aa_whitelist_device
 * (web + mobile) with user, platform, status, last-seen; status toggle + delete.
 * FCM push send (send_push/_fcm_send) is deferred (needs the FCM service-account).
 */
class Device extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\device\listing', [
            'title' => 'Device Management · C R Industries ERP',
            'count' => (new DeviceModel())->countDeviceData(),
        ]);
    }

    public function view_all()
    {
        $model = new DeviceModel();
        $total = $model->countDeviceData();
        $rows  = $model->getDeviceData();

        $badge = ['Active' => 'success', 'Inactive' => 'warning', 'Delete' => 'danger'];
        $data  = [];
        $j = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $r) {
            $row = (array) $r;
            $j++;
            $uname = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $user  = $uname !== ''
                ? '<strong>' . esc($uname) . '</strong><br><small>' . esc($row['email'] ?? '') . '</small>'
                : '<span class="label label-default">Unmapped</span>';
            $platform = trim(($row['platform'] ?? '') . ' ' . ($row['os_version'] ?? ''));
            $platform = $platform !== '' ? esc($platform) : esc($row['device_type'] ?? '');
            $st = (string) ($row['status'] ?? 'Active');
            $status = '<span class="label label-' . ($badge[$st] ?? 'default') . '">' . esc($st) . '</span>';
            $seen = ! empty($row['last_seen_at']) ? date('d-M-Y H:i', strtotime($row['last_seen_at'])) : '-';

            $act = ($st === 'Active')
                ? '<button class="btn btn-xs btn-warning dv-toggle" data-id="' . (int) $row['id'] . '" data-status="Inactive"><i class="fa fa-pause"></i></button> '
                : '<button class="btn btn-xs btn-success dv-toggle" data-id="' . (int) $row['id'] . '" data-status="Active"><i class="fa fa-check"></i></button> ';
            $act .= '<button class="btn btn-xs btn-danger dv-del" data-id="' . (int) $row['id'] . '"><i class="fa fa-trash"></i></button>';

            $data[] = [$j, '<strong>' . esc($row['device_name'] ?? '') . '</strong>', $user, $platform, $status, $seen, '<div class="text-nowrap">' . $act . '</div>'];
        }

        return $this->response->setJSON([
            'draw' => (int) $this->request->getPost('draw'),
            'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data,
        ]);
    }

    public function update_status()
    {
        $id = (int) $this->request->getPost('id');
        (new DeviceModel())->updateStatus($id, (string) $this->request->getPost('status'));
        return $this->response->setJSON(['status' => 'success']);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        if ($id) { (new DeviceModel())->delete($id); }
        return $this->response->setJSON(['status' => 'success']);
    }
}
