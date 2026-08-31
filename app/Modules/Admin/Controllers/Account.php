<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AccountModel;

/**
 * Account — CI4 port of admin/Account (Jama/Naam voucher — cash-book write).
 *
 * The combined entry page (admin/account/entry?type=deposit|expenses) tabs the
 * Deposit (जमा) and Expenditure (नाम) forms into one page. save_entry() is the
 * AJAX write; entry() also handles a no-JS POST fallback. Both funnel through
 * _persist_entry(), which resolves/creates the party and inserts one aa_rokad
 * row scoped by FY + firm, with optional image/voice/video media.
 */
class Account extends BaseController
{
    protected $helpers = ['url', 'form', 'text', 'app', 'cr_cache'];

    private function model(): AccountModel
    {
        return new AccountModel();
    }

    /** Combined Jama/Naam entry page (+ no-JS POST fallback). */
    public function entry()
    {
        if ($this->request->is('post')) {
            if ($this->validate($this->_entry_rules())) {
                $this->_ensure_rokad_media_columns();
                $media_err = $this->_validate_entry_media();
                if ($media_err !== '') {
                    session()->setFlashdata('error', $media_err);
                    return redirect()->to('/admin/account/entry');
                }
                $this->_persist_entry();
                $type = (string) $this->request->getPost('type_of_account');
                session()->setFlashdata('success', ($type === 'expenses' ? 'Expenditure (नाम)' : 'Deposit (जमा)') . ' entry added successfully.');
                return redirect()->to('/admin/account/entry');
            }
        }

        $req_type = $this->request->getGet('type');
        return _layout('\App\Modules\Admin\Views\account\entry_tabs', [
            'title'        => 'Track (The Rest Accounting Key) || Add Entry',
            'default_type' => ($req_type === 'expenses') ? 'expenses' : 'deposit',
            'validation'   => $this->validator,
        ]);
    }

