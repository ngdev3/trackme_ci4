<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AccountNameModel;

/**
 * Account_name — CI4 port of admin/Account_name (account master, LISTING slice).
 * aa_account_name = global trade parties + farmers. Ships the core read path
 * (server-side DataTable) + status toggle + soft delete/restore + inline
 * quick-update. Accounting-group/ledger/GST-verify enrichment is a follow-up
 * (needs the accounting + gstin subsystems). rbac('account_name').
 */
class Account_name extends BaseController
{
    protected $helpers = ['url', 'app', 'form'];

    public function index()
    {
        return $this->listing();
    }

    /**
     * Add / Generate Account. Functional CI4 port: saves the account into
     * aa_account_name. Ledger provisioning + full GST auto-parse are deferred
     * (they need the accounting subsystem) — mirrors CI3's schema-not-ready path,
     * which saves the account without provisioning a ledger. rbac('account_name').
     */
    public function add()
    {
        if (strtoupper($this->request->getMethod()) === 'POST') {
            $r    = $this->request;
            $name = trim((string) $r->getPost('account_name'));
            $reg  = in_array($r->getPost('registration_type'), ['gst', 'pan', 'unregistered'], true)
                ? $r->getPost('registration_type') : 'gst';

            if ($name === '') {
                return redirect()->back()->withInput()->with('error', 'Account Name is required.');
            }
            $gst = strtoupper(trim((string) $r->getPost('purchaser_gst_no')));
            if ($reg === 'gst' && $gst === '') {
                return redirect()->back()->withInput()->with('error', 'GST number is required for a GST-registered party.');
            }

            $model = new AccountNameModel();
            if ($model->nameExists($name)) {
                return redirect()->back()->withInput()->with('error', 'An account with this name already exists.');
            }

            // Derive the GST state code (first 2 digits) when not supplied.
            $stateCode = trim((string) $r->getPost('state_code'));
            if ($stateCode === '' && $gst !== '' && preg_match('/^\d{2}/', $gst, $mm)) {
                $stateCode = $mm[0];
            }

            $db = \Config\Database::connect();
            $payload = [
                'name'                   => $name,
                'contact_person_name'    => trim((string) $r->getPost('contact_person_name')) ?: $name,
                'contact_person_number'  => trim((string) $r->getPost('contact_person_number')),
                'purchaser_gst_no'       => $gst,
                'pan_card'               => strtoupper(trim((string) $r->getPost('pan_card'))),
                'state'                  => trim((string) $r->getPost('state')),
                'state_code'             => $stateCode,
                'city'                   => trim((string) $r->getPost('city')),
                'pin_code'               => trim((string) $r->getPost('pin_code')),
                'purchaser_address'      => trim((string) $r->getPost('purchaser_address')),
                'email_id'               => trim((string) $r->getPost('email_id')),
                'bank_name'              => trim((string) $r->getPost('bank_name')),
                'ifsc_code'              => strtoupper(trim((string) $r->getPost('ifsc_code'))),
                'purchaser_account_no'   => trim((string) $r->getPost('purchaser_account_no')),
                'is_Kisan'               => $r->getPost('is_Kisan') ? 1 : 0,
                'account_type'           => trim((string) $r->getPost('account_type')),
                'status'                 => in_array($r->getPost('status'), ['Active', 'Inactive'], true) ? $r->getPost('status') : 'Active',
                'added_by'               => (int) (currentuserinfo()->id ?? 0),
                'updated_date'           => date('Y-m-d'),
            ];
            if ($db->fieldExists('registration_type', 'aa_account_name')) {
                $payload['registration_type'] = $reg;
            }

            $id = $model->add($payload);
            if (function_exists('notify')) {
                notify('New account <b>' . esc($name) . '</b> added' . ($gst !== '' ? ' &middot; GST ' . esc($gst) : ''),
                    base_url('admin/account_name/listing'), ['event' => 'added']);
            }
            if (function_exists('flash_toast')) {
                flash_toast('Account "' . $name . '" was added.', 'success', 'Account added');
            }
            return redirect()->to(base_url('admin/account_name/listing'))->with('success', 'Account added successfully (id ' . $id . ').');
        }

        $data = ['title' => 'Track (The Rest Accounting Key) || Add Account', 'result' => null];
        $this->account_form_data($data);
        return _layout('\App\Modules\Admin\Views\account_name\add', $data);
    }

