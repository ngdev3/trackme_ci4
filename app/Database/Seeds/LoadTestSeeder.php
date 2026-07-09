<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Heavy load-test data. Inserts 100,000 rows into each of users, notes,
 * reminders, calculator_history and rokad_entries (~500k rows), so the UI /
 * pagination / charts / AJAX can be tested at scale. The `admin` account
 * (and its firm) receives 10,000 of each so it is heavy on its own.
 *
 * Everything is tagged for easy removal:
 *   - users:  username LIKE 'load\_%'
 *   - notes / reminders / calculator: title LIKE '[LT]%'
 *   - rokad_entries: remarks = 'LT'
 * See the cleanup SQL at the bottom of this file.
 *
 *   php spark db:seed LoadTestSeeder
 */
class LoadTestSeeder extends Seeder
{
    private const USERS       = 100000;
    private const PER_TABLE   = 100000;
    private const ADMIN_SHARE = 10000;   // rows pinned to the admin account
    private const CHUNK       = 2000;
    private const ADMIN_ID    = 2;        // username 'admin'

    private array $first = ['Aarav', 'Vivaan', 'Aditya', 'Arjun', 'Sai', 'Rohan', 'Ananya', 'Diya', 'Myra', 'Riya',
        'John', 'Emma', 'Liam', 'Olivia', 'Noah', 'Ava', 'Rahul', 'Neha', 'Amit', 'Pooja', 'Vikram', 'Sneha'];
    private array $last  = ['Sharma', 'Verma', 'Gupta', 'Patel', 'Singh', 'Kumar', 'Rao', 'Nair', 'Shah', 'Mehta',
        'Jain', 'Khan', 'Smith', 'Brown', 'Jones', 'Taylor', 'Wilson', 'Lee', 'Clark', 'King'];
    private array $words = ['Payment', 'Invoice', 'Follow up', 'Meeting', 'Delivery', 'Order', 'Rent', 'Salary',
        'Purchase', 'Sale', 'Refund', 'Deposit', 'Withdrawal', 'Expense', 'Vendor', 'Client', 'Review', 'Report'];

