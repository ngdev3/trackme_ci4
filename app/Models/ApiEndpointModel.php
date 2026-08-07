<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Registry of mobile-app REST endpoints (api/v1/*). Rows are synced from the
 * route collection by App\Libraries\ApiRegistry; the Super Admin API Monitor
 * reads/updates health + the active toggle here.
 */
class ApiEndpointModel extends Model
{
    protected $table         = 'api_endpoints';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'http_method', 'path', 'handler', 'grp', 'title', 'auth', 'params',
        'description', 'is_active', 'http_status', 'health', 'response_ms', 'last_checked',
    ];

    /** Whether an endpoint (by method + path) is currently switched off. */
    public function isDisabled(string $method, string $path): bool
    {
        $row = $this->where('http_method', strtoupper($method))
            ->where('path', $path)
            ->first();
        return $row !== null && (int) $row['is_active'] === 0;
    }

    /** All endpoints grouped by their `grp` bucket, ordered for display. */
    public function grouped(): array
    {
        $rows = $this->orderBy('grp', 'ASC')->orderBy('path', 'ASC')->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['grp'] ?: 'other'][] = $r;
        }
        return $out;
    }
}
