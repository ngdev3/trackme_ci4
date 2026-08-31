<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Config\Database;

/** FCI Driver — CI4 port, full CRUD (aa_driver, global). Gated rbac('driver_module'). */
class DriverModule extends BaseController
{
    protected $helpers = ['url', 'app'];

    private function db()
    {
        return Database::connect();
    }

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\driver_module\listing', ['title' => 'FCI Driver · C R Industries ERP']);
    }

    public function viewAll()
    {
        $post  = $this->request->getPost();
        $start = (int) ($post['start'] ?? 0);
        $b = $this->db()->table('aa_driver')->where("COALESCE(status,'') != 'Delete'", null, false)->orderBy('driver_id', 'desc');
        $total = (clone $b)->countAllResults(false);
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], $start);
        }
        $rows = $b->get()->getResult();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [
                $j, esc($row->name), esc($row->mobile_number),
                '<span class="label label-' . (strtolower((string) $row->status) === 'active' || $row->status === '' ? 'success' : 'default') . '">' . esc($row->status ?: 'Active') . '</span>',
                '<button class="btn btn-xs btn-primary dr-edit" data-id="' . (int) $row->driver_id . '"><i class="fa fa-edit"></i></button> '
                . '<button class="btn btn-xs btn-danger dr-del" data-id="' . (int) $row->driver_id . '"><i class="fa fa-trash"></i></button>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) ($post['draw'] ?? 0), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }

    public function save()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'POST required.']);
        }
        $id     = (int) $this->request->getPost('id');
        $name   = trim((string) $this->request->getPost('name'));
        $mobile = trim((string) $this->request->getPost('mobile_number'));
        $status = in_array($this->request->getPost('status'), ['Active', 'Inactive'], true) ? $this->request->getPost('status') : 'Active';

        if ($name === '') {
            return $this->json('error', 'Driver name is required.');
        }
        $now = date('Y-m-d H:i:s');
        if ($id > 0) {
            $this->db()->table('aa_driver')->where('driver_id', $id)->update(['name' => $name, 'mobile_number' => $mobile, 'status' => $status, 'updated_date' => $now]);
            return $this->json('success', 'Driver updated.', ['id' => $id]);
        }
        $this->db()->table('aa_driver')->insert(['name' => $name, 'mobile_number' => $mobile, 'status' => $status, 'added_by' => (int) (currentuserinfo()->id ?? 0), 'added_date' => $now, 'updated_date' => $now]);
        return $this->json('success', 'Driver added.', ['id' => (int) $this->db()->insertID()]);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        $this->db()->table('aa_driver')->where('driver_id', $id)->update(['status' => 'Delete']);
        return $this->json('success', 'Driver deleted.');
    }

    public function row($id = 0)
    {
        $r = $this->db()->table('aa_driver')->where('driver_id', (int) $id)->get()->getRow();
        return $r ? $this->response->setJSON(['status' => 'success', 'data' => $r]) : $this->json('error', 'Not found.');
    }

    private function json(string $s, string $m, array $x = [])
    {
        return $this->response->setJSON(array_merge(['status' => $s, 'message' => $m], $x));
    }
}
