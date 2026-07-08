<?php

namespace Modules\Transactions\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ReminderService;
use App\Models\ReminderModel;
use App\Models\TransactionAttachmentModel;
use App\Models\TransactionModel;
use Config\Services;

/**
 * Jama (money received) / Naam (money paid) transaction register — a clean,
 * TrackMe-style ledger with search, filters, a running balance, unlimited
 * attachments, reports and exports.
 *
 * Scope: per-company (each company keeps its own Hisaab-Kitaab Vahi). Switching
 * the active company shows that company's book. The Super Admin sees and manages
 * every company's rows (scope resolves to null → no company filter).
 */
class TransactionController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected string $moduleCode = 'transactions';
    protected string $baseRoute  = 'transactions';

    protected TransactionModel $txns;
    protected TransactionAttachmentModel $files;

    public function __construct()
    {
        $this->txns  = new TransactionModel();
        $this->files = new TransactionAttachmentModel();
    }

    private function uid(): int
    {
        return (int) user_id();
    }

    /** Query scope: null for the Super Admin (all rows), else the active company. */
    private function scope(): ?int
    {
        return Services::acl()->isSuperAdmin() ? null : company_id();
    }

    private function filters(): array
    {
        return [
            'q'      => trim((string) $this->request->getGet('q')),
            'from'   => (string) $this->request->getGet('from'),
            'to'     => (string) $this->request->getGet('to'),
            'status' => (string) $this->request->getGet('status'),
            'type'   => (string) $this->request->getGet('type'),
            'mode'   => (string) $this->request->getGet('mode'),
        ];
    }

    // ===============================================================
    // Listing
    // ===============================================================
    public function index()
    {
        // Announce any of the company's reminders that have come due.
        (new ReminderService())->fireDueForCompany(company_id());

        $scope = $this->scope();
        $f     = $this->filters();

        $per = (int) $this->request->getGet('per');
        $per = in_array($per, [10, 25, 50, 100], true) ? $per : 10;

        $rows = $this->txns->page($scope, $f, $per);

        // Opening cash carried into the ledger window: the Shri Rokad Nagad
        // (opening balance) plus the net of everything before the window start.
        // Window start = the "From" filter, else the book's first entry.
        $ob      = new \App\Libraries\OpeningBalance($scope, (int) company_id());
        $effFrom = $f['from'] ?: $this->txns->earliestDate($scope);
        $opening = $effFrom ? $ob->carryInto($effFrom) : 0.0;

        // Running balance for the visible page (newest → oldest), seeded with the
        // opening so each row shows the true cash-in-hand at that point.
        if ($rows !== []) {
            $running = $opening + $this->txns->balanceUpTo($scope, $f, $rows[0]['txn_date'], (int) $rows[0]['id']);
            foreach ($rows as $i => &$r) {
                if ($i > 0) {
                    $prev = $rows[$i - 1];
                    $running -= ($prev['type'] === 'jama' ? (float) $prev['amount'] : -(float) $prev['amount']);
                }
                $r['balance'] = round($running, 2);
            }
            unset($r);
        }

        $counts  = $this->files->countsFor(array_column($rows, 'id'));
        $summary = $this->txns->summary($scope, $f);
        // Closing = opening cash + net of the filtered set (jama − naam).
        $summary['opening'] = round($opening, 2);
        $summary['closing'] = round($opening + $summary['net'], 2);

        // Chart data: daily Jama/Naam trend over the active range (default 30d).
        $trendFrom = $f['from'] ?: date('Y-m-d', strtotime('-29 days'));
        $trendTo   = $f['to'] ?: date('Y-m-d');

        return $this->render('index', [
            'title'      => 'Transactions',
            'breadcrumb' => [['label' => 'Transactions']],
            'rows'       => $rows,
            'counts'     => $counts,
            'pager'      => $this->txns->pager,
            'summary'    => $summary,
            'openLabel'  => $ob->label(),
            'byMode'     => $this->txns->byMode($scope, $f),
            'trend'      => $this->txns->dailyBuckets($scope, $trendFrom, $trendTo),
            'filters'    => $f,
            'per'        => $per,
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
            'css'        => [base_url('assets/css/tm-table.css'), base_url('assets/css/transactions.css')],
            'js'         => [base_url('assets/vendor/chart/chart.umd.min.js')],
        ]);
    }

    // ===============================================================
    // Account (party) statement — searchable per-account ledger
    // ===============================================================

    /**
     * Build the statement dataset for the chosen party (account) and range.
     * Running balance is seeded with the party's opening (net before `from`).
     */
    private function statementData(): array
    {
        $scope = $this->scope();
        $party = trim((string) $this->request->getGet('party'));
        $from  = (string) $this->request->getGet('from');
        $to    = (string) $this->request->getGet('to');

        // Only accept well-formed dates; swap if reversed.
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : '';
        $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : '';
        if ($from !== '' && $to !== '' && $to < $from) {
            [$from, $to] = [$to, $from];
        }

        $data = ['party' => $party, 'from' => $from, 'to' => $to, 'hasParty' => false];

        if ($party !== '') {
            $rows    = $this->txns->partyRows($scope, $party, $from ?: null, $to ?: null);
            $opening = $from !== '' ? round($this->txns->partyNetBefore($scope, $party, $from), 2) : 0.0;

            $running = $opening;
            foreach ($rows as &$r) {
                $running += ($r['type'] === 'jama' ? (float) $r['amount'] : -(float) $r['amount']);
                $r['balance'] = round($running, 2);
            }
            unset($r);

            [$jama, $naam] = $this->txns->partyTotals($scope, $party, $from ?: null, $to ?: null);
            $data = array_merge($data, [
                'hasParty'  => true,
                'rows'      => $rows,
                'opening'   => $opening,
                'totalJama' => $jama,
                'totalNaam' => $naam,
                'closing'   => round($opening + $jama - $naam, 2),
                'count'     => count($rows),
            ]);
        }

        return $data;
    }

    /** On-screen account statement with a search/browse picker. */
    public function statement()
    {
        $data = $this->statementData();
        return $this->render('statement', $data + [
            'title'      => 'Account Statement',
            'breadcrumb' => [['label' => 'Transactions', 'url' => site_url('transactions')], ['label' => 'Account Statement']],
            'parties'    => $this->txns->partyDirectory($this->scope()),
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
            'css'        => [base_url('assets/css/tm-table.css'), base_url('assets/css/transactions.css')],
        ]);
    }

    /** Print-friendly account statement (opens in a new tab). */
    public function statementPrint()
    {
        $data = $this->statementData();
        if (! $data['hasParty']) {
            return redirect()->to(site_url('transactions/statement'))->with('error', 'Choose an account to print its statement.');
        }
        return view('Modules\Transactions\Views\statement_print', $data + [
            'firm' => function_exists('current_company') ? current_company() : null,
        ]);
    }

    // ===============================================================
    // Create / edit / view
    // ===============================================================
    public function create()
    {
        return $this->render('form', $this->formData(null, 'create'));
    }

    public function edit($id = null)
    {
        $row = $this->txns->findScoped(unhid($id), $this->scope());
        if (! $row) {
            return redirect()->to(site_url('transactions/list'))->with('error', 'Transaction not found.');
        }
        return $this->render('form', $this->formData($row, 'edit'));
    }

    public function view($id = null)
    {
        $row = $this->txns->findScoped(unhid($id), $this->scope());
        if (! $row) {
            return redirect()->to(site_url('transactions/list'))->with('error', 'Transaction not found.');
        }
        return $this->render('view', [
            'title'       => 'Transaction ' . ($row['txn_no'] ?? ''),
            'breadcrumb'  => [['label' => 'Transactions', 'url' => site_url('transactions/list')], ['label' => 'View']],
            'row'         => $row,
            'attachments' => $this->files->forTransaction((int) $row['id']),
            'moduleCode'  => $this->moduleCode,
            'css'         => [base_url('assets/css/tm-table.css'), base_url('assets/css/transactions.css')],
        ]);
    }

    private function formData(?array $row, string $mode): array
    {
        return [
            'title'       => $mode === 'edit' ? 'Edit Transaction' : 'Add Transaction',
            'breadcrumb'  => [['label' => 'Transactions', 'url' => site_url('transactions/list')], ['label' => $mode === 'edit' ? 'Edit' : 'Add']],
            'row'         => $row,
            'mode'        => $mode,
            'nextNo'      => $mode === 'edit' ? ($row['txn_no'] ?? '') : $this->txns->nextTxnNo((int) company_id()),
            'attachments' => $row ? $this->files->forTransaction((int) $row['id']) : [],
            'errors'      => session()->getFlashdata('errors') ?? [],
            'moduleCode'  => $this->moduleCode,
            'css'         => [base_url('assets/css/transactions.css')],
        ];
    }

    // ===============================================================
    // Persist
    // ===============================================================
    public function store()
    {
        return $this->persist(null);
    }

    public function update($id = null)
    {
        $rid = unhid($id);
        if (! $rid) {
            return redirect()->to(site_url('transactions/list'))->with('error', 'Transaction not found.');
        }
        return $this->persist($rid);
    }

    private function persist(?int $id)
    {
        $rules = [
            'txn_date' => 'required|valid_date[Y-m-d]',
            'name'     => 'required|max_length[191]',
            'type'     => 'in_list[jama,naam]',
            'amount'   => 'required|numeric|greater_than[0]',
            'status'   => 'in_list[paid,pending,overdue,cancelled,draft]',
            'payment_mode' => 'permit_empty|in_list[cash,bank,upi,cheque,card,other]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $mode = (string) $this->request->getPost('payment_mode');
        $data = [
            'txn_date'     => date('Y-m-d', strtotime((string) $this->request->getPost('txn_date'))),
            'name'         => trim((string) $this->request->getPost('name')),
            'type'         => $this->request->getPost('type') === 'naam' ? 'naam' : 'jama',
            'amount'       => round((float) $this->request->getPost('amount'), 2),
            'payment_mode' => in_array($mode, TransactionModel::MODES, true) ? $mode : 'cash',
            'status'       => (string) $this->request->getPost('status'),
            'notes'        => trim((string) $this->request->getPost('notes')) ?: null,
        ];

        if ($id) {
            $existing = $this->txns->findScoped($id, $this->scope());
            if (! $existing) {
                return redirect()->to(site_url('transactions/list'))->with('error', 'Transaction not found.');
            }
            $this->txns->update($id, $data);
            $this->saveUploads($id, (int) $existing['user_id']);
            $remindAt = trim((string) $this->request->getPost('remind_at'));
            if ($remindAt !== '') {
                $this->upsertReminder(array_merge($existing, $data, ['id' => $id]), $remindAt);
            }
            activity_log('Transactions', 'Edit', "Transaction {$existing['txn_no']} updated");
            return redirect()->to(site_url('transactions/view/' . hid($id)))->with('success', 'Transaction updated.');
        }

        $data['user_id']    = $this->uid();
        $data['company_id'] = company_id();
        $data['source']     = 'web';
        $data['txn_no']     = $this->txns->nextTxnNo((int) company_id());
        $newId              = (int) $this->txns->insert($data);
        $this->saveUploads($newId, $this->uid());
        $remindAt = trim((string) $this->request->getPost('remind_at'));
        if ($remindAt !== '') {
            $this->upsertReminder($data + ['id' => $newId], $remindAt);
        }
        activity_log('Transactions', 'Add', "Transaction {$data['txn_no']} added");

        return redirect()->to(site_url('transactions/view/' . hid($newId)))->with('success', 'Transaction ' . $data['txn_no'] . ' added.');
    }

    public function delete($id = null)
    {
        $id  = unhid($id);
        $row = $this->txns->findScoped($id, $this->scope());
        if (! $row) {
            return redirect()->back()->with('error', 'Transaction not found.');
        }
        // A reason is mandatory and recorded for the audit trail.
        $reason = trim((string) $this->request->getPost('reason'));
        if ($reason === '') {
            return redirect()->back()->with('error', 'Please provide a reason for deletion.');
        }
        // Keep attachments intact so the entry can be restored with its files.
        $this->txns->update($id, ['delete_reason' => mb_substr($reason, 0, 255)]);
        $this->txns->delete($id);
        activity_log('Transactions', 'Delete', "Transaction {$row['txn_no']} deleted — reason: {$reason}");
        return redirect()->back()->with('success', 'Transaction ' . $row['txn_no'] . ' deleted.');
    }

    // ===============================================================
    // Entry modal (view details + attachments + reminder) — AJAX fragment
    // ===============================================================
    public function entryModal($id = null)
    {
        $row = $this->txns->findScoped(unhid($id), $this->scope());
        if (! $row) {
            return $this->response->setStatusCode(404)->setBody('<div class="p-4 text-center text-secondary">Entry not found.</div>');
        }
        return view('Modules\Transactions\Views\entry_modal', [
            'row'         => $row,
            'attachments' => $this->files->forTransaction((int) $row['id']),
            'reminder'    => $this->reminderFor((int) $row['id']),
            'moduleCode'  => $this->moduleCode,
        ]);
    }

    // ===============================================================
    // Reminders
    // ===============================================================
    /** The current user's latest reminder linked to a transaction (if any). */
    private function reminderFor(int $txnId): ?array
    {
        return (new ReminderModel())
            ->where('attach_module', 'transactions')
            ->where('attach_ref', (string) $txnId)
            ->where('user_id', $this->uid())
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Create or update a reminder linked to a transaction. When due, the shared
     * ReminderService announces it (Notifications) and it appears in Reminders.
     */
    private function upsertReminder(array $row, string $remindAt, string $priority = 'medium'): bool
    {
        $ts = strtotime($remindAt);
        if (! $ts) {
            return false;
        }
        $rm   = new ReminderModel();
        $data = [
            'user_id'       => (int) $row['user_id'],
            'title'         => mb_substr('Rokad entry: ' . $row['name'] . ' (' . $row['txn_no'] . ')', 0, 191),
            'description'   => ucfirst($row['type']) . ' ' . money((float) $row['amount'])
                . ($row['notes'] ? ' — ' . $row['notes'] : '') . ' · ' . fmt_date($row['txn_date']),
            'remind_at'     => date('Y-m-d H:i:s', $ts),
            'priority'      => in_array($priority, ['low', 'medium', 'high'], true) ? $priority : 'medium',
            'status'        => 'pending',
            'attach_module' => 'transactions',
            'attach_ref'    => (string) $row['id'],
        ];

        $existing = $rm->where('attach_module', 'transactions')
            ->where('attach_ref', (string) $row['id'])
            ->where('user_id', (int) $row['user_id'])
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            // Reset the notified flag so the new time fires again.
            $rm->update($existing['id'], $data + ['notified' => 0, 'last_notified_at' => null]);
        } else {
            $rm->insert($data + ['notified' => 0]);
        }
        return true;
    }

    public function setReminder($id = null)
    {
        $row = $this->txns->findScoped(unhid($id), $this->scope());
        if (! $row) {
            return redirect()->back()->with('error', 'Transaction not found.');
        }
        $remindAt = (string) $this->request->getPost('remind_at');
        $priority = (string) $this->request->getPost('priority');
        if (! $this->upsertReminder($row, $remindAt, $priority)) {
            return redirect()->back()->with('error', 'Please choose a valid reminder date & time.');
        }
        activity_log('Transactions', 'Edit', "Reminder set for {$row['txn_no']} at " . date('Y-m-d H:i', strtotime($remindAt)));
        return redirect()->back()->with('success', 'Reminder set for ' . date('d M Y, H:i', strtotime($remindAt)) . '.');
    }

    // ===============================================================
    // Attachments
    // ===============================================================
    private function uploadDir(int $ownerId): string
    {
        $dir = WRITEPATH . 'uploads/transactions/' . $ownerId . '/';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private function unlinkFile(int $ownerId, string $stored): void
    {
        $path = $this->uploadDir($ownerId) . $stored;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /** Move any uploaded files (input name="attachments[]") onto a transaction. */
    private function saveUploads(int $txnId, int $ownerId): void
    {
        $uploaded = $this->request->getFileMultiple('attachments');
        if (! $uploaded) {
            return;
        }
        $dir = $this->uploadDir($ownerId);
        foreach ($uploaded as $file) {
            if (! $file || ! $file->isValid() || $file->hasMoved()) {
                continue;
            }
            if ($file->getSizeByUnit('mb') > 25) {
                continue; // skip oversized (25 MB cap)
            }
            $ext    = strtolower($file->getExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
            $stored = $file->getRandomName();
            try {
                $file->move($dir, $stored);
            } catch (\Throwable $e) {
                log_message('error', 'Attachment move failed: ' . $e->getMessage());
                continue;
            }
            $this->files->insert([
                'transaction_id' => $txnId,
                'user_id'        => $ownerId,
                'company_id'     => company_id(),
                'original_name'  => $file->getClientName(),
                'stored_name'    => $stored,
                'mime'           => $file->getClientMimeType(),
                'kind'           => TransactionAttachmentModel::kindFor((string) $file->getClientMimeType(), (string) $ext),
                'size'           => (int) filesize($dir . $stored),
                'created_by'     => $this->uid(),
            ]);
        }
    }

    /** Attach files to an existing transaction (from the detail page). */
    public function attach($id = null)
    {
        $row = $this->txns->findScoped(unhid($id), $this->scope());
        if (! $row) {
            return redirect()->to(site_url('transactions/list'))->with('error', 'Transaction not found.');
        }
        $this->saveUploads((int) $row['id'], (int) $row['user_id']);
        activity_log('Transactions', 'Edit', "Attachment(s) added to {$row['txn_no']}");
        return redirect()->to(site_url('transactions/view/' . hid($row['id'])))->with('success', 'Attachment(s) uploaded.');
    }

    private function findAttachment(int $attId): ?array
    {
        $att = $this->files->find($attId);
        if (! $att) {
            return null;
        }
        // Ownership: the parent transaction must be in scope.
        if (! $this->txns->findScoped((int) $att['transaction_id'], $this->scope())) {
            return null;
        }
        return $att;
    }

    public function download($attId = null)
    {
        return $this->stream(unhid($attId), true);
    }

    public function preview($attId = null)
    {
        return $this->stream(unhid($attId), false);
    }

    private function stream(int $attId, bool $forceDownload)
    {
        $att = $this->findAttachment($attId);
        if (! $att) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $path = $this->uploadDir((int) $att['user_id']) . $att['stored_name'];
        if (! is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if ($forceDownload) {
            return $this->response->download($path, null)->setFileName($att['original_name']);
        }
        return $this->response
            ->setHeader('Content-Type', $att['mime'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'inline; filename="' . rawurlencode($att['original_name']) . '"')
            ->setBody(file_get_contents($path));
    }

    public function replaceAttachment($attId = null)
    {
        $att = $this->findAttachment(unhid($attId));
        if (! $att) {
            return redirect()->to(site_url('transactions/list'))->with('error', 'Attachment not found.');
        }
        $file = $this->request->getFile('replacement');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Please choose a valid file.');
        }
        $ownerId = (int) $att['user_id'];
        $dir     = $this->uploadDir($ownerId);
        $ext     = strtolower($file->getExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
        $stored  = $file->getRandomName();
        try {
            $file->move($dir, $stored);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Upload failed.');
        }
        $this->unlinkFile($ownerId, $att['stored_name']);
        $this->files->update($att['id'], [
            'original_name' => $file->getClientName(),
            'stored_name'   => $stored,
            'mime'          => $file->getClientMimeType(),
            'kind'          => TransactionAttachmentModel::kindFor((string) $file->getClientMimeType(), (string) $ext),
            'size'          => (int) filesize($dir . $stored),
        ]);
        activity_log('Transactions', 'Edit', "Attachment #{$att['id']} replaced");
        return redirect()->to(site_url('transactions/view/' . hid($att['transaction_id'])))->with('success', 'Attachment replaced.');
    }

    public function deleteAttachment($attId = null)
    {
        $att = $this->findAttachment(unhid($attId));
        if (! $att) {
            return redirect()->to(site_url('transactions/list'))->with('error', 'Attachment not found.');
        }
        $this->unlinkFile((int) $att['user_id'], $att['stored_name']);
        $this->files->delete($att['id']);
        activity_log('Transactions', 'Delete', "Attachment #{$att['id']} deleted");
        return redirect()->to(site_url('transactions/view/' . hid($att['transaction_id'])))->with('success', 'Attachment deleted.');
    }
}