    /**
     * Build the full Add/Edit form dataset (account types, ledger groups, sister
     * firms, State/City master, GST state master). CI3 account_form_data().
     */
    private function account_form_data(array &$data, $account_id = null): void
    {
        helper('app'); // gstin_state_master, acc helpers
        $db  = \Config\Database::connect();
        $acc = new \App\Modules\Admin\Models\AccountingModel();

        $data['account_types']      = function_exists('acc_account_types') ? acc_account_types() : [];
        $data['can_override_group'] = $this->can_override_group();
        $data['accounting_ready']   = $acc->schema_ready();
        $data['acc_groups']         = $data['accounting_ready'] ? $acc->group_options((int) fy()->template_id) : [];

        $data['firms'] = $db->table('aa_template')
            ->select('template_id, template_name')
            ->where('status', 'Active')
            ->orderBy('template_name', 'asc')
            ->get()->getResult();

        $data['states'] = $db->tableExists('am_state')
            ? $db->table('am_state')->select('state_id, name')->where('status', 'Active')->orderBy('name', 'asc')->get()->getResult()
            : [];
        $data['cities'] = $db->tableExists('am_city')
            ? $db->table('am_city')->select('city_id, name, state_id')->where('status', 'Active')->orderBy('name', 'asc')->get()->getResult()
            : [];
        $data['gst_states'] = function_exists('gstin_state_master') ? gstin_state_master() : [];

        $data['linked_template_id'] = null;
        if ($account_id && $data['accounting_ready'] && $db->tableExists('aa_ledger')) {
            $led = $db->table('aa_ledger')->select('linked_template_id')
                ->where('template_id', (int) fy()->template_id)
                ->where('legacy_account_id', (int) $account_id)
                ->get()->getRow();
            if ($led) { $data['linked_template_id'] = $led->linked_template_id; }
        }
    }

    private function can_override_group(): bool
    {
        return (function_exists('erp_is_super_admin') && erp_is_super_admin())
            || (function_exists('erp_current_user_can') && erp_current_user_can('account_name', 'edit'));
    }

