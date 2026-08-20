<?php

namespace App\Commands;

use App\Libraries\CompanyProvisioner;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Bulk-seed a customer account with many firms, each with many accounts (parties)
 * and Jama/Naam entries, plus calculator history. For load / scale testing.
 *
 *   php spark seed:bulk --email rajatinvoice@gmail.com
 *   php spark seed:bulk --email x@y.com --firms 25 --accounts 200 --entries 100 --calc 200
 *
 * `--entries N` is PER TYPE, so each account gets N Jama + N Naam rows.
 * Defaults: 25 firms x 200 accounts x (100 Jama + 100 Naam) = ~1,000,000 entries.
 */
class SeedBulkData extends BaseCommand
{
    protected $group       = 'Data';
    protected $name        = 'seed:bulk';
    protected $description = 'Bulk-seed firms, accounts, Jama/Naam entries and calculator history for a user.';
    protected $usage       = 'seed:bulk --email <email> [--firms 25] [--accounts 200] [--entries 100] [--calc 200]';
    protected $options     = [
        '--email'    => 'Owner account email (required).',
        '--firms'    => 'Number of firms/companies to create (default 25).',
        '--accounts' => 'Unique accounts (parties) per firm (default 200).',
        '--entries'  => 'Entries per type per account — N Jama + N Naam (default 100).',
        '--calc'     => 'Calculator history rows for the user (default 200).',
    ];

    private const BATCH = 3000; // rows per insertBatch chunk

