<?php

namespace Modules\Passwords\Controllers;

use App\Controllers\BaseController;
use App\Libraries\PasswordVault;
use App\Models\PasswordModel;

/**
 * Password Manager — a company-scoped, encrypted credential vault with search,
 * category filter, add/edit/delete and an on-demand (permission-checked) reveal.
 *
 * Scope: per-company (shared with a company's members, gated by the `passwords`
 * module permissions). `created_by` records the author. Passwords are stored
 * encrypted and only decrypted on an explicit reveal call — never rendered into
 * a listing.
 */
class PasswordController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company', 'hashid'];

    protected string $moduleCode = 'passwords';
    protected string $baseRoute  = 'passwords';

    /** Preset categories offered in the form (free text is also allowed). */
    private const CATEGORIES = ['Website', 'Email', 'Banking', 'Social', 'Server / Hosting', 'Application', 'API / Keys', 'Other'];

    protected PasswordModel $vault;
    protected PasswordVault $crypt;

    public function __construct()
    {
        $this->vault = new PasswordModel();
        $this->crypt = new PasswordVault();
    }

    private function uid(): int
    {
        return (int) user_id();
    }

    /** The active company id — all vault data is scoped to it. */
    private function cid(): ?int
    {
        return company_id();
    }

    /**
     * Accept current opaque IDs while keeping old numeric URLs/forms working.
     */
    private function decodeId($id): int
    {
        $id = trim((string) $id);
        if ($id === '') {
            return 0;
        }
        if (ctype_digit($id)) {
            return (int) $id;
        }
        return unhid($id);
    }

    private function encodedUrl(string $action, int $id): string
    {
        return site_url($this->baseRoute . '/' . trim($action, '/') . '/' . hid($id));
    }

    // ---------------------------------------------------------------
    // Listing (search + category filter)
    // ---------------------------------------------------------------
    public function index()
    {
        $cid      = $this->cid();
        $search   = trim((string) $this->request->getGet('q'));
        $category = trim((string) $this->request->getGet('category'));

        $builder = $this->vault->scoped($cid)->orderBy('passwords.title', 'ASC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('passwords.title', $search)
                ->orLike('passwords.website', $search)
                ->orLike('passwords.username', $search)
                ->orLike('passwords.category', $search)
                ->groupEnd();
        }
        if ($category !== '') {
            $builder->where('passwords.category', $category);
        }

        return $this->render('index', [
            'title'      => 'Password Manager',
            'breadcrumb' => [['label' => 'Password Manager']],
            'rows'       => $builder->paginate(12),
            'pager'      => $this->vault->pager,
            'search'     => $search,
            'category'   => $category,
            'categories' => $this->vault->categories($cid),
            'canAdd'     => can('passwords', 'add'),
            'canEdit'    => can('passwords', 'edit'),
            'canDelete'  => can('passwords', 'delete'),
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
            'css'        => [
                base_url('assets/css/tm-table.css'),
                base_url('assets/css/passwords.css'),
            ],
        ]);
    }

    // ---------------------------------------------------------------
    // Details view (a single record — read-only)
    // ---------------------------------------------------------------
    public function view($id = null)
    {
        $row = $this->vault->findForCompany($this->decodeId($id), $this->cid());
        if (! $row) {
            return redirect()->to(site_url('passwords'))->with('error', 'Password entry not found.');
        }
        $creator = null;
        if (! empty($row['created_by'])) {
            $u = (new \App\Models\UserModel())->find((int) $row['created_by']);
            $creator = $u['name'] ?? ($u['username'] ?? null);
        }

        return $this->render('view', [
            'title'      => $row['title'],
            'breadcrumb' => [['label' => 'Password Manager', 'url' => site_url('passwords')], ['label' => 'View']],
            'row'        => $row,
            'creator'    => $creator,
            'canEdit'    => can('passwords', 'edit'),
            'canDelete'  => can('passwords', 'delete'),
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
            'css'        => [base_url('assets/css/passwords.css')],
        ]);
    }

    // ---------------------------------------------------------------
    // Create / edit forms
    // ---------------------------------------------------------------
    public function create()
    {
        return $this->render('form', $this->formData(null, 'create'));
    }

    public function edit($id = null)
    {
        $row = $this->vault->findForCompany($this->decodeId($id), $this->cid());
        if (! $row) {
            return redirect()->to(site_url('passwords'))->with('error', 'Password entry not found.');
        }
        return $this->render('form', $this->formData($row, 'edit'));
    }

    private function formData(?array $row, string $mode): array
    {
        return [
            'title'      => $mode === 'edit' ? 'Edit Password' : 'Add Password',
            'breadcrumb' => [
                ['label' => 'Password Manager', 'url' => site_url('passwords')],
                ['label' => $mode === 'edit' ? 'Edit' : 'Add'],
            ],
            'row'         => $row,
            'mode'        => $mode,
            'presetCats'  => self::CATEGORIES,
            // The current password is pre-filled (decrypted) only when editing.
            'currentPass' => $row ? $this->crypt->decrypt($row['password_enc'] ?? '') : '',
            'errors'      => session()->getFlashdata('errors') ?? [],
            'moduleCode'  => $this->moduleCode,
            'baseRoute'   => $this->baseRoute,
            'css'         => [base_url('assets/css/passwords.css')],
        ];
    }

    // ---------------------------------------------------------------
    // Persist
    // ---------------------------------------------------------------
    public function store()
    {
        return $this->persist(null);
    }

    public function update($id = null)
    {
        return $this->persist($this->decodeId($id));
    }

    private function persist(?int $id)
    {
        $title = trim((string) $this->request->getPost('title'));
        $pass  = (string) $this->request->getPost('password');

        $errors = [];
        if ($title === '') {
            $errors['title'] = 'A title / name is required.';
        }
        // Password is required on create; on edit it may be left blank to keep the existing one.
        if ($id === null && $pass === '') {
            $errors['password'] = 'A password is required.';
        }
        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data = [
            'title'    => $title,
            'website'  => trim((string) $this->request->getPost('website')) ?: null,
            'username' => trim((string) $this->request->getPost('username')) ?: null,
            'notes'    => trim((string) $this->request->getPost('notes')) ?: null,
            'category' => trim((string) $this->request->getPost('category')) ?: null,
        ];

        // Editing an existing entry.
        if ($id !== null) {
            $existing = $this->vault->findForCompany($id, $this->cid());
            if (! $existing) {
                return redirect()->to(site_url('passwords'))->with('error', 'Password entry not found.');
            }
            // Only re-encrypt when a new password was entered; blank keeps the old one.
            if ($pass !== '') {
                $data['password_enc'] = $this->crypt->encrypt($pass);
            }
            $this->vault->update($id, $data);
            activity_log('Passwords', 'Edit', "Password entry #{$id} ({$title}) updated");
            return redirect()->to($this->encodedUrl('view', $id))->with('success', 'Password entry updated.');
        }

        // New entry.
        $data['password_enc'] = $this->crypt->encrypt($pass);
        $data['company_id']   = $this->cid();
        $data['created_by']   = $this->uid();
        $newId = (int) $this->vault->insert($data);
        activity_log('Passwords', 'Add', "Password entry #{$newId} ({$title}) added");
        return redirect()->to(site_url('passwords/list'))->with('success', 'Password entry added.');
    }

    // ---------------------------------------------------------------
    // Reveal (AJAX) — decrypt one entry's password on demand
    // ---------------------------------------------------------------
    public function reveal($id = null)
    {
        $row = $this->vault->findForCompany($this->decodeId($id), $this->cid());
        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Not found']);
        }
        return $this->response->setJSON([
            'status'   => 'success',
            'password' => $this->crypt->decrypt($row['password_enc'] ?? ''),
        ]);
    }

    // ---------------------------------------------------------------
    // Delete (soft)
    // ---------------------------------------------------------------
    public function delete($id = null)
    {
        $id  = $this->decodeId($id);
        $row = $this->vault->findForCompany($id, $this->cid());
        if (! $row) {
            return redirect()->to(site_url('passwords'))->with('error', 'Password entry not found.');
        }
        $this->vault->delete($id);
        activity_log('Passwords', 'Delete', "Password entry #{$id} ({$row['title']}) deleted");
        return redirect()->to(site_url('passwords'))->with('success', 'Password entry deleted.');
    }
}
