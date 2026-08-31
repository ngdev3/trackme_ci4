<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * InvoiceWriteModel — CI4 port of the invoice WRITE primitives:
 *   createRokadEntry()  == CI3 Invoice_mod::add_rokadh_entry (2 aa_rokad rows)
 *   addInvoice()        == CI3 Invoice_mod::add             (invoice_system + link)
 *   stockUpdation()     == CI3 stockupdation() helper        (stock_log_details)
 *
 * These are the exact inserts an invoice create performs. They use the shared
 * connection (Config\Database::connect()), so a caller can wrap them in a
 * transaction — which the P6 add flow (and the rollback test harness) does.
 * Faithful 1:1 with CI3; no business logic here (that lives in the payload
 * builder, ported separately).
 */
class InvoiceWriteModel
{
    public function db()
    {
        return Database::connect();
    }

    /** Next invoice_id for the current firm/FY/product (serialised by the caller). */
    public function nextInvoiceId(): int
    {
        $row = $this->db()->table('invoice_system')
            ->selectMax('invoice_id')
            ->where('template_id', fy()->template_id)->where('FY', fy()->FY)->where('product_type', fy()->product_type)
            ->get()->getRow();
        return $row && $row->invoice_id ? ((int) $row->invoice_id + 1) : 1;
    }

    /**
     * Build the Bill-of-Supply write payload from a POST array — CI4 port of the
     * BOS core of build_invoice_payload(). Returns ['invoice','sale_jama',
     * 'sale_nama','sale_stock'] or ['error'=>msg]. Money is recomputed
     * server-side (never trusted from the client). TDS/TCS + cross-firm purchase
     * mirror are advanced branches, deferred.
     *
     * $post keys expected: account_id, naam_id, hsn_code, hsn_code_id,
     * product_name, uom, quantity, rate, freight, others, truck_no, driver_name,
     * remark, billing_date, status, bill_type, enable_delivery, delivery_account_id.
     */
    public function buildBosPayload(array $p, int $invoiceNo, ?array $scope = null, ?int $uidIn = null): array
    {
        // Scope/uid come from the session in web context; can be injected (CLI test).
        $scope ??= ['FY' => fy()->FY, 'product_type' => fy()->product_type, 'template_id' => fy()->template_id];
        $uid    = $uidIn ?? (int) (currentuserinfo()?->id ?? 0);

        $accountId = (int) ($p['account_id'] ?? 0);
        $naamId    = (int) ($p['naam_id'] ?? 0);
        $hsnId     = (int) ($p['hsn_code_id'] ?? 0);
        $hsnCode   = (string) ($p['hsn_code'] ?? '');

        if (! $accountId || ! $naamId || ! $hsnId || $hsnCode === '') {
            return ['error' => 'Please select valid account, naam and HSN details.'];
        }
        $bt = strtotime((string) ($p['billing_date'] ?? ''));
        if (! $bt) {
            return ['error' => 'Please enter a valid billing date.'];
        }
        $billingDate = date('Y-m-d', $bt);
        $truck  = strtoupper((string) ($p['truck_no'] ?? ''));
        $status = (string) ($p['status'] ?? 'Active');
        $ledger = (strcasecmp($status, 'Active') === 0) ? 'Active' : 'Delete';

        $qty     = (float) ($p['quantity'] ?? 0);
        $rate    = (float) ($p['rate'] ?? 0);
        $freight = (float) ($p['freight'] ?? 0);
        $advance = (float) ($p['others'] ?? 0);
        $amount  = round($qty * $rate, 2);
        $total   = round($amount + $freight - $advance, 2);

        if ($qty <= 0 || $rate <= 0) {
            return ['error' => 'Quantity and rate must be greater than 0.'];
        }

        $invoice = array_merge([
            'invoice_id' => $invoiceNo, 'billing_date' => $billingDate, 'account_id' => $accountId,
            'jama' => $accountId, 'naam' => $naamId, 'bill_type' => (string) ($p['bill_type'] ?? '0'),
            'type_of_invoice' => 2, 'enable_delivery' => (string) ($p['enable_delivery'] ?? 'no'),
            'delivery_at_account' => (int) ($p['delivery_account_id'] ?? 0),
            'product_name' => (string) ($p['product_name'] ?? ''), 'hsn_code' => $hsnCode,
            'uom' => (string) ($p['uom'] ?? ''), 'quantity' => $qty, 'rate' => $rate, 'amount' => $amount,
            'freight' => $freight, 'others' => $advance, 'total_invoice' => $total, 'truck_no' => $truck,
            'driver_name' => (string) ($p['driver_name'] ?? ''), 'remark' => (string) ($p['remark'] ?? ''),
            'added_by' => $uid, 'status' => $status, 'updated_date' => date('Y-m-d'),
        ], $scope);

        $saleJama = array_merge([
            'rokad_date' => $billingDate, 'type_of_account' => 'deposit', 'remark' => (string) ($p['remark'] ?? ''),
            'account_name' => (string) ($p['naam_label'] ?? ''), 'karch_amount' => $total, 'quantity' => $qty,
            'rate' => $rate, 'truck_no' => $truck, 'added_by' => $uid, 'status' => $ledger, 'account_no' => $naamId,
        ], $scope);

        $saleNama = array_merge([
            'rokad_date' => $billingDate, 'type_of_account' => 'expenses', 'remark' => (string) ($p['remark'] ?? ''),
            'account_name' => (string) ($p['account_label'] ?? ''), 'karch_amount' => $total, 'added_by' => $uid,
            'status' => $ledger, 'account_no' => $accountId, 'quantity' => $qty, 'rate' => $rate, 'truck_no' => $truck,
        ], $scope);

        $saleStock = [
            'invoice_no' => $invoiceNo, 'type_of_invoice' => 'sale', 'invoice_type' => 'bos',
            'product_name' => (string) ($p['product_name'] ?? ''), 'hsn_code' => $hsnCode,
            'sales_stock' => $qty, 'purchase_stock' => 0, 'hsn_code_id' => $hsnId, 'status' => $ledger,
            'date' => $billingDate, 'month' => date('m', $bt), 'year' => date('Y', $bt),
            'template_id' => $scope['template_id'], 'FY' => $scope['FY'],
        ];

        return ['invoice' => $invoice, 'sale_jama' => $saleJama, 'sale_nama' => $saleNama, 'sale_stock' => $saleStock];
    }

