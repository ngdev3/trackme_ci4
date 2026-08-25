<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Product Master (Stock / Inventory). Company-scoped catalogue; every listing
 * must go through {@see scoped()} so one company can never read another's items.
 */
class ProductModel extends Model
{
    protected $table          = 'products';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'company_id', 'created_by', 'name', 'sku', 'category', 'unit', 'hsn',
        'sale_price', 'purchase_price', 'opening_stock', 'current_stock',
        'low_stock', 'tax_rate', 'description', 'status',
    ];

    protected $validationRules = [
        'name' => 'required|min_length[1]|max_length[191]',
    ];

    /** Company-scoped builder (never query products without it). */
    public function scoped(?int $companyId)
    {
        return $this->where('company_id', (int) $companyId);
    }

    /**
     * Headline inventory figures for the dashboard.
     *
     * @return array{count:int, stock_value:float, sale_value:float, low:int, out:int}
     */
    public function summary(?int $companyId): array
    {
        $rows = $this->scoped($companyId)->findAll();
        $count = 0; $stockValue = 0.0; $saleValue = 0.0; $low = 0; $out = 0;
        foreach ($rows as $r) {
            $count++;
            $stock = (float) $r['current_stock'];
            $stockValue += $stock * (float) $r['purchase_price'];
            $saleValue  += $stock * (float) $r['sale_price'];
            if ($stock <= 0) {
                $out++;
            } elseif ((float) $r['low_stock'] > 0 && $stock <= (float) $r['low_stock']) {
                $low++;
            }
        }
        return [
            'count'       => $count,
            'stock_value' => round($stockValue, 2),
            'sale_value'  => round($saleValue, 2),
            'low'         => $low,
            'out'         => $out,
        ];
    }
}
