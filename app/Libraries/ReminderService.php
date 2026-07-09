<?php

namespace App\Libraries;

use App\Models\ReminderModel;
use App\Models\ReminderNotificationModel;
use Config\Services;

/**
 * Fires in-app notifications for reminders that have become due. Idempotent per
 * occurrence: a reminder is only announced once (guarded by the `notified` flag
 * and the reminder_notifications log). Runs cheaply on page load for the current
 * user, and can also be driven for all users by a scheduled command.
 */
class ReminderService
{
    protected ReminderModel $reminders;
    protected ReminderNotificationModel $log;

    public function __construct()
    {
        $this->reminders = new ReminderModel();
        $this->log       = new ReminderNotificationModel();
    }

    /**
     * Announce a company's newly-due reminders. Reminders are shared across the
     * company, but each is announced to its author. Idempotent per occurrence,
     * so any member's page load can safely drive it. Returns the number fired.
     */
    public function fireDueForCompany(?int $companyId): int
    {
        if ($companyId === null) {
            return 0;
        }

        $due   = $this->reminders->dueForNotification($companyId);
        $count = 0;

        foreach ($due as $r) {
            $effective = ! empty($r['snoozed_until']) ? $r['snoozed_until'] : $r['remind_at'];
            $author    = (int) $r['user_id'];

            $notificationId = Services::notifier()->user(
                $author,
                'Reminder: ' . $r['title'],
                $r['description'] ? mb_substr((string) $r['description'], 0, 160) : 'This reminder is now due.',
                [
                    'type'       => 'warning',
                    'module'     => 'reminders',
                    'priority'   => $r['priority'] === 'high' ? 'high' : 'normal',
                    'action_url' => site_url('reminders/edit/' . $r['id']),
                    'created_by' => null,
                ]
            );

            $this->log->record((int) $r['id'], $author, $companyId, $notificationId, $effective);
            $this->reminders->update($r['id'], [
                'notified'         => 1,
                'last_notified_at' => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        return $count;
    }
}
