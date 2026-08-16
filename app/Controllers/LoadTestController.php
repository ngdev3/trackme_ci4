<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Browser-based load-testing dashboard, served SAME-ORIGIN so its fetch() calls
 * to /api/v1/* need no CORS. Gated by the same secret as HealthController.
 *
 *   GET /loadtest?key=<health.key>
 *
 * It drives concurrent "virtual users" from the browser and visualises latency,
 * throughput, percentiles, status codes and an overall score. Read-only workflow
 * by default. Intended for the developer against staging / your own server —
 * NOT for pointing a mob of requests at someone else's site.
 */
class LoadTestController extends Controller
{
    public function index()
    {
        // Access: a logged-in Super Admin (via the sidebar menu link), OR anyone
        // with the ?key=<health.key> secret (for CLI / no-session use).
        helper('auth');
        $isSuper = function_exists('is_super_admin_account') && is_super_admin_account();
        $secret  = (string) (env('health.key') ?? '');
        $given   = (string) ($this->request->getGet('key') ?? '');
        $keyOk   = $secret !== '' && hash_equals($secret, $given);
        if (! $isSuper && ! $keyOk) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'forbidden', 'hint' => 'log in as Super Admin, or append ?key=<health.key>']);
        }

        // Default API base = same origin + /api/v1 (so calls are same-origin).
        $base = rtrim((string) config('App')->baseURL, '/') . '/api/v1';

        return view('loadtest', ['apiBase' => $base]);
    }
}
