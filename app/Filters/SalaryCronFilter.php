<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SalaryCronFilter — replaces MY_Controller step 4 (maybe_run_monthly_salary()).
 * Idempotent monthly salary generation, triggered opportunistically on admin
 * requests (CI3 had no real cron).
 *
 * NOTE: in CI4 this is better as a `spark` command run by a real scheduler.
 * The filter is kept for parity during transition; once a cron exists, drop it.
 */
class SalaryCronFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request->isAJAX()) {
            return;
        }

        // TODO(P5): port maybe_run_monthly_salary() (idempotent guard via
        // salary_module_cron_log). Prefer moving this to `spark salary:run`
        // invoked by Task Scheduler / cron on the server.
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
