<?php

namespace Modules\Api\Controllers;

use App\Models\ReminderModel;

/**
 * Calendar API for the mobile app — a month view built over reminders (their
 * effective due date). Company-scoped; gated by the `calendar` feature (Plus
 * plan and up).
 *
 *   GET calendar?year=YYYY&month=M   reminders for the month, grouped by day.
 */
class CalendarApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    private const FEATURE = 'calendar';

    public function month()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        if (! $this->apiHasFeature($user, self::FEATURE)) {
            return $this->failForbidden('Your plan does not include the Calendar.');
        }
        $companyId = $this->resolveCompanyId($user);

        $year  = (int) ($this->request->getGet('year') ?: date('Y'));
        $month = (int) ($this->request->getGet('month') ?: date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end   = date('Y-m-t 23:59:59', strtotime($start));

        $model = new ReminderModel();
        $rows  = $model->scoped($companyId)
            ->where('COALESCE(snoozed_until, remind_at) >=', $start)
            ->where('COALESCE(snoozed_until, remind_at) <=', $end)
            ->orderBy('COALESCE(snoozed_until, remind_at)', 'ASC', false)
            ->findAll();

        // Group by calendar day so the app can dot/badge each date.
        $days = [];
        foreach ($rows as $r) {
            $due = $r['snoozed_until'] ?: $r['remind_at'];
            $key = date('Y-m-d', strtotime((string) $due));
            $days[$key][] = [
                'id'       => (int) $r['id'],
                'title'    => $r['title'],
                'time'     => date('H:i', strtotime((string) $due)),
                'priority' => $r['priority'],
                'status'   => $r['status'],
            ];
        }

        return $this->respond([
            'status' => 'ok',
            'year'   => $year,
            'month'  => $month,
            'days'   => (object) $days,
        ]);
    }
}
