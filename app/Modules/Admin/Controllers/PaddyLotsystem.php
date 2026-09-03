<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\PaddyLotsystemModel;

/** Paddy Center Challan (Paddy Lot System) — CI4 port, listing slice. Gated rbac('PaddyLotsystem'). */
class PaddyLotsystem extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\paddy_lot_system\listing', ['title' => 'Paddy Lot System · C R Industries ERP']);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new PaddyLotsystemModel();
        $total = $model->countData();
        $rows  = $model->getData();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [
                $j,
                esc($row->lot_number ?? ''),
                esc($row->dispatch_date ?? ''),
                esc($row->type_of_bags ?? ''),
                esc($row->total_bags ?? ''),
                esc($row->quantity ?? ''),
                esc($row->mill_name ?? ''),
                '<span class="label label-' . (strtolower((string) ($row->status ?? '')) === 'accept' ? 'success' : 'default') . '">' . esc($row->status ?? '') . '</span>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }

    /**
     * Add Paddy Lot — GET renders the form with its dropdowns.
     * Faithful port of CI3 PaddyLotsystem::add() render path. (Submit not ported.)
     */
    public function add()
    {
        $model = new PaddyLotsystemModel();

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $lot = trim((string) $this->request->getPost('lot_number'));
            $err = '';
            if ($lot === '') { $err = 'Lot Number is required.'; }
            elseif (trim((string) $this->request->getPost('center_id')) === '') { $err = 'Center is required.'; }
            elseif (trim((string) $this->request->getPost('dispatch_date')) === '') { $err = 'Dispatch Date is required.'; }
            elseif (trim((string) $this->request->getPost('mill_name')) === '') { $err = 'Mill Name is required.'; }
            elseif (trim((string) $this->request->getPost('status')) === '') { $err = 'Status is required.'; }
            elseif ($this->request->getPost('status') === 'accept' && trim((string) $this->request->getPost('lot_accept_date')) === '') {
                $err = 'Lot Accept Date is required when status is Accept.';
            }

            if ($err === '' && $model->check_preexistance($lot)) {
                $err = 'Paddy Lot "' . $lot . '" already exists.';
            }

            if ($err !== '') {
                session()->setFlashdata('error', $err);
                return redirect()->to(base_url('admin/PaddyLotsystem/add'))->withInput();
            }

            $model->add($this->paddyPayloadFromPost());
            $center = function_exists('get_center_name') ? get_center_name($this->request->getPost('center_id')) : '';
            $detail = 'Paddy Lot "' . $lot . '"' . ($center ? ' (Center: ' . $center . ')' : '') . ' was added.';
            if (function_exists('notify')) {
                notify('New paddy lot <b>' . esc($lot) . '</b> added' . ($center ? ' &middot; ' . esc($center) : ''), base_url('admin/PaddyLotsystem/listing'), ['event' => 'added', 'remark' => $detail]);
            }
            if (function_exists('flash_toast')) { flash_toast($detail, 'success', 'Paddy Lot added'); }
            return redirect()->to(base_url('admin/PaddyLotsystem/listing'))->with('success', 'Paddy Lot Added Successfully');
        }

        $data = [
            'center_list'    => $model->center_list(),
            'get_truck_list' => $model->get_truck_list(),
            'get_driver_list' => $model->get_driver_list(),
            'result'         => null,
            'title'          => 'Track (The Rest Accounting Key) || Add',
        ];
        return _layout('\App\Modules\Admin\Views\paddy_lot_system\add', $data);
    }

    /** Build the paddy_lot_system row from POST. (1:1 port of paddy_payload_from_post) */
    private function paddyPayloadFromPost(): array
    {
        return [
            'center_id'       => $this->request->getPost('center_id'),
            'lot_number'      => $this->request->getPost('lot_number'),
            'dispatch_date'   => $this->normalizePaddyDate($this->request->getPost('dispatch_date')),
            'type_of_bags'    => $this->request->getPost('type_of_bags'),
            'total_bags'      => $this->request->getPost('total_bags'),
            'quantity'        => $this->request->getPost('quantity'),
            'mill_name'       => $this->request->getPost('mill_name'),
            'remark'          => $this->request->getPost('remark'),
            'status'          => $this->request->getPost('status'),
            'added_by'        => (int) (currentuserinfo()->id ?? 0),
            'FY'              => fy()->FY,
            'product_type'    => fy()->product_type,
            'template_id'     => fy()->template_id,
            'updated_date'    => date('Y-m-d'),
            'lot_accept_date' => $this->request->getPost('status') === 'accept'
                ? $this->normalizePaddyDate($this->request->getPost('lot_accept_date')) : '',
        ];
    }

    /** Accept d-m-Y / Y-m-d / d/m/Y, store as Y-m-d. (1:1 port of normalize_paddy_date) */
    private function normalizePaddyDate($date): string
    {
        $date = trim((string) $date);
        if ($date === '') { return ''; }
        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $format) {
            $parsed = \DateTime::createFromFormat($format, $date);
            if ($parsed && $parsed->format($format) === $date) {
                return $parsed->format('Y-m-d');
            }
        }
        $time = strtotime($date);
        return $time ? date('Y-m-d', $time) : '';
    }
}
