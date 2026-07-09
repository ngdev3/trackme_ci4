<?php

namespace Modules\Accounting\Controllers;

use App\Controllers\BaseController;
use App\Models\LedgerModel;
use App\Models\VoucherEntryModel;
use App\Models\VoucherModel;
use Config\Database;

/**
 * Accounting vouchers (transactions) for the ACTIVE firm. Every voucher is a
 * balanced double-entry (total debits = total credits) and is firm-scoped.
 */
class VoucherController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected VoucherModel $vouchers;
    protected VoucherEntryModel $entries;
    protected LedgerModel $ledgers;

    public function __construct()
    {
        $this->vouchers = new VoucherModel();
        $this->entries  = new VoucherEntryModel();
        $this->ledgers  = new LedgerModel();
    }

    private function cid(): int
    {
        return (int) company_id();
    }

    /** Day book. */
    public function index()
    {
        $from = (string) $this->request->getGet('from');
        $to   = (string) $this->request->getGet('to');
        $type = (string) $this->request->getGet('type');

        return $this->render('vouchers', [
            'title'      => 'Day Book',
            'breadcrumb' => [['label' => 'Accounting'], ['label' => 'Day Book']],
            'rows'       => $this->vouchers->dayBook($this->cid(), $from ?: null, $to ?: null, $type)->paginate(20),
            'pager'      => $this->vouchers->pager,
            'from'       => $from,
            'to'         => $to,
            'type'       => $type,
            'canEdit'    => firm_can('accounting', 'edit'),
        ]);
    }

    public function create()
    {
        $ledgers = $this->ledgers->optionsForCompany($this->cid());
        if ($ledgers === []) {
            return redirect()->to(site_url('accounting/ledgers/create'))->with('info', 'Create at least two ledgers before recording a voucher.');
        }
        return $this->render('voucher_form', [
            'title'      => 'New Voucher',
            'breadcrumb' => [['label' => 'Day Book', 'url' => site_url('accounting/vouchers')], ['label' => 'New']],
            'ledgers'    => $ledgers,
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function store()
    {
        $type      = (string) $this->request->getPost('voucher_type');
        $date      = (string) $this->request->getPost('date');
        $narration = trim((string) $this->request->getPost('narration')) ?: null;
        $ledgerIds = (array) $this->request->getPost('ledger_id');
        $drs       = (array) $this->request->getPost('dr_amount');
        $crs       = (array) $this->request->getPost('cr_amount');

        if (! in_array($type, VoucherModel::TYPES, true) || $date === '') {
            return redirect()->back()->withInput()->with('errors', ['A voucher type and date are required.']);
        }

        // Build valid lines (a line needs a ledger + a positive Dr or Cr).
        $lines = [];
        $totalDr = 0.0;
        $totalCr = 0.0;
        foreach ($ledgerIds as $i => $lid) {
            $lid = (int) $lid;
            $dr  = round((float) ($drs[$i] ?? 0), 2);
            $cr  = round((float) ($crs[$i] ?? 0), 2);
            if ($lid <= 0 || ($dr <= 0 && $cr <= 0)) {
                continue;
            }
            // A line is either a debit or a credit, not both.
            if ($dr > 0 && $cr > 0) {
                return redirect()->back()->withInput()->with('errors', ['Each line must be either a debit or a credit, not both.']);
            }
            // Ledger must belong to this firm (isolation).
            if (! $this->ledgers->findForCompany($lid, $this->cid())) {
                return redirect()->back()->withInput()->with('errors', ['Invalid ledger selected.']);
            }
            $lines[] = ['ledger_id' => $lid, 'dr' => $dr, 'cr' => $cr];
            $totalDr += $dr;
            $totalCr += $cr;
        }

        if (count($lines) < 2) {
            return redirect()->back()->withInput()->with('errors', ['A voucher needs at least two lines.']);
        }
        if (abs($totalDr - $totalCr) > 0.001 || $totalDr <= 0) {
            return redirect()->back()->withInput()->with('errors', ['Debits and credits must balance (Dr ' . number_format($totalDr, 2) . ' ≠ Cr ' . number_format($totalCr, 2) . ').']);
        }

        $db = Database::connect();
        $db->transStart();

        $voucherId = (int) $this->vouchers->insert([
            'company_id'   => $this->cid(),
            'voucher_type' => $type,
            'voucher_no'   => $this->vouchers->nextNumber($this->cid(), $type),
            'date'         => date('Y-m-d', strtotime($date)),
            'narration'    => $narration,
            'amount'       => $totalDr,
            'created_by'   => (int) user_id(),
        ], true);

        foreach ($lines as $ln) {
            $this->entries->insert([
                'voucher_id' => $voucherId,
                'company_id' => $this->cid(),
                'ledger_id'  => $ln['ledger_id'],
                'dr_amount'  => $ln['dr'],
                'cr_amount'  => $ln['cr'],
            ]);
        }

        $db->transComplete();
        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Could not save the voucher. Please try again.');
        }

        activity_log('Accounting', 'Add', "Voucher #{$voucherId} ({$type}) recorded");
        return redirect()->to(site_url('accounting/vouchers'))->with('success', 'Voucher recorded.');
    }

    public function view($id = null)
    {
        $voucher = $this->vouchers->findForCompany((int) $id, $this->cid());
        if (! $voucher) {
            return redirect()->to(site_url('accounting/vouchers'))->with('error', 'Voucher not found.');
        }
        return $this->render('voucher_view', [
            'title'      => 'Voucher ' . ($voucher['voucher_no'] ?? ''),
            'breadcrumb' => [['label' => 'Day Book', 'url' => site_url('accounting/vouchers')], ['label' => 'View']],
            'voucher'    => $voucher,
            'entries'    => $this->entries->forVoucher((int) $id),
        ]);
    }

    public function delete($id = null)
    {
        $id = (int) $id;
        if (! $this->vouchers->findForCompany($id, $this->cid())) {
            return redirect()->to(site_url('accounting/vouchers'))->with('error', 'Voucher not found.');
        }
        $this->vouchers->delete($id); // soft delete keeps the audit trail
        activity_log('Accounting', 'Delete', "Voucher #{$id} deleted");
        return redirect()->to(site_url('accounting/vouchers'))->with('success', 'Voucher deleted.');
    }
}
