<?php

namespace App\Libraries;

use App\Models\AccountingGroupModel;
use App\Models\CompanyModel;
use App\Models\CompanySettingModel;
use App\Models\CompanyUserModel;
use Config\Database;

/**
 * Creates a company and everything it needs to start, atomically:
 *   1. the company profile
 *   2. the creator's owner membership (Company Owner/Admin)
 *   3. default Tally-style accounting groups
 *   4. default company settings (general / dashboard / notes / reminders)
 *
 * The whole thing runs in a transaction so a company is never left half-built.
 */
class CompanyProvisioner
{
    /** Standard Tally primary groups: [name, nature]. */
    private const DEFAULT_GROUPS = [
        ['Capital Account', 'Liabilities'],
        ['Loans (Liability)', 'Liabilities'],
        ['Current Liabilities', 'Liabilities'],
        ['Duties & Taxes', 'Liabilities'],
        ['Sundry Creditors', 'Liabilities'],
        ['Fixed Assets', 'Assets'],
        ['Investments', 'Assets'],
        ['Current Assets', 'Assets'],
        ['Bank Accounts', 'Assets'],
        ['Cash-in-Hand', 'Assets'],
        ['Sundry Debtors', 'Assets'],
        ['Sales Accounts', 'Income'],
        ['Direct Incomes', 'Income'],
        ['Indirect Incomes', 'Income'],
        ['Purchase Accounts', 'Expenses'],
        ['Direct Expenses', 'Expenses'],
        ['Indirect Expenses', 'Expenses'],
    ];

    /**
     * @param array<string,mixed> $data validated company fields
     * @return int|null new company id, or null on failure
     */
    public function create(int $ownerId, array $data): ?int
    {
        $db = Database::connect();
        $db->transStart();

        $data['owner_id'] = $ownerId;
        $data['status']   = 1;
        $companyId = (int) (new CompanyModel())->insert($data, true);

        if ($companyId > 0) {
            (new CompanyUserModel())->insert([
                'company_id'  => $companyId,
                'customer_id' => $ownerId,
                'user_id'     => $ownerId,
                'role'        => 'owner',
                'status'      => 1,
            ]);

            $this->seedAccountingGroups($companyId);
            $this->seedSettings($companyId, $data);
            $this->ensureSubscription($ownerId);
        }

        $db->transComplete();

        return ($db->transStatus() && $companyId > 0) ? $companyId : null;
    }

    /** Start the customer on a trial of the default (free) plan, once. */
    private function ensureSubscription(int $ownerId): void
    {
        $plan = Database::connect()->table('subscription_plans')->where('code', 'free')->get()->getRowArray();
        (new \App\Models\SubscriptionModel())->ensureFor($ownerId, $plan ? (int) $plan['id'] : null);
    }

    private function seedAccountingGroups(int $companyId): void
    {
        $rows = [];
        foreach (self::DEFAULT_GROUPS as [$name, $nature]) {
            $rows[] = ['company_id' => $companyId, 'name' => $name, 'nature' => $nature, 'is_default' => 1];
        }
        (new AccountingGroupModel())->insertBatch($rows);
    }

    private function seedSettings(int $companyId, array $data): void
    {
        $defaults = [
            'general' => [
                'currency'      => 'INR',
                'date_format'   => 'd-m-Y',
                'timezone'      => 'Asia/Kolkata',
                'state'         => $data['state'] ?? '',
                'country'       => $data['country'] ?? 'India',
            ],
            'dashboard' => [
                'show_reminders'   => '1',
                'show_notes'       => '1',
                'show_finance'     => '1',
                'default_landing'  => 'dashboard',
            ],
            'notes' => [
                'default_category' => 'General',
                'autosave'         => '1',
            ],
            'reminders' => [
                'default_priority'      => 'medium',
                'default_snooze_minutes'=> '10',
                'notify_in_app'         => '1',
            ],
        ];

        $rows = [];
        foreach ($defaults as $scope => $pairs) {
            foreach ($pairs as $key => $value) {
                $rows[] = ['company_id' => $companyId, 'scope' => $scope, 'key' => $key, 'value' => (string) $value];
            }
        }
        (new CompanySettingModel())->insertBatch($rows);
    }
}