    /** AJAX save — one rokad row, returns JSON { status, message }. */
    public function save_entry()
    {
        if (! $this->validate($this->_entry_rules())) {
            $msg = trim(strip_tags(implode(' ', $this->validator->getErrors())));
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $msg !== '' ? $msg : 'Please fill all the required fields.',
            ]);
        }

        $this->_ensure_rokad_media_columns();
        $media_err = $this->_validate_entry_media();
        if ($media_err !== '') {
            return $this->response->setJSON(['status' => 'error', 'message' => $media_err]);
        }

        $this->_persist_entry();
        $type = (string) $this->request->getPost('type_of_account');
        return $this->response->setJSON([
            'status'  => 'success',
            'message' => ($type === 'expenses' ? 'Expenditure (नाम)' : 'Deposit (जमा)') . ' entry saved successfully.',
        ]);
    }

    /** Shared validation rules for the combined add-entry flow. */
    private function _entry_rules(): array
    {
        return [
            'billing_date'    => 'trim|required',
            'account_name'    => 'trim|required',
            'type_of_account' => 'trim|required',
            'karch_amount'    => 'trim|required',
            'status'          => 'trim|required',
        ];
    }

    /**
     * Persist one rokad entry from the posted form. Resolves the account id
     * robustly (trailing _<id> -> map by name -> create), scopes by FY + firm,
     * saves any optional media. Returns the new rokad id.
     */
    private function _persist_entry(): int
    {
        $req      = $this->request;
        $old_date = $req->getPost('billing_date');
        session()->set('billing_date', $old_date);
        $new_date = date('Y-m-d', strtotime($old_date));

        $postedName = trim((string) $req->getPost('account_name'));
        $account_no = '';
        if (preg_match('/^(.*)_(\d+)$/', $postedName, $mm)) {
            $account_no   = (int) $mm[2];
            $accountLabel = $postedName;
        } else {
            $acc = $this->model()->get_account_by_name($postedName);
            if ($acc) {
                $account_no = $acc->account_id;
            } else {
                $account_no = $this->model()->add_account([
                    'name'     => $postedName,
                    'added_by' => currentuserinfo()->id,
                    'status'   => $req->getPost('status') ?: 'Active',
                ]);
            }
            $accountLabel = $postedName . '_' . $account_no;
        }

        // Party account is optional — never index an empty value.
        $party_account_no = '';
        if (! empty($req->getPost('party_account_no'))) {
            $pp = explode('_', $req->getPost('party_account_no'));
            $party_account_no = $pp[1] ?? '';
        }

        $userdata = [
            'rokad_date'       => $new_date,
            'rokad_entry_no'   => $req->getPost('khata_entry_no'),
            'challan_no'       => $req->getPost('challan_no'),
            'type_of_account'  => $req->getPost('type_of_account'),
            'remark'           => $req->getPost('remark'),
            'account_name'     => $accountLabel,
            'karch_amount'     => $req->getPost('karch_amount'),
            'payment_mode'     => $req->getPost('payment_mode'),
            'mill_id'          => $req->getPost('mill_id'),
            'truck_no'         => $req->getPost('truck_no'),
            'party_account_no' => $party_account_no,
            'party_invoice_no' => $req->getPost('party_invoice_no'),
            'quantity'         => $req->getPost('quantity'),
            'rate'             => $req->getPost('rate'),
            'added_by'         => currentuserinfo()->id,
            'status'           => $req->getPost('status'),
            'account_no'       => $account_no,
            'FY'               => fy()->FY,
            'product_type'     => fy()->product_type,
            'template_id'      => fy()->template_id,
        ];

        $rokad_id = $this->model()->add($userdata);
        $media    = $this->_process_entry_media($rokad_id, null);
        if (! empty($media)) {
            $this->model()->edit($rokad_id, $media);
        }
        if (function_exists('entry_trace')) {
            entry_trace('rokad', $rokad_id, 'create');
        }
        return $rokad_id;
    }

    /* ------------------------------------------------------------ media */

    private function _entry_media_specs(): array
    {
        return [
            'image' => [
                'field' => 'image_path', 'file' => 'image_file', 'remove' => 'remove_image',
                'folder' => 'rokad_images', 'prefix' => 'rokad', 'label' => 'Image',
                'exts' => ['jpg', 'jpeg', 'png', 'webp', 'gif'], 'max' => 5242880,
            ],
            'voice' => [
                'field' => 'voice_note_path', 'file' => 'voice_file', 'remove' => 'remove_voice',
                'folder' => 'rokad_voice', 'prefix' => 'rokad_voice', 'label' => 'Voice recording',
                'exts' => ['webm', 'ogg', 'mp3', 'm4a', 'aac', 'wav'], 'max' => 15728640,
            ],
            'video' => [
                'field' => 'video_note_path', 'file' => 'video_file', 'remove' => 'remove_video',
                'folder' => 'rokad_video', 'prefix' => 'rokad_video', 'label' => 'Video recording',
                'exts' => ['webm', 'mp4', 'mov', 'avi', '3gp', 'mkv'], 'max' => 62914560,
            ],
        ];
    }

    /** Idempotent guard: ensure the media + payment_mode columns exist (MariaDB). */
    private function _ensure_rokad_media_columns(): void
    {
        $db = $this->model_db();
        @$db->query("ALTER TABLE aa_rokad ADD COLUMN IF NOT EXISTS image_path TEXT DEFAULT NULL");
        @$db->query("ALTER TABLE aa_rokad ADD COLUMN IF NOT EXISTS voice_note_path TEXT DEFAULT NULL");
        @$db->query("ALTER TABLE aa_rokad ADD COLUMN IF NOT EXISTS video_note_path TEXT DEFAULT NULL");
        @$db->query("ALTER TABLE aa_rokad ADD COLUMN IF NOT EXISTS payment_mode VARCHAR(20) DEFAULT NULL");
    }

    private function model_db()
    {
        return \Config\Database::connect();
    }

    /** Validate uploaded files (type + size). Returns error string, or '' when valid. */
    private function _validate_entry_media(): string
    {
        foreach ($this->_entry_media_specs() as $spec) {
            $key = $spec['file'];
            if (empty($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $f = $_FILES[$key];
            if ($f['error'] !== UPLOAD_ERR_OK) {
                return $spec['label'] . ' upload failed. Please try again.';
            }
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (! in_array($ext, $spec['exts'], true)) {
                return $spec['label'] . ' must be one of: ' . implode(', ', $spec['exts']) . '.';
            }
            if ($f['size'] > $spec['max']) {
                return $spec['label'] . ' exceeds the maximum size of ' . round($spec['max'] / 1048576) . ' MB.';
            }
        }
        return '';
    }

    /** Apply attachment changes for a rokad entry. Returns columns to update. */
    private function _process_entry_media($rokad_id, $existing = null): array
    {
        $update = [];
        foreach ($this->_entry_media_specs() as $spec) {
            $current = ($existing && isset($existing->{$spec['field']})) ? $existing->{$spec['field']} : '';

            $new = $this->_save_uploaded_entry_file($spec['file'], $rokad_id, $spec['folder'], $spec['prefix'], $spec['exts']);
            if ($new !== null) {
                if (! empty($current)) {
                    $this->_delete_entry_file($current);
                }
                $update[$spec['field']] = $new;
                continue;
            }

            if ($this->request->getPost($spec['remove']) && ! empty($current)) {
                $this->_delete_entry_file($current);
                $update[$spec['field']] = '';
            }
        }
        return $update;
    }

    /** Move one validated upload into uploads/<folder>/<FY>/. Returns web path or null. */
    private function _save_uploaded_entry_file($file_key, $rokad_id, $folder, $prefix, $allowed_exts)
    {
        if (empty($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $f   = $_FILES[$file_key];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (! in_array($ext, $allowed_exts, true) || ! is_uploaded_file($f['tmp_name'])) {
            return null;
        }

        $fy_folder = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) fy()->FY);
        if ($fy_folder === '') {
            $fy_folder = 'unknown';
        }
        $rel_dir = 'uploads/' . $folder . '/' . $fy_folder . '/';
        $abs_dir = FCPATH . $rel_dir;
        if (! is_dir($abs_dir)) {
            @mkdir($abs_dir, 0755, true);
        }

        $filename = $prefix . '_' . $rokad_id . '_' . time() . '.' . $ext;
        if (! @move_uploaded_file($f['tmp_name'], $abs_dir . $filename)) {
            return null;
        }
        return $rel_dir . $filename;
    }

    /** Safely delete a stored attachment (only inside uploads/). */
    private function _delete_entry_file($path): void
    {
        $path = (string) $path;
        if ($path === '' || strpos($path, 'uploads/') !== 0 || strpos($path, '..') !== false) {
            return;
        }
        $abs = FCPATH . $path;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
