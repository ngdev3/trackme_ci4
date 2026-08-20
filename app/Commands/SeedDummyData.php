<?php

namespace App\Commands;

use App\Libraries\PasswordVault;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Populate a company with ~1 year of realistic demo data across every module
 * (cash-book transactions, ledgers/groups/vouchers, notes, reminders, passwords,
 * calculator history). For demos/screenshots only.
 *
 *   php spark seed:dummy --company 78 --user 999019
 *   php spark seed:dummy --company 78 --user 999019 --fresh   (wipe prior demo rows first)
 */
class SeedDummyData extends BaseCommand
{
    protected $group       = 'Data';
    protected $name        = 'seed:dummy';
    protected $description = 'Insert ~1 year of demo data into every module for a company.';
    protected $usage       = 'seed:dummy --company <id> --user <id> [--fresh]';
    protected $options     = [
        '--company'   => 'Company id to seed.',
        '--user'      => 'Owner user id (author of the rows).',
        '--fresh'     => 'Delete existing rows for this company first.',
        '--from'      => 'Transactions start date (Y-m-d). Default: 12 months ago.',
        '--to'        => 'Transactions end date (Y-m-d). Default: today.',
        '--txns-only' => 'Only add cash-book transactions for the given range (skip other modules).',
    ];

    private $db;
    private int $cid;
    private int $uid;

    public function run(array $params): int
    {
        $this->cid = (int) (CLI::getOption('company') ?? 0);
        $this->uid = (int) (CLI::getOption('user') ?? 0);
        if ($this->cid <= 0 || $this->uid <= 0) {
            CLI::error('Provide --company and --user, e.g. php spark seed:dummy --company 78 --user 999019');
            return 1;
        }
        $this->db = Database::connect();

        if (CLI::getOption('fresh') !== null) {
            foreach (['transactions', 'notes', 'note_categories', 'reminders', 'passwords', 'vouchers', 'voucher_entries', 'ledgers'] as $t) {
                if ($this->db->fieldExists('company_id', $t)) {
                    $this->db->table($t)->where('company_id', $this->cid)->delete();
                }
            }
            $this->db->table('calculator_history')->where('user_id', $this->uid)->delete();
            CLI::write('Cleared prior rows for company ' . $this->cid, 'yellow');
        }

        $from = CLI::getOption('from');
        $to   = CLI::getOption('to');
        $start = $from ? new \DateTime($from) : (new \DateTime('-12 months'))->modify('first day of this month');
        $end   = $to ? new \DateTime($to) : new \DateTime('today');

        // Keep the books opening at/before the earliest data we insert.
        $bb = (string) ($this->db->table('companies')->select('books_beginning_from')->where('id', $this->cid)->get()->getRowArray()['books_beginning_from'] ?? '');
        if ($bb === '' || strtotime($start->format('Y-m-d')) < strtotime($bb)) {
            $this->db->table('companies')->where('id', $this->cid)->update(['books_beginning_from' => $start->format('Y-m-d')]);
        }

        $txns = $this->seedTransactions($start, $end);

        if (CLI::getOption('txns-only') !== null) {
            CLI::write("Added {$txns} transactions ({$start->format('Y-m-d')} → {$end->format('Y-m-d')}) for company {$this->cid}.", 'green');
            return 0;
        }

        $groups  = $this->seedGroups();
        $ledgers = $this->seedLedgers($groups);
        $this->seedVouchers($start, $ledgers);
        $this->seedNotes();
        $this->seedReminders();
        $this->seedPasswords();
        $this->seedCalculator();

        CLI::write(str_repeat('-', 46));
        CLI::write('Demo data inserted for company ' . $this->cid . ':', 'green');
        foreach (['transactions', 'ledgers', 'accounting_groups', 'vouchers', 'voucher_entries', 'notes', 'reminders', 'passwords'] as $t) {
            $col = $t === 'accounting_groups' ? 'company_id' : 'company_id';
            $n   = $this->db->table($t)->where('company_id', $this->cid)->countAllResults();
            CLI::write(sprintf('  %-18s %d', $t, $n));
        }
        CLI::write(sprintf('  %-18s %d', 'calculator_history', $this->db->table('calculator_history')->where('user_id', $this->uid)->countAllResults()));
        return 0;
    }

    private function now(): string { return date('Y-m-d H:i:s'); }