    public function run()
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);

        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        // ---- 0. Clear any previous load-test data (idempotent re-runs) ----
        CLI::write('Clearing previous load-test data…');
        $db->table('calculator_history')->like('title', '[LT]', 'after')->delete();
        $db->table('notes')->like('title', '[LT]', 'after')->delete();
        $db->table('reminders')->like('title', '[LT]', 'after')->delete();
        $db->table('rokad_entries')->where('remarks', 'LT')->delete();
        $db->table('users')->like('username', 'load_', 'after')->delete(); // FK cascade clears their calc rows

        // ---- 1. Users ----
        $typeIds = array_column($db->table('user_types')->where('deleted_at', null)->get()->getResultArray(), 'id') ?: [null];
        $pass    = password_hash('Test@123', PASSWORD_DEFAULT);
        $base    = (int) ($db->table('users')->selectMax('id')->get()->getRowArray()['id'] ?? 0);

        $this->bulk('users', self::USERS, function (int $i) use ($typeIds, $pass, $now, $base) {
            $fn = $this->first[$i % count($this->first)];
            $ln = $this->last[($i * 7) % count($this->last)];
            $n  = $base + $i;
            return [
                'name' => "{$fn} {$ln}", 'email' => "load{$n}@loadtest.local", 'username' => "load_{$n}",
                'password' => $pass, 'mobile' => '9' . str_pad((string) ($i % 1000000000), 9, '0', STR_PAD_LEFT),
                'user_type_id' => $typeIds[$i % count($typeIds)], 'account_type' => 'super_admin',
                'auth_provider' => 'local', 'status' => ($i % 5 === 0) ? 0 : 1,
                'created_at' => $now, 'updated_at' => $now,
            ];
        });
        // Pool of REAL user ids (some scoped tables have FKs, and gaps exist).
        $pool  = array_column($db->table('users')->select('id')->get()->getResultArray(), 'id');
        $poolN = count($pool);
        $uid   = fn (int $i) => $i <= self::ADMIN_SHARE ? self::ADMIN_ID : $pool[random_int(0, $poolN - 1)];

        // ---- 2. Notes ----
        $this->bulk('notes', self::PER_TABLE, function (int $i) use ($uid, $now) {
            return [
                'user_id' => $uid($i), 'title' => '[LT] ' . $this->words[$i % count($this->words)] . " note #{$i}",
                'content' => 'Load-test note body number ' . $i . '. ' . $this->words[($i * 3) % count($this->words)] . ' details.',
                'tags' => ($i % 3 === 0) ? 'work,urgent' : null, 'is_pinned' => (int) ($i % 11 === 0),
                'is_important' => (int) ($i % 7 === 0), 'created_at' => $now, 'updated_at' => $now,
            ];
        });

        // ---- 3. Reminders ----
        $pri = ['low', 'medium', 'high'];
        $this->bulk('reminders', self::PER_TABLE, function (int $i) use ($uid, $pri) {
            $when = date('Y-m-d H:i:s', time() + random_int(-30, 30) * 86400 + random_int(0, 86400));
            return [
                'user_id' => $uid($i), 'title' => '[LT] ' . $this->words[$i % count($this->words)] . " reminder #{$i}",
                'description' => null, 'remind_at' => $when, 'priority' => $pri[$i % 3],
                'status' => ($i % 4 === 0) ? 'completed' : 'pending', 'repeat_type' => 'none', 'notified' => 0,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ];
        });

        // ---- 4. Calculator history ----
        $this->bulk('calculator_history', self::PER_TABLE, function (int $i) use ($uid, $now) {
            $a = random_int(2, 999); $b = random_int(2, 99); $op = ['+', '-', '*', '/'][$i % 4];
            $res = $op === '+' ? $a + $b : ($op === '-' ? $a - $b : ($op === '*' ? $a * $b : round($a / $b, 2)));
            return [
                'user_id' => $uid($i), 'title' => "[LT] Calc #{$i}", 'expression' => "{$a} {$op} {$b}",
                'result' => (string) $res, 'created_at' => $now, 'updated_at' => $now,
            ];
        });

        // ---- 5. Rokad entries (firm cash book) ----
        $companies = array_column($db->table('companies')->where('deleted_at', null)->get()->getResultArray(), 'id');
        if ($companies === []) {
            CLI::write(CLI::color('  (no companies — skipping rokad_entries)', 'yellow'));
        } else {
            $this->bulk('rokad_entries', self::PER_TABLE, function (int $i) use ($companies, $now) {
                $in = $i % 2 === 0;
                $amt = random_int(50, 50000);
                return [
                    'company_id' => $companies[$i % count($companies)],
                    'entry_date' => date('Y-m-d', time() - random_int(0, 365) * 86400),
                    'particular' => '[LT] ' . $this->words[$i % count($this->words)] . " #{$i}",
                    'jama' => $in ? $amt : 0, 'naam' => $in ? 0 : $amt, 'remarks' => 'LT',
                    'created_by' => self::ADMIN_ID, 'created_at' => $now, 'updated_at' => $now,
                ];
            });
        }

        CLI::write(CLI::color('Done. Users +' . self::USERS . ', and ' . self::PER_TABLE . ' rows each into notes/reminders/calculator/rokad.', 'green'));
    }

    /** Insert $total rows built by $rowFn, committing in chunks to stay fast + light. */
    private function bulk(string $table, int $total, callable $rowFn): void
    {
        $rows = [];
        for ($i = 1; $i <= $total; $i++) {
            $rows[] = $rowFn($i);
            if (count($rows) >= self::CHUNK) {
                $this->db->table($table)->insertBatch($rows);
                $rows = [];
            }
        }
        if ($rows !== []) {
            $this->db->table($table)->insertBatch($rows);
        }
        CLI::write('  ' . str_pad($table, 20) . ' +' . number_format($total));
    }
}

/*
 * Cleanup (removes ALL load-test data):
 *   DELETE FROM user_roles WHERE user_id IN (SELECT id FROM users WHERE username LIKE 'load\_%');
 *   DELETE FROM users              WHERE username LIKE 'load\_%';
 *   DELETE FROM notes              WHERE title LIKE '[LT]%';
 *   DELETE FROM reminders          WHERE title LIKE '[LT]%';
 *   DELETE FROM calculator_history WHERE title LIKE '[LT]%';
 *   DELETE FROM rokad_entries      WHERE remarks = 'LT';
 */
