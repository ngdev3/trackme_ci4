<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database;

/**
 * One-URL health / self-test for the whole backend, for verifying a deployment
 * on a live/staging server before real testing.
 *
 *   GET /health               -> minimal public status  (safe to expose)
 *   GET /health?key=SECRET     -> full diagnostics       (DB, migrations, config,
 *                                 writable paths, OPcache, table counts, timings)
 *
 * The full report is gated by a secret so it can't be scraped publicly. Set the
 * secret in the server .env:   health.key = 'some-long-random-string'
 * If health.key is empty, the detailed report is DISABLED (fail-safe).
 *
 * 100% read-only. It never writes, migrates, or mutates data.
 */
class HealthController extends Controller
{
    public function index()
    {
        $t0 = microtime(true);

        // ---- Always-public minimal status --------------------------------
        $public = [
            'status'  => 'ok',
            'app'     => 'HissabKitaab / ERP API',
            'env'     => ENVIRONMENT,
            'php'     => PHP_VERSION,
            'time'    => date('c'),
        ];

        // Detailed report: a logged-in Super Admin (sidebar link) OR the secret.
        helper('auth');
        $isSuper = function_exists('is_super_admin_account') && is_super_admin_account();
        $secret  = (string) (env('health.key') ?? '');
        $given   = (string) ($this->request->getGet('key') ?? '');
        if (! $isSuper && ! ($secret !== '' && hash_equals($secret, $given))) {
            return $this->response->setJSON($public);
        }

        // ?logs=1 -> tail the newest CI4 error log so a 500's real exception can be
        // read via URL (no SSH). Authorized only (we're already past the gate).
        if ($this->request->getGet('logs')) {
            $n     = min(200, max(10, (int) $this->request->getGet('logs') ?: 60));
            $files = glob(WRITEPATH . 'logs/log-*') ?: [];
            rsort($files);
            $tail = [];
            if ($files) {
                $lines = @file($files[0], FILE_IGNORE_NEW_LINES) ?: [];
                $tail  = array_slice($lines, -$n);
            }
            return $this->response->setJSON([
                'status'  => 'ok',
                'logfile' => $files[0] ?? null,
                'lines'   => count($tail),
                'tail'    => $tail,
            ]);
        }

        $checks  = [];
        $overall = 'ok';
        $fail = static function (string $name, bool $ok, $detail = null) use (&$checks, &$overall) {
            $checks[$name] = ['ok' => $ok, 'detail' => $detail];
            if (! $ok && $overall !== 'fail') {
                $overall = 'fail';
            }
        };

        // ---- Database connectivity + latency -----------------------------
        try {
            $db  = Database::connect();
            $q0  = microtime(true);
            $db->query('SELECT 1')->getRow();
            $fail('database', true, ['latency_ms' => round((microtime(true) - $q0) * 1000, 1), 'driver' => $db->DBDriver, 'database' => $db->database]);
        } catch (\Throwable $e) {
            $fail('database', false, ['error' => $e->getMessage()]);
            $db = null;
        }

        // ---- DB connection limits (confirms the "500s = connection cap" theory)
        if ($db) {
            $lim = [];
            $one = static function ($db, string $sql, string $col = 'Value') {
                try { $r = $db->query($sql)->getRowArray(); return $r[$col] ?? ($r['Value'] ?? null); }
                catch (\Throwable $e) { return null; }
            };
            $lim['max_connections']      = $one($db, "SHOW VARIABLES LIKE 'max_connections'");
            $lim['max_user_connections'] = $one($db, "SHOW VARIABLES LIKE 'max_user_connections'");
            $lim['threads_connected']    = $one($db, "SHOW STATUS LIKE 'Threads_connected'");
            $lim['max_used_connections'] = $one($db, "SHOW STATUS LIKE 'Max_used_connections'");
            try {
                $grants = $db->query('SHOW GRANTS FOR CURRENT_USER')->getResultArray();
                foreach ($grants as $g) {
                    $line = implode(' ', $g);
                    if (preg_match('/MAX_USER_CONNECTIONS\s+(\d+)/i', $line, $m)) { $lim['grant_max_user_connections'] = (int) $m[1]; }
                }
            } catch (\Throwable $e) { /* no privilege — fine */ }
            // Flag if peak usage is close to a known cap.
            $cap = (int) ($lim['grant_max_user_connections'] ?? $lim['max_user_connections'] ?? 0);
            $peak = (int) ($lim['max_used_connections'] ?? 0);
            $tight = $cap > 0 && $peak >= $cap - 1;
            $checks['db_limits'] = ['ok' => ! $tight, 'detail' => $lim + ['note' => $tight ? 'peak usage HIT the per-user cap — this is your 500 ceiling' : 'headroom ok']];
            if ($tight && $overall !== 'fail') { $overall = 'fail'; }
        }

        // ---- Migrations: applied vs available ----------------------------
        try {
            $files = glob(APPPATH . 'Database/Migrations/*.php') ?: [];
            $applied = 0;
            if ($db && $db->tableExists('migrations')) {
                $applied = (int) $db->table('migrations')->countAllResults();
            }
            $pending = max(0, count($files) - $applied);
            $fail('migrations', $pending === 0, ['available' => count($files), 'applied' => $applied, 'pending' => $pending]);
        } catch (\Throwable $e) {
            $fail('migrations', false, ['error' => $e->getMessage()]);
        }

        // ---- Writable paths (cache + uploads) ----------------------------
        $fail('writable_writepath', is_writable(WRITEPATH), ['path' => WRITEPATH]);
        $uploads = WRITEPATH . 'uploads';
        $fail('writable_uploads', is_dir($uploads) ? is_writable($uploads) : is_writable(WRITEPATH), ['path' => $uploads]);

        // ---- OPcache (big perf signal) -----------------------------------
        $opcache = false;
        if (function_exists('opcache_get_status')) {
            $st = @opcache_get_status(false);
            $opcache = is_array($st) && ! empty($st['opcache_enabled']);
        }
        // Not a hard failure, but flagged — production should have this ON.
        $checks['opcache'] = ['ok' => $opcache, 'detail' => $opcache ? 'enabled' : 'DISABLED (enable in prod php.ini)'];

        // ---- Config sanity ------------------------------------------------
        $checks['config'] = ['ok' => true, 'detail' => [
            'environment'   => ENVIRONMENT,
            'prod_ready'    => ENVIRONMENT === 'production',
            'base_url'      => rtrim((string) config('App')->baseURL, '/'),
            'sendgrid_set'  => (bool) (env('sendgrid.apiKey') ?? false),
            'google_client' => (bool) (env('oauth.google.clientId') ?? false),
        ]];

        // ---- Key table row counts (quick data snapshot) ------------------
        if ($db) {
            $counts = [];
            foreach (['users', 'companies', 'company_users', 'transactions', 'subscriptions', 'api_tokens'] as $tbl) {
                try {
                    $counts[$tbl] = $db->tableExists($tbl) ? (int) $db->table($tbl)->countAllResults() : null;
                } catch (\Throwable $e) {
                    $counts[$tbl] = 'err';
                }
            }
            $checks['tables'] = ['ok' => true, 'detail' => $counts];
        }

        return $this->response->setJSON([
            'status'       => $overall,
            'app'          => $public['app'],
            'env'          => ENVIRONMENT,
            'php'          => PHP_VERSION,
            'time'         => date('c'),
            'took_ms'      => round((microtime(true) - $t0) * 1000, 1),
            'checks'       => $checks,
        ]);
    }
}
