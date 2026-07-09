<?php

namespace App\Models;

use CodeIgniter\Model;

class VoucherEntryModel extends Model
{
    protected $table         = 'voucher_entries';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';

    protected $allowedFields = ['voucher_id', 'company_id', 'ledger_id', 'dr_amount', 'cr_amount'];

    /** Entries of a voucher, joined to ledger names. */
    public function forVoucher(int $voucherId): array
    {
        return $this->select('voucher_entries.*, ledgers.name AS ledger_name')
            ->join('ledgers', 'ledgers.id = voucher_entries.ledger_id', 'left')
            ->where('voucher_id', $voucherId)
            ->findAll();
    }

    /**
     * Ledger statement rows (each posting against a ledger) with voucher meta,
     * newest first, for the ledger-statement report.
     */
    public function statement(int $ledgerId, int $companyId): array
    {
        return $this->select('voucher_entries.dr_amount, voucher_entries.cr_amount, vouchers.date, vouchers.voucher_type, vouchers.voucher_no, vouchers.narration')
            ->join('vouchers', 'vouchers.id = voucher_entries.voucher_id')
            ->where('voucher_entries.ledger_id', $ledgerId)
            ->where('voucher_entries.company_id', $companyId)
            ->where('vouchers.deleted_at', null)
            ->orderBy('vouchers.date', 'ASC')->orderBy('vouchers.id', 'ASC')
            ->findAll();
    }

    /** Net movement (dr - cr) posted to a ledger. */
    public function ledgerBalance(int $ledgerId, int $companyId): float
    {
        $row = $this->select('COALESCE(SUM(dr_amount),0) AS dr, COALESCE(SUM(cr_amount),0) AS cr')
            ->where('ledger_id', $ledgerId)->where('company_id', $companyId)
            ->first();
        return (float) ($row['dr'] ?? 0) - (float) ($row['cr'] ?? 0);
    }
}
