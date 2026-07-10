<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Remove load-test / demo bulk data before taking a production backup (F-6).
 *
 * LoadTestSeeder tags everything it inserts, so this only ever deletes tagged
 * rows — real records are never touched:
 *   - users:                        username LIKE 'load\_%'
 *   - notes / reminders / calculator_history: title LIKE '[LT]%'
 *   - rokad_entries:                remarks = 'LT'
 *   - load_test_records / load_test_runs: emptied entirely
 *
 * Dry-run by default (prints counts). Pass --force to actually delete:
 *
 *   php spark data:purge-testdata           # report only
 *   php spark data:purge-testdata --force    # delete
 */
class PurgeTestData extends BaseCommand
{
    protected $group       = 'Data';
    protected $name        = 'data:purge-testdata';
    protected $description = 'Delete tagged load-test / demo data so it never ships to production.';
    protected $usage       = 'data:purge-testdata [--force]';
    protected $options     = ['--force' => 'Actually delete (otherwise counts are reported and nothing is removed).'];

    /**
     * Each target as [table, describe-closure, delete-closure].
     * Children are listed before users so nothing is left dangling.
     *
     * @var list<array{0:string, 1:callable, 2:callable}>
     */
    private function targets($db): array
    {
        $tagged   = static fn ($t) => $db->table($t)->like('title', '[LT]', 'after');
        return [
            ['calculator_history', fn () => $tagged('calculator_history')->countAllResults(false), fn () => $tagged('calculator_history')->delete()],
            ['notes',              fn () => $tagged('notes')->countAllResults(false),              fn () => $tagged('notes')->delete()],
            ['reminders',          fn () => $tagged('reminders')->countAllResults(false),          fn () => $tagged('reminders')->delete()],
            ['rokad_entries',      fn () => $db->table('rokad_entries')->where('remarks', 'LT')->countAllResults(false), fn () => $db->table('rokad_entries')->where('remarks', 'LT')->delete()],
            ['users',              fn () => $db->table('users')->like('username', 'load_', 'after')->countAllResults(false), fn () => $db->table('users')->like('username', 'load_', 'after')->delete()],
            ['load_test_records',  fn () => $db->table('load_test_records')->countAllResults(false), fn () => $db->query('DELETE FROM load_test_records')],
            ['load_test_runs',     fn () => $db->table('load_test_runs')->countAllResults(false),    fn () => $db->query('DELETE FROM load_test_runs')],
        ];
    }

    public function run(array $params)
    {
        $db    = Database::connect();
        $force = in_array('--force', $params, true) || array_key_exists('force', $params);

        $targets = $this->targets($db);
        $total   = 0;

        CLI::write($force ? 'Purging load-test data…' : 'Load-test data found (dry run — nothing deleted):', $force ? 'yellow' : 'green');
        CLI::newLine();

        foreach ($targets as [$table, $count, $delete]) {
            $n = (int) $count();
            $total += $n;
            if ($n === 0) {
                CLI::write(sprintf('  %-22s %s', $table, CLI::color('clean', 'dark_gray')));
                continue;
            }
            if ($force) {
                $delete();
                CLI::write(sprintf('  %-22s %s', $table, CLI::color('removed ' . number_format($n), 'red')));
            } else {
                CLI::write(sprintf('  %-22s %s', $table, CLI::color(number_format($n) . ' tagged rows', 'yellow')));
            }
        }

        CLI::newLine();
        if ($total === 0) {
            CLI::write(CLI::color('No load-test data present — database is production-clean.', 'green'));
        } elseif ($force) {
            CLI::write(CLI::color("Done. Removed {$total} row(s). Take the backup now.", 'green'));
        } else {
            CLI::write(CLI::color("{$total} row(s) would be removed. Re-run with --force to delete.", 'yellow'));
        }
        return 0;
    }
}
