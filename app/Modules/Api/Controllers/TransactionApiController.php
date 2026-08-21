<?php

namespace Modules\Api\Controllers;

use App\Libraries\OpeningBalance;
use App\Models\CompanySettingModel;
use App\Models\TransactionAttachmentModel;
use App\Models\TransactionModel;

/**
 * Jama / Naam (Hisaab Kitaab Vahi) REST API for the mobile app. Bearer-token
 * authenticated (see BaseApiController). The company is resolved from the
 * caller's membership, so one firm's ledger can never be written by another.
 * Mirrors the web TransactionController::persist() insert path.
 */
class TransactionApiController extends BaseApiController
{
    /** Attachment policy — enforced server-side regardless of the client. */
    private const ATTACH_MAX     = 5;
    private const ATTACH_MAX_MB  = 10;
    /**
     * Extension allowlist. Covers the app's Camera/Gallery photos, voice notes,
     * and document uploads. Deliberately excludes executables/scripts AND svg
     * (SVG can carry inline script — never serve it inline). Keep in sync with
     * the streaming Content-Type map in attachment().
     */
    private const ATTACH_EXT     = [
        // documents
        'pdf', 'csv', 'xls', 'xlsx',
        // images (photos)
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic',
        // audio (voice notes)
        'm4a', 'mp3', 'aac', 'ogg', 'wav', 'webm',
    ];

    /**
     * GET api/v1/transactions/list — recent cash-book entries (Rokadh Parcha)
     * for the caller's company, newest first, with jama/naam/net totals.
     * Optional `q` filters by name / notes / txn_no.
     */
    public function list()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->resolveCompanyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $model = new TransactionModel();
        $f     = ['q' => trim((string) ($this->request->getGet('q') ?? ''))];

