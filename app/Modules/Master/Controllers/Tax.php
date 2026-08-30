<?php

namespace App\Modules\Master\Controllers;

use App\Controllers\BaseController;
use App\Modules\Master\Models\TaxModel;

/**
 * Tax — CI4 port of master/Tax (GST-rate lookup: CGST/SGST/GST %). URLs
 * preserved: master/tax[...]. Gated rbac('tax').
 */
class Tax extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Master\Views\tax\listing', [
            'title' => 'Tax Master · C R Industries ERP',
            'total' => (new TaxModel())->countActive(),
        ]);
    }

    public function listingData()
    {
        $rows = (new TaxModel())->listRows();
        $data = [];
        $i = 0;
        foreach ($rows as $row) {
            $i++;
            $cls = $row->status === 'Active' ? 'success' : ($row->status === 'Inactive' ? 'default' : 'danger');
            $gst = ($row->gst !== null && $row->gst !== '') ? $row->gst : (float) $row->cgst + (float) $row->sgst;
            $data[] = [
                $i,
                esc($row->cgst) . ' %',
                esc($row->sgst) . ' %',
                '<b>' . esc($gst) . ' %</b>',
                '<span class="label label-' . $cls . '">' . esc($row->status) . '</span>',
                '<button class="btn btn-xs btn-primary tx-edit" data-id="' . (int) $row->tax_id . '"><i class="fa fa-edit"></i> Edit</button> '
                . '<button class="btn btn-xs btn-danger tx-del" data-id="' . (int) $row->tax_id . '"><i class="fa fa-trash"></i></button>',
            ];
        }
        return $this->response->setJSON([
            'draw' => (int) $this->request->getPost('draw'),
            'recordsTotal' => count($data), 'recordsFiltered' => count($data), 'data' => $data,
        ]);
    }

    public function save()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'POST required.']);
        }
        $id   = (int) $this->request->getPost('id');
        $cgst = trim((string) $this->request->getPost('cgst'));
        $sgst = trim((string) $this->request->getPost('sgst'));
        $gst  = trim((string) $this->request->getPost('gst'));
        $status = in_array($this->request->getPost('status'), ['Active', 'Inactive'], true) ? $this->request->getPost('status') : 'Active';

        if ($cgst === '' || ! is_numeric($cgst) || $sgst === '' || ! is_numeric($sgst)) {
            return $this->json('error', 'CGST % and SGST % are required and must be numeric.');
        }
        if ($gst !== '' && ! is_numeric($gst)) {
            return $this->json('error', 'GST % must be numeric.');
        }
        if ($gst === '') {
            $gst = (float) $cgst + (float) $sgst; // sensible default = CGST + SGST
        }

        $savedId = (new TaxModel())->saveRow(['cgst' => $cgst, 'sgst' => $sgst, 'gst' => $gst, 'status' => $status], $id);
        return $this->json('success', $id > 0 ? 'Tax rate updated.' : 'Tax rate added.', ['id' => $savedId]);
    }

    public function delete()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'POST required.']);
        }
        return (new TaxModel())->softDelete((int) $this->request->getPost('id'))
            ? $this->json('success', 'Tax rate deleted.') : $this->json('error', 'Delete failed.');
    }

    public function row($id = 0)
    {
        $row = (new TaxModel())->find((int) $id);
        return $row ? $this->response->setJSON(['status' => 'success', 'data' => $row]) : $this->json('error', 'Not found.');
    }

    private function json(string $status, string $message, array $extra = [])
    {
        return $this->response->setJSON(array_merge(['status' => $status, 'message' => $message], $extra));
    }
}
