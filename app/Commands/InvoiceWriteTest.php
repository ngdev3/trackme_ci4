<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use App\Modules\Admin\Models\InvoiceWriteModel;

/**
 * Rollback harness for the invoice WRITE path. Proves that the CI4 write
 * primitives create the full 4-table invoice transaction correctly — TWO
 * aa_rokad cash rows + the invoice_system row (back-linked) + a
 * stock_log_details movement — then ROLLS BACK so NOTHING persists to the live
 * DB. Verification without touching real financial data.
 *
 * Run:  <php8.3> spark invoice:writetest
 */
class InvoiceWriteTest extends BaseCommand
{
    protected $group       = 'Migration';
    protected $name        = 'invoice:writetest';
    protected $description = 'Prove the invoice 4-table write in a rolled-back transaction (no data persisted).';

    public function run(array $params)
    {
        helper(['app', 'cr_cache']); // app helper (currentuserinfo/fy) isn't auto-loaded in CLI
        $db    = Database::connect();
        $write = new InvoiceWriteModel();
        $TID   = 20;

        // A real invoice to clone field shapes from (guaranteed-valid values).
        $src = $db->table('invoice_system')->where('template_id', $TID)
            ->where('rokadh_jama_id >', 0)->where('rokadh_nama_id >', 0)
            ->orderBy('bos_id', 'DESC')->get()->getRowArray();
        if (! $src) {
            CLI::error('No source invoice to clone.');
            return;
        }
        $fy  = $src['FY'];
        $pt  = $src['product_type'];
        $rj  = $db->table('aa_rokad')->where('rokad_id', (int) $src['rokadh_jama_id'])->get()->getRowArray();
        $rn  = $db->table('aa_rokad')->where('rokad_id', (int) $src['rokadh_nama_id'])->get()->getRowArray();

        // Build test payloads by cloning + neutralising PKs and marking as TEST.
        $mark = 'ZZTEST' . substr(md5((string) getmypid()), 0, 5);
        $jama = $rj; unset($jama['rokad_id']); $jama['remark'] = $mark;
        $nama = $rn; unset($nama['rokad_id']); $nama['remark'] = $mark;
        $inv  = $src; unset($inv['bos_id']); $inv['invoice_id'] = 999999; $inv['remark'] = $mark;
        unset($inv['rokadh_jama_id'], $inv['rokadh_nama_id']);

        $cntInv0   = $db->table('invoice_system')->countAllResults();
        $cntRokad0 = $db->table('aa_rokad')->countAllResults();
        $cntStock0 = $db->table('stock_log_details')->countAllResults();

        CLI::write('Starting transaction…', 'yellow');
        $linked = false; $chkRokad = 0; $chkStock = 0;
        $db->transBegin();
        try {
            // 1) cash-book jama + nama
            $res = $write->createRokadEntry($jama, $nama);
            CLI::write("  aa_rokad JAMA id={$res['deposit_data']}  NAMA id={$res['expenses_data']}");

            // 2) invoice_system (+ back-link)
            $bos = $write->addInvoice($inv, $res);
            CLI::write("  invoice_system bos_id={$bos}");

            // 3) stock movement
            $stockId = $write->stockUpdation([
                'invoice_no' => 999999, 'type_of_invoice' => 'sale', 'invoice_type' => 'test',
                'product_name' => $src['product_name'], 'hsn_code' => $src['hsn_code'], 'hsn_code_id' => $src['hsn_code_id'] ?? 0,
                'sales_stock' => $src['quantity'], 'status' => 'Active',
                'rokad_nama_id' => $res['expenses_data'], 'rokad_jama_id' => $res['deposit_data'],
                'template_id' => $TID, 'FY' => $fy,
            ]);
            CLI::write("  stock_log_details id={$stockId}");

            // Verify inside the transaction.
            $chkInv   = $db->table('invoice_system')->where('bos_id', $bos)->get()->getRowArray();
            $linked   = $chkInv && (int) $chkInv['rokadh_jama_id'] === $res['deposit_data'] && (int) $chkInv['rokadh_nama_id'] === $res['expenses_data'];
            $chkRokad = $db->table('aa_rokad')->whereIn('rokad_id', [$res['deposit_data'], $res['expenses_data']])->countAllResults();
            $chkStock = $db->table('stock_log_details')->where('id', $stockId)->countAllResults();
        } catch (\Throwable $e) {
            $db->transRollback();
            CLI::error('Write threw: ' . $e->getMessage());
            return;
        }

        CLI::write('');
        CLI::write('Verification (inside txn):', 'yellow');
        CLI::write('  invoice_system row created + rokad ids back-linked: ' . ($linked ? CLI::color('YES', 'green') : CLI::color('NO', 'red')));
        CLI::write('  aa_rokad rows (jama+nama) present: ' . ($chkRokad === 2 ? CLI::color('2/2', 'green') : CLI::color("$chkRokad/2", 'red')));
        CLI::write('  stock_log_details movement present: ' . ($chkStock === 1 ? CLI::color('YES', 'green') : CLI::color('NO', 'red')));

        // ROLL BACK — nothing persists.
        $db->transRollback();
        CLI::write('');
        CLI::write('Rolled back. Confirming nothing persisted…', 'yellow');

        $cntInv1   = $db->table('invoice_system')->countAllResults();
        $cntRokad1 = $db->table('aa_rokad')->countAllResults();
        $cntStock1 = $db->table('stock_log_details')->countAllResults();
        $leftover  = $db->table('invoice_system')->where('remark', $mark)->countAllResults();

        $clean = $cntInv1 === $cntInv0 && $cntRokad1 === $cntRokad0 && $cntStock1 === $cntStock0 && $leftover === 0;
        CLI::write("  invoice_system: {$cntInv0} -> {$cntInv1}   aa_rokad: {$cntRokad0} -> {$cntRokad1}   stock: {$cntStock0} -> {$cntStock1}");
        CLI::write('  leftover TEST rows: ' . $leftover);
        CLI::write('');
        $test1 = $clean && $linked && $chkRokad === 2 && $chkStock === 1;
        CLI::write($test1
            ? CLI::color('  TEST 1 (clone write): PASS', 'green')
            : CLI::color('  TEST 1 (clone write): FAIL', 'red'));

        // ---- TEST 2: full add flow via buildBosPayload() (the POST path) ----
        CLI::write('');
        CLI::write('TEST 2 — buildBosPayload() → write → rollback (the invoice/add POST path):', 'yellow');
        $accs = $db->table('aa_account_name')->select('account_id')->where("COALESCE(status,'') != 'Delete'", null, false)->limit(2)->get()->getResultArray();
        $hsn  = $db->table('stock_detail')->where('template_id', $TID)->where('FY', $fy)->limit(1)->get()->getRowArray();
        $test2 = false;
        if (count($accs) >= 2 && $hsn) {
            $post = [
                'account_id' => $accs[0]['account_id'], 'account_label' => 'Party A',
                'naam_id' => $accs[1]['account_id'], 'naam_label' => 'Party B',
                'hsn_code_id' => $hsn['hsn_code_id'], 'hsn_code' => $hsn['hsn_code'], 'product_name' => $hsn['product_name'],
                'uom' => $hsn['stock_unit'], 'quantity' => 1, 'rate' => 100, 'freight' => 10, 'others' => 5,
                'billing_date' => date('Y-m-d'), 'status' => 'Active', 'truck_no' => 'TEST01', 'driver_name' => 'Test',
                'remark' => $mark, 'bill_type' => '0', 'enable_delivery' => 'no',
            ];
            $pay = $write->buildBosPayload($post, 999998, ['FY' => $fy, 'product_type' => $pt, 'template_id' => $TID], 0);
            if (isset($pay['error'])) {
                CLI::error('  payload error: ' . $pay['error']);
            } else {
                CLI::write('  computed amount=' . $pay['invoice']['amount'] . ' total=' . $pay['invoice']['total_invoice'] . ' (expected amount=100, total=105)');
                $db->transBegin();
                try {
                    $r2  = $write->createRokadEntry($pay['sale_jama'], $pay['sale_nama']);
                    $b2  = $write->addInvoice($pay['invoice'], $r2);
                    $st2 = $pay['sale_stock']; $st2['rokad_nama_id'] = $r2['expenses_data']; $st2['rokad_jama_id'] = $r2['deposit_data'];
                    $s2  = $write->stockUpdation($st2);
                    $ok  = $db->table('invoice_system')->where('bos_id', $b2)->countAllResults() === 1
                        && $db->table('aa_rokad')->whereIn('rokad_id', [$r2['deposit_data'], $r2['expenses_data']])->countAllResults() === 2
                        && $db->table('stock_log_details')->where('id', $s2)->countAllResults() === 1;
                    $amtOk = (float) $pay['invoice']['amount'] === 100.0 && (float) $pay['invoice']['total_invoice'] === 105.0;
                    $test2 = $ok && $amtOk;
                    CLI::write('  rows created (invoice+2 rokad+stock): ' . ($ok ? CLI::color('YES', 'green') : CLI::color('NO', 'red')) . ' · money correct: ' . ($amtOk ? CLI::color('YES', 'green') : CLI::color('NO', 'red')));
                } catch (\Throwable $e) {
                    CLI::error('  write threw: ' . $e->getMessage());
                }
                $db->transRollback();
                $left2 = $db->table('invoice_system')->where('remark', $mark)->countAllResults();
                CLI::write('  rolled back · leftover: ' . $left2);
                $test2 = $test2 && $left2 === 0;
            }
        }
        CLI::write($test2 ? CLI::color('  TEST 2 (add-flow write): PASS', 'green') : CLI::color('  TEST 2 (add-flow write): FAIL', 'red'));

        CLI::write('');
        CLI::write($test1 && $test2
            ? CLI::color('  OVERALL: PASS — invoice add write (clone + full payload path) works; live DB untouched.', 'green')
            : CLI::color('  OVERALL: FAIL — see above.', 'red'));
    }
}