        // Optional single-day view (Rokad Parcha): scope entries + totals to one
        // date, and report the running cash balance (jama − naam) up to that day.
        $date = trim((string) ($this->request->getGet('date') ?? ''));
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) !== false) {
            $f['from'] = $date;
            $f['to']   = $date;
        } else {
            $date = '';
        }

        $rows    = $model->limitedFiltered($cid, $f, $date !== '' ? 500 : 100, 0);
        $summary = $model->summary($cid, $f);

        // Final balance = true cash-in-hand through the selected day (or today when
        // no date is given): the Shri Rokad Nagad opening carried in, plus the net
        // Jama − Naam up to and including that day. carryInto() is the exclusive
        // opening, so we anchor at the day AFTER to make it inclusive. When a
        // search filter is active we fall back to the plain filtered net, since an
        // opening balance can't be meaningfully filtered by a query.
        if ($f['q'] !== '') {
            $finalBalance = $date !== ''
                ? (float) $model->summary($cid, ['q' => $f['q'], 'to' => $date])['net']
                : (float) $summary['net'];
        } else {
            $anchor       = $date !== '' ? $date : date('Y-m-d');
            $nextDay      = date('Y-m-d', strtotime($anchor . ' +1 day'));
            $finalBalance = (new OpeningBalance($cid, $cid))->carryInto($nextDay);
        }

        $entries = array_map(static fn (array $r): array => [
            'id'           => (int) $r['id'],
            'txn_no'       => $r['txn_no'],
            'date'         => $r['txn_date'],
            'name'         => $r['name'],
            'party_type'   => $r['party_type'],
            'type'         => $r['type'],
            'amount'       => (float) $r['amount'],
            'payment_mode' => $r['payment_mode'],
            'status'       => $r['status'],
            'notes'        => $r['notes'],
        ], $rows);

        return $this->respond([
            'status'        => 'ok',
            'date'          => $date !== '' ? $date : null,
            'entries'       => $entries,
            'final_balance' => round($finalBalance, 2),
            'summary' => [
                'jama'  => round((float) $summary['jama'], 2),
                'naam'  => round((float) $summary['naam'], 2),
                'net'   => round((float) $summary['net'], 2),
                'count' => (int) $summary['count'],
            ],
        ]);
    }

    /**
     * GET api/v1/transactions/report — Jama/Naam totals for a date range with
     * breakdowns by payment mode and party type. Defaults to the current month.
     */
    public function report()
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }

        $from = trim((string) ($this->request->getGet('from') ?? ''));
        $to   = trim((string) ($this->request->getGet('to') ?? ''));
        if ($from === '' || strtotime($from) === false) {
            $from = date('Y-m-01');
        }
        if ($to === '' || strtotime($to) === false) {
            $to = date('Y-m-d');
        }

        $model = new TransactionModel();
        $f     = ['from' => $from, 'to' => $to];

        $summary = $model->summary($cid, $f);

        // Opening cash carried into the range, and the closing after this range's
        // net — so the report reflects true cash-in-hand, not just the period net.
        $opening = round((new OpeningBalance($cid, $cid))->carryInto($from), 2);
        $closing = round($opening + (float) $summary['net'], 2);

        $byMode = [];
        foreach ($model->byMode($cid, $f) as $mode => $v) {
            $byMode[] = [
                'mode' => $mode,
                'jama' => round((float) $v['jama'], 2),
                'naam' => round((float) $v['naam'], 2),
            ];
        }

        $byParty = array_map(static fn (array $g): array => [
            'label' => $g['label'] !== '' ? $g['label'] : 'Unspecified',
            'count' => (int) $g['count'],
            'jama'  => round((float) $g['jama'], 2),
            'naam'  => round((float) $g['naam'], 2),
            'net'   => round((float) $g['net'], 2),
        ], $model->groupTotals($cid, 'party_type', $from, $to));

        // Account-wise ledger — one bucket per account NAME (the mobile Report tab
        // shows this, not the party-type grouping). Sorted by |net| desc so the
        // biggest accounts lead. Rows with no name fall under 'Unnamed'.
        $byAccount = array_map(static fn (array $g): array => [
            'label' => trim((string) $g['label']) !== '' ? $g['label'] : 'Unnamed',
            'count' => (int) $g['count'],
            'jama'  => round((float) $g['jama'], 2),
            'naam'  => round((float) $g['naam'], 2),
            'net'   => round((float) $g['net'], 2),
        ], $model->groupTotals($cid, 'name', $from, $to));
        usort($byAccount, static fn (array $a, array $b): int => abs((float) $b['net']) <=> abs((float) $a['net']));

        return $this->respond([
            'status'        => 'ok',
            'range'         => ['from' => $from, 'to' => $to],
            'summary'       => [
                'jama'    => round((float) $summary['jama'], 2),
                'naam'    => round((float) $summary['naam'], 2),
                'net'     => round((float) $summary['net'], 2),
                'count'   => (int) $summary['count'],
                'opening' => $opening,
                'closing' => $closing,
            ],
            'by_mode'       => $byMode,
            'by_party_type' => $byParty,
            'by_account'    => $byAccount,
        ]);
    }

    /**
     * GET api/v1/transactions/parties?q= — type-ahead account (party) suggestions
     * for the add/entry forms. Returns the caller's own previously-used party
     * names (most active first) filtered by the query, so the same account can be
     * picked again instead of retyped. Mirrors the web entry form's picker
     * (TransactionModel::searchParties). Empty `q` returns the top recent parties.
     */
    public function parties()
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }

        $q     = trim((string) ($this->request->getGet('q') ?? ''));
        $limit = (int) ($this->request->getGet('limit') ?? 20);

        $rows = (new TransactionModel())->searchParties($cid, $q, $limit);

        $parties = array_map(static fn (array $r): array => [
            'name'      => (string) $r['name'],
            'count'     => (int) $r['count'],
            'net'       => round((float) $r['net'], 2),
            'last_date' => $r['last_date'] ?? null,
        ], $rows);

        return $this->respond(['status' => 'ok', 'parties' => $parties]);
    }

    /**
     * GET api/v1/transactions/statement — per-account (party) ledger with a
     * running balance seeded by the account's opening (net before `from`), plus
     * jama/naam totals and closing. Mirrors TransactionController::statementData().
     */
    public function statement()
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $model = new TransactionModel();

        $party = trim((string) ($this->request->getGet('party') ?? ''));
        $from  = (string) ($this->request->getGet('from') ?? '');
        $to    = (string) ($this->request->getGet('to') ?? '');
        $from  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : '';
        $to    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : '';
        if ($from !== '' && $to !== '' && $to < $from) {
            [$from, $to] = [$to, $from];
        }

        if ($party === '') {
            return $this->respond([
                'status'    => 'ok',
                'has_party' => false,
                'accounts'  => $model->partyDirectory($cid, 200),
            ]);
        }

        $rows    = $model->partyRows($cid, $party, $from ?: null, $to ?: null);
        $opening = $from !== '' ? round($model->partyNetBefore($cid, $party, $from), 2) : 0.0;

        $running = $opening;
        $out     = [];
        foreach ($rows as $r) {
            $running += ($r['type'] === 'jama' ? (float) $r['amount'] : -(float) $r['amount']);
            $out[]    = [
                'id'           => (int) $r['id'],
                'txn_no'       => $r['txn_no'],
                'date'         => $r['txn_date'],
                'type'         => $r['type'],
                'amount'       => (float) $r['amount'],
                'payment_mode' => $r['payment_mode'],
                'status'       => $r['status'],
                'notes'        => $r['notes'],
                'balance'      => round($running, 2),
            ];
        }

        [$jama, $naam] = $model->partyTotals($cid, $party, $from ?: null, $to ?: null);

        return $this->respond([
            'status'     => 'ok',
            'has_party'  => true,
            'party'      => $party,
            'from'       => $from,
            'to'         => $to,
            'opening'    => $opening,
            'rows'       => $out,
            'total_jama' => round((float) $jama, 2),
            'total_naam' => round((float) $naam, 2),
            'closing'    => round($opening + (float) $jama - (float) $naam, 2),
            'count'      => count($out),
        ]);
    }

    /**
     * GET api/v1/transactions/opening — Shri Rokad Nagad (opening cash) per
     * financial year, ±5 years around the current FY. Each carries its value
     * (explicit or auto-rolled from the prior year's closing) and an `auto` flag.
     */
    public function opening()
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }

        $ob     = new OpeningBalance($cid, $cid);
        $thisFy = $ob->fyStartFor(date('Y-m-d'));
        // Show the current financial year plus 3 years back and 3 ahead (7 total).
        $window = 3;

        $years = [];
        for ($y = $thisFy + $window; $y >= $thisFy - $window; $y--) {
            $years[] = [
                'fy'         => $y,
                'label'      => OpeningBalance::fyLabel($y),
                'value'      => $ob->shriNagad($y),
                'auto'       => ! $ob->isExplicit($y),
                'is_current' => $y === $thisFy,
            ];
        }

        return $this->respond([
            'status'  => 'ok',
            'label'   => $ob->label(),
            'this_fy' => $thisFy,
            'years'   => $years,
        ]);
    }

    /**
     * POST api/v1/transactions/opening — set the opening cash for a financial
     * year ({fy, amount}) or rename the label ({label}). Mirrors saveOpening().
     */
    public function saveOpening()
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $settings = new CompanySettingModel();

        // Rename the label.
        $label = $this->input('label');
        if ($label !== null) {
            $clean = trim((string) $label);
            if ($clean === '' || mb_strlen($clean) > 60) {
                return $this->failValidationErrors('Please provide a name (max 60 characters).');
            }
            $settings->put($cid, 'transactions', 'shri_rokad_label', $clean);
            return $this->respond(['status' => 'ok', 'message' => 'Name updated.', 'label' => $clean]);
        }

        // Set / update the amount for a chosen FY.
        $fy = (int) $this->input('fy');
        if ($fy < 2000 || $fy > 2100) {
            return $this->failValidationErrors('Please choose a valid financial year.');
        }
        $amountRaw = $this->input('amount');
        if ($amountRaw === null || $amountRaw === '' || ! is_numeric($amountRaw)) {
            return $this->failValidationErrors('Please enter a valid opening cash amount.');
        }
        $amount = round((float) $amountRaw, 2);
        if ($amount < -9999999999.99 || $amount > 9999999999.99) {
            return $this->failValidationErrors('The amount is out of range.');
        }

        $settings->put($cid, 'transactions', 'shri_rokad_nagad_' . $fy, $amount);

        // Forward cascade: an opening set for FY N flows into every later year
        // automatically (each year's closing carries into the next — see
        // OpeningBalance::shriNagad). Clear any explicit openings for years AFTER
        // N so a stale later value (e.g. a 0) can't block that roll-forward.
        $prefix = 'shri_rokad_nagad_';
        $rows   = $settings->builder()
            ->select('id, `key`')
            ->where('company_id', $cid)
            ->where('scope', 'transactions')
            ->like('key', $prefix, 'after')
            ->get()->getResultArray();
        $staleIds = [];
        foreach ($rows as $r) {
            $year = (int) substr((string) $r['key'], strlen($prefix));
            if ($year > $fy) {
                $staleIds[] = (int) $r['id'];
            }
        }
        if ($staleIds !== []) {
            $settings->builder()->whereIn('id', $staleIds)->delete();
        }

        if (function_exists('activity_log')) {
            activity_log('Transactions', 'Edit', 'Opening cash set for FY ' . OpeningBalance::fyLabel($fy) . ' (mobile)');
        }

        return $this->respond([
            'status'  => 'ok',
            'message' => 'Opening cash saved for FY ' . OpeningBalance::fyLabel($fy) . '.',
            'fy'      => $fy,
            'value'   => $amount,
        ]);
    }

    /**
     * GET api/v1/transactions/entry/{id} — one entry, honouring company scope.
     */
    public function entry($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }

        $row = (new TransactionModel())->findScoped((int) $id, $cid);
        if (! $row) {
            return $this->failNotFound('Entry not found.');
        }

        return $this->respond([
            'status'      => 'ok',
            'entry'       => $this->shape($row),
            'attachments' => $this->attachmentList((int) $row['id']),
        ]);
    }

    /**
     * POST api/v1/transactions/update/{id} — edit an existing entry. Same fields
     * as store; txn_no / company / author are immutable.
     */
    public function update($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }

        $model = new TransactionModel();
        $row   = $model->findScoped((int) $id, $cid);
        if (! $row) {
            return $this->failNotFound('Entry not found.');
        }

        $txnDate = (string) ($this->input('txn_date') ?? '');
        $ts      = strtotime($txnDate);
        if ($ts === false) {
            return $this->failValidationErrors('Invalid date.');
        }
        $mode   = (string) ($this->input('payment_mode') ?? 'cash');
        $status = (string) ($this->input('status') ?? 'paid');
        if (! in_array($mode, TransactionModel::MODES, true)) {
            $mode = 'cash';
        }
        if (! in_array($status, TransactionModel::STATUSES, true)) {
            $status = 'paid';
        }
        $partyType = trim((string) ($this->input('party_type') ?? ''));
        $notes     = trim((string) ($this->input('notes') ?? ''));

        $data = [
            'txn_date'     => date('Y-m-d', $ts),
            'name'         => trim((string) ($this->input('name') ?? '')),
            'party_type'   => $partyType !== '' ? mb_substr($partyType, 0, 32) : null,
            'type'         => $this->input('type') === 'naam' ? 'naam' : 'jama',
            'amount'       => round((float) $this->input('amount'), 2),
            'payment_mode' => $mode,
            'status'       => $status,
            'notes'        => $notes !== '' ? $notes : null,
        ];

        if ($model->update((int) $id, $data) === false) {
            return $this->failValidationErrors($model->errors());
        }
        if (function_exists('activity_log')) {
            activity_log('Transactions', 'Edit', "Transaction {$row['txn_no']} updated (mobile)");
        }

        return $this->respond([
            'status'  => 'ok',
            'message' => 'Entry updated.',
            'txn_no'  => $row['txn_no'],
        ]);
    }

    /**
     * POST api/v1/transactions/delete/{id} — soft-delete an entry. A `reason`
     * is mandatory and recorded for the audit trail (mirrors the web flow).
     */
    public function delete($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }

        $model = new TransactionModel();
        $row   = $model->findScoped((int) $id, $cid);
        if (! $row) {
            return $this->failNotFound('Entry not found.');
        }

        $reason = trim((string) ($this->input('reason') ?? ''));
        if ($reason === '') {
            return $this->failValidationErrors('Please provide a reason for deletion.');
        }

        $model->update((int) $id, ['delete_reason' => mb_substr($reason, 0, 255)]);
        $model->delete((int) $id);
        if (function_exists('activity_log')) {
            activity_log('Transactions', 'Delete', "Transaction {$row['txn_no']} deleted (mobile) — reason: {$reason}");
        }

        return $this->respond([
            'status'  => 'ok',
            'message' => 'Entry deleted.',
            'txn_no'  => $row['txn_no'],
        ]);
    }

    /** GET api/v1/transactions/deleted — soft-deleted entries for the company (Trash). */
    public function deleted()
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }

        $rows = (new TransactionModel())
            ->withDeleted()
            ->where('company_id', $cid)
            ->where('deleted_at IS NOT NULL')
            ->orderBy('deleted_at', 'DESC')
            ->findAll(200);

        $out = array_map(fn (array $r): array => $this->shape($r) + [
            'delete_reason' => $r['delete_reason'] ?? null,
            'deleted_at'    => $r['deleted_at'] ?? null,
        ], $rows);

        return $this->respond(['status' => 'ok', 'count' => count($out), 'entries' => $out]);
    }

    /** POST api/v1/transactions/restore/{id} — un-delete a soft-deleted entry. */
    public function restore($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }

        $model = new TransactionModel();
        $row   = $model->withDeleted()->find((int) $id);
        if (! $row || (int) $row['company_id'] !== (int) $cid || empty($row['deleted_at'])) {
            return $this->failNotFound('Deleted entry not found.');
        }

        // Restore via the builder so the soft-delete scope doesn't filter it out.
        $model->builder()->where('id', (int) $id)->update(['deleted_at' => null, 'delete_reason' => null]);

        if (function_exists('activity_log')) {
            activity_log('Transactions', 'Edit', "Transaction {$row['txn_no']} restored (mobile)");
        }

        return $this->respond(['status' => 'ok', 'message' => 'Entry restored.', 'txn_no' => $row['txn_no']]);
    }

    /**
     * POST api/v1/transactions/purge/{id} — permanently delete a soft-deleted
     * entry (from Trash) plus its attachment files/rows. Irreversible.
     */
    public function purge($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }

        $model = new TransactionModel();
        $row   = $model->withDeleted()->find((int) $id);
        if (! $row || (int) $row['company_id'] !== (int) $cid || empty($row['deleted_at'])) {
            return $this->failNotFound('Deleted entry not found.');
        }

        // Remove attachment files from disk, then their rows, then the entry.
        $att   = new TransactionAttachmentModel();
        $files = $att->where('transaction_id', (int) $id)->findAll();
        foreach ($files as $a) {
            $path = WRITEPATH . 'uploads/transactions/' . (int) $a['user_id'] . '/' . basename((string) $a['stored_name']);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $att->where('transaction_id', (int) $id)->delete();
        $model->delete((int) $id, true); // hard delete (purge)

        if (function_exists('activity_log')) {
            activity_log('Transactions', 'Delete', "Transaction {$row['txn_no']} permanently deleted (mobile)");
        }

        return $this->respond(['status' => 'ok', 'message' => 'Entry permanently deleted.', 'txn_no' => $row['txn_no']]);
    }

    // ===============================================================
    // Attachments (photos / PDFs / audio on an entry) — mirrors the web
    // TransactionController attach flow + transaction_attachments table.
    // ===============================================================

    /** GET api/v1/transactions/entry/{id}/attachments — files on an entry. */
    public function entryAttachments($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $row = (new TransactionModel())->findScoped((int) $id, $cid);
        if (! $row) {
            return $this->failNotFound('Entry not found.');
        }
        return $this->respond(['status' => 'ok', 'attachments' => $this->attachmentList((int) $row['id'])]);
    }

    /** POST api/v1/transactions/entry/{id}/attach — upload files (multipart `attachments[]`). */
    public function attachToEntry($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $row = (new TransactionModel())->findScoped((int) $id, $cid);
        if (! $row) {
            return $this->failNotFound('Entry not found.');
        }

        $att   = new TransactionAttachmentModel();
        $have  = count($att->forTransaction((int) $row['id']));
        $slots = self::ATTACH_MAX - $have;
        if ($slots <= 0) {
            return $this->failValidationErrors('This entry already has the maximum of ' . self::ATTACH_MAX . ' attachments.');
        }

        $ownerId = (int) ($row['user_id'] ?? $user['id']);
        $dir     = WRITEPATH . 'uploads/transactions/' . $ownerId . '/';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $stored   = 0;
        $rejected = [];
        foreach ((array) $this->request->getFileMultiple('attachments') as $file) {
            if ($stored >= $slots) {
                break;
            }
            if (! $file || ! $file->isValid() || $file->hasMoved()) {
                continue;
            }
            // Gate on the real filename extension (client mime is untrusted and
            // easily spoofed). Blocks .php/.exe/.js/.html/.svg/etc.
            $ext = strtolower((string) $file->getClientExtension());
            if (! in_array($ext, self::ATTACH_EXT, true) || $file->getSizeByUnit('mb') > self::ATTACH_MAX_MB) {
                $rejected[] = $file->getClientName();
                continue;
            }
            // Server-controlled random name with a known-safe extension — never
            // trust the uploaded name for the stored file.
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            try {
                $file->move($dir, $name, false);
            } catch (\Throwable $e) {
                continue;
            }
            $att->insert([
                'transaction_id' => (int) $row['id'],
                'user_id'        => $ownerId,
                'company_id'     => $cid,
                'original_name'  => mb_substr((string) $file->getClientName(), 0, 180),
                'stored_name'    => $name,
                'mime'           => $file->getClientMimeType(),
                'kind'           => TransactionAttachmentModel::kindFor((string) $file->getClientMimeType(), $ext),
                'size'           => (int) filesize($dir . $name),
                'created_by'     => (int) $user['id'],
            ]);
            $stored++;
        }

        if ($stored > 0) {
            // Bump the parent so the incremental sync-pull re-sends it and the
            // mobile attachment-count badge stays accurate (attachment rows live
            // in their own table and don't otherwise touch the transaction).
            (new TransactionModel())->builder()->where('id', (int) $row['id'])
                ->update(['updated_at' => date('Y-m-d H:i:s')]);
            if (function_exists('activity_log')) {
                activity_log('Transactions', 'Edit', "{$stored} attachment(s) added to {$row['txn_no']} (mobile)");
            }
        }

        $message = "{$stored} file(s) attached.";
        if ($rejected !== []) {
            $message .= ' Rejected (images, PDF, audio or CSV/Excel only, max ' . self::ATTACH_MAX_MB . ' MB): ' . implode(', ', $rejected) . '.';
        }

        return $this->respond([
            'status'      => $stored > 0 ? 'ok' : 'rejected',
            'stored'      => $stored,
            'rejected'    => $rejected,
            'message'     => $message,
            'attachments' => $this->attachmentList((int) $row['id']),
        ]);
    }

    /** GET api/v1/transactions/attachment/{id} — stream the file (bearer-auth). */
    public function attachment($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $a = (new TransactionAttachmentModel())->find((int) $id);
        if (! $a || (int) $a['company_id'] !== (int) $cid) {
            return $this->failNotFound('Attachment not found.');
        }
        // basename() defends against any path-traversal in stored_name.
        $path = WRITEPATH . 'uploads/transactions/' . (int) $a['user_id'] . '/' . basename((string) $a['stored_name']);
        if (! is_file($path)) {
            return $this->failNotFound('File missing.');
        }

        // Content-Type is derived from the SERVER-controlled stored extension,
        // not the client mime, and paired with nosniff so the browser never
        // sniffs the bytes as HTML/script. Images, PDFs and audio are served
        // INLINE so the app (and a browser) can render them; everything else is
        // a forced download. SVG is not in the allowlist, so inline is safe.
        $ext      = strtolower(pathinfo((string) $a['stored_name'], PATHINFO_EXTENSION));
        $types    = [
            'csv'  => 'text/csv',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif'  => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp', 'heic' => 'image/heic',
            'm4a'  => 'audio/mp4', 'mp3' => 'audio/mpeg', 'aac' => 'audio/aac',
            'ogg'  => 'audio/ogg', 'wav' => 'audio/wav', 'webm' => 'audio/webm',
        ];
        $mime     = $types[$ext] ?? 'application/octet-stream';
        $inline   = str_starts_with($mime, 'image/') || str_starts_with($mime, 'audio/') || $mime === 'application/pdf';
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $a['original_name']) ?: 'attachment';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Disposition', ($inline ? 'inline' : 'attachment') . '; filename="' . $safeName . '"')
            ->setBody(file_get_contents($path));
    }

    /** DELETE api/v1/transactions/attachment/{id} — soft-delete a file. */
    public function deleteAttachment($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $model = new TransactionAttachmentModel();
        $a     = $model->find((int) $id);
        if (! $a || (int) $a['company_id'] !== (int) $cid) {
            return $this->failNotFound('Attachment not found.');
        }
        $model->delete((int) $id);
        // Bump the parent so the sync-pull re-sends it with the lowered count.
        (new TransactionModel())->builder()->where('id', (int) $a['transaction_id'])
            ->update(['updated_at' => date('Y-m-d H:i:s')]);
        return $this->respond(['status' => 'ok', 'message' => 'Attachment removed.']);
    }

    /** Attachment rows for a transaction, shaped for the app (with a fetch URL). */
    private function attachmentList(int $transactionId): array
    {
        $rows = (new TransactionAttachmentModel())->forTransaction($transactionId);
        return array_map(static fn (array $r): array => [
            'id'            => (int) $r['id'],
            'original_name' => $r['original_name'],
            'mime'          => $r['mime'],
            'kind'          => $r['kind'],
            'size'          => (int) $r['size'],
            'url'           => site_url('api/v1/transactions/attachment/' . $r['id']),
        ], $rows);
    }

    /** Standard row → API shape. */
    private function shape(array $r): array
    {
        return [
            'id'           => (int) $r['id'],
            'txn_no'       => $r['txn_no'],
            'date'         => $r['txn_date'],
            'name'         => $r['name'],
            'party_type'   => $r['party_type'],
            'type'         => $r['type'],
            'amount'       => (float) $r['amount'],
            'payment_mode' => $r['payment_mode'],
            'status'       => $r['status'],
            'notes'        => $r['notes'],
            'source'       => $r['source'] ?? null,
            'created_at'   => $r['created_at'] ?? null,
            'updated_at'   => $r['updated_at'] ?? null,
        ];
    }

    /** Resolve + authorise the API caller. Returns [user, companyId, errorResponse|null]. */
    private function authScope(): array
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return [null, null, $this->failUnauthorized('Invalid or missing token.')];
        }
        $cid = $this->resolveCompanyId($user);
        if (! $cid) {
            return [null, null, $this->failValidationErrors('No company for this user.')];
        }
        return [$user, $cid, null];
    }

    /**
     * POST api/v1/transactions/store — record a Jama (money in) or Naam
     * (money out) entry. Returns the generated txn_no on success.
     */
    public function store()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->resolveCompanyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $type      = $this->input('type') === 'naam' ? 'naam' : 'jama';
        $amount    = round((float) $this->input('amount'), 2);
        $name      = trim((string) ($this->input('name') ?? ''));
        $txnDate   = (string) ($this->input('txn_date') ?? '');
        $mode      = (string) ($this->input('payment_mode') ?? 'cash');
        $status    = (string) ($this->input('status') ?? 'paid');
        $partyType = trim((string) ($this->input('party_type') ?? ''));
        $notes     = trim((string) ($this->input('notes') ?? ''));

        $ts = strtotime($txnDate);
        if ($ts === false) {
            return $this->failValidationErrors('Invalid date.');
        }
        if (! in_array($mode, TransactionModel::MODES, true)) {
            $mode = 'cash';
        }
        if (! in_array($status, TransactionModel::STATUSES, true)) {
            $status = 'paid';
        }

        $txnDateNorm = date('Y-m-d', $ts);
        $notesNorm   = $notes !== '' ? $notes : null;

        // Idempotency guard: a double-tap on Save, a slow-network retry, or a
        // resubmit can POST the same entry twice. If an identical, non-deleted
        // entry was created within the last 120s (same core fields), return it
        // instead of inserting a duplicate — so "the same record can't be added
        // twice" by accident. A repeat entered deliberately later still saves.
        $dup = new TransactionModel();
        $dup->where('company_id', $cid)
            ->where('type', $type)
            ->where('amount', $amount)
            ->where('name', $name)
            ->where('txn_date', $txnDateNorm)
            ->where('payment_mode', $mode)
            ->where('status', $status)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 120));
        $notesNorm === null ? $dup->where('notes', null) : $dup->where('notes', $notesNorm);
        $existing = $dup->orderBy('id', 'DESC')->first();
        if ($existing) {
            return $this->respond([
                'status'    => 'ok',
                'duplicate' => true,
                'message'   => 'This entry was just saved — not added again.',
                'id'        => (int) $existing['id'],
                'txn_no'    => $existing['txn_no'],
                'type'      => $existing['type'],
                'amount'    => (float) $existing['amount'],
            ]);
        }

        $model = new TransactionModel();
        $data  = [
            'user_id'      => (int) $user['id'],
            'company_id'   => $cid,
            'txn_no'       => $model->nextTxnNo($cid),
            'txn_date'     => $txnDateNorm,
            'name'         => $name,
            'party_type'   => $partyType !== '' ? mb_substr($partyType, 0, 32) : null,
            'type'         => $type,
            'amount'       => $amount,
            'payment_mode' => $mode,
            'status'       => $status,
            'notes'        => $notesNorm,
            'source'       => 'mobile',
        ];

        // Model runs its own validation rules (amount > 0, name required, etc.).
        $id = $model->insert($data);
        if ($id === false) {
            return $this->failValidationErrors($model->errors());
        }

        if (function_exists('activity_log')) {
            activity_log('Transactions', 'Add', "Transaction {$data['txn_no']} added (mobile)");
        }

        return $this->respondCreated([
            'status'  => 'ok',
            'message' => ($type === 'naam' ? 'Naam' : 'Jama') . ' entry saved.',
            'id'      => (int) $id,
            'txn_no'  => $data['txn_no'],
            'type'    => $type,
            'amount'  => $amount,
        ]);
    }

    // =====================================================================
    //  Offline-first sync (mobile). Pull = changes since a cursor; Push =
    //  batch apply the mobile outbox. Timestamps are the server clock so the
    //  cursor is monotonic regardless of device time.
    // =====================================================================

    /** Row → sync feed shape (includes tombstones + soft-delete flags). */
    private function syncShape(array $r): array
    {
        return [
            'id'           => (int) $r['id'],
            'client_uuid'  => $r['client_uuid'] ?? null,
            'txn_no'       => $r['txn_no'],
            'txn_date'     => $r['txn_date'],
            'name'         => $r['name'],
            'party_type'   => $r['party_type'],
            'type'         => $r['type'],
            'amount'       => (float) $r['amount'],
            'payment_mode' => $r['payment_mode'],
            'status'       => $r['status'],
            'notes'        => $r['notes'],
            'source'       => $r['source'] ?? null,
            'is_deleted'   => empty($r['deleted_at']) ? 0 : 1,
            'delete_reason' => $r['delete_reason'] ?? null,
            'created_at'   => $r['created_at'] ?? null,
            'updated_at'   => $r['updated_at'] ?? null,
            'deleted_at'   => $r['deleted_at'] ?? null,
        ];
    }

    /**
     * GET api/v1/transactions/changes?since=<Y-m-d H:i:s>
     * Incremental pull: every row (incl. soft-deleted tombstones) changed on or
     * after `since`. Omit `since` for the initial full download.
     */
    public function changes()
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $since   = trim((string) ($this->request->getGet('since') ?? ''));
        $afterId = (int) $this->request->getGet('after_id');
        // Newest-first initial backfill (dir=desc): the mobile client pulls a firm's
        // history newest→oldest so TODAY/this-week's entries land in the first chunk
        // and the app is instantly useful, while old history streams in the background.
        // Steady-state incremental sync stays ASC (default) so the updated_at cursor
        // advances monotonically. DESC paginates by a (before, before_id) keyset.
        $desc     = strtolower(trim((string) ($this->request->getGet('dir') ?? 'asc'))) === 'desc';
        $before   = trim((string) ($this->request->getGet('before') ?? ''));
        $beforeId = (int) $this->request->getGet('before_id');
        // Optional pagination: bound the payload so a data-heavy firm never ships
        // its whole history in one response (the mobile company-switch "hang").
        $limit = (int) $this->request->getGet('limit');
        $limit = $limit > 0 ? min($limit, 1000) : 0; // 0 = unlimited (back-compat)

        // Apply the keyset filter shared by the page query, has-more sentinel, and
        // the count probes — so all three see exactly the same window of rows.
        $applyCursor = function ($b) use ($desc, $since, $afterId, $before, $beforeId) {
            if ($desc) {
                // Backfill continuation: rows strictly BEFORE (updated_at, id).
                if ($before !== '') {
                    $b->groupStart()
                        ->where('updated_at <', $before)
                        ->orGroupStart()->where('updated_at', $before)->where('id <', $beforeId)->groupEnd()
                      ->groupEnd();
                }
            } elseif ($since !== '') {
                if ($afterId > 0) {
                    // Keyset continuation within a page run: strictly after (updated_at, id).
                    $b->groupStart()
                        ->where('updated_at >', $since)
                        ->orGroupStart()->where('updated_at', $since)->where('id >', $afterId)->groupEnd()
                      ->groupEnd();
                } else {
                    $b->where('updated_at >=', $since);
                }
            }
            return $b;
        };

        // Cheap progress probe: ?count_only=1 returns just how many rows the pull
        // will bring (from the cursor), so the mobile client can show a real %
        // + ETA while syncing a firm — without shipping any row data.
        if ($this->request->getGet('count_only') !== null) {
            $cb = $applyCursor((new TransactionModel())->builder()->where('company_id', $cid));
            return $this->respond(['status' => 'ok', 'total' => $cb->countAllResults()]);
        }

        $b = $applyCursor((new TransactionModel())->builder()->where('company_id', $cid));
        $b->orderBy('updated_at', $desc ? 'DESC' : 'ASC')->orderBy('id', $desc ? 'DESC' : 'ASC');
        if ($limit > 0) {
            $b->limit($limit + 1); // one extra row tells us whether more pages remain
        }
        $rows = $b->get()->getResultArray();

        $hasMore = false;
        if ($limit > 0 && count($rows) > $limit) {
            $hasMore = true;
            array_pop($rows); // drop the sentinel row
        }

        // ?with_total=1 → also return the TOTAL rows this pull will bring (from the
        // cursor), so the mobile loader can show a real % + ETA. Computed once on
        // the first page (one COUNT); the client passes the same cursor filters.
        $total = null;
        if ($this->request->getGet('with_total') !== null) {
            $tb = $applyCursor((new TransactionModel())->builder()->where('company_id', $cid));
            $total = $tb->countAllResults();
        }

        // Lightweight per-entry attachment counts (ONE grouped COUNT query, no
        // files) — same source the web list uses, so the mobile paperclip badge
        // matches. Excludes soft-deleted attachments.
        $counts = (new TransactionAttachmentModel())
            ->countsFor(array_map(static fn (array $r): int => (int) $r['id'], $rows));

        $payload = [
            'status'      => 'ok',
            'server_time' => date('Y-m-d H:i:s'),
            'has_more'    => $hasMore,
            'changes'     => array_map(function (array $r) use ($counts): array {
                $shaped = $this->syncShape($r);
                $shaped['attachment_count'] = $counts[(int) $r['id']] ?? 0;
                return $shaped;
            }, $rows),
        ];
        if ($total !== null) {
            $payload['total'] = $total;
        }
        return $this->respond($payload);
    }


    /**
     * POST api/v1/transactions/sync
     * Batch push of the mobile outbox: { creates:[], updates:[], deletes:[] }.
     * Returns id-mappings for creates so the client can link local rows, plus
     * the server clock as the next pull cursor.
     */
    public function sync()
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }

        $creates = (array) ($this->input('creates') ?? []);
        $updates = (array) ($this->input('updates') ?? []);
        $deletes = (array) ($this->input('deletes') ?? []);
        $model   = new TransactionModel();
        $mapped  = [];

        foreach ($creates as $c) {
            $norm = $this->normalizeSync($c);
            if ($norm === null) {
                continue;
            }
            // C2 — idempotent create. A push retried after the server already
            // committed (app killed / response lost) re-sends the same
            // client_uuid; link the existing row instead of inserting a duplicate.
            $uuid = isset($c['client_uuid']) ? trim((string) $c['client_uuid']) : '';
            if ($uuid !== '') {
                $existing = $model->withDeleted()
                    ->where('company_id', $cid)
                    ->where('client_uuid', $uuid)
                    ->first();
                if ($existing) {
                    $mapped[] = [
                        'local_id'   => isset($c['local_id']) ? (int) $c['local_id'] : null,
                        'server_id'  => (int) $existing['id'],
                        'txn_no'     => $existing['txn_no'],
                        'updated_at' => $existing['updated_at'] ?? date('Y-m-d H:i:s'),
                    ];
                    continue;
                }
            }
            $data = array_merge($norm, [
                'user_id'     => (int) $user['id'],
                'company_id'  => $cid,
                'client_uuid' => $uuid !== '' ? $uuid : null,
                'txn_no'      => $model->nextTxnNo($cid),
                'source'      => $c['source'] ?? 'mobile',
            ]);
            $id = $model->insert($data);
            if ($id === false) {
                // A concurrent push may have inserted the same uuid a moment ago
                // (unique index rejected this one) — link that row instead of
                // dropping the job so it doesn't retry forever.
                if ($uuid !== '') {
                    $dup = $model->withDeleted()->where('company_id', $cid)->where('client_uuid', $uuid)->first();
                    if ($dup) {
                        $mapped[] = [
                            'local_id'   => isset($c['local_id']) ? (int) $c['local_id'] : null,
                            'server_id'  => (int) $dup['id'],
                            'txn_no'     => $dup['txn_no'],
                            'updated_at' => $dup['updated_at'] ?? date('Y-m-d H:i:s'),
                        ];
                    }
                }
                continue;
            }
            // Honour a create that was already soft-deleted offline.
            if (! empty($c['is_deleted'])) {
                $model->builder()->where('id', (int) $id)->update([
                    'deleted_at'    => $c['deleted_at'] ?? date('Y-m-d H:i:s'),
                    'delete_reason' => $c['delete_reason'] ?? null,
                ]);
            }
            $fresh = $model->find((int) $id);
            $mapped[] = [
                'local_id'   => isset($c['local_id']) ? (int) $c['local_id'] : null,
                'server_id'  => (int) $id,
                'txn_no'     => $data['txn_no'],
                'updated_at' => $fresh['updated_at'] ?? date('Y-m-d H:i:s'),
            ];
        }

        foreach ($updates as $u) {
            $sid = (int) ($u['server_id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $row = $model->withDeleted()->find($sid);
            if (! $row || (int) $row['company_id'] !== (int) $cid) {
                continue;
            }
            $norm = $this->normalizeSync($u);
            if ($norm === null) {
                continue;
            }
            $norm['deleted_at']    = ! empty($u['is_deleted']) ? ($u['deleted_at'] ?? date('Y-m-d H:i:s')) : null;
            $norm['delete_reason'] = ! empty($u['is_deleted']) ? ($u['delete_reason'] ?? null) : null;
            $norm['updated_at']    = date('Y-m-d H:i:s');
            $model->builder()->where('id', $sid)->update($norm);
            $mapped[] = ['local_id' => isset($u['local_id']) ? (int) $u['local_id'] : null, 'server_id' => $sid, 'updated_at' => $norm['updated_at']];
        }

        foreach ($deletes as $d) {
            $sid = (int) ($d['server_id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $row = $model->withDeleted()->find($sid);
            if ($row && (int) $row['company_id'] === (int) $cid) {
                $model->builder()->where('id', $sid)->delete();
            }
        }

        // Builder updates/deletes above bypass the model's afterUpdate hook, so
        // invalidate this firm's dashboard cache explicitly after a sync batch.
        if (function_exists('dash_bust')) {
            dash_bust((int) $cid);
        }

        return $this->respond([
            'status'      => 'ok',
            'server_time' => date('Y-m-d H:i:s'),
            'mapped'      => $mapped,
        ]);
    }

    /** Validate/normalise a synced entry's core fields (shared by creates/updates). */
    private function normalizeSync(array $r): ?array
    {
        $ts = strtotime((string) ($r['txn_date'] ?? ''));
        if ($ts === false) {
            return null;
        }
        $mode   = (string) ($r['payment_mode'] ?? 'cash');
        $status = (string) ($r['status'] ?? 'paid');
        return [
            'txn_date'     => date('Y-m-d', $ts),
            'name'         => trim((string) ($r['name'] ?? '')),
            'party_type'   => ($p = trim((string) ($r['party_type'] ?? ''))) !== '' ? mb_substr($p, 0, 32) : null,
            'type'         => ($r['type'] ?? 'jama') === 'naam' ? 'naam' : 'jama',
            'amount'       => round((float) ($r['amount'] ?? 0), 2),
            'payment_mode' => in_array($mode, TransactionModel::MODES, true) ? $mode : 'cash',
            'status'       => in_array($status, TransactionModel::STATUSES, true) ? $status : 'paid',
            'notes'        => ($n = trim((string) ($r['notes'] ?? ''))) !== '' ? $n : null,
        ];
    }
}
