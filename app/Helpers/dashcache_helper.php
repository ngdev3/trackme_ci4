<?php

/**
 * Dashboard cache — makes the (query-heavy) firm dashboard load fast and cuts
 * server load from repeated loads + the ~20s live poll.
 *
 * Strategy: cache the computed aggregates per company with a SHORT TTL, keyed by
 * a per-company "version". Any data write bumps the version (dash_bust), which
 * instantly invalidates every cached block for that company without needing a
 * wildcard delete (file cache can't do that). So the dashboard is at most `ttl`
 * seconds stale, and refreshes immediately after a transaction changes.
 */

if (! function_exists('dash_ver')) {
    /** Current cache version for a company (0 = super-admin/all-companies scope). */
    function dash_ver(?int $companyId): int
    {
        $key = 'dashver_' . (int) $companyId;
        $v   = cache($key);
        if ($v === null) {
            $v = 1;
            cache()->save($key, $v, 0); // 0 = never expires (only bumped on writes)
        }
        return (int) $v;
    }
}

if (! function_exists('dash_bust')) {
    /** Invalidate all cached dashboard blocks for a company (call after any write). */
    function dash_bust(?int $companyId): void
    {
        $key = 'dashver_' . (int) $companyId;
        $v   = (int) (cache($key) ?? 1);
        cache()->save($key, $v + 1, 0);
    }
}

if (! function_exists('dash_remember')) {
    /**
     * Return a cached block or compute + store it. The key embeds the company's
     * current version, so a dash_bust() makes the old key unreachable.
     *
     * @template T
     * @param  callable():T $compute
     * @return T
     */
    function dash_remember(?int $companyId, string $suffix, int $ttl, callable $compute)
    {
        // CI4 cache keys may not contain the reserved chars {}()/\@: — sanitize.
        $suffix = preg_replace('/[{}()\/\\\\@:]/', '_', $suffix);
        $key    = 'dash_' . (int) $companyId . '_v' . dash_ver($companyId) . '_' . $suffix;

        // A cache-buster (?fresh=1) forces a recompute for the current request.
        $req = service('request');
        if ($req->getGet('fresh') !== null) {
            $val = $compute();
            cache()->save($key, $val, $ttl);
            return $val;
        }

        $hit = cache($key);
        if ($hit !== null) {
            return $hit;
        }
        $val = $compute();
        cache()->save($key, $val, $ttl);
        return $val;
    }
}
