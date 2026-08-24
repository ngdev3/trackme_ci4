<?php

namespace Modules\Api\Controllers;

use App\Models\UpiQrPayeeModel;

/**
 * Saved UPI QR payees REST API for the mobile app (Bearer auth). Company-scoped
 * via authScope() so one firm's payees are never visible to another. Backs the
 * mobile UPI QR directory, which syncs its local cache with these endpoints.
 *
 *   GET    api/v1/upi-qr                 — list the company's payees
 *   POST   api/v1/upi-qr                 — create a payee
 *   POST   api/v1/upi-qr/update/(:num)   — update a payee
 *   POST   api/v1/upi-qr/delete/(:num)   — delete a payee
 */
class UpiQrApiController extends BaseApiController
{
    public function index()
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $rows = (new UpiQrPayeeModel())
            ->where('company_id', $cid)
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        return $this->respond([
            'status' => 'ok',
            'payees' => array_map([$this, 'shape'], $rows),
        ]);
    }

    public function create()
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $data = $this->payload($cid, (int) $user['id']);
        if (isset($data['error'])) {
            return $this->failValidationErrors($data['error']);
        }
        $model = new UpiQrPayeeModel();
        $id    = $model->insert($data, true);

        return $this->respondCreated([
            'status' => 'ok',
            'payee'  => $this->shape($model->find($id)),
        ]);
    }

    public function update($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $model = new UpiQrPayeeModel();
        $row   = $model->where('company_id', $cid)->find((int) $id);
        if (! $row) {
            return $this->failNotFound('Payee not found.');
        }
        $data = $this->payload($cid, (int) $user['id']);
        if (isset($data['error'])) {
            return $this->failValidationErrors($data['error']);
        }
        $model->update((int) $id, $data);

        return $this->respond([
            'status' => 'ok',
            'payee'  => $this->shape($model->find((int) $id)),
        ]);
    }

    public function remove($id = null)
    {
        [$user, $cid, $err] = $this->authScope();
        if ($err) {
            return $err;
        }
        $model = new UpiQrPayeeModel();
        $row   = $model->where('company_id', $cid)->find((int) $id);
        if (! $row) {
            return $this->respond(['status' => 'ok', 'deleted' => 0]); // already gone
        }
        $model->delete((int) $id);

        return $this->respond(['status' => 'ok', 'deleted' => 1]);
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

    /** Validate + assemble a writable row from the request. */
    private function payload(int $cid, int $userId): array
    {
        $method    = $this->input('method') === 'bank' ? 'bank' : 'upi';
        $payee     = trim((string) ($this->input('payee_name') ?? ''));
        $label     = trim((string) ($this->input('label') ?? '')) ?: $payee;
        $upiId     = trim((string) ($this->input('upi_id') ?? ''));
        $account   = trim((string) ($this->input('account_number') ?? ''));
        $ifsc      = strtoupper(trim((string) ($this->input('ifsc') ?? '')));

        if ($payee === '') {
            return ['error' => ['payee_name' => 'Payee name is required.']];
        }
        if ($method === 'upi' && ! preg_match('/^[\w.\-]{2,256}@[\w.\-]{2,64}$/', $upiId)) {
            return ['error' => ['upi_id' => 'A valid UPI ID is required.']];
        }
        if ($method === 'bank' && ($account === '' || $ifsc === '')) {
            return ['error' => ['account' => 'Account number and IFSC are required.']];
        }

        $amount = $this->input('amount');
        $amount = ($amount === null || $amount === '' || (float) $amount <= 0) ? null : round((float) $amount, 2);

        return [
            'company_id'     => $cid,
            'user_id'        => $userId,
            'label'          => mb_substr($label, 0, 80),
            'method'         => $method,
            'payee_name'     => mb_substr($payee, 0, 80),
            'upi_id'         => $method === 'upi' ? mb_substr($upiId, 0, 120) : null,
            'bank_name'      => $method === 'bank' ? (mb_substr(trim((string) ($this->input('bank_name') ?? '')), 0, 80) ?: null) : null,
            'branch'         => $method === 'bank' ? (mb_substr(trim((string) ($this->input('branch') ?? '')), 0, 120) ?: null) : null,
            'city'           => $method === 'bank' ? (mb_substr(trim((string) ($this->input('city') ?? '')), 0, 80) ?: null) : null,
            'account_number' => $method === 'bank' ? mb_substr($account, 0, 30) : null,
            'ifsc'           => $method === 'bank' ? mb_substr($ifsc, 0, 15) : null,
            'amount'         => $amount,
            'note'           => mb_substr(trim((string) ($this->input('note') ?? '')), 0, 120) ?: null,
        ];
    }

    /** Shape a DB row for the API (typed amount, string ids). */
    private function shape(array $r): array
    {
        return [
            'id'             => (int) $r['id'],
            'label'          => $r['label'],
            'method'         => $r['method'],
            'payee_name'     => $r['payee_name'],
            'upi_id'         => $r['upi_id'],
            'bank_name'      => $r['bank_name'],
            'branch'         => $r['branch'],
            'city'           => $r['city'],
            'account_number' => $r['account_number'],
            'ifsc'           => $r['ifsc'],
            'amount'         => $r['amount'] !== null ? (float) $r['amount'] : null,
            'note'           => $r['note'],
            'updated_at'     => $r['updated_at'] ?? null,
            'created_at'     => $r['created_at'] ?? null,
        ];
    }
}
