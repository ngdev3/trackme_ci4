<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Package feature gate. Blocks access to a gated module when the active
 * subscription package does not include it, enforcing the SAME rule the menu and
 * views use (hasFeature) so a direct URL cannot bypass the UI.
 *
 * The feature can be passed as an argument (`feature:inventory`) or inferred from
 * the request path (so it can also be wired globally by URI pattern without
 * duplicating the map per route). Blocked web requests are redirected to the
 * upgrade page; AJAX/API requests get a 403 JSON payload.
 */
class FeatureGate implements FilterInterface
{
    /** First URL segment → feature key. */
    private const MAP = [
        'calculator' => 'calculator',
        'passwords'  => 'password_manager',
        'notes'      => 'notes',
        'reminders'  => 'reminder',
        'inventory'  => 'inventory',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['subscription', 'url']);

        $feature = $arguments[0] ?? $this->resolveFeature();
        if (! $feature || hasFeature($feature)) {
            return; // baseline feature or included in the package — allow
        }

        $message = feature_lock_message($feature);
        $path    = trim(uri_string(), '/');

        if ($request->isAJAX() || str_starts_with($path, 'api/')) {
            return service('response')->setStatusCode(403)->setJSON([
                'status'  => false,
                'message' => $message,
                'feature' => $feature,
                'upgrade' => site_url('subscription'),
            ]);
        }

        return redirect()->to(site_url('subscription'))
            ->with('error', $message)
            ->with('locked_feature', $feature);
    }

    /** Map the current request path to a feature key when no argument is given. */
    private function resolveFeature(): ?string
    {
        $path = trim(uri_string(), '/');

        if ($path === 'company/trash') {
            return 'trash';
        }
        if (str_starts_with($path, 'reminders/calendar')) {
            return 'calendar';
        }
        $segment = explode('/', $path)[0] ?? '';

        return self::MAP[$segment] ?? null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
