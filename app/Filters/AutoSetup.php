<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Config\Services;

/**
 * Keeps the database schema in sync on hosts where no CLI/Composer is
 * available (e.g. Hostinger git auto-deploy).
 *
 *  - Runs any PENDING migrations — idempotent, tracked by the `migrations`
 *    table, so already-applied ones are skipped and existing data is never
 *    touched. This creates tables/columns added after the last DB import
 *    (e.g. the `notifications` table the login flow writes to).
 *  - Seeds the baseline data ONLY when the database is empty (fresh install),
 *    so it never duplicates existing rows.
 *
 * Fully guarded: any failure is logged and the request continues. The result
 * is cached for an hour so this is not re-checked on every request.
 */
class AutoSetup implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (is_cli()) {
            return null;
        }

        // Already verified this hour — cheap early-out.
        if (cache('erp_schema_ok')) {
            return null;
        }

        try {
            // Apply migrations that haven't been recorded yet.
            Services::migrations()->latest();

            // Only seed when there is no baseline data at all (fresh DB).
            $db = Database::connect();
            if (! $db->tableExists('users') || $db->table('users')->countAllResults() === 0) {
                Services::seeder()->call('DatabaseSeeder');
            }

            cache()->save('erp_schema_ok', 1, 3600);
        } catch (\Throwable $e) {
            log_message('error', '[AutoSetup] ' . $e->getMessage());
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
