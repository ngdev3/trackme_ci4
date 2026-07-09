<?php

namespace Modules\Accounting\Controllers;

use App\Controllers\BaseController;
use App\Models\AccountingGroupModel;
use App\Models\LedgerModel;
use App\Models\VoucherEntryModel;

/**
 * Ledger accounts for the ACTIVE firm, filed under accounting groups.
 * Everything is scoped to company_id() so ledgers never cross firms.
 */
class LedgerController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected LedgerModel $ledgers;
    protected AccountingGroupModel $groups;

    public function __construct()
    {
        $this->ledgers = new LedgerModel();
        $this->groups  = new AccountingGroupModel();
    }

    private function cid(): int
    {
        return (int) company_id();
    }

    public function index()
    {
        return $this->render('ledgers', [
            'title'      => 'Ledgers',
            'breadcrumb' => [['label' => 'Accounting'], ['label' => 'Ledgers']],
            'rows'       => $this->ledgers->forCompany($this->cid())->findAll(),
            'canEdit'    => firm_can('accounting', 'edit'),
        ]);
    }

    public function create()
    {
        return $this->render('ledger_form', $this->formData(null, 'create'));
    }

    public function edit($id = null)
    {
        $row = $this->ledgers->findForCompany((int) $id, $this->cid());
        if (! $row) {
            return redirect()->to(site_url('accounting/ledgers'))->with('error', 'Ledger not found.');
        }
        return $this->render('ledger_form', $this->formData($row, 'edit'));
    }

    private function formData(?array $row, string $mode): array
    {
        return [
            'title'      => $mode === 'edit' ? 'Edit Ledger' : 'Add Ledger',
            'breadcrumb' => [['label' => 'Ledgers', 'url' => site_url('accounting/ledgers')], ['label' => $mode === 'edit' ? 'Edit' : 'Add']],
            'row'        => $row,
            'mode'       => $mode,
            'groups'     => $this->groups->forCompany($this->cid()),
            'errors'     => session()->getFlashdata('errors') ?? [],
        ];
    }

    public function store()
    {
        if (! $this->validate(['name' => 'required|max_length[150]', 'group_id' => 'required'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $this->ledgers->insert($this->input());
        activity_log('Accounting', 'Add', 'Ledger created in firm #' . $this->cid());
        return redirect()->to(site_url('accounting/ledgers'))->with('success', 'Ledger created.');
    }

    public function update($id = null)
    {
        $id  = (int) $id;
        if (! $this->ledgers->findForCompany($id, $this->cid())) {
            return redirect()->to(site_url('accounting/ledgers'))->with('error', 'Ledger not found.');
        }
        if (! $this->validate(['name' => 'required|max_length[150]', 'group_id' => 'required'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $this->ledgers->update($id, $this->input());
        activity_log('Accounting', 'Edit', "Ledger #{$id} updated");
        return redirect()->to(site_url('accounting/ledgers'))->with('success', 'Ledger updated.');
    }

    public function delete($id = null)
    {
        $id  = (int) $id;
        if (! $this->ledgers->findForCompany($id, $this->cid())) {
            return redirect()->to(site_url('accounting/ledgers'))->with('error', 'Ledger not found.');
        }
        // Guard: do not delete a ledger that has postings.
        if ((new VoucherEntryModel())->where('ledger_id', $id)->where('company_id', $this->cid())->countAllResults() > 0) {
            return redirect()->back()->with('error', 'This ledger has transactions and cannot be deleted.');
        }
        $this->ledgers->delete($id);
        activity_log('Accounting', 'Delete', "Ledger #{$id} deleted");
        return redirect()->to(site_url('accounting/ledgers'))->with('success', 'Ledger deleted.');
    }

    public function statement($id = null)
    {
        $id     = (int) $id;
        $ledger = $this->ledgers->findForCompany($id, $this->cid());
        if (! $ledger) {
            return redirect()->to(site_url('accounting/ledgers'))->with('error', 'Ledger not found.');
        }
        $entries = (new VoucherEntryModel())->statement($id, $this->cid());

        // Running balance starting from the opening balance.
        $running = ($ledger['opening_type'] === 'Cr' ? -1 : 1) * (float) $ledger['opening_balance'];
        foreach ($entries as &$e) {
            $running += (float) $e['dr_amount'] - (float) $e['cr_amount'];
            $e['balance'] = $running;
        }

        return $this->render('ledger_statement', [
            'title'      => 'Ledger: ' . $ledger['name'],
            'breadcrumb' => [['label' => 'Ledgers', 'url' => site_url('accounting/ledgers')], ['label' => 'Statement']],
            'ledger'     => $ledger,
            'entries'    => $entries,
            'closing'    => $running,
        ]);
    }

    private function input(): array
    {
        return [
            'company_id'      => $this->cid(),
            'group_id'        => ((int) $this->request->getPost('group_id')) ?: null,
            'name'            => trim((string) $this->request->getPost('name')),
            'opening_balance' => (float) $this->request->getPost('opening_balance'),
            'opening_type'    => $this->request->getPost('opening_type') === 'Cr' ? 'Cr' : 'Dr',
            'gst_number'      => trim((string) $this->request->getPost('gst_number')) ?: null,
            'contact'         => trim((string) $this->request->getPost('contact')) ?: null,
            'notes'           => trim((string) $this->request->getPost('notes')) ?: null,
        ];
    }
}
