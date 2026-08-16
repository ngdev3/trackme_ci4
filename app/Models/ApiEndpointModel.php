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

    /** Cache key + TTL for the disabled-endpoints set (see isDisabled). */
    public const DISABLED_CACHE_KEY = 'api_disabled_endpoints';
    public const DISABLED_CACHE_TTL = 60;

    /**
     * Whether an endpoint (by method + path) is currently switched off.
     *
     * This runs in the ApiToggle "before" filter on EVERY api/v1 request, so it
     * must NOT hit the database each time. We cache the small set of disabled
     * endpoints for 60s: one query per minute instead of one per request. Under
     * a traffic burst this removes the filter's DB round-trip entirely (a cache
     * hit needs no connection), which is exactly what starves under the host's
     * connection cap. Toggling an endpoint in the API Monitor should call
     * clearDisabledCache() for instant effect; otherwise it propagates in <=60s.
     */
    public function isDisabled(string $method, string $path): bool
    {
        $cache = \Config\Services::cache();
        $set   = $cache->get(self::DISABLED_CACHE_KEY);
        if (! is_array($set)) {
            $set  = [];
            $rows = $this->select('http_method, path')->where('is_active', 0)->findAll();
            foreach ($rows as $r) {
                $set[strtoupper((string) $r['http_method']) . ' ' . $r['path']] = true;
            }
            $cache->save(self::DISABLED_CACHE_KEY, $set, self::DISABLED_CACHE_TTL);
        }
        return isset($set[strtoupper($method) . ' ' . $path]);
    }

    /** Invalidate the disabled-endpoints cache (call after a toggle). */
    public function clearDisabledCache(): void
    {
        \Config\Services::cache()->delete(self::DISABLED_CACHE_KEY);
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
