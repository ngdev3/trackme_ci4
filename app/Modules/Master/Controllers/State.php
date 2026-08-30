<?php

namespace App\Modules\Master\Controllers;

use App\Controllers\BaseController;
use App\Modules\Master\Models\StateModel;

/**
 * State — CI4 port of master/State (simplest lookup CRUD; name + status).
 * Feeds the City master's dropdown. URLs preserved: master/state[...].
 * Gated rbac('state').
 */
class State extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Master\Views\state\listing', [
            'title' => 'State Master · C R Industries ERP',
            'total' => (new StateModel())->countActive(),
        ]);
    }

    public function listingData()
    {
        $search = $this->request->getPost('search')['value'] ?? null;
        $rows   = (new StateModel())->listRows($search ?: null);
        $data = [];
        $i = 0;
        foreach ($rows as $row) {
            $i++;
            $cls = $row->status === 'Active' ? 'success' : ($row->status === 'Inactive' ? 'default' : 'danger');
            $data[] = [
                $i,
                esc($row->name),
                '<span class="label label-' . $cls . '">' . esc($row->status) . '</span>',
                '<button class="btn btn-xs btn-primary st-edit" data-id="' . (int) $row->state_id . '"><i class="fa fa-edit"></i> Edit</button> '
                . '<button class="btn btn-xs btn-danger st-del" data-id="' . (int) $row->state_id . '" data-name="' . esc($row->name, 'attr') . '"><i class="fa fa-trash"></i></button>',
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
        $id     = (int) $this->request->getPost('id');
        $name   = trim((string) $this->request->getPost('name'));
        $status = in_array($this->request->getPost('status'), ['Active', 'Inactive'], true) ? $this->request->getPost('status') : 'Active';

        if ($name === '') {
            return $this->json('error', 'State name is required.');
        }
        $model = new StateModel();
        if ($model->isDuplicate($name, $id)) {
            return $this->json('exists', 'This state already exists.');
        }
        $savedId = $model->saveRow(['name' => $name, 'status' => $status], $id);
        return $this->json('success', $id > 0 ? 'State updated successfully.' : 'State added successfully.', ['id' => $savedId]);
    }

    public function delete()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'POST required.']);
        }
        return (new StateModel())->softDelete((int) $this->request->getPost('id'))
            ? $this->json('success', 'State deleted.') : $this->json('error', 'Delete failed.');
    }

    public function row($id = 0)
    {
        $row = (new StateModel())->find((int) $id);
        return $row ? $this->response->setJSON(['status' => 'success', 'data' => $row]) : $this->json('error', 'Not found.');
    }

    private function json(string $status, string $message, array $extra = [])
    {
        return $this->response->setJSON(array_merge(['status' => $status, 'message' => $message], $extra));
    }
}
