<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Config\Database;

/** Attendance — CI4 port, full CRUD (aa_attendance). Gated rbac('attendance'). */
class Attendance extends BaseController
{
    protected $helpers = ['url', 'app'];

    private function db()
    {
        return Database::connect();
    }

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\attendance\listing', ['title' => 'Attendance · C R Industries ERP']);
    }

    public function viewAll()
    {
        $post  = $this->request->getPost();
        $start = (int) ($post['start'] ?? 0);
        $b = $this->db()->table('aa_attendance')
            ->where('template_id', fy()->template_id)->where('FY', fy()->FY)
            ->where("COALESCE(status,'') != 'Delete'", null, false)->orderBy('attendance_id', 'desc');
        $total = (clone $b)->countAllResults(false);
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], $start);
        }
        $rows = $b->get()->getResult();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $st = strtolower((string) ($row->attendance_status ?? ''));
            $cls = $st === 'present' ? 'success' : ($st === 'absent' ? 'danger' : 'warning');
            $data[] = [
                $j, esc($row->person_name ?? ''), esc($row->attendance_date ?? ''),
                '<span class="label label-' . $cls . '">' . esc($row->attendance_status ?? '') . '</span>',
                esc($row->check_in ?? ''), esc($row->check_out ?? ''),
                '<button class="btn btn-xs btn-primary at-edit" data-id="' . (int) $row->attendance_id . '"><i class="fa fa-edit"></i></button> '
                . '<button class="btn btn-xs btn-danger at-del" data-id="' . (int) $row->attendance_id . '"><i class="fa fa-trash"></i></button>',
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
        $person = trim((string) $this->request->getPost('person_name'));
        $date   = trim((string) $this->request->getPost('attendance_date'));
        $astat  = $this->request->getPost('attendance_status') ?: 'Present';
        $cin    = trim((string) $this->request->getPost('check_in'));
        $cout   = trim((string) $this->request->getPost('check_out'));

        if ($person === '' || $date === '') {
            return $this->json('error', 'Person name and date are required.');
        }
        $now = date('Y-m-d H:i:s');
        $payload = ['person_name' => $person, 'attendance_date' => $date, 'attendance_status' => $astat, 'check_in' => $cin, 'check_out' => $cout, 'updated_date' => $now];
        if ($id > 0) {
            $this->db()->table('aa_attendance')->where('attendance_id', $id)->update($payload);
            return $this->json('success', 'Attendance updated.', ['id' => $id]);
        }
        $this->db()->table('aa_attendance')->insert(array_merge($payload, [
            'status' => 'Active', 'FY' => fy()->FY, 'template_id' => fy()->template_id,
            'added_by' => (int) (currentuserinfo()->id ?? 0), 'created_date' => $now,
        ]));
        return $this->json('success', 'Attendance added.', ['id' => (int) $this->db()->insertID()]);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        $this->db()->table('aa_attendance')->where('attendance_id', $id)->update(['status' => 'Delete']);
        return $this->json('success', 'Attendance deleted.');
    }

    public function row($id = 0)
    {
        $r = $this->db()->table('aa_attendance')->where('attendance_id', (int) $id)->get()->getRow();
        return $r ? $this->response->setJSON(['status' => 'success', 'data' => $r]) : $this->json('error', 'Not found.');
    }

    private function json(string $s, string $m, array $x = [])
    {
        return $this->response->setJSON(array_merge(['status' => $s, 'message' => $m], $x));
    }
}
