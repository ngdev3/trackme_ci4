<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanySettingModel extends Model
{
    protected $table         = 'company_settings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['company_id', 'scope', 'key', 'value'];

    /** All settings in a scope for a company as key => value. */
    public function scopeMap(int $companyId, string $scope): array
    {
        $rows = $this->where('company_id', $companyId)->where('scope', $scope)->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['key']] = $r['value'];
        }
        return $out;
    }

    /** Read a single setting value (or default). */
    public function get(int $companyId, string $scope, string $key, $default = null)
    {
        $row = $this->where('company_id', $companyId)->where('scope', $scope)->where('key', $key)->first();
        return $row ? $row['value'] : $default;
    }

    /** Upsert a single setting value. */
    public function put(int $companyId, string $scope, string $key, $value): void
    {
        $row = $this->where('company_id', $companyId)->where('scope', $scope)->where('key', $key)->first();
        if ($row) {
            $this->update($row['id'], ['value' => (string) $value]);
        } else {
            $this->insert(['company_id' => $companyId, 'scope' => $scope, 'key' => $key, 'value' => (string) $value]);
        }
    }
}
