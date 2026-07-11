<?php

namespace Modules\Api\Controllers;

use App\Libraries\PasswordVault;
use App\Models\PasswordModel;

/**
 * Password Manager API for the mobile app. Company-scoped vault; the stored
 * secret is encrypted (PasswordVault) and never returned in a listing — it is
 * only decrypted on an explicit /reveal. Gated by the `password_manager`
 * feature (Standard plan and up).
 *
 *   GET    passwords                 list (no secrets)
 *   GET    passwords/(:num)/reveal   decrypt one secret
 *   POST   passwords                 create
 *   PUT    passwords/(:num)          update
 *   DELETE passwords/(:num)          soft-delete
 */
class PasswordsApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    private const FEATURE = 'password_manager';

    /** Fields safe to return in a listing (secret excluded). */
    private const PUBLIC_FIELDS = 'id, company_id, title, website, username, notes, category, created_by, created_at, updated_at';

    /** Resolve auth + feature gate + active company in one step. */
    private function guard(): array
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return ['error' => $this->failUnauthorized('Invalid or missing token.')];
        }
        if (! $this->apiHasFeature($user, self::FEATURE)) {
            return ['error' => $this->failForbidden('Your plan does not include the Password Manager.')];
        }
        return ['user' => $user, 'companyId' => $this->resolveCompanyId($user)];
    }

    public function index()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new PasswordModel();
        $rows  = $model->scoped($g['companyId'])
            ->select(self::PUBLIC_FIELDS)
            ->orderBy('title', 'ASC')
            ->findAll();

        return $this->respond([
            'status'     => 'ok',
            'passwords'  => $rows,
            'categories' => $model->categories($g['companyId']),
        ]);
    }

    public function reveal($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $row = (new PasswordModel())->findForCompany((int) $id, $g['companyId']);
        if (! $row) {
            return $this->failNotFound('Password not found.');
        }
        return $this->respond([
            'status'   => 'ok',
            'id'       => (int) $row['id'],
            'password' => (new PasswordVault())->decrypt($row['password_enc'] ?? ''),
        ]);
    }

    public function create()
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $data = $this->collect($g['companyId'], (int) $g['user']['id'], true);
        if ($data['title'] === '') {
            return $this->failValidationErrors('Title is required.');
        }
        $model = new PasswordModel();
        $id    = $model->insert($data, true);
        if (! $id) {
            return $this->failValidationErrors($model->errors() ?: 'Could not save password.');
        }
        return $this->respondCreated(['status' => 'ok', 'id' => (int) $id]);
    }

    public function update($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new PasswordModel();
        $row   = $model->findForCompany((int) $id, $g['companyId']);
        if (! $row) {
            return $this->failNotFound('Password not found.');
        }
        // Only re-encrypt the secret when a new one was supplied.
        $withSecret = $this->input('password') !== null && (string) $this->input('password') !== '';
        $data       = $this->collect($g['companyId'], (int) $row['created_by'], $withSecret);
        if ($data['title'] === '') {
            return $this->failValidationErrors('Title is required.');
        }
        if (! $model->update((int) $id, $data)) {
            return $this->failValidationErrors($model->errors() ?: 'Could not update password.');
        }
        return $this->respond(['status' => 'ok', 'id' => (int) $id]);
    }

    public function delete($id = null)
    {
        $g = $this->guard();
        if (isset($g['error'])) {
            return $g['error'];
        }
        $model = new PasswordModel();
        $row   = $model->findForCompany((int) $id, $g['companyId']);
        if (! $row) {
            return $this->failNotFound('Password not found.');
        }
        $model->delete((int) $id); // soft delete (recoverable from Trash)
        return $this->respondDeleted(['status' => 'ok', 'id' => (int) $id]);
    }

    /** Assemble an allowed-fields payload from the request. */
    private function collect(?int $companyId, int $createdBy, bool $withSecret): array
    {
        $data = [
            'company_id' => $companyId,
            'title'      => trim((string) ($this->input('title') ?? '')),
            'website'    => trim((string) ($this->input('website') ?? '')) ?: null,
            'username'   => trim((string) ($this->input('username') ?? '')) ?: null,
            'notes'      => trim((string) ($this->input('notes') ?? '')) ?: null,
            'category'   => trim((string) ($this->input('category') ?? '')) ?: null,
            'created_by' => $createdBy,
        ];
        if ($withSecret) {
            $data['password_enc'] = (new PasswordVault())->encrypt((string) $this->input('password'));
        }
        return $data;
    }
}
