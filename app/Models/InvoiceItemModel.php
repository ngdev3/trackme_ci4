<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Line items of a sales / purchase invoice. One row per product line.
 */
class InvoiceItemModel extends Model
{
    protected $table         = 'invoice_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;
    protected $updatedField   = '';

    protected $allowedFields = [
        'invoice_id', 'product_id', 'name', 'qty', 'rate', 'tax_rate', 'amount',
    ];

    /** All line rows for an invoice, in entry order. */
    public function forInvoice(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)->orderBy('id', 'ASC')->findAll();
    }
}
