<?php

namespace Modules\ApiMonitor\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ApiRegistry;
use App\Models\ApiEndpointModel;

/**
 * Super-Admin-only Mobile API Monitor.
 *
 * Lists every mobile-app endpoint (api/v1/*), shows its request parameters,
 * lets the operator health-check each one live (is it alive/reachable?), and
 * toggle it active/inactive (inactive → the live API returns 503 via the
 * ApiToggle filter). The whole route group is gated by the `superadmin` filter.
 */
class ApiMonitorController extends BaseController
{
    protected ApiEndpointModel $model;
    protected ApiRegistry $registry;

    public function __construct()
    {
        $this->model    = new ApiEndpointModel();
        $this->registry = new ApiRegistry();
    }

    public function index()
    {
        // First visit (or after a deploy that added endpoints): auto-sync so the
        // list is never empty / stale.
        if ($this->model->countAllResults() === 0) {
            $this->registry->sync();
        }

        $groups = $this->model->grouped();

        $all    = $this->model->findAll();
        $summary = [
            'total'    => count($all),
            'active'   => count(array_filter($all, static fn ($r) => (int) $r['is_active'] === 1)),
            'inactive' => count(array_filter($all, static fn ($r) => (int) $r['is_active'] === 0)),
            'online'   => count(array_filter($all, static fn ($r) => $r['health'] === 'online')),
            'down'     => count(array_filter($all, static fn ($r) => in_array($r['health'], ['down', 'error', 'missing'], true))),
        ];

        return $this->render('index', [
            'title'      => 'Mobile API Monitor',
            'breadcrumb' => [['label' => 'Mobile API Monitor']],
            'groups'     => $groups,
            'summary'    => $summary,
            'baseUrl'    => rtrim(base_url(), '/') . '/api/v1/',
        ]);
    }

    /** Re-scan the route collection and refresh the registry. */
    public function sync()
    {
        $res = $this->registry->sync();
        activity_log('ApiMonitor', 'Sync', "Synced API registry ({$res['total']} endpoints)");
        return $this->json(200, ['status' => 'success', 'result' => $res]);
    }

    /** Health-check a single endpoint. */
    public function check($id = null)
    {
        $row = $this->model->find((int) $id);
        if (! $row) {
            return $this->json(404, ['status' => 'error', 'message' => 'Endpoint not found.']);
        }
        $res = $this->registry->checkOne($row);
        return $this->json(200, ['status' => 'success', 'endpoint' => $this->presentOne($res)]);
    }

    /** Health-check every endpoint (used by the "Check all" button). */
    public function checkAll()
    {
        $counts = $this->registry->checkAll();
        activity_log('ApiMonitor', 'Check', 'Ran health check on all API endpoints');
        // Return the refreshed rows so the page can repaint every badge at once.
        $rows = array_map([$this, 'presentOne'], $this->model->findAll());
        return $this->json(200, ['status' => 'success', 'counts' => $counts, 'endpoints' => $rows]);
    }

    /** Flip one endpoint active/inactive. */
    public function toggle($id = null)
    {
        $row = $this->model->find((int) $id);
        if (! $row) {
            return $this->json(404, ['status' => 'error', 'message' => 'Endpoint not found.']);
        }
        $next = (int) $row['is_active'] === 1 ? 0 : 1;
        $this->model->update($row['id'], ['is_active' => $next]);
        $this->model->clearDisabledCache(); // instant effect (the filter caches for 60s)
        activity_log('ApiMonitor', 'Edit', ($next ? 'Enabled' : 'Disabled') . " API {$row['http_method']} {$row['path']}");
        return $this->json(200, ['status' => 'success', 'is_active' => $next]);
    }

    private function presentOne(array $r): array
    {
        return [
            'id'          => (int) $r['id'],
            'is_active'   => (int) $r['is_active'],
            'http_status' => $r['http_status'] !== null ? (int) $r['http_status'] : null,
            'health'      => $r['health'],
            'response_ms' => $r['response_ms'] !== null ? (int) $r['response_ms'] : null,
            'last_checked'=> $r['last_checked'] ? date('d M Y, H:i', strtotime($r['last_checked'])) : null,
        ];
    }

    private function json(int $status, array $body)
    {
        $body['csrf'] = csrf_hash();
        return $this->response->setStatusCode($status)->setJSON($body);
    }
}
