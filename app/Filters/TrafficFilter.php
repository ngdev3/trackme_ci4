<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TrafficFilter — replaces MY_Controller step 2 (traffic_count()). Logs a page
 * hit into daily_traffic, skipping AJAX to avoid poll flooding, and runs the
 * once-a-day retention prune.
 */
class TrafficFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // CI3 skipped AJAX to avoid DataTables poll flooding.
        if ($request->isAJAX()) {
            return;
        }

        // TODO(P3): port traffic_count() (daily_traffic insert + once/day prune
        // driven by aa_traffic_settings). Keep the AJAX skip above.
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
