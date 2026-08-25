<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Sales / Purchase invoices (bills). Company-scoped; always query through
 * {@see scoped()}. Each invoice links to its cash-book transaction (txn_id) and
 * has rows in invoice_items.
 */
class InvoiceModel extends Model
{
    protected $table          = 'invoices';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'company_id', 'client_uuid', 'created_by', 'type', 'ref_invoice_id', 'invoice_no', 'party_name', 'party_type', 'party_id',
        'invoice_date', 'subtotal', 'tax_total', 'discount', 'total', 'received',
        'payment_mode', 'status', 'txn_id', 'pay_txn_id', 'notes',
    ];

    /** Company-scoped builder (never query invoices without it). */
    public function scoped(?int $companyId)
    {
        return $this->where('company_id', (int) $companyId);
    }

    /**
     * Next invoice number for a company + type, e.g. INV-000123 / PUR-000045.
     * Sequence is per company and per type.
     */
    public function nextInvoiceNo(?int $companyId, string $type): string
    {
        $prefix = ['sale' => 'INV', 'purchase' => 'PUR', 'sale_return' => 'SRT', 'purchase_return' => 'PRT'][$type] ?? 'INV';
        $n = $this->withDeleted()
            ->where('company_id', (int) $companyId)
            ->where('type', $type)
            ->countAllResults();
        return $prefix . '-' . str_pad((string) ($n + 1), 6, '0', STR_PAD_LEFT);
    }
}
