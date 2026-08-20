<?php

namespace Modules\Company\Controllers;

use App\Controllers\BaseController;
use App\Libraries\CompanyProvisioner;
use App\Models\CompanyModel;
use App\Models\CompanySettingModel;
use Config\Services;

/**
 * Company / firm creation and switching. New Gmail sign-ups land on create()
 * (enforced by the RequireCompany filter); on save the company is provisioned
 * with the owner membership + default groups/settings, made active, and the
 * user is taken to the dashboard.
 */
class CompanyController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected CompanyModel $companies;

    public function __construct()
    {
        $this->companies = new CompanyModel();
    }

    /** Only customers own firms; firm users are assigned to one. */
    private function guardCustomer()
    {
        if (session()->get('account_type') === 'firm_user') {
            return redirect()->to(site_url('dashboard'))->with('error', 'Only the account owner can create firms.');
        }
        return null;
    }

    /**
     * Enforce the active package's firm cap (Basic = 2; others unlimited). Reads
     * the limit from the plan in the DB, never a hardcoded number.
     */
    private function guardFirmLimit()
    {
        helper('subscription');
        if (! firm_limit_reached()) {
            return null;
        }
        $limit = plan_firm_limit();
        $msg   = "You have reached the maximum limit of {$limit} firms. Upgrade to Standard or above to create unlimited firms.";
        return redirect()->to(site_url('subscription'))->with('error', $msg)->with('locked_feature', 'firms');
    }

    public function create()
    {
        if ($r = $this->guardCustomer()) {
            return $r;
        }

        // Sensible Tally-like defaults: current Indian financial year (Apr–Mar).
        $year   = (int) date('n') >= 4 ? (int) date('Y') : (int) date('Y') - 1;
        $fyFrom = sprintf('%04d-04-01', $year);

        return $this->render('form', [
            'title'       => 'Create Company',
            'breadcrumb'  => [['label' => 'Company'], ['label' => 'Create']],
            'row'         => null,
            'defaults'    => ['financial_year_from' => $fyFrom, 'books_beginning_from' => $fyFrom, 'country' => 'India'],
            'errors'      => session()->getFlashdata('errors') ?? [],
            'hasCompany'  => Services::company()->activeId() !== null,
            'accountName' => $this->accountName(),
            'nameSuggestions' => $this->nameSuggestions(),
        ]);
    }

    /**
     * Ready-made company-name ideas so a user with none in mind can pick one
     * (mirrors the mobile onboarding chips). First ten are personalised with the
     * account's first name; the rest are common Indian shop/firm names.
     *
     * @return list<string>
     */
    private function nameSuggestions(): array
    {
        $n = trim((string) strtok($this->accountName(), ' ')) ?: 'My';
        return [
            "{$n} Chai Wala", "{$n} Kirana Store", "{$n} General Store", "{$n} Traders",
            "{$n} Enterprises", "{$n} Chicken Corner", "{$n} Sweets & Snacks", "{$n} Mobile Shop",
            "{$n} Medical Store", "{$n} Electronics",
            'Sharma Kirana Store', 'Gupta Traders', 'Balaji Enterprises', 'Sai Provision Store',
            'New Chicken Corner', 'Annapurna Sweets', 'Maa Vaishno Dhaba', 'Royal Tailors',
            'City Hardware', 'Bombay Fashion',
        ];
    }

    /**
     * Store a firm's opening cash balance for the financial year that its books
     * begin in, as the "Opening Balance" (Shri Rokad Nagad) transactions setting
     * the Rokadh Parcha / ledger read from.
     */
    private function seedOpeningBalance(int $companyId, string $fyFrom, float $amount): void
    {
        $ts      = strtotime($fyFrom) ?: time();
        $fyStart = (int) date('n', $ts) >= 4 ? (int) date('Y', $ts) : (int) date('Y', $ts) - 1;
        (new CompanySettingModel())->put($companyId, 'transactions', 'shri_rokad_nagad_' . $fyStart, round($amount, 2));
        activity_log('Company', 'Add', "Opening balance set for company #{$companyId} (FY {$fyStart}) = " . number_format($amount, 2));
    }

    /**
     * A display name derived from the signed-in account, used to auto-name a
     * starter ("dummy") company when the user would rather not type one in yet.
     */
    private function accountName(): string
    {
        $name = trim((string) (session('user_name') ?: session('username') ?: ''));
        if ($name === '') {
            $email = (string) session('user_email');
            $name  = $email !== '' ? ucfirst(strtok($email, '@')) : 'My Company';
        }
        return $name;
    }

    /**
     * "I don't have a company name yet" — create a starter company named after
     * the account, with sensible defaults, so the user can get straight in and
     * fill in the real details later from Company Profile.
     */
    public function quickStart()
    {
        if ($r = $this->guardCustomer()) {
            return $r;
        }
        if ($r = $this->guardFirmLimit()) {
            return $r;
        }
        $uid  = (int) user_id();
        $base = $this->accountName();

        // Keep the auto-name unique for this user.
        $name = $base;
        $i    = 1;
        while ($this->companies->nameTakenByUser($name, $uid) && $i < 50) {
            $name = $base . ' ' . (++$i);
        }

        $year   = (int) date('n') >= 4 ? (int) date('Y') : (int) date('Y') - 1;
        $fyFrom = sprintf('%04d-04-01', $year);

        $companyId = (new CompanyProvisioner())->create($uid, [
            'name'                  => $name,
            'financial_year_from'   => $fyFrom,
            'books_beginning_from'  => $fyFrom,
            'state'                 => '',
            'country'               => 'India',
            'gst_registration_type' => 'Unregistered',
        ]);
        if (! $companyId) {
            return redirect()->back()->with('error', 'Could not set up your workspace. Please try again.');
        }

        Services::company()->setActive($companyId);
        activity_log('Company', 'Add', "Starter company #{$companyId} ({$name}) auto-created from account name");

        return redirect()->to(site_url('dashboard'))
            ->with('success', "We've created a starter company \"{$name}\" from your account name. Rename it and add details anytime under Company Profile.");
    }

    public function store()
    {
        if ($r = $this->guardCustomer()) {
            return $r;
        }
        if ($r = $this->guardFirmLimit()) {
            return $r;
        }
        $uid = (int) user_id();

        $rules = [
            'name'                 => 'required|min_length[2]|max_length[191]',
            'financial_year_from'  => 'required|valid_date[Y-m-d]',
            'books_beginning_from' => 'required|valid_date[Y-m-d]',
            'state'                => 'required|max_length[100]',
            'country'              => 'required|max_length[100]',
            'gst_registration_type'=> 'required',
            'email'                => 'permit_empty|valid_email',
            'mobile'               => 'permit_empty|max_length[20]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name = trim((string) $this->request->getPost('name'));

        // No duplicate company name for the same user.
        if ($this->companies->nameTakenByUser($name, $uid)) {
            return redirect()->back()->withInput()->with('errors', ['name' => 'You already have a company with this name.']);
        }

        // GST is optional, but must be a valid GSTIN when provided.
        $gst = strtoupper(trim((string) $this->request->getPost('gst_number')));
        if ($gst !== '' && ! preg_match(CompanyModel::GST_REGEX, $gst)) {
            return redirect()->back()->withInput()->with('errors', ['gst_number' => 'Enter a valid 15-character GSTIN (e.g. 27ABCDE1234F1Z5).']);
        }

        $data = [
            'name'                  => $name,
            'financial_year_from'   => (string) $this->request->getPost('financial_year_from'),
            'books_beginning_from'  => (string) $this->request->getPost('books_beginning_from'),
            'state'                 => trim((string) $this->request->getPost('state')),
            'country'               => trim((string) $this->request->getPost('country')) ?: 'India',
            'gst_registration_type' => (string) $this->request->getPost('gst_registration_type') ?: null,
            'gst_number'            => $gst !== '' ? $gst : null,
            'address'               => trim((string) $this->request->getPost('address')) ?: null,
            'mobile'                => trim((string) $this->request->getPost('mobile')) ?: null,
            'email'                 => trim((string) $this->request->getPost('email')) ?: null,
            'business_type'         => (string) $this->request->getPost('business_type') ?: null,
        ];

        $companyId = (new CompanyProvisioner())->create($uid, $data);
        if (! $companyId) {
            return redirect()->back()->withInput()->with('error', 'Could not create the company. Please try again.');
        }

        // Opening balance (optional) — seed the firm's Opening Balance (Shri Rokad
        // Nagad) for its financial year so the very first Rokadh Parcha opens with
        // the right cash-in-hand.
        $opening = $this->request->getPost('opening_balance');
        if ($opening !== null && trim((string) $opening) !== '') {
            $this->seedOpeningBalance($companyId, $data['financial_year_from'], (float) $opening);
        }

        Services::company()->setActive($companyId);
        activity_log('Company', 'Add', "Company #{$companyId} ({$name}) created");

        return redirect()->to(site_url('dashboard'))->with('success', "Company \"{$name}\" is ready. Welcome aboard!");
    }

    /** Switch the active company (must be a member). */
    public function switchTo($id = null)
    {
        $id = (int) $id;
        if (! Services::company()->setActive($id)) {
            return redirect()->back()->with('error', 'You do not have access to that company.');
        }
        activity_log('Company', 'Edit', "Switched to company #{$id}");

        // Reload the page the user switched from so they stay in context, unless
        // that page shows a single record (view/edit/details) belonging to the
        // company they just left — those would be stale/missing, so fall back to
        // the dashboard instead.
        $to = $this->resolveSwitchReturn($this->request->getGet('return'));
        return redirect()->to($to)->with('success', 'Company switched.');
    }

    /**
     * Resolve the post-switch redirect target from the `return` path captured
     * when the user opened the switcher. Only same-site app paths are honoured;
     * entry/detail pages (which belong to the previous company) are sent to the
     * dashboard. Query strings (filters, pagination) are preserved on reload.
     */
    private function resolveSwitchReturn(?string $return): string
    {
        $return = trim((string) $return);
        if ($return === '') {
            return site_url('dashboard');
        }
        // Backward-compatible shortcut used by the company profile page.
        if ($return === 'profile') {
            return site_url('company/profile');
        }
        // Never redirect off-site (open-redirect guard).
        if (strpos($return, '://') !== false || str_starts_with($return, '//') || str_starts_with($return, '\\')) {
            return site_url('dashboard');
        }

        $return = ltrim($return, '/');
        $path   = $return;
        $query  = '';
        if (($qpos = strpos($return, '?')) !== false) {
            $path  = substr($return, 0, $qpos);
            $query = substr($return, $qpos + 1);
        }
        if ($path === '') {
            return site_url('dashboard');
        }

        // A record-specific page (view/edit/details/entry) references a row from
        // the company just left — reloading it would 404 or show wrong data.
        $segments      = array_filter(explode('/', strtolower($path)));
        $detailMarkers = ['view', 'edit', 'show', 'details', 'detail', 'entry', 'entry-modal'];
        if (array_intersect($segments, $detailMarkers)) {
            return site_url('dashboard');
        }

        return site_url($path) . ($query !== '' ? '?' . $query : '');
    }

    /**
     * Company profile — view the active firm's details, its completeness score,
     * and switch between / add companies. Any member can view; owner/admin edit.
     */
    public function profile()
    {
        $company = Services::company()->current();
        if (! $company) {
            return redirect()->to(site_url('company/create'));
        }

        $role = Services::company()->role();
        $companies = Services::company()->userCompanies();

        return $this->render('profile', [
            'title'      => 'Company Profile',
            'breadcrumb' => [['label' => 'Company'], ['label' => 'Profile']],
            'row'        => $company,
            'score'      => company_score($company),
            'role'       => $role,
            'canEdit'    => in_array($role, ['owner', 'admin'], true),
            'companies'  => $companies,
            'entryCounts'=> $this->transactionEntryCounts($companies),
            'activeId'   => (int) $company['id'],
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    /** Active Jama/Naam transaction counts keyed by company id. */
    private function transactionEntryCounts(array $companies): array
    {
        $ids = array_values(array_unique(array_map(static fn ($company) => (int) ($company['id'] ?? 0), $companies)));
        $ids = array_values(array_filter($ids, static fn (int $id) => $id > 0));
        if ($ids === []) {
            return [];
        }

        $rows = db_connect()->table('transactions')
            ->select('company_id, COUNT(*) AS cnt')
            ->where('deleted_at', null)
            ->whereIn('company_id', $ids)
            ->groupBy('company_id')
            ->get()
            ->getResultArray();

        $counts = array_fill_keys($ids, 0);
        foreach ($rows as $row) {
            $counts[(int) $row['company_id']] = (int) $row['cnt'];
        }
        return $counts;
    }

    /** Update the active company's details. Owner / admin only. */
    public function update()
    {
        $company = Services::company()->current();
        if (! $company) {
            return redirect()->to(site_url('company/create'));
        }
        if (! in_array(Services::company()->role(), ['owner', 'admin'], true)) {
            return redirect()->to(site_url('company/profile'))->with('error', 'Only the owner or an admin can edit company details.');
        }

        $id  = (int) $company['id'];
        $uid = (int) $company['owner_id'];

        $rules = [
            'name'                  => 'required|min_length[2]|max_length[191]',
            'financial_year_from'   => 'required|valid_date[Y-m-d]',
            'books_beginning_from'  => 'required|valid_date[Y-m-d]',
            'state'                 => 'required|max_length[100]',
            'country'               => 'required|max_length[100]',
            'gst_registration_type' => 'required',
            'email'                 => 'permit_empty|valid_email',
            'mobile'                => 'permit_empty|max_length[20]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name = trim((string) $this->request->getPost('name'));
        if ($this->companies->nameTakenByUser($name, $uid, $id)) {
            return redirect()->back()->withInput()->with('errors', ['name' => 'You already have another company with this name.']);
        }

        $gst = strtoupper(trim((string) $this->request->getPost('gst_number')));
        if ($gst !== '' && ! preg_match(CompanyModel::GST_REGEX, $gst)) {
            return redirect()->back()->withInput()->with('errors', ['gst_number' => 'Enter a valid 15-character GSTIN (e.g. 27ABCDE1234F1Z5).']);
        }

        $this->companies->update($id, [
            'name'                  => $name,
            'financial_year_from'   => (string) $this->request->getPost('financial_year_from'),
            'books_beginning_from'  => (string) $this->request->getPost('books_beginning_from'),
            'state'                 => trim((string) $this->request->getPost('state')),
            'country'               => trim((string) $this->request->getPost('country')) ?: 'India',
            'gst_registration_type' => (string) $this->request->getPost('gst_registration_type') ?: null,
            'gst_number'            => $gst !== '' ? $gst : null,
            'address'               => trim((string) $this->request->getPost('address')) ?: null,
            'mobile'                => trim((string) $this->request->getPost('mobile')) ?: null,
            'email'                 => trim((string) $this->request->getPost('email')) ?: null,
            'business_type'         => (string) $this->request->getPost('business_type') ?: null,
        ]);

        // Re-sync the session (company name shown in the top bar may have changed).
        Services::company()->setActive($id);
        activity_log('Company', 'Edit', "Company #{$id} details updated");

        return redirect()->to(site_url('company/profile'))->with('success', 'Company details updated.');
    }

    // ===============================================================
    // Recycle bin: soft-delete → Trash → restore / permanent delete
    // ===============================================================

    /** May the signed-in user manage (delete/restore) this company? Owner or Super Admin. */
    private function canManage(?array $company): bool
    {
        if (! $company) {
            return false;
        }
        if (is_super_admin_account()) {
            return true;
        }
        $uid = (int) user_id();
        if ((int) $company['owner_id'] === $uid) {
            return true;
        }
        // An explicit owner/admin membership also qualifies.
        $role = Services::company()->activeId() === (int) $company['id'] ? Services::company()->role() : null;
        return in_array($role, ['owner'], true);
    }

    /** Soft-delete (move to Trash) a company the user owns. */
    public function deleteCompany($id = null)
    {
        $id      = (int) $id;
        $company = $this->companies->find($id);
        if (! $this->canManage($company)) {
            return redirect()->to(site_url('settings'))->with('error', 'You can only delete a company you own.');
        }

        $this->companies->delete($id); // soft delete (sets deleted_at)
        activity_log('Company', 'Delete', "Company #{$id} ({$company['name']}) moved to Trash");

        // Re-point the active company. If this was the one in use, switch to
        // another the user still has; if none remain, clear it entirely.
        $others = $this->companies->forUser((int) user_id());
        if ((int) (session('company_id') ?? 0) === $id) {
            Services::company()->clear();
            if ($others !== []) {
                Services::company()->setActive((int) $others[0]['id']);
            }
        }

        // Deleted their last company → send them to onboarding to make a new one.
        if ($others === []) {
            return redirect()->to(site_url('company/create'))
                ->with('info', 'Your last company "' . $company['name'] . '" was moved to Trash. Create a company to continue — you can also restore the deleted one from Trash later.');
        }

        return redirect()->to(site_url('company/trash'))
            ->with('success', 'Company "' . $company['name'] . '" moved to Trash. You can restore it anytime.');
    }

    /** Trash view: soft-deleted companies with restore / permanent-delete. */
    public function trash()
    {
        $rows = is_super_admin_account()
            ? $this->companies->trashedAll()
            : $this->companies->trashedForUser((int) user_id());

        return $this->render('trash', [
            'title'      => 'Trash — Deleted Companies',
            'breadcrumb' => [['label' => 'Company'], ['label' => 'Trash']],
            'rows'       => $rows,
        ]);
    }

    /** Restore a soft-deleted company. */
    public function restoreCompany($id = null)
    {
        $id      = (int) $id;
        $company = $this->companies->onlyDeleted()->find($id);
        if (! $this->canManage($company)) {
            return redirect()->to(site_url('company/trash'))->with('error', 'You can only restore a company you own.');
        }
        // A trashed firm already counts toward the plan cap, so if the account is
        // OVER its current cap (e.g. after a downgrade), block restoring until the
        // total is back within the limit.
        helper('subscription');
        $state = company_limit_state((int) $company['owner_id']);
        if (! $state['can_restore']) {
            return redirect()->to(site_url('company/trash'))->with('error', $state['message']);
        }
        // deleted_at is not an allowed field, so clear it via the builder directly.
        $this->companies->builder()->where('id', $id)->update(['deleted_at' => null]);
        activity_log('Company', 'Edit', "Company #{$id} ({$company['name']}) restored from Trash");
        return redirect()->to(site_url('company/trash'))->with('success', 'Company "' . $company['name'] . '" restored.');
    }

    /** Permanently delete a trashed company and ALL its data. Irreversible. */
    public function forceDeleteCompany($id = null)
    {
        $id      = (int) $id;
        $company = $this->companies->onlyDeleted()->find($id);
        if (! $this->canManage($company)) {
            return redirect()->to(site_url('company/trash'))->with('error', 'You can only delete a company you own.');
        }

        // Email the owner a FINAL report of the company BEFORE anything is erased
        // (entries, accounts, totals + a "cannot be recovered" notice). Best-effort.
        helper('company_report');
        send_company_deletion_report($company);

        $db = \Config\Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        // Purge every table scoped by company_id, then memberships, then the row.
        $tables = $db->query(
            'SELECT DISTINCT TABLE_NAME AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = "company_id"'
        )->getResultArray();
        foreach ($tables as $row) {
            $db->table($row['t'])->where('company_id', $id)->delete();
        }
        $db->table('company_users')->where('company_id', $id)->delete();
        $this->companies->delete($id, true); // hard delete (purge)
        $db->query('SET FOREIGN_KEY_CHECKS=1');

        activity_log('Company', 'Delete', "Company #{$id} ({$company['name']}) permanently deleted");
        return redirect()->to(site_url('company/trash'))
            ->with('success', 'Company "' . $company['name'] . '" was permanently deleted.');
    }
}
