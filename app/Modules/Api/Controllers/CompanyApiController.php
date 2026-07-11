<?php

namespace Modules\Api\Controllers;

use App\Libraries\CompanyProvisioner;
use App\Models\CompanyModel;
use App\Models\CompanySettingModel;

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

        // Firm-limit guard (mirrors firm_limit_reached). The very first firm is
        // always allowed, so we skip the plan lookup for a brand-new owner — this
        // also avoids pre-creating a subscription row before the provisioner seeds
        // the free plan (which would otherwise leave the row plan-less). Only an
        // owner who already has firms is measured against their plan's max_firms
        // (NULL = unlimited).
        $count = (new CompanyModel())->where('owner_id', $uid)->countAllResults();
        if ($count > 0) {
            $limit = customer_effective_plan($uid)['max_firms'] ?? null;
            if ($limit !== null && $limit !== '' && $count >= (int) $limit) {
                return $this->failForbidden(
                    "You have reached the maximum limit of {$limit} firm(s). Upgrade your plan to create more."
                );
            }
        }

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
