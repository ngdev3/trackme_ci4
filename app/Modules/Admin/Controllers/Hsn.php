<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\HsnModel;

/**
 * HSN Code Master — CI4 port of admin/Hsn (write-pattern reference).
 * Full CRUD: listing (server-side DataTable) + add/edit (AJAX save with
 * validation, uniqueness, soft-delete). hsn_codes is a global master; every
 * write invalidates the get_hsn_code() picker cache. Gated rbac('hsn').
 */
class Hsn extends BaseController
{
    protected $helpers = ['url', 'app', 'cr_cache'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\hsn\listing', [
            'title' => 'HSN Code Master · C R Industries ERP',
            'total' => (new HsnModel())->countActive(),
        ]);
    }

    /** DataTables JSON feed. */
    public function listingData()
    {
        $model = new HsnModel();
        $search = $this->request->getPost('search')['value'] ?? null;
        $rows = $model->listRows($search ?: null);

        $data = [];
        $i = 0;
        foreach ($rows as $row) {
            $i++;
            $data[] = [
                $i,
                esc($row->hsn_code),
                esc($row->product_name),
                esc($row->map_account ?: '—'),
                $this->statusBadge($row->status),
                $this->rowActions($row),
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => count($data),
            'recordsFiltered' => count($data),
            'data'            => $data,
        ]);
    }

    /** Add / edit (AJAX). Faithful validation from CI3 save(). */
    public function save()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'POST required.']);
        }

        $id          = (int) $this->request->getPost('id');
        $hsnCode     = trim((string) $this->request->getPost('hsn_code'));
        $productName = trim((string) $this->request->getPost('product_name'));
        $mapAccount  = trim((string) $this->request->getPost('map_account'));
        $status      = $this->request->getPost('status');
        $status      = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';

        if ($hsnCode === '' || $productName === '') {
            return $this->json('error', 'Both HSN code and commodity name are required.');
        }
        if (! ctype_digit($hsnCode)) {
            return $this->json('error', 'HSN code must contain numbers only.');
        }
        if (strlen($hsnCode) > 20) {
            return $this->json('error', 'HSN code is too long (max 20 digits).');
        }

        $model = new HsnModel();
        if ($model->isDuplicate($hsnCode, $id)) {
            return $this->json('exists', 'This HSN code already exists in the system.');
        }
        if ($id > 0 && ! $model->find($id)) {
            return $this->json('error', 'HSN entry not found.');
        }

        $savedId = $model->saveRow([
            'hsn_code' => $hsnCode, 'product_name' => $productName,
            'map_account' => $mapAccount, 'status' => $status,
        ], $id);

        return $this->json('success', $id > 0 ? 'HSN code updated successfully.' : 'HSN code added successfully.', ['id' => $savedId]);
    }

    /** Soft-delete (AJAX). */
    public function delete()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'POST required.']);
        }
        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->json('error', 'Invalid id.');
        }
        $ok = (new HsnModel())->softDelete($id, (string) $this->request->getPost('reason'));
        return $ok ? $this->json('success', 'HSN code deleted.') : $this->json('error', 'Delete failed.');
    }

    /** Single row as JSON (for the edit modal prefill). */
    public function row($id = 0)
    {
        $row = (new HsnModel())->find((int) $id);
        return $row ? $this->response->setJSON(['status' => 'success', 'data' => $row])
                    : $this->json('error', 'Not found.');
    }

    private function json(string $status, string $message, array $extra = [])
    {
        return $this->response->setJSON(array_merge(['status' => $status, 'message' => $message], $extra));
    }

    private function statusBadge($status): string
    {
        $cls = $status === 'Active' ? 'success' : ($status === 'Inactive' ? 'default' : 'danger');
        return '<span class="label label-' . $cls . '">' . esc($status) . '</span>';
    }

    private function rowActions($row): string
    {
        return '<button class="btn btn-xs btn-primary hsn-edit" data-id="' . (int) $row->id . '"><i class="fa fa-edit"></i> Edit</button> '
            . '<button class="btn btn-xs btn-danger hsn-del" data-id="' . (int) $row->id . '" data-name="' . esc($row->hsn_code, 'attr') . '"><i class="fa fa-trash"></i> Delete</button>';
    }
}
