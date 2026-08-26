<?php

namespace App\Commands;

use App\Models\InvoiceModel;
use App\Models\ProductModel;
use App\Models\TransactionModel;
use App\Services\LedgerPostingService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * LOCAL-ONLY inventory load/flow test. Seeds products + sale bills + purchase
 * bills (distinct party names) through the REAL posting engine, then reconciles
 * stock + accounts. Never run on production.
 *
 *   php spark seed:inventory                                  # 500p/100s/100p into a throwaway "DEMO Inventory Co"
 *   php spark seed:inventory --into=18 --products=50 --sales=100 --purchases=100
 *   php spark seed:inventory --into=18 --wipe                 # remove only DEMO rows from company 18
 *   php spark seed:inventory --wipe                           # remove the throwaway demo company
 *
 * When --into is given, everything it creates is prefixed "DEMO " / "DEMO-" so
 * it is isolated from and removable without touching the company's real data.
 */
class SeedInventory extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'seed:inventory';
    protected $description = 'Seed products + sales + purchases (local) and reconcile.';

    private const COMPANY   = 'DEMO Inventory Co';
    private const P_PREFIX  = 'DEMO ';       // product / party name prefix (into mode)
    private const SKU_PFX   = 'DEMO-';       // product sku prefix (into mode)

    public function run(array $params)
    {
        $db   = Database::connect();
        mt_srand(20260827);

        $into = (int) (CLI::getOption('into') ?? 0);
        $wipe = in_array('--wipe', $params, true) || CLI::getOption('wipe');

        // ---- WIPE -------------------------------------------------------------
        if ($wipe) {
            if ($into > 0) {
                $n = $this->wipeDemoRows($db, $into);
                CLI::write(CLI::color("Removed {$n} DEMO products (+ their bills/entries) from company {$into}.", 'yellow'));
            } else {
                $ex = $db->table('companies')->where('name', self::COMPANY)->get()->getRowArray();
                if ($ex) { $this->wipeCompany($db, (int) $ex['id']); CLI::write(CLI::color('Removed ' . self::COMPANY . '.', 'yellow')); }
                else { CLI::write('Nothing to wipe.'); }
            }
            return;
        }

        // ---- Target company ---------------------------------------------------
        $intoMode = $into > 0;
        if ($intoMode) {
            $co = $db->table('companies')->where('id', $into)->get()->getRowArray();
            if (! $co) { CLI::error("Company {$into} not found."); return; }
            $cid    = $into;
            $uid    = (int) ($co['owner_id'] ?? 1);
            $pPfx   = self::P_PREFIX; $skuPfx = self::SKU_PFX;
            $custPfx = 'DEMO Cust '; $suppPfx = 'DEMO Supp '; $uPfx = 'seedinto-';
            $this->wipeDemoRows($db, $cid); // clean prior DEMO rows for a fresh run
            CLI::write('Seeding into ' . CLI::color($co['name'] . " (id {$cid})", 'green') . ', owner user ' . $uid . '.');
        } else {
            $uid = (int) ($db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray()['id'] ?? 1);
            $ex  = $db->table('companies')->where('name', self::COMPANY)->get()->getRowArray();
            if ($ex) { $this->wipeCompany($db, (int) $ex['id']); }
            $db->table('companies')->insert([
                'owner_id' => $uid, 'name' => self::COMPANY, 'financial_year_from' => date('Y-04-01'),
                'books_beginning_from' => date('Y-04-01'), 'state' => 'Demo', 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $cid = (int) $db->insertID();
            $pPfx = 'Product '; $skuPfx = 'SKU-'; $custPfx = 'Customer '; $suppPfx = 'Supplier '; $uPfx = 'seed-';
            CLI::write('Company ' . CLI::color(self::COMPANY, 'green') . " (id {$cid}), owner user {$uid}.");
        }

        $nProducts = (int) (CLI::getOption('products') ?: ($intoMode ? 50 : 500));
        $nSales    = (int) (CLI::getOption('sales') ?: 100);
        $nPurch    = (int) (CLI::getOption('purchases') ?: 100);

        $t0 = microtime(true);

        // ---- 1) Products ------------------------------------------------------
        $products = new ProductModel();
        $ids = []; $expected = [];
        $units = ['pcs', 'kg', 'box', 'ltr', 'pkt', 'bag'];
        for ($i = 1; $i <= $nProducts; $i++) {
            $cost = mt_rand(20, 800);
            $sale = (int) round($cost * (1.15 + mt_rand(0, 60) / 100));
            $open = mt_rand(200, 1200);
            $products->skipValidation(true)->insert([
                'company_id' => $cid, 'created_by' => $uid,
                'name' => $pPfx . 'P' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'sku' => $skuPfx . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'unit' => $units[$i % count($units)], 'sale_price' => $sale, 'purchase_price' => $cost,
                'opening_stock' => $open, 'current_stock' => $open, 'low_stock' => 10,
                'tax_rate' => [0, 5, 12, 18][$i % 4], 'status' => 1,
            ]);
            $pid = (int) $products->getInsertID();
            $ids[] = $pid;
            $expected[$pid] = ['open' => $open, 'in' => 0.0, 'out' => 0.0, 'cost' => $cost, 'sale' => $sale];
        }
        CLI::write('Seeded ' . CLI::color("{$nProducts} products", 'green') . '.');

        $svc = new LedgerPostingService();
        $modes = ['cash', 'upi', 'bank'];

        // ---- 2) Purchases -----------------------------------------------------
        $purchaseTotal = 0.0; $paidTotal = 0.0; $purchaseBills = 0;
        for ($s = 1; $s <= $nPurch; $s++) {
            $lines = $this->pickLines($products, $ids, $expected, 'purchase');
            [$sub, $tax, $tot] = $this->totals($lines);
            $paid = $this->payChoice($tot);
            $svc->postInvoice([
                'company_id' => $cid, 'user_id' => $uid, 'type' => 'purchase',
                'party_name' => $suppPfx . str_pad((string) $s, 3, '0', STR_PAD_LEFT), 'party_type' => 'Firm',
                'payment_mode' => $modes[$s % 3], 'invoice_date' => date('Y-m-d'), 'notes' => null,
                'discount' => 0, 'subtotal' => $sub, 'tax_total' => $tax, 'total' => $tot, 'received' => $paid,
                'client_uuid' => $uPfx . 'pur-' . $s, 'lines' => $lines,
            ]);
            foreach ($lines as $ln) { $expected[$ln['product_id']]['in'] += $ln['qty']; }
            $purchaseTotal += $tot; $paidTotal += $paid; $purchaseBills++;
        }
        CLI::write('Posted ' . CLI::color("{$purchaseBills} purchase bills", 'green') . '.');

        // ---- 3) Sales ---------------------------------------------------------
        $saleTotal = 0.0; $recdTotal = 0.0; $saleBills = 0; $blocked = 0;
        for ($c = 1; $c <= $nSales; $c++) {
            $lines = $this->pickLines($products, $ids, $expected, 'sale');
            if ($lines === []) { continue; }
            [$sub, $tax, $tot] = $this->totals($lines);
            $recd = $this->payChoice($tot);
            try {
                $svc->postInvoice([
                    'company_id' => $cid, 'user_id' => $uid, 'type' => 'sale',
                    'party_name' => $custPfx . str_pad((string) $c, 3, '0', STR_PAD_LEFT), 'party_type' => 'Trader',
                    'payment_mode' => $modes[$c % 3], 'invoice_date' => date('Y-m-d'), 'notes' => null,
                    'discount' => 0, 'subtotal' => $sub, 'tax_total' => $tax, 'total' => $tot, 'received' => $recd,
                    'client_uuid' => $uPfx . 'sale-' . $c, 'lines' => $lines,
                ]);
                foreach ($lines as $ln) { $expected[$ln['product_id']]['out'] += $ln['qty']; }
                $saleTotal += $tot; $recdTotal += $recd; $saleBills++;
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'INSUFFICIENT_STOCK')) { $blocked++; } else { throw $e; }
            }
        }
        CLI::write('Posted ' . CLI::color("{$saleBills} sale bills", 'green') . ($blocked ? " ({$blocked} blocked by stock guard)" : '') . '.');

        $secs = round(microtime(true) - $t0, 1);
        $this->reconcile($db, $cid, $products, $expected, [
            'saleBills' => $saleBills, 'purchaseBills' => $purchaseBills, 'saleTotal' => $saleTotal,
            'recdTotal' => $recdTotal, 'purchaseTotal' => $purchaseTotal, 'paidTotal' => $paidTotal, 'secs' => $secs,
            'custPfx' => $custPfx, 'suppPfx' => $suppPfx,
        ]);

        CLI::newLine();
        if ($intoMode) {
            CLI::write('Kept in ' . CLI::color("company {$cid}", 'green') . ' (local only). Remove with: '
                . CLI::color("php spark seed:inventory --into={$cid} --wipe", 'yellow'));
        } else {
            CLI::write('Kept in ' . CLI::color(self::COMPANY, 'green') . ' (local only). Remove with: '
                . CLI::color('php spark seed:inventory --wipe', 'yellow'));
        }
    }

    private function pickLines(ProductModel $products, array $ids, array $expected, string $type): array
    {
        $n = mt_rand(1, 4); $lines = []; $used = [];
        for ($k = 0; $k < $n; $k++) {
            $pid = $ids[array_rand($ids)];
            if (isset($used[$pid])) { continue; }
            $used[$pid] = true;
            $e = $expected[$pid];
            $row = $products->find($pid);
            if (! $row) { continue; }
            if ($type === 'sale') {
                $avail = (float) $row['current_stock'];
                if ($avail < 1) { continue; }
                $qty = mt_rand(1, (int) min(25, $avail)); $rate = $e['sale'];
            } else {
                $qty = mt_rand(5, 40); $rate = $e['cost'];
            }
            $lines[] = ['product_id' => $pid, 'product' => $row, 'name' => $row['name'],
                'qty' => $qty, 'rate' => $rate, 'tax_rate' => 0, 'amount' => round($qty * $rate, 2)];
        }
        return $lines;
    }

    private function totals(array $lines): array
    {
        $sub = 0.0;
        foreach ($lines as $ln) { $sub += $ln['amount']; }
        return [round($sub, 2), 0.0, round($sub, 2)];
    }

    private function payChoice(float $total): float
    {
        $r = mt_rand(1, 100);
        if ($r <= 40) { return $total; }
        if ($r <= 75) { return round($total * mt_rand(20, 80) / 100, 2); }
        return 0.0;
    }

    private function reconcile($db, int $cid, ProductModel $products, array $expected, array $agg): void
    {
        $pass = true;
        $line = function ($ok, $label, $got, $want) use (&$pass) {
            $pass = $pass && $ok;
            CLI::write(($ok ? CLI::color('  PASS ', 'green') : CLI::color('  FAIL ', 'red'))
                . str_pad($label, 40) . ' got=' . $got . '  want=' . $want);
        };

        $mismatch = 0; $negative = 0; $stockValue = 0.0;
        foreach ($expected as $pid => $e) {
            $cur = (float) $products->find($pid)['current_stock'];
            $exp = round($e['open'] + $e['in'] - $e['out'], 3);
            if (abs($cur - $exp) > 0.001) { $mismatch++; }
            if ($cur < -0.0001) { $negative++; }
            $stockValue += $cur * $e['cost'];
        }

        $recv = (float) $db->query("SELECT COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE -amount END),0) AS b
                    FROM transactions WHERE company_id = ? AND deleted_at IS NULL AND name LIKE ?", [$cid, $agg['custPfx'] . '%'])->getRowArray()['b'];
        $pay  = (float) $db->query("SELECT COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE -amount END),0) AS b
                    FROM transactions WHERE company_id = ? AND deleted_at IS NULL AND name LIKE ?", [$cid, $agg['suppPfx'] . '%'])->getRowArray()['b'];

        $expRecv = round($agg['saleTotal'] - $agg['recdTotal'], 2);
        $expPay  = round($agg['purchaseTotal'] - $agg['paidTotal'], 2);

        CLI::newLine();
        CLI::write(CLI::color("Reconciliation ({$agg['secs']}s)", 'yellow'));
        $line($mismatch === 0, 'Stock: opening + in − out = current', ($mismatch === 0 ? 'all match' : "{$mismatch} off"), 'all match');
        $line($negative === 0, 'No product stock went negative', ($negative === 0 ? 'none' : "{$negative} neg"), 'none');
        $line(abs($recv - $expRecv) < 0.5, 'Receivable (customers, Naam−Jama)', round($recv, 2), $expRecv);
        $line(abs($pay - $expPay) < 0.5, 'Payable (suppliers, Jama−Naam)', round($pay, 2), $expPay);

        CLI::newLine();
        CLI::write('  Sale bills: ' . $agg['saleBills'] . '   Purchase bills: ' . $agg['purchaseBills']);
        CLI::write('  Sales ₹' . number_format($agg['saleTotal']) . '   Purchases ₹' . number_format($agg['purchaseTotal']));
        CLI::write('  Receivable ₹' . number_format($expRecv) . '   Payable ₹' . number_format($expPay) . '   Stock value ₹' . number_format($stockValue));
        CLI::newLine();
        CLI::write($pass ? CLI::color('ALL RECONCILIATION CHECKS PASSED', 'green') : CLI::color('SOME CHECKS FAILED', 'red'));
    }

    /** Remove only DEMO-prefixed rows from an existing company (FK-safe). */
    private function wipeDemoRows($db, int $cid): int
    {
        // Bills seeded by this tool (identified by client_uuid prefix).
        $rows = $db->query("SELECT id, txn_id, pay_txn_id FROM invoices WHERE company_id = ? AND client_uuid LIKE 'seedinto-%'", [$cid])->getResultArray();
        $ivIds = []; $txns = [];
        foreach ($rows as $r) { $ivIds[] = (int) $r['id']; if ($r['txn_id']) $txns[] = (int) $r['txn_id']; if ($r['pay_txn_id']) $txns[] = (int) $r['pay_txn_id']; }
        if ($txns)  { $db->query('DELETE FROM transactions WHERE id IN (' . implode(',', $txns) . ')'); }
        if ($ivIds) { $il = implode(',', $ivIds); $db->query("DELETE FROM invoice_items WHERE invoice_id IN ($il)"); $db->query("DELETE FROM invoices WHERE id IN ($il)"); }
        // Any stray DEMO-named transactions.
        $db->query("DELETE FROM transactions WHERE company_id = ? AND name LIKE 'DEMO %'", [$cid]);
        // DEMO products + their stock movements.
        $pr = $db->query("SELECT id FROM products WHERE company_id = ? AND sku LIKE '" . self::SKU_PFX . "%'", [$cid])->getResultArray();
        $pids = array_map(static fn ($r) => (int) $r['id'], $pr);
        if ($pids) { $pl = implode(',', $pids); $db->query("DELETE FROM stock_movements WHERE product_id IN ($pl)"); $db->query("DELETE FROM products WHERE id IN ($pl)"); }
        // DEMO party masters.
        if ($db->tableExists('parties')) { $db->query("DELETE FROM parties WHERE company_id = ? AND name LIKE 'DEMO %'", [$cid]); }
        return count($pids);
    }

    /** FK-safe teardown of a throwaway demo company. */
    private function wipeCompany($db, int $cid): void
    {
        if ($db->tableExists('invoice_items')) {
            $db->query('DELETE ii FROM invoice_items ii JOIN invoices i ON i.id = ii.invoice_id WHERE i.company_id = ?', [$cid]);
        }
        foreach (['transactions', 'invoices', 'stock_movements', 'products', 'parties'] as $t) {
            if ($db->tableExists($t)) { $db->table($t)->where('company_id', $cid)->delete(); }
        }
        $db->table('companies')->where('id', $cid)->delete();
    }
}
