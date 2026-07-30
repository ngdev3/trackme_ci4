<?php

namespace Modules\Api\Controllers;

use App\Models\MonitorModel;
use App\Models\RoleModel;
use App\Models\UserModel;

/**
 * Activity & Audit Monitor — Overview (mobile).
 *
 *   GET /api/v1/monitor/overview?from=YYYY-MM-DD&to=YYYY-MM-DD&user=0
 *
 * SUPER-ADMIN ONLY: this dashboard exposes every user's activity, IPs and
 * online status, so it is gated to super admins and returns 403 otherwise.
 * The underlying data is recorded for ALL users (activity_logs + login_logs).
 */
class MonitorApiController extends BaseApiController
{
    public function overview()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        if (! $this->isSuperAdmin((int) $user['id'])) {
            return $this->failForbidden('This dashboard is available to super administrators only.');
        }

        // Filters (with the spec's defaults + validation).
        $from = $this->validDate((string) $this->request->getGet('from'), date('Y-m-01'));
        $to   = $this->validDate((string) $this->request->getGet('to'), date('Y-m-d'));
        if (strtotime($from) > strtotime($to)) {
            [$from, $to] = [$to, $from];
        }
        $userId = (int) $this->request->getGet('user');
        $userId = $userId > 0 ? $userId : 0;

        $start = $from . ' 00:00:00';
        $end   = $to . ' 23:59:59';

        $model = new MonitorModel();

        return $this->respond([
            'status'  => 'success',
            'filters' => ['from' => $from, 'to' => $to, 'user' => $userId],
            'kpis'    => $model->overviewKpis($start, $end, $userId),
            'series'  => $model->activitySeries($start, $end, $userId),
            'hours'   => $model->hourlySeries($start, $end, $userId),
            'online'  => $model->onlineNow(),
            'recent'  => $model->recentActivity($start, $end, $userId),
            'users'   => $this->userList(),
        ]);
    }

    /** Distinct users (id + name) for the filter dropdown. */
    private function userList(): array
    {
        return (new UserModel())
            ->select('id, name')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    private function isSuperAdmin(int $userId): bool
    {
        $roleIds = (new UserModel())->roleIds($userId);
        if ($roleIds === []) {
            return false;
        }
        return (new RoleModel())->whereIn('id', $roleIds)->where('is_superadmin', 1)->countAllResults() > 0;
    }

    private function validDate(string $value, string $default): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return ($d && $d->format('Y-m-d') === $value) ? $value : $default;
    }
}
