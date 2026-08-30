<?php

namespace App\Controllers;

use Config\Database;
use Throwable;

/**
 * P0 self-test — proves the CI4 foundation is wired correctly:
 * framework/PHP versions, all three DB groups connect against the shared
 * MySQL, the FyContext service resolves, and the ported helpers load.
 * Visit /health (HTML) or /health/json (JSON).
 */
class Health extends BaseController
{
    private function checks(): array
    {
        $out = [
            'framework'  => \CodeIgniter\CodeIgniter::CI_VERSION,
            'php'        => PHP_VERSION,
            'environment'=> ENVIRONMENT,
            'db'         => [],
            'services'   => [],
            'helpers'    => [],
        ];

        foreach (['default', 'old', 'challan'] as $group) {
            try {
                $db  = Database::connect($group);
                $row = $db->query('SELECT DATABASE() AS db, VERSION() AS ver')->getRowArray();
                $out['db'][$group] = ['ok' => true, 'database' => $row['db'], 'server' => $row['ver']];
            } catch (Throwable $e) {
                $out['db'][$group] = ['ok' => false, 'error' => $e->getMessage()];
            }
        }

        // Sample scoped count from the default DB (read-only, proves QB works).
        try {
            $db = Database::connect();
            $c  = $db->table('invoice_system')->countAllResults();
            $out['db']['default']['sample_invoice_rows'] = $c;
        } catch (Throwable $e) {
            $out['db']['default']['sample_error'] = $e->getMessage();
        }

        $out['services']['fyContext'] = service('fyContext') instanceof \App\Libraries\FyContext;
        $out['helpers']['app']        = function_exists('fy') && function_exists('flash_toast');
        $out['helpers']['cr_cache']   = function_exists('cr_remember') && function_exists('cr_cache_scope');

        return $out;
    }

    public function index()
    {
        return view('health', ['checks' => $this->checks()]);
    }

    public function json()
    {
        return $this->response->setJSON($this->checks());
    }
}
