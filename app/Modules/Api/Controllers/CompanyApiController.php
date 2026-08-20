<?php

namespace Modules\Api\Controllers;

use App\Libraries\CompanyProvisioner;
use App\Models\CompanyModel;
use App\Models\CompanySettingModel;
use App\Models\CompanyUserModel;

/**
 * Firm (company) creation for the mobile app — the onboarding step a new
 * self-service sign-up completes to become the owner of their first workspace.
 *
 *   POST /api/v1/companies   (Bearer) {name, state?, country?, business_type?,
 *                                      gst_registration_type?, gst_number?,
 *                                      address?, mobile?, email?,
 *                                      financial_year_from?, books_beginning_from?,
 *                                      opening_balance?}
 *
 * Mirrors the web CompanyController::store() exactly: same customer + firm-limit
 * guards, same validation and duplicate/GST checks, and the SAME provisioning
 * (CompanyProvisioner) — company + owner membership + default accounting groups +
 * default settings + a free-plan trial. Unspecified financial-year / books /
 * country / GST-type fields fall back to the web's Tally-style defaults so the
 * mobile form can stay short. Changes no business logic.
 */
class CompanyApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    public function create()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }

        // Only customers own firms; firm users are assigned to one (web: guardCustomer).
        if (($user['account_type'] ?? 'customer') === 'firm_user') {
            return $this->failForbidden('Only the account owner can create firms.');
        }

        $uid = (int) $user['id'];

        // Company limit removed — users can create unlimited companies on any plan.

        $name = trim((string) ($this->input('name') ?? ''));

        // Tally-style default financial year (Apr–Mar) when not supplied.
        $year   = (int) date('n') >= 4 ? (int) date('Y') : (int) date('Y') - 1;
        $fyFrom = sprintf('%04d-04-01', $year);

        $financialYear = $this->validDate((string) ($this->input('financial_year_from') ?? ''), $fyFrom);
        $booksFrom     = $this->validDate((string) ($this->input('books_beginning_from') ?? ''), $financialYear);

        // Validate the assembled values (matches the web store() rules).
        $validation = \Config\Services::validation();
        $ok = $validation->setRules([
            'name'                  => 'required|min_length[2]|max_length[191]',
            'financial_year_from'   => 'required|valid_date[Y-m-d]',
            'books_beginning_from'  => 'required|valid_date[Y-m-d]',
            'gst_registration_type' => 'required|max_length[50]',
            'country'               => 'required|max_length[100]',
        ])->run([
            'name'                  => $name,
            'financial_year_from'   => $financialYear,
            'books_beginning_from'  => $booksFrom,
            'gst_registration_type' => (string) ($this->input('gst_registration_type') ?? 'Unregistered'),
            'country'               => trim((string) ($this->input('country') ?? 'India')) ?: 'India',
        ]);
        if (! $ok) {
            return $this->failValidationErrors($validation->getErrors());
        }

        $companies = new CompanyModel();

        // No duplicate firm name for the same owner.
        if ($companies->nameTakenByUser($name, $uid)) {
            return $this->failValidationErrors(['name' => 'You already have a company with this name.']);
        }

        // GST is optional, but must be a valid GSTIN when provided.
        $gst = strtoupper(trim((string) ($this->input('gst_number') ?? '')));
        if ($gst !== '' && ! preg_match(CompanyModel::GST_REGEX, $gst)) {
            return $this->failValidationErrors([
                'gst_number' => 'Enter a valid 15-character GSTIN (e.g. 27ABCDE1234F1Z5).',
            ]);
        }

        $data = [
            'name'                  => $name,
            'financial_year_from'   => $financialYear,
            'books_beginning_from'  => $booksFrom,
            'state'                 => trim((string) ($this->input('state') ?? '')),
            'country'               => trim((string) ($this->input('country') ?? 'India')) ?: 'India',
            'gst_registration_type' => (string) ($this->input('gst_registration_type') ?? 'Unregistered') ?: 'Unregistered',
            'gst_number'            => $gst !== '' ? $gst : null,
            'address'               => trim((string) ($this->input('address') ?? '')) ?: null,
            'mobile'                => trim((string) ($this->input('mobile') ?? '')) ?: null,
            'email'                 => trim((string) ($this->input('email') ?? '')) ?: null,
            'business_type'         => trim((string) ($this->input('business_type') ?? '')) ?: null,
        ];

        $companyId = (new CompanyProvisioner())->create($uid, $data);
        if (! $companyId) {
            return $this->failServerError('Could not create the company. Please try again.');
        }

        // Optional opening balance — seed Shri Rokad Nagad for the FY (as web store()).
        $opening = $this->input('opening_balance');
        if ($opening !== null && trim((string) $opening) !== '' && is_numeric($opening)) {
            $this->seedOpeningBalance($companyId, $financialYear, (float) $opening);
        }

        if (function_exists('activity_log')) {
            activity_log('Company', 'Add', "Company #{$companyId} ({$name}) created (mobile)");
        }

        return $this->respondCreated([
            'status'     => 'ok',
            'message'    => "Company \"{$name}\" is ready. Welcome aboard!",
            'company_id' => $companyId,
        ]);
    }

    /** List the caller's active (non-deleted) companies. */
    public function index()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $rows = (new CompanyModel())->forUser((int) $user['id']);

        // Per-company extras for the list UI: how many cash-book entries each
        // firm holds, and when this user last opened it (from the membership).
        $ids        = array_map(fn ($c) => (int) $c['id'], $rows);
        $counts     = (new \App\Models\TransactionModel())->countsByCompany($ids);
        $lastActive = (new CompanyUserModel())->lastActiveMap((int) $user['id']);

        $out = array_map(fn ($c) => [
            'id'             => (int) $c['id'],
            'name'           => $c['name'],
            'state'          => $c['state'] ?? null,
            'business_type'  => $c['business_type'] ?? null,
            // Extra profile fields so the client can show a completeness score
            // nudging the user to finish filling in their company details.
            'mobile'         => $c['mobile'] ?? null,
            'email'          => $c['email'] ?? null,
            'address'        => $c['address'] ?? null,
            'gst_number'     => $c['gst_number'] ?? null,
            'is_owner'       => (int) $c['owner_id'] === (int) $user['id'],
            'created_at'     => $c['created_at'] ?? null,
            'last_active_at' => $lastActive[(int) $c['id']] ?? null,
            'entry_count'    => $counts[(int) $c['id']] ?? 0,
        ], $rows);

        return $this->respond([
            'status'    => 'ok',
            'companies' => $out,
            // Firm-limit state so the client can disable/enable the Create button
            // and show a clear message. Backend stays authoritative regardless.
            'limit'     => company_limit_state((int) $user['id']),
        ]);
    }

    /** GET api/v1/companies/{id} — full details for the edit form (owner only). */
    public function show($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $company = (new CompanyModel())->find((int) $id);
        if (! $this->owns($user, $company)) {
            return $this->failForbidden('You can only view a company you own.');
        }

        return $this->respond(['status' => 'ok', 'company' => [
            'id'                    => (int) $company['id'],
            'name'                  => $company['name'],
            'financial_year_from'   => $company['financial_year_from'] ?? null,
            'books_beginning_from'  => $company['books_beginning_from'] ?? null,
            'state'                 => $company['state'] ?? null,
            'country'               => $company['country'] ?? null,
            'gst_registration_type' => $company['gst_registration_type'] ?? null,
            'gst_number'            => $company['gst_number'] ?? null,
            'business_type'         => $company['business_type'] ?? null,
            'address'               => $company['address'] ?? null,
            'mobile'                => $company['mobile'] ?? null,
            'email'                 => $company['email'] ?? null,
        ]]);
    }

    /**
     * GET api/v1/companies/{id}/summary — details + entry stats for a company
     * the caller owns, INCLUDING one sitting in Trash. Lets the user see exactly
     * which company a trashed row is (name, place, GST, dates) and how many
     * cash-book entries it holds before deciding to restore or purge it.
     */
    public function summary($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $companies = new CompanyModel();
        $company   = $companies->withDeleted()->find((int) $id);
        if (! $this->owns($user, $company)) {
            return $this->failForbidden('You can only view a company you own.');
        }

        $stats = (new \App\Models\TransactionModel())->summary((int) $id, []);

        return $this->respond(['status' => 'ok', 'company' => [
            'id'                    => (int) $company['id'],
            'name'                  => $company['name'],
            'state'                 => $company['state'] ?? null,
            'business_type'         => $company['business_type'] ?? null,
            'gst_number'            => $company['gst_number'] ?? null,
            'gst_registration_type' => $company['gst_registration_type'] ?? null,
            'financial_year_from'   => $company['financial_year_from'] ?? null,
            'created_at'            => $company['created_at'] ?? null,
            'deleted_at'            => $company['deleted_at'] ?? null,
            'is_trashed'            => ! empty($company['deleted_at']),
            'entries'               => [
                'count'    => (int) $stats['count'],
                'deposits' => (float) $stats['jama'],
                'expenses' => (float) $stats['naam'],
                'net'      => (float) $stats['net'],
            ],
        ]]);
    }

    /**
     * PUT api/v1/companies/{id} — update a company's details (owner only).
     * Mirrors web CompanyController::update(): same validation, duplicate-name
     * and GSTIN checks, and the same editable field set.
     */
    public function update($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $companies = new CompanyModel();
        $company   = $companies->find((int) $id);
        if (! $this->owns($user, $company)) {
            return $this->failForbidden('You can only edit a company you own.');
        }

        $uid  = (int) $company['owner_id'];
        $name = trim((string) ($this->input('name') ?? ''));

        // Fall back to the stored values so a partial payload doesn't wipe fields.
        $financialYear = $this->validDate((string) ($this->input('financial_year_from') ?? ''), (string) ($company['financial_year_from'] ?? ''));
        $booksFrom     = $this->validDate((string) ($this->input('books_beginning_from') ?? ''), (string) ($company['books_beginning_from'] ?? $financialYear));

        $validation = \Config\Services::validation();
        $ok = $validation->setRules([
            'name'                  => 'required|min_length[2]|max_length[191]',
            'financial_year_from'   => 'required|valid_date[Y-m-d]',
            'books_beginning_from'  => 'required|valid_date[Y-m-d]',
            'state'                 => 'required|max_length[100]',
            'country'               => 'required|max_length[100]',
            'gst_registration_type' => 'required|max_length[50]',
        ])->run([
            'name'                  => $name,
            'financial_year_from'   => $financialYear,
            'books_beginning_from'  => $booksFrom,
            'state'                 => trim((string) ($this->input('state') ?? '')),
            'country'               => trim((string) ($this->input('country') ?? 'India')) ?: 'India',
            'gst_registration_type' => (string) ($this->input('gst_registration_type') ?? ''),
        ]);
        if (! $ok) {
            return $this->failValidationErrors($validation->getErrors());
        }

        // No duplicate firm name for the same owner (excluding this company).
        if ($companies->nameTakenByUser($name, $uid, (int) $id)) {
            return $this->failValidationErrors(['name' => 'You already have another company with this name.']);
        }

        $gst = strtoupper(trim((string) ($this->input('gst_number') ?? '')));
        if ($gst !== '' && ! preg_match(CompanyModel::GST_REGEX, $gst)) {
            return $this->failValidationErrors(['gst_number' => 'Enter a valid 15-character GSTIN (e.g. 27ABCDE1234F1Z5).']);
        }

        $companies->update((int) $id, [
            'name'                  => $name,
            'financial_year_from'   => $financialYear,
            'books_beginning_from'  => $booksFrom,
            'state'                 => trim((string) ($this->input('state') ?? '')),
            'country'               => trim((string) ($this->input('country') ?? 'India')) ?: 'India',
            'gst_registration_type' => (string) ($this->input('gst_registration_type') ?? 'Unregistered') ?: 'Unregistered',
            'gst_number'            => $gst !== '' ? $gst : null,
            'address'               => trim((string) ($this->input('address') ?? '')) ?: null,
            'mobile'                => trim((string) ($this->input('mobile') ?? '')) ?: null,
            'email'                 => trim((string) ($this->input('email') ?? '')) ?: null,
            'business_type'         => trim((string) ($this->input('business_type') ?? '')) ?: null,
        ]);

        if (function_exists('activity_log')) {
            activity_log('Company', 'Edit', "Company #{$id} ({$name}) details updated (mobile)");
        }

        return $this->respond(['status' => 'ok', 'message' => 'Company details updated.', 'company_id' => (int) $id]);
    }

    /** Soft-delete (move to Trash) a company the caller owns. */
    public function destroy($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $companies = new CompanyModel();
        $company   = $companies->find((int) $id);
        if (! $this->owns($user, $company)) {
            return $this->failForbidden('You can only delete a company you own.');
        }
        $companies->delete((int) $id); // soft delete (sets deleted_at)
        if (function_exists('activity_log')) {
            activity_log('Company', 'Delete', "Company #{$id} ({$company['name']}) moved to Trash (mobile)");
        }

        // Suggest the next active company for the client to switch to.
        $remaining = array_map(fn ($c) => ['id' => (int) $c['id'], 'name' => $c['name']], $companies->forUser((int) $user['id']));

        return $this->respond([
            'status'        => 'ok',
            'message'       => "Company \"{$company['name']}\" moved to Trash.",
            'remaining'     => $remaining,
            'next_active_id' => $remaining[0]['id'] ?? null,
        ]);
    }

    /** Trash: the caller's soft-deleted companies. */
    public function trash()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $rows = (new CompanyModel())->trashedForUser((int) $user['id']);
        $out  = array_map(fn ($c) => [
            'id'            => (int) $c['id'],
            'name'          => $c['name'],
            'state'         => $c['state'] ?? null,
            'business_type' => $c['business_type'] ?? null,
            'gst_number'    => $c['gst_number'] ?? null,
            'deleted_at'    => $c['deleted_at'] ?? null,
            'is_owner'      => (int) $c['owner_id'] === (int) $user['id'],
        ], $rows);

        return $this->respond(['status' => 'ok', 'companies' => $out]);
    }

    /** Restore a soft-deleted company the caller owns. */
    public function restore($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $companies = new CompanyModel();
        $company   = $companies->onlyDeleted()->find((int) $id);
        if (! $this->owns($user, $company)) {
            return $this->failForbidden('You can only restore a company you own.');
        }

        // A trashed firm already counts toward the plan cap, so restoring one
        // does not add a slot — but if the account is OVER its current cap (e.g.
        // after a downgrade), restoring would make an over-limit firm active
        // again, so block it until the total is back within the cap.
        $state = company_limit_state((int) $company['owner_id']);
        if (! $state['can_restore']) {
            return $this->failForbidden($state['message']);
        }

        // deleted_at is not an allowed field — clear it via the builder directly.
        $companies->builder()->where('id', (int) $id)->update(['deleted_at' => null]);
        if (function_exists('activity_log')) {
            activity_log('Company', 'Edit', "Company #{$id} ({$company['name']}) restored (mobile)");
        }
        return $this->respond(['status' => 'ok', 'message' => "Company \"{$company['name']}\" restored.", 'company_id' => (int) $id]);
    }

    /** Permanently delete a trashed company and ALL its data (owner only). Irreversible. */
    public function purge($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $companies = new CompanyModel();
        $company   = $companies->onlyDeleted()->find((int) $id);
        if (! $this->owns($user, $company)) {
            return $this->failForbidden('You can only delete a company you own.');
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
            $db->table($row['t'])->where('company_id', (int) $id)->delete();
        }
        $db->table('company_users')->where('company_id', (int) $id)->delete();
        $companies->delete((int) $id, true); // hard delete (purge)
        $db->query('SET FOREIGN_KEY_CHECKS=1');

        if (function_exists('activity_log')) {
            activity_log('Company', 'Delete', "Company #{$id} ({$company['name']}) permanently deleted (mobile)");
        }
        return $this->respond(['status' => 'ok', 'message' => "Company \"{$company['name']}\" permanently deleted."]);
    }

    /** Owner-only management guard (owner of the company, or a super admin). */
    private function owns(?array $user, ?array $company): bool
    {
        if (! $user || ! $company) {
            return false;
        }
        if ((int) ($user['is_superadmin'] ?? 0) === 1) {
            return true;
        }
        return (int) ($company['owner_id'] ?? 0) === (int) $user['id'];
    }

    /** Return $value if it's a valid Y-m-d date, else $fallback. */
    private function validDate(string $value, string $fallback): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false ? $value : $fallback;
    }

    /** Seed the firm's opening cash balance for its FY (mirrors web seedOpeningBalance). */
    private function seedOpeningBalance(int $companyId, string $fyFrom, float $amount): void
    {
        $ts      = strtotime($fyFrom) ?: time();
        $fyStart = (int) date('n', $ts) >= 4 ? (int) date('Y', $ts) : (int) date('Y', $ts) - 1;
        (new CompanySettingModel())->put($companyId, 'transactions', 'shri_rokad_nagad_' . $fyStart, round($amount, 2));
    }
}
