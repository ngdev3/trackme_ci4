<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\ItemMasterModel;

/**
 * Item_master — CI4 port of admin/Item_master. CRUD for stock items
 * (Name + Unit + HSN + Status) backed by the global hsn_codes catalog. Editing
 * is super-admin only; others view. Soft delete status='Delete'. rbac('item_master').
 */
class Item_master extends BaseController
{
    protected $helpers = ['url', 'app', 'cr_cache'];

    public array $units = ['Qtl', 'Kg', 'Ton', 'Bag', 'Bori', 'Packet', 'Piece', 'Nos', 'Litre', 'Bundle'];

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        $status = $this->request->getGet('status');
        $status = in_array($status, ['Active', 'Inactive', 'Delete'], true) ? $status : '';
        $model  = new ItemMasterModel();

        return _layout('\App\Modules\Admin\Views\item_master\listing', [
            'title'    => 'Item Master · C R Industries ERP',
            'stats'    => $model->stats(),
            'items'    => $model->getAll($status),
            'cur'      => $status,
            'can_edit' => function_exists('erp_is_super_admin') && erp_is_super_admin(),
        ]);
    }

    private function guardWrite(bool $ajax = false)
    {
        if (function_exists('erp_is_super_admin') && erp_is_super_admin()) {
            return true;
        }
        if ($ajax) {
            return $this->response->setJSON(['status' => 'denied', 'message' => 'Only the super admin can modify items.']);
        }
        return redirect()->to(base_url('admin/item_master/listing'))->with('error', 'Only the super admin can add, edit or delete items.');
    }

    public function add()
    {
        $g = $this->guardWrite();
        if ($g !== true) { return $g; }

        $error = ''; $old = null;
        if (strtoupper($this->request->getMethod()) === 'POST') {
            $res = $this->save(null);
            if ($res['ok']) {
                return redirect()->to(base_url('admin/item_master/listing'))->with('success', 'Item "' . $res['name'] . '" was added.');
            }
            $error = $res['error']; $old = $res['old'];
        }

        return _layout('\App\Modules\Admin\Views\item_master\add', [
            'title' => 'Add Item · C R Industries ERP', 'units' => $this->units, 'row' => null, 'error' => $error, 'old' => $old,
        ]);
    }

    public function edit($id = null)
    {
        $g = $this->guardWrite();
        if ($g !== true) { return $g; }

        $model = new ItemMasterModel();
        $itemId = (int) ID_decode($id);
        $row = $itemId ? $model->getOne($itemId) : null;
        if (! $row) {
            return redirect()->to(base_url('admin/item_master/listing'))->with('error', 'Item not found');
        }

        $error = '';
        if (strtoupper($this->request->getMethod()) === 'POST') {
            $res = $this->save($itemId);
            if ($res['ok']) {
                return redirect()->to(base_url('admin/item_master/listing'))->with('success', 'Item "' . $res['name'] . '" was updated.');
            }
            $error = $res['error'];
            $row = $model->getOne($itemId);
        }

        return _layout('\App\Modules\Admin\Views\item_master\add', [
            'title' => 'Edit Item · C R Industries ERP', 'units' => $this->units, 'row' => $row, 'error' => $error, 'old' => null,
        ]);
    }

    /** Shared validate + persist for add/edit. */
    private function save(?int $id): array
    {
        $name   = trim((string) $this->request->getPost('product_name'));
        $unit   = trim((string) $this->request->getPost('unit'));
        $hsn    = trim((string) $this->request->getPost('hsn_code'));
        $status = $this->request->getPost('status');
        $status = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';
        if (! in_array($unit, $this->units, true)) { $unit = 'Qtl'; }

        $old   = ['product_name' => $name, 'unit' => $unit, 'hsn_code' => $hsn, 'status' => $status];
        $model = new ItemMasterModel();

        if ($name === '') { return ['ok' => false, 'error' => 'Item name is required.', 'old' => $old]; }
        if ($hsn === '')  { return ['ok' => false, 'error' => 'HSN code is required.', 'old' => $old]; }
        if ($model->nameExists($name, $id)) { return ['ok' => false, 'error' => 'An item with this name already exists.', 'old' => $old]; }

        $data = ['product_name' => $name, 'unit' => $unit, 'hsn_code' => $hsn, 'status' => $status];
        if ($id) {
            $model->update($id, $data);
        } else {
            $data['added_by'] = (int) (currentuserinfo()->id ?? 0);
            $data['account_id'] = 0;
            $model->add($data);
        }
        return ['ok' => true, 'name' => $name, 'old' => $old, 'error' => ''];
    }

    public function delete()
    {
        $g = $this->guardWrite(true);
        if ($g !== true) { return $g; }
        $id = (int) $this->request->getPost('id');
        if (! $id) { return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid item']); }

        $model = new ItemMasterModel();
        if ($this->request->getPost('action') === 'restore') {
            $model->restore($id);
        } else {
            $model->softDelete($id, (int) (currentuserinfo()->id ?? 0) ?: null);
        }
        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateStatus()
    {
        $g = $this->guardWrite(true);
        if ($g !== true) { return $g; }
        $id = (int) $this->request->getPost('id');
        if (! $id) { return $this->response->setJSON(['status' => 'error']); }
        (new ItemMasterModel())->setStatus($id, (string) $this->request->getPost('status'));
        return $this->response->setJSON(['status' => 'success']);
    }
}
