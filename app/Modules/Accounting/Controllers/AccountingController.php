<?php

namespace Modules\Accounting\Controllers;

use App\Controllers\BaseController;
use App\Models\AccountingGroupModel;
use App\Models\LedgerModel;
use App\Models\VoucherModel;
use Config\Database;

/**
 * Accounting overview + group management for the ACTIVE firm.
 */
class AccountingController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected AccountingGroupModel $groups;

    public function __construct()
    {
        $this->groups = new AccountingGroupModel();
    }

    private function cid(): int
    {
        return (int) company_id();
    }

    public function index()
    {
        $db  = Database::connect();
        $cid = $this->cid();

        // Group list with ledger counts.
        $groups = $this->groups->forCompany($cid);
        $counts = [];
        foreach ($db->table('ledgers')->select('group_id, COUNT(*) AS c')->where('company_id', $cid)->where('deleted_at', null)->groupBy('group_id')->get()->getResultArray() as $r) {
            $counts[(int) $r['group_id']] = (int) $r['c'];
        }

        return $this->render('overview', [
            'title'      => 'Accounting',
            'breadcrumb' => [['label' => 'Accounting']],
            'groups'     => $groups,
            'ledgerCounts' => $counts,
            'stats'      => [
                'groups'   => count($groups),
                'ledgers'  => (new LedgerModel())->where('company_id', $cid)->countAllResults(),
                'vouchers' => (new VoucherModel())->where('company_id', $cid)->countAllResults(),
            ],
            'canEdit'    => firm_can('accounting', 'edit'),
        ]);
    }

    public function storeGroup()
    {
        if (! $this->validate(['name' => 'required|max_length[100]', 'nature' => 'required|in_list[Assets,Liabilities,Income,Expenses]'])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }
        $this->groups->insert([
            'company_id' => $this->cid(),
            'name'       => trim((string) $this->request->getPost('name')),
            'nature'     => (string) $this->request->getPost('nature'),
            'is_default' => 0,
        ]);
        activity_log('Accounting', 'Add', 'Accounting group added');
        return redirect()->to(site_url('accounting'))->with('success', 'Group added.');
    }

    public function deleteGroup($id = null)
    {
        $id  = (int) $id;
        $row = $this->groups->where('company_id', $this->cid())->find($id);
        if (! $row) {
            return redirect()->back()->with('error', 'Group not found.');
        }
        if ((int) $row['is_default'] === 1) {
            return redirect()->back()->with('error', 'Default groups cannot be deleted.');
        }
        if (Database::connect()->table('ledgers')->where('group_id', $id)->where('deleted_at', null)->countAllResults() > 0) {
            return redirect()->back()->with('error', 'This group has ledgers and cannot be deleted.');
        }
        $this->groups->delete($id);
        activity_log('Accounting', 'Delete', "Accounting group #{$id} deleted");
        return redirect()->to(site_url('accounting'))->with('success', 'Group deleted.');
    }
}
