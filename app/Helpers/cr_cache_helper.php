<?php

/**
 * cr_cache — the safe caching layer, ported from CI3 to CI4.
 * Same public API (cr_remember / cr_forget / cr_cache_scope / cr_cache_flush)
 * so ported controllers/helpers keep working unchanged.
 *
 * SAFE-LAYER POLICY (unchanged): cache read-mostly MASTER / LOOKUP / CONFIG
 * data only (FY list, firm details, HSN codes, states, tax rates). NEVER cache
 * live financial/transactional reads (rokad, ledgers, stock, invoice registers).
 *
 * Kill switch: set CR_CACHE_OFF=true in .env.
 */

if (! function_exists('cr_cache_enabled')) {
    function cr_cache_enabled(): bool
    {
        return ! (bool) env('CR_CACHE_OFF', false);
    }
}

if (! function_exists('cr_cache_key')) {
    /** Normalise a key to a safe cache handle. */
    function cr_cache_key($name): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '_', (string) $name);
    }
}

if (! function_exists('cr_cache_scope')) {
    /** Tenant scope suffix (f<tid>_<FY>), delegating to FyContext. */
    function cr_cache_scope(): string
    {
        return service('fyContext')->cacheScope();
    }
}

if (! function_exists('cr_remember')) {
    /**
     * Return $key from cache, or run $cb, cache its (non-empty) result for
     * $ttl seconds, and return it. A transient empty result is never frozen in.
     */
    function cr_remember(string $key, int $ttl, callable $cb)
    {
        if (! cr_cache_enabled()) {
            return $cb();
        }
        $key   = cr_cache_key($key);
        $cache = service('cache');
        $hit   = $cache->get($key);
        if ($hit !== null) {
            return $hit;
        }
        $val = $cb();
        if (! empty($val)) {
            $cache->save($key, $val, $ttl);
        }
        return $val;
    }
}

if (! function_exists('cr_forget')) {
    /** Drop one cache key. */
    function cr_forget(string $key): void
    {
        if (! cr_cache_enabled()) {
            return;
        }
        service('cache')->delete(cr_cache_key($key));
    }
}

if (! function_exists('cr_cache_flush')) {
    /** Nuke the whole cache (admin "clear cache" action). */
    function cr_cache_flush(): void
    {
        if (! cr_cache_enabled()) {
            return;
        }
        service('cache')->clean();
    }
}
