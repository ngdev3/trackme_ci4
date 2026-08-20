<?php

namespace App\Commands;

use App\Libraries\ApiRegistry;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Rescan the route collection and refresh the Mobile API Monitor registry
 * (the api_endpoints table behind /api-monitor). New/renamed endpoints are
 * added, existing ones are refreshed; the operator's is_active toggle and the
 * last health result are preserved.
 *
 *   php spark api:sync
 *
 * Equivalent to clicking "Sync" in the Super-Admin API Monitor, but runnable
 * from the CLI / a deploy hook with no session.
 */
class ApiSync extends BaseCommand
{
    protected $group       = 'API';
    protected $name        = 'api:sync';
    protected $description = 'Rescan api/v1 routes and refresh the API Monitor registry (api_endpoints).';
    protected $usage       = 'api:sync';

    public function run(array $params)
    {
        $res = (new ApiRegistry())->sync();

        CLI::write('API Monitor registry synced.', 'green');
        CLI::write('  added:   ' . $res['added']);
        CLI::write('  updated: ' . $res['updated']);
        CLI::write('  removed: ' . ($res['removed'] ?? 0), 'yellow');
        CLI::write('  total:   ' . $res['total']);
        // NOTE: no activity_log() here — the audit logger reads the User-Agent,
        // which a CLIRequest doesn't have (fatal on CLI). The web "Sync" button
        // logs the action; a CLI/deploy sync intentionally does not.

        return 0;
    }
}
