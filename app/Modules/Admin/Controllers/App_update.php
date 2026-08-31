<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AppUpdateModel;

/**
 * App_update — CI4 port of admin/App_update (APK Manager). Publish Android builds:
 * version history, .apk upload (magic-byte checked, size-limited, unique code),
 * enable/disable + force-update + website flags, mark-latest, delete, settings,
 * download logs. Global (one app for all firms). rbac('app_update').
 */
class App_update extends BaseController
{
    protected $helpers = ['url', 'app'];
    private const APKDIR = 'uploads/apk/';

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        $model = new AppUpdateModel();
        $model->ensureTables();
        return _layout('\App\Modules\Admin\Views\app_update\listing', [
            'title' => 'APK Manager · C R Industries ERP',
            'stats' => $model->dashboardStats(),
        ]);
    }

    public function versions_data()
    {
        $model = new AppUpdateModel();
        $rows  = $model->getVersions();
        $data = [];
        $i = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $v) {
            $i++;
            $flags = [];
            if ((int) $v->is_latest) { $flags[] = '<span class="label label-success">Latest</span>'; }
            if ((int) $v->force_update) { $flags[] = '<span class="label label-danger">Force</span>'; }
            if ((int) $v->website_visible) { $flags[] = '<span class="label label-info">Website</span>'; }
            $status = $v->status === 'Active' ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Inactive</span>';
            $data[] = [
                $i,
                esc($v->version_name) . ' <small>(code ' . (int) $v->version_code . ')</small>',
                number_format($v->file_size / 1048576, 2) . ' MB',
                esc($v->release_notes ?? ''),
                implode(' ', $flags) ?: '—',
                $status,
                (int) $v->download_count,
                esc($v->uploaded_by_name ?? ''),
                $this->rowActions($v),
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $model->countVersions(), 'recordsFiltered' => $model->countVersions(), 'data' => $data]);
    }

    public function upload()
    {
        $model = new AppUpdateModel();
        $model->ensureTables();
        $error = '';

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $res = $this->handleUpload($model);
            if ($res['status'] === 'success') {
                return redirect()->to(base_url('admin/app_update/listing'))->with('success', 'Build ' . $res['version'] . ' uploaded.');
            }
            $error = $res['message'];
        }
        return _layout('\App\Modules\Admin\Views\app_update\upload', ['title' => 'Upload Build', 'error' => $error, 'max_mb' => $model->setting('max_apk_mb', '150')]);
    }

    private function handleUpload(AppUpdateModel $model): array
    {
        $name = trim((string) $this->request->getPost('version_name'));
        $code = (int) $this->request->getPost('version_code');
        if ($name === '' || $code <= 0) { return ['status' => 'error', 'message' => 'Version name and a positive version code are required.']; }
        if ($model->versionCodeExists($code)) { return ['status' => 'error', 'message' => 'Version code ' . $code . ' already exists.']; }

        $file = $this->request->getFile('apk_file');
        if (! $file || ! $file->isValid()) { return ['status' => 'error', 'message' => 'Please choose a valid .apk file.']; }
        if (strtolower($file->getClientExtension()) !== 'apk') { return ['status' => 'error', 'message' => 'Only .apk files are allowed.']; }
        $maxBytes = (int) $model->setting('max_apk_mb', '150') * 1048576;
        if ($maxBytes > 0 && $file->getSize() > $maxBytes) { return ['status' => 'error', 'message' => 'File exceeds ' . $model->setting('max_apk_mb', '150') . ' MB.']; }

        // APK = ZIP: verify PK magic bytes.
        $fh = @fopen($file->getTempName(), 'rb');
        $magic = $fh ? fread($fh, 4) : '';
        if ($fh) { fclose($fh); }
        if ($magic !== "PK\x03\x04") { return ['status' => 'error', 'message' => 'Not a valid APK (failed signature check).']; }

        $dir = FCPATH . self::APKDIR;
        if (! is_dir($dir)) { @mkdir($dir, 0755, true); }
        $stored = preg_replace('/[^A-Za-z0-9._-]+/', '_', 'crerp_v' . $name . '_c' . $code . '_' . date('YmdHis') . '.apk');
        $size = $file->getSize();
        $file->move($dir, $stored);

        $id = $model->insertVersion([
            'version_name' => $name, 'version_code' => $code,
            'apk_file_name' => $stored, 'apk_file_path' => self::APKDIR . $stored, 'file_size' => $size,
            'release_notes' => trim((string) $this->request->getPost('release_notes')),
            'status' => 'Active', 'uploaded_by' => (int) (currentuserinfo()->id ?? 0),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($this->request->getPost('mark_latest')) { $model->markLatest($id); }
        return ['status' => 'success', 'version' => $name];
    }

    public function toggle_status()
    {
        $model = new AppUpdateModel();
        $id = (int) $this->request->getPost('id');
        $v = $model->getVersion($id);
        if ($v) { $model->updateVersion($id, ['status' => $v->status === 'Active' ? 'Inactive' : 'Active', 'updated_at' => date('Y-m-d H:i:s')]); }
        return $this->response->setJSON(['status' => 'success']);
    }

    public function flag_toggle()
    {
        $model = new AppUpdateModel();
        $id = (int) $this->request->getPost('id');
        $flag = (string) $this->request->getPost('flag');
        if (in_array($flag, ['force_update', 'website_visible'], true) && ($v = $model->getVersion($id))) {
            $model->updateVersion($id, [$flag => (int) $v->$flag === 1 ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')]);
        }
        return $this->response->setJSON(['status' => 'success']);
    }

    public function mark_latest()
    {
        $id = (int) $this->request->getPost('id');
        if ($id) { (new AppUpdateModel())->markLatest($id); }
        return $this->response->setJSON(['status' => 'success']);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        if ($id) { (new AppUpdateModel())->softDelete($id); }
        return $this->response->setJSON(['status' => 'success']);
    }

    public function download($id = null)
    {
        $model = new AppUpdateModel();
        $v = $model->getVersion((int) $id);
        if (! $v) { return redirect()->to(base_url('admin/app_update/listing'))->with('error', 'Build not found.'); }
        $path = FCPATH . $v->apk_file_path;
        if (! is_file($path)) { return redirect()->to(base_url('admin/app_update/listing'))->with('error', 'APK file missing on server.'); }
        $model->updateVersion((int) $v->id, ['download_count' => (int) $v->download_count + 1]);
        return $this->response->download($path, null)->setFileName($v->version_name . '.apk');
    }

    public function settings()
    {
        $model = new AppUpdateModel();
        $model->ensureTables();
        if (strtoupper($this->request->getMethod()) === 'POST') {
            foreach (['play_store_url', 'website_section_enabled', 'public_download_enabled', 'app_name', 'keep_apk_files', 'max_apk_mb'] as $k) {
                $val = $this->request->getPost($k);
                if ($val !== null) { $model->saveSetting($k, (string) $val); }
            }
            return redirect()->to(base_url('admin/app_update/settings'))->with('success', 'Settings saved.');
        }
        return _layout('\App\Modules\Admin\Views\app_update\settings', ['title' => 'APK Settings', 'settings' => $model->allSettings()]);
    }

    public function logs()
    {
        $model = new AppUpdateModel();
        $model->ensureTables();
        return _layout('\App\Modules\Admin\Views\app_update\logs', ['title' => 'Download Logs', 'logs' => $model->getDownloadLogs(500)]);
    }

    public function portal()
    {
        $model = new AppUpdateModel();
        $model->ensureTables();
        return _layout('\App\Modules\Admin\Views\app_update\portal', ['title' => 'Employee Portal', 'latest' => $model->latestVersion()]);
    }

    private function rowActions($v): string
    {
        $id = (int) $v->id;
        return '<div class="text-nowrap">'
            . '<a class="btn btn-xs btn-default" href="' . base_url('admin/app_update/download/' . $id) . '"><i class="fa fa-download"></i></a> '
            . '<button class="btn btn-xs btn-success apk-latest" data-id="' . $id . '" title="Mark latest"><i class="fa fa-star"></i></button> '
            . '<button class="btn btn-xs btn-warning apk-toggle" data-id="' . $id . '" title="Enable/disable"><i class="fa fa-power-off"></i></button> '
            . '<button class="btn btn-xs btn-info apk-flag" data-id="' . $id . '" data-flag="force_update" title="Force update"><i class="fa fa-exclamation"></i></button> '
            . '<button class="btn btn-xs btn-danger apk-del" data-id="' . $id . '"><i class="fa fa-trash"></i></button></div>';
    }
}