    /** Inline "+ New" state from the Add form (AJAX). Seeds am_state. */
    public function quick_add_state()
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') { return $this->response->setJSON(['status' => 'error', 'message' => 'Name required']); }
        $db = \Config\Database::connect();
        if (! $db->tableExists('am_state')) { return $this->response->setJSON(['status' => 'error', 'message' => 'State master unavailable']); }
        $row = $db->table('am_state')->where('LOWER(name)', strtolower($name))->get()->getRow();
        if ($row) {
            $id = (int) $row->state_id;
        } else {
            $db->table('am_state')->insert(['name' => $name, 'status' => 'Active']);
            $id = (int) $db->insertID();
        }
        $gst  = gstin_state_master();
        $code = '';
        foreach ($gst as $c => $sn) { if (strcasecmp($sn, $name) === 0) { $code = $c; break; } }
        return $this->response->setJSON(['status' => 'success', 'id' => $id, 'code' => $code]);
    }

    /** Inline "+ New" city from the Add form (AJAX). Seeds am_city. */
    public function quick_add_city()
    {
        $name      = trim((string) $this->request->getPost('name'));
        $stateName = trim((string) $this->request->getPost('state_name'));
        $stateId   = (int) $this->request->getPost('state_id');
        if ($name === '') { return $this->response->setJSON(['status' => 'error', 'message' => 'Name required']); }
        $db = \Config\Database::connect();
        if (! $db->tableExists('am_city')) { return $this->response->setJSON(['status' => 'error', 'message' => 'City master unavailable']); }
        if (! $stateId && $stateName !== '' && $db->tableExists('am_state')) {
            $s = $db->table('am_state')->where('LOWER(name)', strtolower($stateName))->get()->getRow();
            if ($s) { $stateId = (int) $s->state_id; }
        }
        $row = $db->table('am_city')->where('LOWER(name)', strtolower($name))->where('state_id', $stateId)->get()->getRow();
        if (! $row) {
            $db->table('am_city')->insert(['name' => $name, 'state_id' => $stateId, 'status' => 'Active']);
        }
        return $this->response->setJSON(['status' => 'success', 'state_id' => $stateId]);
    }

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\accountname\listing', [
            'title'   => 'Account Master · C R Industries ERP',
            'summary' => (new AccountNameModel())->listingSummary(),
        ]);
    }

    public function view_all()
    {
        $model = new AccountNameModel();
        $total = $model->countBillingData();
        $rows  = $model->getBillingData();

        $data = [];
        $j    = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $r) {
            $row = (array) $r;
            $j++;
            $aid = (int) $row['account_id'];

            $gst = trim((string) ($row['purchaser_gst_no'] ?? ''));
            $gstCell = $gst !== ''
                ? esc($gst) . (! empty($row['gst_verified']) ? ' <span class="label label-success">Verified</span>' : '')
                : '<span class="text-muted">No GST</span>';

            $status = (string) ($row['status'] ?? 'Active');
            $statusCell = $status === 'Active' ? '<span class="label label-success">Active</span>'
                : ($status === 'Inactive' ? '<span class="label label-warning">Inactive</span>'
                : '<span class="label label-danger">' . esc($status) . '</span>');

            $src = (isset($row['entry_source']) && strtolower($row['entry_source']) === 'app') ? 'App' : 'Web';
            $farmer = ! empty($row['is_farmer']) ? ' <span class="label label-info">Kisan</span>' : '';

            $data[] = [
                $j,
                '<a href="' . base_url('admin/account_name/view/' . ID_encode($aid)) . '">' . $aid . '</a>',
                esc($row['name'] ?? '') . $farmer,
                esc($row['contact_person_name'] ?? ''),
                $gstCell,
                esc($src),
                $statusCell,
                $this->rowActions($aid, $status),
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    public function updateStatus()
    {
        $id = (int) $this->request->getPost('id');
        if (! $id) { return $this->response->setJSON(['status' => 'error']); }
        (new AccountNameModel())->setStatus($id, (string) $this->request->getPost('status'));
        return $this->response->setJSON(['status' => 'success']);
    }

    public function soft_delete()
    {
        $id = (int) $this->request->getPost('id');
        if (! $id) { return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid account']); }
        $model = new AccountNameModel();
        if ($model->hasRokad($id)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'This account has cash-book entries and cannot be deleted.']);
        }
        $model->softDelete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function restore()
    {
        $id = (int) $this->request->getPost('id');
        if ($id) { (new AccountNameModel())->restore($id); }
        return $this->response->setJSON(['status' => 'success']);
    }

    public function quick_update()
    {
        $id = (int) $this->request->getPost('id');
        if (! $id) { return $this->response->setJSON(['status' => 'error']); }
        (new AccountNameModel())->quickUpdate($id, [
            'name'                  => trim((string) $this->request->getPost('name')),
            'contact_person_name'   => trim((string) $this->request->getPost('contact_person_name')),
            'contact_person_number' => trim((string) $this->request->getPost('contact_person_number')),
            'purchaser_gst_no'      => trim((string) $this->request->getPost('purchaser_gst_no')),
            'status'                => (string) $this->request->getPost('status'),
        ]);
        return $this->response->setJSON(['status' => 'success']);
    }

    private function rowActions(int $id, string $status): string
    {
        $enc = ID_encode($id);
        $a = '<a class="btn btn-xs btn-default" href="' . base_url('admin/account_name/view/' . $enc) . '" title="View"><i class="fa fa-eye"></i></a> ';
        if ($status === 'Active') {
            $a .= '<button class="btn btn-xs btn-warning acc-toggle" data-id="' . $id . '" data-status="Inactive" title="Deactivate"><i class="fa fa-pause"></i></button> ';
        } else {
            $a .= '<button class="btn btn-xs btn-success acc-toggle" data-id="' . $id . '" data-status="Active" title="Activate"><i class="fa fa-check"></i></button> ';
        }
        $a .= '<button class="btn btn-xs btn-danger acc-del" data-id="' . $id . '" title="Delete"><i class="fa fa-trash"></i></button>';
        return '<div class="text-nowrap">' . $a . '</div>';
    }
}
