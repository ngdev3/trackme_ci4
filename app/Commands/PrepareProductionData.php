<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class PrepareProductionData extends BaseCommand
{
    protected $group       = 'Data';
    protected $name        = 'data:prepare-production';
    protected $description = 'Remove demo/test/non-owner data before production deployment.';
    protected $usage       = 'data:prepare-production --keep-user <username|email|id> [--force]';
    protected $options     = [
        '--keep-user' => 'Owner account to keep. All other users and their companies are removed.',
        '--force'     => 'Actually delete rows. Without this, only a dry-run report is shown.',
    ];

    public function run(array $params): int
    {
        $keepUser = (string) (CLI::getOption('keep-user') ?? '');
        $force    = CLI::getOption('force') !== null;

        if ($keepUser === '') {
            CLI::error('Missing --keep-user. Example: php spark data:prepare-production --keep-user rajatinvoice');
            return 1;
        }

        $db = Database::connect();
        $owner = $db->table('users')
            ->groupStart()
                ->where('id', ctype_digit($keepUser) ? (int) $keepUser : 0)
                ->orWhere('username', $keepUser)
                ->orWhere('email', $keepUser)
            ->groupEnd()
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (! $owner) {
            CLI::error('Keep user not found: ' . $keepUser);
            return 1;
        }

        $keepUserId = (int) $owner['id'];
        $removeCompanyIds = array_map('intval', array_column(
            $db->table('companies')->select('id')->where('owner_id !=', $keepUserId)->get()->getResultArray(),
            'id'
        ));
        $removeUserIds = array_map('intval', array_column(
            $db->table('users')->select('id')->where('id !=', $keepUserId)->get()->getResultArray(),
            'id'
        ));

        CLI::write(($force ? 'Preparing production data...' : 'Production cleanup dry run:'), $force ? 'yellow' : 'green');
        CLI::write('  keeping user: #' . $keepUserId . ' ' . $owner['username'] . ' <' . $owner['email'] . '>');
        CLI::write('  companies to remove: ' . count($removeCompanyIds));
        CLI::write('  users to remove: ' . count($removeUserIds));

        if (! $force) {
            CLI::write('Re-run with --force to delete.', 'yellow');
            return 0;
        }

        $db->transStart();

        $this->deleteWhereIn($db, 'reminder_notifications', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'note_history', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'notes', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'note_categories', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'transaction_attachments', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'transactions', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'voucher_entries', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'vouchers', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'ledgers', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'accounting_groups', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'passwords', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'rokad_entries', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'inv_attachments', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'inv_corrections', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'inv_movements', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'inv_stock', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'inv_lots', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'inv_products', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'inv_parties', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'inv_warehouses', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'inv_daily_closings', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'company_settings', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'company_users', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'load_test_records', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'load_test_runs', 'company_id', $removeCompanyIds);
        $this->deleteWhereIn($db, 'companies', 'id', $removeCompanyIds);

        $this->deleteWhereIn($db, 'transaction_attachments', 'user_id', $removeUserIds);
        $this->deleteWhereIn($db, 'transactions', 'user_id', $removeUserIds);
        $this->deleteWhereIn($db, 'activity_logs', 'user_id', $removeUserIds);
        $this->deleteWhereIn($db, 'login_logs', 'user_id', $removeUserIds);
        $this->deleteWhereIn($db, 'notifications', 'user_id', $removeUserIds);
        $this->deleteWhereIn($db, 'settings', 'user_id', $removeUserIds);
        $this->deleteWhereIn($db, 'users', 'id', $removeUserIds);

        $db->transComplete();

        if (! $db->transStatus()) {
            CLI::error('Cleanup failed and was rolled back.');
            return 1;
        }

        CLI::write('Production data cleanup complete.', 'green');
        return 0;
    }

    private function deleteWhereIn($db, string $table, string $column, array $ids): void
    {
        if ($ids === [] || ! $db->tableExists($table) || ! $db->fieldExists($column, $table)) {
            return;
        }

        $db->table($table)->whereIn($column, $ids)->delete();
    }
}