    /** @return array<string,int> group name => id */
    private function seedGroups(): array
    {
        $defs = [
            ['Sundry Debtors', 'assets'], ['Sundry Creditors', 'liabilities'],
            ['Cash-in-Hand', 'assets'], ['Bank Accounts', 'assets'],
            ['Sales Accounts', 'income'], ['Purchase Accounts', 'expenses'],
            ['Direct Expenses', 'expenses'], ['Indirect Expenses', 'expenses'],
        ];
        $map = [];
        foreach ($defs as [$name, $nature]) {
            $existing = $this->db->table('accounting_groups')->where('company_id', $this->cid)->where('name', $name)->get()->getRowArray();
            if ($existing) { $map[$name] = (int) $existing['id']; continue; }
            $this->db->table('accounting_groups')->insert([
                'company_id' => $this->cid, 'name' => $name, 'nature' => $nature,
                'is_default' => 1, 'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
            $map[$name] = (int) $this->db->insertID();
        }
        return $map;
    }

    /** @return list<int> ledger ids */
    private function seedLedgers(array $groups): array
    {
        $rows = [
            ['Ramesh Traders', 'Sundry Debtors', 45000, 'Dr'], ['Sunrise Supplies', 'Sundry Debtors', 12500, 'Dr'],
            ['Balaji Enterprises', 'Sundry Debtors', 0, 'Dr'], ['Gupta Distributors', 'Sundry Creditors', 33000, 'Cr'],
            ['Sharma Wholesale', 'Sundry Creditors', 18750, 'Cr'], ['HDFC Bank', 'Bank Accounts', 250000, 'Dr'],
            ['SBI Current A/c', 'Bank Accounts', 120000, 'Dr'], ['Cash', 'Cash-in-Hand', 15000, 'Dr'],
            ['Sales - Local', 'Sales Accounts', 0, 'Cr'], ['Sales - Interstate', 'Sales Accounts', 0, 'Cr'],
            ['Purchase - Goods', 'Purchase Accounts', 0, 'Dr'], ['Rent A/c', 'Indirect Expenses', 0, 'Dr'],
            ['Electricity', 'Indirect Expenses', 0, 'Dr'], ['Salaries', 'Indirect Expenses', 0, 'Dr'],
            ['Transport Charges', 'Direct Expenses', 0, 'Dr'],
        ];
        $ids = [];
        foreach ($rows as [$name, $grp, $ob, $ot]) {
            $this->db->table('ledgers')->insert([
                'company_id' => $this->cid, 'group_id' => $groups[$grp] ?? null, 'name' => $name,
                'opening_balance' => $ob, 'opening_type' => $ot,
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
            $ids[] = (int) $this->db->insertID();
        }
        return $ids;
    }

    private function seedTransactions(\DateTime $start, ?\DateTime $end = null): int
    {
        $parties = ['Ramesh Traders', 'Sunrise Supplies', 'Balaji Enterprises', 'Gupta Distributors',
            'Sharma Wholesale', 'City Hardware', 'Annapurna Sweets', 'New Chicken Corner', 'Royal Tailors',
            'Bombay Fashion', 'Sai Provision Store', 'Maa Vaishno Dhaba', 'Krishna Auto Parts', 'Verma Electronics',
            'Patel Agencies', 'Metro Cash & Carry', 'Green Farm Produce', 'Star Stationers', 'Modern Furniture', 'Apex Traders'];
        $ptypes  = ['Customer', 'Supplier', 'Transporter', 'Firm', 'Vendor', 'Retailer'];
        $modes   = ['cash', 'upi', 'bank', 'cheque', 'card'];
        $statuses = ['paid', 'paid', 'paid', 'paid', 'pending', 'overdue'];
        $jamaNotes = ['Payment received', 'Sale settled', 'Advance received', 'Cash deposit', 'Invoice cleared'];
        $naamNotes = ['Goods purchased', 'Rent paid', 'Transport charges', 'Salary paid', 'Electricity bill', 'Supplier payment'];

        // Continue the txn number from the company's current max so re-runs never collide.
        $maxNo = (string) ($this->db->table('transactions')->selectMax('txn_no')->where('company_id', $this->cid)->get()->getRowArray()['txn_no'] ?? '');
        $seq   = max(100, (int) preg_replace('/\D/', '', $maxNo));
        $end   = $end ?? new \DateTime('today');
        $cur = clone $start;
        $count = 0;
        $this->db->transStart();
        while ($cur <= $end) {
            // 0–3 entries per day, weighted so most days have some activity.
            $perDay = [0, 1, 1, 2, 2, 3][random_int(0, 5)];
            for ($k = 0; $k < $perDay; $k++) {
                $isJama = random_int(1, 100) <= 58;              // slightly more inflows
                $amount = random_int(200, 45000) + random_int(0, 99) / 100;
                $seq++;
                $t = clone $cur;
                $t->setTime(random_int(9, 20), random_int(0, 59), random_int(0, 59));
                $this->db->table('transactions')->insert([
                    'user_id'      => $this->uid,
                    'company_id'   => $this->cid,
                    'txn_no'       => 'TXN-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
                    'txn_date'     => $cur->format('Y-m-d'),
                    'name'         => $parties[array_rand($parties)],
                    'party_type'   => $ptypes[array_rand($ptypes)],
                    'type'         => $isJama ? 'jama' : 'naam',
                    'amount'       => $amount,
                    'payment_mode' => $modes[array_rand($modes)],
                    'source'       => random_int(0, 1) ? 'web' : 'app',
                    'status'       => $statuses[array_rand($statuses)],
                    'notes'        => $isJama ? $jamaNotes[array_rand($jamaNotes)] : $naamNotes[array_rand($naamNotes)],
                    'created_at'   => $t->format('Y-m-d H:i:s'),
                    'updated_at'   => $t->format('Y-m-d H:i:s'),
                ]);
                $count++;
            }
            $cur->modify('+1 day');
        }
        $this->db->transComplete();
        return $count;
    }

    private function seedVouchers(\DateTime $start, array $ledgers): void
    {
        $types = ['payment', 'receipt', 'journal', 'contra', 'sales', 'purchase'];
        $narr  = ['Being amount received from party', 'Being payment made to supplier',
            'Being goods sold on credit', 'Being purchase of stock', 'Being cash deposited to bank', 'Adjustment entry'];
        $end = new \DateTime('today');
        for ($i = 0; $i < 40; $i++) {
            $d = (clone $start)->modify('+' . random_int(0, (int) (($end->getTimestamp() - $start->getTimestamp()) / 86400)) . ' days');
            $amount = random_int(500, 60000);
            $this->db->table('vouchers')->insert([
                'company_id' => $this->cid, 'voucher_type' => $types[array_rand($types)],
                'voucher_no' => 'V-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'date' => $d->format('Y-m-d'), 'narration' => $narr[array_rand($narr)],
                'amount' => $amount, 'created_by' => $this->uid,
                'created_at' => $this->now(), 'updated_at' => $this->now(),
            ]);
            $vid = (int) $this->db->insertID();
            if (count($ledgers) >= 2) {
                $dr = $ledgers[array_rand($ledgers)];
                $cr = $ledgers[array_rand($ledgers)];
                if ($cr === $dr) { $cr = $ledgers[($dr + 1) % count($ledgers)] ?? $ledgers[0]; }
                $this->db->table('voucher_entries')->insert(['voucher_id' => $vid, 'company_id' => $this->cid, 'ledger_id' => $dr, 'dr_amount' => $amount, 'cr_amount' => 0, 'created_at' => $this->now()]);
                $this->db->table('voucher_entries')->insert(['voucher_id' => $vid, 'company_id' => $this->cid, 'ledger_id' => $cr, 'dr_amount' => 0, 'cr_amount' => $amount, 'created_at' => $this->now()]);
            }
        }
    }

    private function seedNotes(): void
    {
        $cats = [['Business', 'blue'], ['Personal', 'green'], ['Ideas', 'amber'], ['Follow-up', 'red'], ['Suppliers', 'purple']];
        $catIds = [];
        foreach ($cats as [$name, $color]) {
            $this->db->table('note_categories')->insert(['user_id' => $this->uid, 'company_id' => $this->cid, 'name' => $name, 'color' => $color, 'created_at' => $this->now(), 'updated_at' => $this->now()]);
            $catIds[] = (int) $this->db->insertID();
        }
        $notes = [
            ['Call Ramesh Traders for pending ₹45,000', 'Follow up on the outstanding invoice from last month. Promised payment by Friday.'],
            ['Diwali stock order', 'Order sweets boxes, decorative lights and gift packs from Annapurna Sweets before the festival rush.'],
            ['GST filing reminder', 'File GSTR-3B before the 20th. Keep purchase invoices ready.'],
            ['New supplier — Green Farm', 'Better rates on vegetables. Negotiate 30-day credit terms.'],
            ['Shop renovation ideas', 'Repaint front, add LED signage, reorganise the billing counter.'],
            ['Staff salary day', 'Pay salaries on the 1st. Total ₹85,000 for 5 staff.'],
            ['Bank loan documents', 'Collect statements and ITR for the working-capital loan application.'],
            ['Festival offer plan', 'Flat 10% off + free delivery above ₹2,000 during the festive week.'],
            ['Inventory audit', 'Physical stock count at month end. Reconcile with the ledger.'],
            ['Customer feedback', 'Regulars asking for home delivery — evaluate a delivery boy on part-time.'],
            ['Electricity meter reading', 'Note the reading on the 5th; compare with last month.'],
            ['Wholesale price list', 'Update the price list; raw material costs went up ~8%.'],
        ];
        foreach ($notes as $i => [$title, $content]) {
            $this->db->table('notes')->insert([
                'user_id' => $this->uid, 'company_id' => $this->cid, 'category_id' => $catIds[array_rand($catIds)],
                'title' => $title, 'content' => $content, 'color' => $cats[array_rand($cats)][1],
                'is_pinned' => $i < 2 ? 1 : 0, 'is_important' => $i % 4 === 0 ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . random_int(1, 300) . ' days')), 'updated_at' => $this->now(),
            ]);
        }
    }

    private function seedReminders(): void
    {
        $items = [
            ['Pay supplier — Gupta Distributors', 'high'], ['Collect payment from Ramesh Traders', 'high'],
            ['GST return filing', 'high'], ['Renew shop licence', 'medium'], ['Staff salary', 'high'],
            ['Electricity bill due', 'medium'], ['Bank EMI', 'medium'], ['Reorder fast-moving stock', 'low'],
            ['Call accountant for audit', 'medium'], ['Insurance premium', 'low'], ['Vendor meeting', 'low'],
            ['Stock-taking day', 'medium'], ['Festival promo launch', 'medium'], ['Update price list', 'low'],
        ];
        foreach ($items as $i => [$title, $prio]) {
            $future = $i % 2 === 0;
            $when = date('Y-m-d H:i:s', strtotime(($future ? '+' : '-') . random_int(1, 45) . ' days'));
            $done = ! $future && random_int(0, 1) === 1;
            $this->db->table('reminders')->insert([
                'user_id' => $this->uid, 'company_id' => $this->cid, 'title' => $title,
                'description' => 'Auto-generated demo reminder — ' . strtolower($title) . '.',
                'remind_at' => $when, 'priority' => $prio,
                'status' => $done ? 'done' : 'pending', 'completed_at' => $done ? $when : null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . random_int(5, 120) . ' days')), 'updated_at' => $this->now(),
            ]);
        }
    }

