<?php

namespace Modules\Api\Controllers;

use App\Models\TransactionModel;

/**
 * Jama / Naam (Hisaab Kitaab Vahi) REST API for the mobile app. Bearer-token
 * authenticated (see BaseApiController). The company is resolved from the
 * caller's membership, so one firm's ledger can never be written by another.
 * Mirrors the web TransactionController::persist() insert path.
 */
class TransactionApiController extends BaseApiController
{
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

        $rows    = $model->limitedFiltered($cid, $f, 100, 0);
        $summary = $model->summary($cid, $f);

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
            'status'  => 'ok',
            'entries' => $entries,
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

        return $this->respond([
            'status'        => 'ok',
            'range'         => ['from' => $from, 'to' => $to],
            'summary'       => [
                'jama'  => round((float) $summary['jama'], 2),
                'naam'  => round((float) $summary['naam'], 2),
                'net'   => round((float) $summary['net'], 2),
                'count' => (int) $summary['count'],
            ],
            'by_mode'       => $byMode,
            'by_party_type' => $byParty,
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

        return $this->respond(['status' => 'ok', 'entry' => $this->shape($row)]);
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

        $model = new TransactionModel();
        $data  = [
            'user_id'      => (int) $user['id'],
            'company_id'   => $cid,
            'txn_no'       => $model->nextTxnNo($cid),
            'txn_date'     => date('Y-m-d', $ts),
            'name'         => $name,
            'party_type'   => $partyType !== '' ? mb_substr($partyType, 0, 32) : null,
            'type'         => $type,
            'amount'       => $amount,
            'payment_mode' => $mode,
            'status'       => $status,
            'notes'        => $notes !== '' ? $notes : null,
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
}
