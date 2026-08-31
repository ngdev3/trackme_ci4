<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Config\Database;

/** Salary Module — CI4 port, full CRUD (salary_module). Gated rbac('salary_module'). */
class SalaryModule extends BaseController
{
    protected $helpers = ['url', 'app'];

    private function db()
    {
        return Database::connect();
    }

    public function listing()
    {
        $users = $this->db()->table('users')->select('id, first_name, last_name')
            ->where("COALESCE(status,'') NOT IN ('Delete')", null, false)->orderBy('first_name', 'asc')->get()->getResult();
        return _layout('\App\Modules\Admin\Views\salary\listing', ['title' => 'Salary Module · C R Industries ERP', 'users' => $users]);
    }

    public function viewAll()
    {
        $post  = $this->request->getPost();
        $start = (int) ($post['start'] ?? 0);
        $b = $this->db()->table('salary_module sm')
            ->select('sm.*, u.first_name, u.last_name')
            ->join('users u', 'u.id = sm.user_id', 'left')
            ->groupStart()->where('sm.mapped_template_id', fy()->template_id)->orWhere('sm.apply_all_firms', 1)->groupEnd()
            ->where("COALESCE(sm.status,'') != 'Delete'", null, false)->orderBy('sm.id', 'desc');
        $total = (clone $b)->countAllResults(false);
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], $start);
        }
        $rows = $b->get()->getResult();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) ?: ('User #' . $row->user_id);
            $data[] = [
                $j, esc($name), esc($row->salary_amount), esc($row->start_date), esc($row->end_date),
                '<span class="label label-' . (strtolower((string) $row->status) === 'active' ? 'success' : 'default') . '">' . esc($row->status ?: 'Active') . '</span>',
                '<button class="btn btn-xs btn-primary sl-edit" data-id="' . (int) $row->id . '"><i class="fa fa-edit"></i></button> '
                . '<button class="btn btn-xs btn-danger sl-del" data-id="' . (int) $row->id . '"><i class="fa fa-trash"></i></button>',
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
        $userId = (int) $this->request->getPost('user_id');
        $amount = (float) $this->request->getPost('salary_amount');
        $start  = trim((string) $this->request->getPost('start_date'));
        $end    = trim((string) $this->request->getPost('end_date'));
        $status = in_array($this->request->getPost('status'), ['Active', 'Inactive'], true) ? $this->request->getPost('status') : 'Active';

        if ($userId <= 0) {
            return $this->json('error', 'Please select an employee.');
        }
        if ($amount <= 0) {
            return $this->json('error', 'Salary amount must be greater than 0.');
        }
        $now = date('Y-m-d H:i:s');
        $payload = ['user_id' => $userId, 'salary_amount' => $amount, 'start_date' => $start, 'end_date' => $end, 'status' => $status, 'updated_on' => $now];
        if ($id > 0) {
            $this->db()->table('salary_module')->where('id', $id)->update($payload);
            return $this->json('success', 'Salary updated.', ['id' => $id]);
        }
        $this->db()->table('salary_module')->insert(array_merge($payload, [
            'mapped_template_id' => fy()->template_id, 'apply_all_firms' => 0,
            'added_by' => (int) (currentuserinfo()->id ?? 0), 'added_date' => $now,
        ]));
        return $this->json('success', 'Salary added.', ['id' => (int) $this->db()->insertID()]);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        $this->db()->table('salary_module')->where('id', $id)->update(['status' => 'Delete']);
        return $this->json('success', 'Salary deleted.');
    }

    public function row($id = 0)
    {
        $r = $this->db()->table('salary_module')->where('id', (int) $id)->get()->getRow();
        return $r ? $this->response->setJSON(['status' => 'success', 'data' => $r]) : $this->json('error', 'Not found.');
    }

    private function json(string $s, string $m, array $x = [])
    {
        return $this->response->setJSON(array_merge(['status' => $s, 'message' => $m], $x));
    }

    /** Credit history from salary_module_cron_log. Ported 1:1 from CI3 Salary_Module::history(). */
    public function history()
    {
        return _layout('\App\Modules\Admin\Views\salary\history', [
            'title' => 'Track (The Rest Accounting Key) || Salary Credit History',
            'logs'  => $this->creditHistory(1000),
        ]);
    }

    /** Salary_Module_mod::credit_history() port — newest-first cron log with names. */
    private function creditHistory(int $limit = 1000): array
    {
        $b = $this->db()->table('salary_module_cron_log cl')
            ->select('cl.*, u.first_name, u.last_name, acn.name as account_name, atp.template_name')
            ->join('users u', 'u.id = cl.user_id', 'left')
            ->join('aa_account_name acn', 'acn.account_id = cl.account_id', 'left')
            ->join('aa_template atp', 'atp.template_id = cl.mapped_template_id', 'left')
            ->orderBy('cl.id', 'desc');
        if ($limit) {
            $b->limit($limit);
        }
        return $b->get()->getResult();
    }
}
