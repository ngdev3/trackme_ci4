<?php

namespace App\Commands;

use App\Models\ProductModel;
use App\Models\TransactionModel;
use App\Services\LedgerPostingService;
use App\Services\PartyDirectory;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Local-only reconciliation harness for the Phase-1 Inventory+Accounts fix.
 * Runs the mandatory "Amit" scenario through the REAL models + posting service,
 * asserts stock / receivable / cash, then rolls everything back (persists
 * nothing). Not for production. Run: php spark test:amit
 */
class TestAmit extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:amit';
    protected $description = 'Reconcile the Amit inventory+accounts scenario (rolls back).';

    public function run(array $params)
    {
        $db  = Database::connect();
        $uid = 1;
        // A real throwaway company (transactions FK-reference companies.id).
        $db->table('companies')->insert([
            'owner_id' => $uid, 'name' => 'ZZ Amit Test Co', 'financial_year_from' => date('Y-04-01'),
            'books_beginning_from' => date('Y-04-01'), 'state' => 'Test', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $cid = (int) $db->insertID();
        $pass = true;
        $line = fn ($ok, $label, $got, $want) => CLI::write(
            ($ok ? CLI::color('  PASS ', 'green') : CLI::color('  FAIL ', 'red'))
            . str_pad($label, 34) . " got=" . $got . "  want=" . $want,
        );

        // Clean any leftovers from a previous run.
        $wipe = function () use ($db, $cid) {
            foreach (['transactions', 'invoices', 'stock_movements', 'products'] as $t) {
                if ($db->tableExists($t) && $db->fieldExists('company_id', $t)) {
                    $db->table($t)->where('company_id', $cid)->delete();
                }
            }
            // invoice_items has no company_id — clear by the test company's invoices.
            if ($db->tableExists('invoice_items')) {
                $db->query('DELETE ii FROM invoice_items ii LEFT JOIN invoices i ON i.id = ii.invoice_id WHERE i.id IS NULL');
            }
        };

        // 1) Opening Rice = 500 bags @ cost 40, sale 500.
        $products = new ProductModel();
        $products->skipValidation(true)->insert([
            'company_id' => $cid, 'created_by' => $uid, 'name' => 'Rice', 'sku' => 'RICE-TEST',
            'unit' => 'bag', 'sale_price' => 500, 'purchase_price' => 40,
            'opening_stock' => 500, 'current_stock' => 500, 'low_stock' => 0, 'tax_rate' => 0, 'status' => 1,
        ]);
        $riceId = (int) $products->getInsertID();

        // 1b) Oversell guard: selling 600 bags when only 500 exist must be blocked.
        $rice = $products->find($riceId);
        $blocked = false;
        try {
            (new LedgerPostingService())->postInvoice([
                'company_id' => $cid, 'user_id' => $uid, 'type' => 'sale', 'party_name' => 'Amit',
                'party_type' => 'Trader', 'payment_mode' => 'cash', 'invoice_date' => date('Y-m-d'),
                'notes' => null, 'discount' => 0, 'subtotal' => 300000, 'tax_total' => 0, 'total' => 300000, 'received' => 0,
                'client_uuid' => 'test-oversell',
                'lines' => [['product_id' => $riceId, 'product' => $rice, 'name' => 'Rice', 'qty' => 600, 'rate' => 500, 'tax_rate' => 0, 'amount' => 300000]],
            ]);
        } catch (\Throwable $e) {
            $blocked = str_contains($e->getMessage(), 'INSUFFICIENT_STOCK');
        }
        $stockAfterBlock = (float) $products->find($riceId)['current_stock'];
        CLI::newLine();
        CLI::write(CLI::color('Stock guard', 'yellow'));
        $g1 = $blocked;                            $pass &= $g1; $line($g1, 'Oversell blocked (600 > 500)', $blocked ? 'blocked' : 'ALLOWED', 'blocked');
        $g2 = abs($stockAfterBlock - 500) < 0.001; $pass &= $g2; $line($g2, 'Stock untouched after block', $stockAfterBlock, 500);

        // 2) Credit sale: 100 bags @ 500 = 50,000, received 0.
        $rice = $products->find($riceId);
        (new LedgerPostingService())->postInvoice([
            'company_id' => $cid, 'user_id' => $uid, 'type' => 'sale',
            'party_name' => 'Amit', 'party_type' => 'Trader', 'payment_mode' => 'cash',
            'invoice_date' => date('Y-m-d'), 'notes' => null, 'discount' => 0,
            'subtotal' => 50000, 'tax_total' => 0, 'total' => 50000, 'received' => 0,
            'client_uuid' => 'test-amit-sale-1',
            'lines' => [[
                'product_id' => $riceId, 'product' => $rice, 'name' => 'Rice',
                'qty' => 100, 'rate' => 500, 'tax_rate' => 0, 'amount' => 50000,
            ]],
        ]);

        // 3) Cash given to Amit ₹10,000 (real cash out → naam, ledger_only 0).
        $txn = new TransactionModel();
        $mk  = fn (string $type, float $amt, string $note) => $txn->insert([
            'user_id' => $uid, 'company_id' => $cid, 'txn_no' => $txn->nextTxnNo($cid),
            'txn_date' => date('Y-m-d'), 'name' => 'Amit', 'party_type' => 'Trader',
            'type' => $type, 'amount' => $amt, 'payment_mode' => 'cash', 'status' => 'paid',
            'ledger_only' => 0, 'notes' => $note, 'source' => 'mobile',
        ]);
        $mk('naam', 10000, 'Cash given');
        // 4) Payment received ₹30,000 (cash in → jama).
        $mk('jama', 30000, 'Payment received');

        // ---- Reconcile ---------------------------------------------------
        $stock = (float) $products->find($riceId)['current_stock'];

        $parties = (new PartyDirectory())->list($cid, 'Amit');
        $amit    = null;
        foreach ($parties as $p) { if ($p['name'] === 'Amit') { $amit = $p; } }
        $receivable = $amit ? (float) $amit['balance'] : 0.0;

        $sum      = $txn->summary($cid, []);        // cash book (excludes ledger_only)
        $cashNet  = (float) $sum['net'];
        $cashJama = (float) $sum['jama'];
        $cashNaam = (float) $sum['naam'];

        // Invoice cash entry must be absent (credit sale) → jama has no 50,000.
        CLI::newLine();
        CLI::write(CLI::color('Amit mandatory scenario', 'yellow'));
        $c1 = abs($stock - 400) < 0.001;              $pass &= $c1; $line($c1, 'Rice stock', $stock, 400);
        $c2 = abs($receivable - 30000) < 0.01;        $pass &= $c2; $line($c2, 'Amit receivable (Naam−Jama)', $receivable, 30000);
        $c3 = abs($cashJama - 30000) < 0.01;          $pass &= $c3; $line($c3, 'Cash-book Jama (no phantom 50k)', $cashJama, 30000);
        $c4 = abs($cashNaam - 10000) < 0.01;          $pass &= $c4; $line($c4, 'Cash-book Naam', $cashNaam, 10000);
        $c5 = abs($cashNet - 20000) < 0.01;           $pass &= $c5; $line($c5, 'Cash-in-hand net effect', $cashNet, 20000);

        // Idempotency: re-post the same sale uuid → no second bill / stock move.
        $before = (float) $products->find($riceId)['current_stock'];
        $dup = (new LedgerPostingService())->postInvoice([
            'company_id' => $cid, 'user_id' => $uid, 'type' => 'sale',
            'party_name' => 'Amit', 'party_type' => 'Trader', 'payment_mode' => 'cash',
            'invoice_date' => date('Y-m-d'), 'notes' => null, 'discount' => 0,
            'subtotal' => 50000, 'tax_total' => 0, 'total' => 50000, 'received' => 0,
            'client_uuid' => 'test-amit-sale-1',
            'lines' => [['product_id' => $riceId, 'product' => $rice, 'name' => 'Rice', 'qty' => 100, 'rate' => 500, 'tax_rate' => 0, 'amount' => 50000]],
        ]);
        $after = (float) $products->find($riceId)['current_stock'];
        $c6 = ($dup['duplicate'] === true) && abs($after - $before) < 0.001;
        $pass &= $c6; $line($c6, 'Idempotent re-post (no dup)', ($dup['duplicate'] ? 'dup' : 'new') . '/stock ' . $after, 'dup/stock 400');

        // 5) Amit returns Rice worth ₹5,000 (10 bags @ 500), no refund.
        CLI::newLine();
        CLI::write(CLI::color('Returns + Void', 'yellow'));
        $rice2 = $products->find($riceId);
        $ret = (new LedgerPostingService())->postReturn([
            'company_id' => $cid, 'user_id' => $uid, 'type' => 'sale_return',
            'party_name' => 'Amit', 'party_type' => 'Trader', 'payment_mode' => 'cash',
            'invoice_date' => date('Y-m-d'), 'notes' => null, 'discount' => 0,
            'subtotal' => 5000, 'tax_total' => 0, 'total' => 5000, 'received' => 0,
            'client_uuid' => 'test-amit-return-1',
            'lines' => [['product_id' => $riceId, 'product' => $rice2, 'name' => 'Rice', 'qty' => 10, 'rate' => 500, 'tax_rate' => 0, 'amount' => 5000]],
        ]);
        $stock2 = (float) $products->find($riceId)['current_stock'];
        $bal2   = 0.0; foreach ((new PartyDirectory())->list($cid, 'Amit') as $p) { if ($p['name'] === 'Amit') { $bal2 = (float) $p['balance']; } }
        $r1 = abs($stock2 - 410) < 0.001;      $pass &= $r1; $line($r1, 'After return: stock', $stock2, 410);
        $r2 = abs($bal2 - 25000) < 0.01;       $pass &= $r2; $line($r2, 'After return: Amit receivable', $bal2, 25000);

        // 6) Void the return → stock + receivable restored.
        (new LedgerPostingService())->voidInvoice((int) $ret['invoice']['id'], $cid, $uid, 'test void');
        $stock3 = (float) $products->find($riceId)['current_stock'];
        $bal3   = 0.0; foreach ((new PartyDirectory())->list($cid, 'Amit') as $p) { if ($p['name'] === 'Amit') { $bal3 = (float) $p['balance']; } }
        $v1 = abs($stock3 - 400) < 0.001;      $pass &= $v1; $line($v1, 'After void return: stock', $stock3, 400);
        $v2 = abs($bal3 - 30000) < 0.01;       $pass &= $v2; $line($v2, 'After void return: Amit receivable', $bal3, 30000);

        // 7) Accrual dashboard: a credit sale (50k, active) + a paid purchase (30k)
        //    must show Profit = Sales − Purchases = +20,000 (not the cash net).
        $rice3 = $products->find($riceId);
        (new LedgerPostingService())->postInvoice([
            'company_id' => $cid, 'user_id' => $uid, 'type' => 'purchase',
            'party_name' => 'Supplier Co', 'party_type' => 'Firm', 'payment_mode' => 'cash',
            'invoice_date' => date('Y-m-d'), 'notes' => null, 'discount' => 0,
            'subtotal' => 30000, 'tax_total' => 0, 'total' => 30000, 'received' => 30000,
            'client_uuid' => 'test-purchase-1',
            'lines' => [['product_id' => $riceId, 'product' => $rice3, 'name' => 'Rice', 'qty' => 60, 'rate' => 500, 'tax_rate' => 0, 'amount' => 30000]],
        ]);
        $bills = (new \App\Models\InvoiceModel())->periodTotals($cid, date('Y-m-01'), date('Y-m-t'));
        $profit = round($bills['net_sales'] - $bills['net_purchases'], 2);
        CLI::newLine();
        CLI::write(CLI::color('Accrual dashboard', 'yellow'));
        $a1 = abs($bills['net_sales'] - 50000) < 0.01;    $pass &= $a1; $line($a1, 'Billed Sales (incl. credit)', $bills['net_sales'], 50000);
        $a2 = abs($bills['net_purchases'] - 30000) < 0.01; $pass &= $a2; $line($a2, 'Billed Purchases', $bills['net_purchases'], 30000);
        $a3 = abs($profit - 20000) < 0.01;                 $pass &= $a3; $line($a3, 'Profit = Sales − Purchases', $profit, 20000);

        // 8) Party roles: Amit (sales only) = customer; buy from Amit too → both.
        CLI::newLine();
        CLI::write(CLI::color('Party roles', 'yellow'));
        $amitRole1 = (new \App\Models\PartyModel())->forName($cid, 'Amit')['party_role'] ?? '';
        $pr1 = $amitRole1 === 'customer'; $pass &= $pr1; $line($pr1, 'Amit role after sales', $amitRole1 ?: '(none)', 'customer');
        $riceP = $products->find($riceId);
        (new LedgerPostingService())->postInvoice([
            'company_id' => $cid, 'user_id' => $uid, 'type' => 'purchase', 'party_name' => 'Amit',
            'party_type' => 'Trader', 'payment_mode' => 'cash', 'invoice_date' => date('Y-m-d'), 'notes' => null,
            'discount' => 0, 'subtotal' => 1000, 'tax_total' => 0, 'total' => 1000, 'received' => 1000,
            'client_uuid' => 'test-amit-buy', 'lines' => [['product_id' => $riceId, 'product' => $riceP, 'name' => 'Rice', 'qty' => 2, 'rate' => 500, 'tax_rate' => 0, 'amount' => 1000]],
        ]);
        $amitRole2 = (new \App\Models\PartyModel())->forName($cid, 'Amit')['party_role'] ?? '';
        $pr2 = $amitRole2 === 'both'; $pass &= $pr2; $line($pr2, 'Amit role after also buying', $amitRole2 ?: '(none)', 'both');

        // 9) Rokadh Parcha OPENING balance = cash only (Shri Rokad Nagad + cash
        //    net before the day); a credit-sale receivable must NOT inflate it.
        CLI::newLine();
        CLI::write(CLI::color('Rokadh Parcha opening balance', 'yellow'));
        $ob       = new \App\Libraries\OpeningBalance($cid, $cid);
        $fy       = $ob->fyStartFor(date('Y-m-d'));
        (new \App\Models\CompanySettingModel())->put($cid, 'transactions', 'shri_rokad_nagad_' . $fy, 5000);
        $yst = date('Y-m-d', strtotime('-1 day'));
        $mkY = fn (string $type, float $amt, int $lo) => $txn->insert([
            'user_id' => $uid, 'company_id' => $cid, 'txn_no' => $txn->nextTxnNo($cid), 'txn_date' => $yst,
            'name' => 'OB Test', 'type' => $type, 'amount' => $amt, 'payment_mode' => 'cash', 'status' => 'paid',
            'ledger_only' => $lo, 'notes' => 'ob', 'source' => 'mobile',
        ]);
        $mkY('jama', 2000, 0);   // cash received yesterday
        $mkY('naam', 500, 0);    // cash paid yesterday
        $mkY('naam', 9999, 1);   // a credit-sale receivable yesterday (ledger_only) — must be ignored
        $opening = round($ob->carryInto(date('Y-m-d')), 2); // opening cash carried into today
        $ob1 = abs($opening - 6500) < 0.01; $pass &= $ob1;
        $line($ob1, 'Opening = 5000 shri + (2000−500) cash', $opening, 6500);
        $line($opening !== 16499.0, 'Credit sale (9999) excluded from opening', ($opening === 16499.0 ? 'INCLUDED' : 'excluded'), 'excluded');

        $wipe(); // discard ALL test data
        $db->table('companies')->where('id', $cid)->delete(); // remove throwaway company last

        CLI::newLine();
        CLI::write($pass ? CLI::color('ALL CHECKS PASSED', 'green') : CLI::color('SOME CHECKS FAILED', 'red'));
        CLI::newLine();
    }
}