    /** Insert the deposit (jama) + expense (nama) cash-book rows; return their ids. */
    public function createRokadEntry(array $jama, array $nama): array
    {
        $db = $this->db();
        $db->table('aa_rokad')->insert($jama);
        $depositId = (int) $db->insertID();
        $db->table('aa_rokad')->insert($nama);
        $expenseId = (int) $db->insertID();
        // entry_trace() is fire-and-forget audit; skipped in the write core.
        return ['deposit_data' => $depositId, 'expenses_data' => $expenseId];
    }

    /** Insert the invoice_system row and back-link the rokad ids; return bos_id. */
    public function addInvoice(array $data, array $res): int
    {
        $db = $this->db();
        $db->table('invoice_system')->insert($data);
        $bosId = (int) $db->insertID();

        $db->table('invoice_system')->where('bos_id', $bosId)->update(['rokadh_jama_id' => $res['deposit_data']]);
        $db->table('invoice_system')->where('bos_id', $bosId)->update(['rokadh_nama_id' => $res['expenses_data']]);

        return $bosId;
    }

    /** Insert the stock movement (CI3 stockupdation); return its id. */
    public function stockUpdation(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $row = [
            'invoice_no'       => $data['invoice_no'] ?? '',
            'type_of_invoice'  => $data['type_of_invoice'] ?? '',
            'invoice_type'     => $data['invoice_type'] ?? '',
            'product_name'     => $data['product_name'] ?? '',
            'hsn_code'         => $data['hsn_code'] ?? '',
            'hsn_code_id'      => $data['hsn_code_id'] ?? '',
            'production_stock' => $data['production_stock'] ?? 0,
            'purchase_stock'   => $data['purchase_stock'] ?? 0,
            'sales_stock'      => $data['sales_stock'] ?? 0,
            'rokad_nama_id'    => $data['rokad_nama_id'] ?? null,
            'rokad_jama_id'    => $data['rokad_jama_id'] ?? null,
            'status'           => $data['status'] ?? 0,
            'date'             => $data['date'] ?? date('Y-m-d'),
            'month'            => $data['month'] ?? date('m'),
            'year'             => $data['year'] ?? date('Y'),
            'updated_on'       => $now,
            'added_on'         => $now,
            'added_by'         => (int) (currentuserinfo()?->id ?? 0),
            'template_id'      => $data['template_id'] ?? fy()->template_id,
            'FY'               => $data['FY'] ?? fy()->FY,
        ];
        $this->db()->table('stock_log_details')->insert($row);
        return (int) $this->db()->insertID();
    }
}
