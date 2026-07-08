<?php

namespace Modules\Company\Controllers;

use App\Controllers\BaseController;
use App\Libraries\CompanyProvisioner;
use App\Models\CompanyModel;
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

    public function create()
    {
        if ($r = $this->guardCustomer()) {
            return $r;
        }

        // Sensible Tally-like defaults: current Indian financial year (Apr–Mar).
        $year   = (int) date('n') >= 4 ? (int) date('Y') : (int) date('Y') - 1;
        $fyFrom = sprintf('%04d-04-01', $year);

        return $this->render('form', [
            'title'      => 'Create Company',
            'breadcrumb' => [['label' => 'Company'], ['label' => 'Create']],
            'row'        => null,
            'defaults'   => ['financial_year_from' => $fyFrom, 'books_beginning_from' => $fyFrom, 'country' => 'India'],
            'errors'     => session()->getFlashdata('errors') ?? [],
            'hasCompany' => Services::company()->activeId() !== null,
        ]);
    }

    public function store()
    {
        if ($r = $this->guardCustomer()) {
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

        return $this->render('profile', [
            'title'      => 'Company Profile',
            'breadcrumb' => [['label' => 'Company'], ['label' => 'Profile']],
            'row'        => $company,
            'score'      => company_score($company),
            'role'       => $role,
            'canEdit'    => in_array($role, ['owner', 'admin'], true),
            'companies'  => Services::company()->userCompanies(),
            'activeId'   => (int) $company['id'],
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
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
}
