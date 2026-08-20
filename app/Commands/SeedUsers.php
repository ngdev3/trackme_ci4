<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Bulk-create LOAD-TEST user accounts (active + email-verified) so the API/auth
 * layer can be load-tested with many distinct logins. LOCAL ONLY — never run
 * against production.
 *
 *   php spark seed:users                 # 1000 users, password Load@12345
 *   php spark seed:users --count 500 --password Secret@123
 *   php spark seed:users --purge          # remove previously seeded load users
 *
 * Emails are loaduser<N>@loadtest.local so they are easy to identify + purge.
 */
class SeedUsers extends BaseCommand
{
    protected $group       = 'Data';
    protected $name        = 'seed:users';
    protected $description = 'Create N active load-test users (loaduser<N>@loadtest.local) for API load testing.';
    protected $usage       = 'seed:users [--count 1000] [--password Load@12345] [--purge]';
    protected $options     = [
        '--count'    => 'How many users to create (default 1000).',
        '--password' => 'Shared password for every user (default Load@12345).',
        '--purge'    => 'Delete all previously seeded @loadtest.local users and exit.',
    ];

    private const DOMAIN = '@loadtest.local';
    private const BATCH  = 500;

    public function run(array $params): int
    {
        $db = Database::connect();

        if (CLI::getOption('purge') !== null) {
            $n = $db->table('users')->like('email', self::DOMAIN, 'before')->countAllResults();
            $db->table('users')->like('email', self::DOMAIN, 'before')->delete();
            CLI::write("Purged {$n} load-test users.", 'yellow');
            return 0;
        }

        $count    = (int) (CLI::getOption('count') ?? 1000);
        $password = (string) (CLI::getOption('password') ?? 'Load@12345');
        $hash     = password_hash($password, PASSWORD_DEFAULT);
        $now      = date('Y-m-d H:i:s');

        // Reuse a valid user_type_id from an existing customer so FK/joins hold.
        $typeId = $db->table('users')->select('user_type_id')
            ->where('account_type', 'customer')->where('user_type_id IS NOT NULL', null, false)
            ->limit(1)->get()->getRowArray()['user_type_id'] ?? null;

        // Re-running is idempotent: emails collide on the unique index and are
        // skipped via ignore(true), so it tops up to `count` without duplicates.
        $start = 1;

        CLI::write(str_repeat('=', 52), 'blue');
        CLI::write("Creating {$count} load-test users (password: {$password})", 'green');
        CLI::write('  emails: loaduser<N>' . self::DOMAIN . '   type_id=' . ($typeId ?? 'null'));
        CLI::write(str_repeat('=', 52), 'blue');

        $t0 = microtime(true);
        $rows = [];
        $made = 0;
        for ($i = $start; $i < $start + $count; $i++) {
            $email = 'loaduser' . $i . self::DOMAIN;
            $rows[] = [
                'name'              => 'Load User ' . $i,
                'email'             => $email,
                'username'          => 'loaduser' . $i,
                'password'          => $hash,
                'auth_provider'     => null,
                'provider_id'       => null,
                'user_type_id'      => $typeId,
                'account_type'      => 'customer',
                'status'            => 1,
                'email_verified_at' => $now,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
            if (count($rows) >= self::BATCH) {
                $made += $this->flush($db, $rows);
                $rows = [];
                CLI::write(sprintf('  ...%s created (%.1fs)', number_format($made), microtime(true) - $t0));
            }
        }
        if ($rows !== []) {
            $made += $this->flush($db, $rows);
        }

        CLI::write(str_repeat('-', 52));
        CLI::write(sprintf('DONE in %.1fs — %s load-test users created.', microtime(true) - $t0, number_format($made)), 'green');
        CLI::write('Log in with any: loaduser1' . self::DOMAIN . ' / ' . $password);
        return 0;
    }

    /** Insert a batch, ignoring duplicate-email collisions from a prior run. */
    private function flush($db, array $rows): int
    {
        try {
            $db->table('users')->ignore(true)->insertBatch($rows);
            return count($rows);
        } catch (\Throwable $e) {
            CLI::error('  batch failed: ' . $e->getMessage());
            return 0;
        }
    }
}
