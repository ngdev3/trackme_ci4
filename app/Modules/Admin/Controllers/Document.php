<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\DocumentModel;

/**
 * Document — CI4 port of admin/Document (Documents Renewal). Full flow: list,
 * add/edit with file upload, validity computation, download, delete. Scope =
 * current firm+FY or is_common (all firms). rbac('document').
 */
class Document extends BaseController
{
    protected $helpers = ['url', 'app', 'form'];
    private const UPDIR = 'uploads/documents/';
    private const ALLOWED = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt', 'csv'];

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        (new DocumentModel())->ensureColumns();
        return _layout('\App\Modules\Admin\Views\document\listing', ['title' => 'Documents Renewal · C R Industries ERP']);
    }

    public function add()
    {
        $model = new DocumentModel();
        $model->ensureColumns();
        $error = '';

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $e = $this->validateDoc();
            if ($e === '') {
                $upload = $this->uploadFile(true);
                if ($upload['status'] === 'error') {
                    return _layout('\App\Modules\Admin\Views\document\add', ['title' => 'Add Document', 'result' => null, 'upload_error' => $upload['message']]);
                }
                $isCommon = $this->request->getPost('is_common') ? 1 : 0;
                $model->add([
                    'name' => $this->request->getPost('name'),
                    'start_date' => $this->request->getPost('start_date'),
                    'end_date' => $this->request->getPost('end_date'),
                    'remark' => $this->request->getPost('remark'),
                    'document_file' => $upload['file_name'], 'original_file_name' => $upload['original_name'],
                    'file_type' => $upload['file_type'], 'file_size' => $upload['file_size'],
                    'FY' => fy()->FY, 'template_id' => $isCommon ? 0 : fy()->template_id, 'is_common' => $isCommon,
                    'status' => $this->request->getPost('status'), 'updated_date' => date('Y-m-d'),
                    'user_id' => (int) (currentuserinfo()->id ?? 0),
                ]);
                return redirect()->to(base_url('admin/document'))->with('success', 'Document added successfully');
            }
            $error = $e;
        }

        return _layout('\App\Modules\Admin\Views\document\add', ['title' => 'Add Document', 'result' => null, 'upload_error' => $error]);
    }

    public function edit($id = null)
    {
        $model = new DocumentModel();
        $model->ensureColumns();
        $docId = (int) ID_decode($id);
        $error = '';

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $e = $this->validateDoc();
            if ($e === '') {
                $isCommon = $this->request->getPost('is_common') ? 1 : 0;
                $data = [
                    'name' => $this->request->getPost('name'), 'start_date' => $this->request->getPost('start_date'),
                    'end_date' => $this->request->getPost('end_date'), 'remark' => $this->request->getPost('remark'),
                    'FY' => fy()->FY, 'template_id' => $isCommon ? 0 : fy()->template_id, 'is_common' => $isCommon,
                    'status' => $this->request->getPost('status'), 'updated_date' => date('Y-m-d'),
                    'user_id' => (int) (currentuserinfo()->id ?? 0),
                ];
                $file = $this->request->getFile('document_file');
                if ($file && $file->isValid()) {
                    $upload = $this->uploadFile(false);
                    if ($upload['status'] === 'error') {
                        return _layout('\App\Modules\Admin\Views\document\add', ['title' => 'Edit Document', 'result' => $model->view($docId), 'upload_error' => $upload['message']]);
                    }
                    $old = $model->getAccessible($docId);
                    $this->deleteFile($old);
                    $data = array_merge($data, [
                        'document_file' => $upload['file_name'], 'original_file_name' => $upload['original_name'],
                        'file_type' => $upload['file_type'], 'file_size' => $upload['file_size'],
                    ]);
                }
                $model->edit($docId, $data);
                return redirect()->to(base_url('admin/document'))->with('success', 'Document updated successfully');
            }
            $error = $e;
        }

        return _layout('\App\Modules\Admin\Views\document\add', ['title' => 'Edit Document', 'result' => $model->view($docId), 'upload_error' => $error]);
    }

    public function view_all()
    {
        $model = new DocumentModel();
        $total = $model->countBillingData();
        $rows  = $model->getBillingData();

        $data = [];
        $j = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $r) {
            $row = (array) $r;
            $j++;
            $name = '<div><i class="fa fa-file-text-o"></i> <strong>' . esc($row['name'] ?? '') . '</strong></div>';
            $remark = ! empty($row['remark']) ? esc($row['remark']) : '<span class="text-muted">Not set</span>';
            $scope = ! empty($row['is_common']) ? '<span class="label label-info">All firms</span>' : '<span class="label label-default">Current firm</span>';
            $file = ! empty($row['document_file'])
                ? '<a class="btn btn-xs btn-primary" href="' . base_url('admin/document/download/' . ID_encode((int) $row['id'])) . '"><i class="fa fa-download"></i> Download</a>'
                : '<span class="text-muted">No file</span>';
            $data[] = [
                $j, $name, $remark, $this->fmtDate($row['start_date'] ?? null), $this->fmtDate($row['end_date'] ?? null),
                $this->fmtDate($row['updated_date'] ?? null), $scope, $file, $this->validity($row['end_date'] ?? null),
                $this->rowActions((int) $row['id']),
            ];
        }

        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }

    public function download($id = null)
    {
        $doc = (new DocumentModel())->getAccessible((int) ID_decode($id));
        if (! $doc || empty($doc->document_file)) {
            return redirect()->to(base_url('admin/document'))->with('error', 'Document file not found.');
        }
        $path = FCPATH . self::UPDIR . basename($doc->document_file);
        if (! is_file($path)) {
            return redirect()->to(base_url('admin/document'))->with('error', 'Uploaded file is missing from server.');
        }
        return $this->response->download($path, null)->setFileName($doc->original_file_name ?: $doc->document_file);
    }

    public function delete()
    {
        $docId = (int) ID_decode($this->request->getPost('id'));
        $model = new DocumentModel();
        $doc = $model->getAccessible($docId);
        if (! $doc) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Document not found or not permitted.']);
        }
        $this->deleteFile($doc);
        $ok = $model->deletePermanent($docId);
        return $this->response->setJSON(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Document deleted.' : 'Delete failed.']);
    }

    /* ---- helpers ---- */
    private function validateDoc(): string
    {
        foreach (['name' => 'Document Name', 'start_date' => 'Start Date', 'end_date' => 'End Date', 'status' => 'Status'] as $k => $lbl) {
            if (trim((string) $this->request->getPost($k)) === '') { return $lbl . ' is required.'; }
        }
        return '';
    }

    private function uploadFile(bool $required): array
    {
        $file = $this->request->getFile('document_file');
        if (! $file || ! $file->isValid() || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $required ? ['status' => 'error', 'message' => 'Please upload a document file.']
                             : ['status' => 'success', 'file_name' => '', 'original_name' => '', 'file_type' => '', 'file_size' => 0];
        }
        $ext = strtolower($file->getClientExtension() ?: $file->getExtension() ?: '');
        if (! in_array($ext, self::ALLOWED, true)) {
            return ['status' => 'error', 'message' => 'File type not allowed.'];
        }
        if ($file->getSize() > 1048576) {
            return ['status' => 'error', 'message' => 'File exceeds 1 MB.'];
        }
        $dir = FCPATH . self::UPDIR;
        if (! is_dir($dir)) { @mkdir($dir, 0755, true); }
        $newName = $file->getRandomName();
        $orig = $file->getClientName();
        $type = $file->getClientMimeType();
        $size = $file->getSize();
        $file->move($dir, $newName);
        return ['status' => 'success', 'file_name' => $newName, 'original_name' => $orig, 'file_type' => $type, 'file_size' => $size];
    }

    private function deleteFile($doc): void
    {
        if ($doc && ! empty($doc->document_file)) {
            $path = FCPATH . self::UPDIR . basename($doc->document_file);
            if (is_file($path)) { @unlink($path); }
        }
    }

    private function fmtDate($d): string
    {
        if (empty($d) || $d === '0000-00-00') { return '<span class="text-muted">Not set</span>'; }
        return '<span>' . date('d M Y', strtotime($d)) . '</span>';
    }

    private function validity($end): string
    {
        if (empty($end)) { return '<span class="text-muted">No valid date</span>'; }
        $days = floor((strtotime($end) - strtotime(date('Y-m-d'))) / 86400);
        if ($days > 30)      { return '<span class="label label-success">Valid for ' . $days . ' days</span>'; }
        if ($days > 0)       { return '<span class="label label-warning">Expiring in ' . $days . ' days</span>'; }
        if ($days === 0.0)   { return '<span class="label label-warning">Expires Today</span>'; }
        return '<span class="label label-danger">Expired ' . abs($days) . ' days ago</span>';
    }

    private function rowActions(int $id): string
    {
        $enc = ID_encode($id);
        return '<div class="text-nowrap">'
            . '<a class="btn btn-xs btn-primary" href="' . base_url('admin/document/edit/' . $enc) . '"><i class="fa fa-edit"></i></a> '
            . '<button class="btn btn-xs btn-danger doc-del" data-id="' . esc($enc, 'attr') . '"><i class="fa fa-trash"></i></button></div>';
    }
}
