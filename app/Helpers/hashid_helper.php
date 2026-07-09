<?php

use Hashids\Hashids;

/**
 * Opaque, reversible ID encoding so sequential database IDs never appear in
 * URLs (users cannot guess or enumerate records). The salt is derived from the
 * app's encryption key, so hashes are stable for this install but unguessable.
 *
 * Reusable across the app — encode with hid($id) in links, decode with
 * unhid($hash) in controllers.
 */

if (! function_exists('hashids_engine')) {
    function hashids_engine(): Hashids
    {
        static $engine = null;
        if ($engine === null) {
            $key  = (string) (env('encryption.key') ?: 'erp-fallback-salt');
            $salt = 'erp:' . hash('sha256', $key);           // stable per install
            $engine = new Hashids($salt, 8);                 // min length 8
        }
        return $engine;
    }
}

if (! function_exists('hid')) {
    /** Encode a numeric ID to an opaque URL token. */
    function hid($id): string
    {
        return hashids_engine()->encode((int) $id);
    }
}

if (! function_exists('unhid')) {
    /**
     * Decode an opaque token back to its integer ID. Returns 0 when the token
     * is invalid (so raw/guessed numeric IDs never resolve — enumeration-safe).
     */
    function unhid($hash): int
    {
        $decoded = hashids_engine()->decode((string) $hash);
        return isset($decoded[0]) ? (int) $decoded[0] : 0;
    }
}
