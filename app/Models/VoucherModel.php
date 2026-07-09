<?php

namespace App\Models;

use CodeIgniter\Model;

class VoucherModel extends Model
{
    protected $table          = 'vouchers';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = ['company_id', 'voucher_type', 'voucher_no', 'date', 'narration', 'amount', 'created_by'];

    public const TYPES = ['payment', 'receipt', 'contra', 'journal', 'sales', 'purchase'];

    /** Day book: vouchers for a firm within an optional date range. */
    public function dayBook(int $companyId, ?string $from = null, ?string $to = null, string $type = '')
    {
        $b = $this->where('company_id', $companyId)->orderBy('date', 'DESC')->orderBy('id', 'DESC');
        if ($from) {
            $b->where('date >=', $from);
        }
        if ($to) {
            $b->where('date <=', $to);
        }
        if ($type !== '') {
            $b->where('voucher_type', $type);
        }
        return $b;
    }

    public function findForCompany(int $id, int $companyId): ?array
    {
        return $this->where('company_id', $companyId)->find($id) ?: null;
    }

    /** Next sequential voucher number for a type within the firm. */
    public function nextNumber(int $companyId, string $type): string
    {
        $count = $this->withDeleted()->where('company_id', $companyId)->where('voucher_type', $type)->countAllResults();
        return strtoupper(substr($type, 0, 3)) . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