    private function seedPasswords(): void
    {
        $vault = new PasswordVault();
        $rows = [
            ['GST Portal', 'https://gst.gov.in', 'ALOK07GSTIN', 'Gst@Portal2026'],
            ['Income Tax e-Filing', 'https://incometax.gov.in', 'AABCA1234F', 'Tax@Filing#26'],
            ['HDFC NetBanking', 'https://netbanking.hdfcbank.com', 'alok_hdfc', 'Hdfc@Net2026'],
            ['SBI YONO Business', 'https://yonobusiness.sbi', 'alok.sbi', 'Sbi@Yono!26'],
            ['Cashfree Merchant', 'https://merchant.cashfree.com', 'alok@store.com', 'Cash@Free2026'],
            ['Google Workspace', 'https://admin.google.com', 'admin@alokstore.in', 'GSuite@2026'],
            ['Amazon Seller', 'https://sellercentral.amazon.in', 'alok-seller', 'Amz@Seller26'],
            ['Flipkart Seller Hub', 'https://seller.flipkart.com', 'alokstore', 'Fk@Seller2026'],
            ['Electricity Board', 'https://portal.mahadiscom.in', 'CA-90210345', 'Elec@Board26'],
            ['Shop Wi-Fi Router', '192.168.1.1', 'admin', 'ShopWifi@2026'],
            ['Tally Vault', '', 'admin', 'Tally@Vault26'],
            ['Zoho Books', 'https://books.zoho.in', 'alok@alokstore.in', 'Zoho@Books26'],
        ];
        foreach ($rows as [$title, $site, $user, $pass]) {
            $this->db->table('passwords')->insert([
                'company_id' => $this->cid, 'title' => $title, 'website' => $site, 'username' => $user,
                'password_enc' => $vault->encrypt($pass), 'category' => 'Business', 'created_by' => $this->uid,
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . random_int(1, 200) . ' days')), 'updated_at' => $this->now(),
            ]);
        }
    }

    private function seedCalculator(): void
    {
        $rows = [
            ['GST 18% on 45000', '45000*18/100', '8100'], ['Margin', '(120-90)/90*100', '33.33'],
            ['Monthly rent share', '25000/30*10', '8333.33'], ['Discount 12%', '15000*0.12', '1800'],
            ['Profit', '720000-540000', '180000'], ['Per unit cost', '48000/240', '200'],
            ['Interest', '250000*0.12*1', '30000'], ['Round off', '12591.60', '12592'],
            ['Bulk price', '250*0.9*100', '22500'], ['Yearly', '85000*12', '1020000'],
            ['Tax + amount', '10000*1.18', '11800'], ['Split bill', '2340/4', '585'],
        ];
        foreach ($rows as [$title, $expr, $res]) {
            $this->db->table('calculator_history')->insert([
                'user_id' => $this->uid, 'title' => $title, 'expression' => $expr, 'result' => $res,
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . random_int(0, 90) . ' days')), 'updated_at' => $this->now(),
            ]);
        }
    }
}
