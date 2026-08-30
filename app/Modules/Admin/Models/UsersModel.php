<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * UsersModel — CI4 port of admin/models/Users_mod (the Users-listing slice).
 * `users` is a GLOBAL table (no template scope); status ∈ Active/Inactive/Delete.
 * The listing joins the user's default firm (aa_template → firm_name) and its
 * role (erp_user_type_roles) exactly like CI3 get_Billing_data(). DataTables
 * params come from the POST body; the status filter from GET (unchanged contract).
 */
class UsersModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Total (search-unfiltered) count, honouring the ?status= filter. */
    public function countBillingData(): int
    {
        $req     = service('request');
        $post    = $req->getPost();
        $builder = $this->db()->table('users');

        $status = $req->getGet('status');
        if ($status !== null && $status !== '') {
            $builder->where('users.status', $status);
        }
        if (! empty($post['search']['value'])) {
            $builder->like("(CONCAT(users.first_name,' ',users.last_name))", $post['search']['value']);
        }
        return $builder->countAllResults();
    }

    /** One page of rows for the DataTable (joins firm + role), CI3-faithful. */
    public function getBillingData(): array
    {
        $req  = service('request');
        $post = $req->getPost();

        $columns = [1 => 'first_name', 2 => 'last_name', 3 => 'email', 4 => 'mobile', 5 => 'pan_number', 6 => 'address', 7 => 'status', 8 => 'default_firm'];

        $builder = $this->db()->table('users ab')
            ->select('ab.*, atp.FY, fn.name as firm_name, r.role_name, r.job_title')
            ->join('aa_template atp', 'atp.template_id = ab.default_firm', 'left')
            ->join('firm_name fn', 'atp.firm_name_id = fn.id', 'left')
            ->join('erp_user_type_roles r', 'r.user_type = ab.user_type', 'left');

        $status = $req->getGet('status');
        if ($status !== null && $status !== '') {
            $builder->where('ab.status', $status);
        }
        if (! empty($post['search']['value'])) {
            $builder->like("(CONCAT(ab.first_name,' ',ab.last_name,' ',ab.email,' ',ab.mobile,' ',ab.pan_number,' ',ab.address,' ',ab.status))", $post['search']['value']);
        }

        if (! empty($post['order'][0]['column']) && ! empty($post['order'][0]['dir'])) {
            $col = $columns[$post['order'][0]['column']] ?? 'id';
            $builder->orderBy($col, $post['order'][0]['dir']);
        } else {
            $builder->orderBy('ab.id', 'desc');
        }
        if (! empty($post['length']) && $post['length'] != '-1') {
            $builder->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }

        return $builder->get()->getResult();
    }

    /** Headline counts for the KPI tiles (single grouped query). */
    public function userStats(): array
    {
        $out  = ['total' => 0, 'active' => 0, 'inactive' => 0, 'deleted' => 0, 'roles' => 0];
        $rows = $this->db()->table('users')->select('status, COUNT(*) AS c', false)->groupBy('status')->get()->getResult();
        foreach ($rows as $r) {
            $c   = (int) $r->c;
            $out['total'] += $c;
            $st = strtolower(trim((string) $r->status));
            if ($st === 'active') {
                $out['active'] += $c;
            } elseif ($st === 'delete' || $st === 'deleted') {
                $out['deleted'] += $c;
            } else {
                $out['inactive'] += $c;
            }
        }
        $rq = $this->db()->table('users')->select('COUNT(DISTINCT user_type) AS c', false)->get()->getRow();
        $out['roles'] = $rq ? (int) $rq->c : 0;
        return $out;
    }

    public function find(int $id)
    {
        return $this->db()->table('users')->where('id', $id)->get()->getRow();
    }

    /** Active/Inactive toggle (CI3 update_userStatus semantics). */
    public function updateStatus(int $id, string $flag): bool
    {
        $status = ($flag === 'checked') ? 'Inactive' : (($flag === 'Delete') ? 'Deleted' : 'Active');
        $this->db()->table('users')->where('id', $id)->update([
            'status'       => $status,
            'updated_date' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /** Soft delete / restore — sets the status value passed from the row action. */
    public function setStatus(int $id, string $status): bool
    {
        $this->db()->table('users')->where('id', $id)->update([
            'status'       => $status,
            'updated_date' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /** One user + its default firm name (for the profile view). */
    public function findWithFirm(int $id)
    {
        return $this->db()->table('users ab')
            ->select('ab.*, fn.name as firm_name')
            ->join('aa_template atp', 'atp.template_id = ab.default_firm', 'left')
            ->join('firm_name fn', 'atp.firm_name_id = fn.id', 'left')
            ->where('ab.id', $id)
            ->get()->getRow();
    }

    /** Active firms/FYs for the default-firm dropdown (CI3 get_all_financial_year shape). */
    public function financialYears(): array
    {
        return $this->db()->table('aa_template atp')
            ->select('atp.*, frn.name as firm_name')
            ->join('firm_name frn', 'frn.id = atp.firm_name_id', 'left')
            ->where('atp.status', 'Active')
            ->get()->getResult();
    }

    /** Role list for the user-type dropdown. */
    public function roles(): array
    {
        return $this->db()->table('erp_user_type_roles')->orderBy('user_type', 'asc')->get()->getResult();
    }

    /** Is this email already used by another user? (is_unique parity) */
    public function emailExists(string $email, int $exceptId = 0): bool
    {
        $b = $this->db()->table('users')->where('email', $email);
        if ($exceptId > 0) {
            $b->where('id !=', $exceptId);
        }
        return (bool) $b->get()->getRow();
    }

    public function create(array $data): int
    {
        $this->db()->table('users')->insert($data);
        return (int) $this->db()->insertID();
    }

    public function updateUser(int $id, array $data): bool
    {
        $this->db()->table('users')->where('id', $id)->update($data);
        return true;
    }
}
