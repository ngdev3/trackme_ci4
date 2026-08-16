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

        // Gate the detailed report behind ?key= matching env('health.key').
        $secret = (string) (env('health.key') ?? '');
        $given  = (string) ($this->request->getGet('key') ?? '');
        if ($secret === '' || ! hash_equals($secret, $given)) {
            return $this->response->setJSON($public);
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