    public function run(array $params): int
    {
        $email    = strtolower(trim((string) (CLI::getOption('email') ?? '')));
        $firms    = (int) (CLI::getOption('firms') ?? 25);
        $accounts = (int) (CLI::getOption('accounts') ?? 200);
        $perType  = (int) (CLI::getOption('entries') ?? 100);
        $calcN    = (int) (CLI::getOption('calc') ?? 200);

        if ($email === '') {
            CLI::error('Provide --email, e.g. php spark seed:bulk --email rajatinvoice@gmail.com');
            return 1;
        }

        $users = new UserModel();
        $user  = $users->withDeleted()->where('email', $email)->first();
        if (! $user) {
            CLI::error("No account found for {$email}. Create it first, then re-run.");
            return 1;
        }
        $uid = (int) $user['id'];

        // Make sure the account is usable (restore if it was in the Recycle Bin).
        if (! empty($user['deleted_at']) || (int) ($user['status'] ?? 0) !== 1) {
            $users->builder()->where('id', $uid)->update([
                'deleted_at' => null, 'status' => 1, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            CLI::write("Restored + activated account {$email} (id {$uid}).", 'yellow');
        }

        $totalEntries = $firms * $accounts * $perType * 2;
        CLI::write(str_repeat('=', 56), 'blue');
        CLI::write("Seeding {$email} (user {$uid})", 'green');
        CLI::write(sprintf('  %d firms x %d accounts x (%d Jama + %d Naam)', $firms, $accounts, $perType, $perType));
        CLI::write(sprintf('  = %s accounts, %s entries + %d calculator rows',
            number_format($firms * $accounts), number_format($totalEntries), $calcN));
        CLI::write(str_repeat('=', 56), 'blue');

        $db = Database::connect();
        $start = microtime(true);
        $grandEntries = 0;

        for ($f = 1; $f <= $firms; $f++) {
            $cid = $this->createFirm($uid, $f);
            if (! $cid) {
                CLI::error("  Firm {$f}: could not create — skipping.");
                continue;
            }
            $made = $this->seedFirmEntries($db, $uid, $cid, $f, $accounts, $perType);
            $grandEntries += $made;
            CLI::write(sprintf('  [%2d/%2d] firm #%d  +%s entries  (running total %s)  %.1fs',
                $f, $firms, $cid, number_format($made), number_format($grandEntries), microtime(true) - $start));
        }

        $this->seedCalculator($db, $uid, $calcN);

        CLI::write(str_repeat('-', 56));
        CLI::write(sprintf('DONE in %.1fs — %d firms, %s entries, %d calculator rows.',
            microtime(true) - $start, $firms, number_format($grandEntries), $calcN), 'green');
        return 0;
    }

    /** Create one firm via the production provisioner (company + owner membership + defaults). */
    private function createFirm(int $uid, int $n): ?int
    {
        $fyStart = date('Y') . '-04-01';
        if ((int) date('n') < 4) {
            $fyStart = (date('Y') - 1) . '-04-01';
        }
        $states = ['Uttar Pradesh', 'Maharashtra', 'Delhi', 'Gujarat', 'Karnataka', 'Rajasthan', 'Bihar', 'Punjab'];
        $btypes = ['Proprietorship', 'Partnership', 'Private Limited', 'LLP', 'HUF'];

        $data = [
            'name'                 => sprintf('Bulk Firm %03d', $n),
            'financial_year_from'  => $fyStart,
            'books_beginning_from' => date('Y-m-d', strtotime('-12 months')),
            'state'                => $states[array_rand($states)],
            'country'              => 'India',
            'business_type'        => $btypes[array_rand($btypes)],
            'status'               => 1,
        ];
        return (new CompanyProvisioner())->create($uid, $data) ?: null;
    }

    /** Seed all accounts + Jama/Naam entries for one firm. Returns entries inserted. */
    private function seedFirmEntries($db, int $uid, int $cid, int $firmNo, int $accounts, int $perType): int
    {
        $bases  = ['Ramesh Traders', 'Sunrise Supplies', 'Balaji Enterprises', 'Gupta Distributors',
            'Sharma Wholesale', 'City Hardware', 'Annapurna Sweets', 'Krishna Auto Parts', 'Verma Electronics',
            'Patel Agencies', 'Metro Cash & Carry', 'Green Farm Produce', 'Star Stationers', 'Apex Traders',
            'Sai Provision', 'Royal Tailors', 'Bombay Fashion', 'Modern Furniture', 'New Chicken Corner', 'Maa Vaishno'];
        $ptypes = ['Customer', 'Supplier', 'Transporter', 'Firm', 'Vendor', 'Retailer'];
        $modes  = ['cash', 'upi', 'bank', 'cheque', 'card'];
        $stat   = ['paid', 'paid', 'paid', 'received', 'pending', 'overdue'];
        $jNotes = ['Payment received', 'Sale settled', 'Advance received', 'Cash deposit', 'Invoice cleared'];
        $nNotes = ['Goods purchased', 'Rent paid', 'Transport charges', 'Salary paid', 'Electricity bill'];

        $seq   = 0;
        $rows  = [];
        $made  = 0;
        $today = time();

        $db->transStart();
        for ($a = 1; $a <= $accounts; $a++) {
            $party = $bases[($a - 1) % count($bases)] . ' #' . $a; // unique per firm
            $ptype = $ptypes[array_rand($ptypes)];
            for ($pass = 0; $pass < 2; $pass++) {
                $type = $pass === 0 ? 'jama' : 'naam';
                for ($e = 0; $e < $perType; $e++) {
                    $seq++;
                    $ts = $today - random_int(0, 365 * 86400);
                    $dt = date('Y-m-d H:i:s', $ts);
                    $rows[] = [
                        'user_id'      => $uid,
                        'company_id'   => $cid,
                        'txn_no'       => 'TXN-' . str_pad((string) $seq, 7, '0', STR_PAD_LEFT),
                        'txn_date'     => date('Y-m-d', $ts),
                        'name'         => $party,
                        'party_type'   => $ptype,
                        'type'         => $type,
                        'amount'       => random_int(100, 50000) + random_int(0, 99) / 100,
                        'payment_mode' => $modes[array_rand($modes)],
                        'source'       => random_int(0, 1) ? 'web' : 'app',
                        'status'       => $stat[array_rand($stat)],
                        'notes'        => $type === 'jama' ? $jNotes[array_rand($jNotes)] : $nNotes[array_rand($nNotes)],
                        'created_at'   => $dt,
                        'updated_at'   => $dt,
                    ];
                    if (count($rows) >= self::BATCH) {
                        $db->table('transactions')->insertBatch($rows);
                        $made += count($rows);
                        $rows = [];
                    }
                }
            }
        }
        if ($rows !== []) {
            $db->table('transactions')->insertBatch($rows);
            $made += count($rows);
        }
        $db->transComplete();
        return $made;
    }

    private function seedCalculator($db, int $uid, int $n): void
    {
        $titles = ['GST 18%', 'Margin', 'Rent share', 'Discount', 'Profit', 'Per unit cost',
            'Interest', 'Round off', 'Bulk price', 'Yearly', 'Tax + amount', 'Split bill'];
        $rows = [];
        for ($i = 0; $i < $n; $i++) {
            $x = random_int(1000, 90000);
            $y = random_int(2, 24);
            $dt = date('Y-m-d H:i:s', time() - random_int(0, 120 * 86400));
            $rows[] = [
                'user_id'    => $uid,
                'title'      => $titles[array_rand($titles)] . ' #' . ($i + 1),
                'expression' => "{$x}*{$y}",
                'result'     => (string) ($x * $y),
                'created_at' => $dt,
                'updated_at' => $dt,
            ];
        }
        if ($rows !== []) {
            $db->table('calculator_history')->insertBatch($rows, self::BATCH);
        }
    }
}
