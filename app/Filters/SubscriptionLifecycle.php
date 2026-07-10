<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Detects a just-lapsed free trial / paid window for the signed-in customer and
 * notifies them once (see sub_sync_expiry). Runs before the response is built so
 * the "trial ended" notification is already present when the navbar bell renders.
 * Cheap and defensive: it early-returns for guests, Super Admins and AJAX/API,
 * and never throws.
 */
class SubscriptionLifecycle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Skip AJAX/API and non-GET requests — this is a page-render concern only.
        if ($request->isAJAX() || $request->getMethod() !== 'GET') {
            return;
        }

        helper(['auth', 'subscription']);

        // Only signed-in, non-super-admin customers have a trial to lapse.
        if (! function_exists('user_id') || ! user_id()) {
            return;
        }
        if (function_exists('is_super_admin_account') && is_super_admin_account()) {
            return;
        }

        try {
            sub_sync_expiry();
        } catch (\Throwable $e) {
            log_message('error', 'SubscriptionLifecycle filter failed: ' . $e->getMessage());
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
