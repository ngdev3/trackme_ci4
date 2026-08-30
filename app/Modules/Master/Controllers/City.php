<?php

namespace App\Modules\Master\Controllers;

use App\Controllers\BaseController;
use App\Modules\Master\Models\CityModel;

/**
 * City — CI4 port of the master/City lookup CRUD. Demonstrates a TOP-LEVEL
 * module (CI3: extends CI_Controller with the manual guard trio; CI4: the
 * master/* filter group applies adminAuth + fyContext + rbac) and a foreign-key
 * dropdown (state). URLs preserved: master/city[...]. Gated rbac('city').
 */
class City extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        $model = new CityModel();
        return _layout('\App\Modules\Master\Views\city\listing', [
            'title'  => 'City Master · C R Industries ERP',
            'total'  => $model->countActive(),
            'states' => $model->states(),
        ]);
    }

    public function listingData()
    {
        $model  = new CityModel();
        $search = $this->request->getPost('search')['value'] ?? null;
        $rows   = $model->listRows($search ?: null);

        $data = [];
        $i = 0;
        foreach ($rows as $row) {
            $i++;
            $data[] = [
                $i,
                esc($row->name),
                esc($row->state_name ?: '—'),
                $this->statusBadge($row->status),
                '<button class="btn btn-xs btn-primary city-edit" data-id="' . (int) $row->city_id . '"><i class="fa fa-edit"></i> Edit</button> '
                . '<button class="btn btn-xs btn-danger city-del" data-id="' . (int) $row->city_id . '" data-name="' . esc($row->name, 'attr') . '"><i class="fa fa-trash"></i></button>',
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
        $id      = (int) $this->request->getPost('id');
        $name    = trim((string) $this->request->getPost('name'));
        $stateId = (int) $this->request->getPost('state_id');
        $status  = in_array($this->request->getPost('status'), ['Active', 'Inactive'], true) ? $this->request->getPost('status') : 'Active';

        if ($name === '') {
            return $this->json('error', 'City name is required.');
        }
        if ($stateId <= 0) {
            return $this->json('error', 'Please select a state.');
        }
        $model = new CityModel();
        if ($model->isDuplicate($name, $id)) {
            return $this->json('exists', 'This city already exists.');
        }
        $savedId = $model->saveRow(['name' => $name, 'state_id' => $stateId, 'status' => $status], $id);
        return $this->json('success', $id > 0 ? 'City updated successfully.' : 'City added successfully.', ['id' => $savedId]);
    }

    public function delete()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'POST required.']);
        }
        $id = (int) $this->request->getPost('id');
        return (new CityModel())->softDelete($id) ? $this->json('success', 'City deleted.') : $this->json('error', 'Delete failed.');
    }

    public function row($id = 0)
    {
        $row = (new CityModel())->find((int) $id);
        return $row ? $this->response->setJSON(['status' => 'success', 'data' => $row]) : $this->json('error', 'Not found.');
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
}
