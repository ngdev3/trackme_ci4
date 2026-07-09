<?php

namespace App\Commands;

use App\Libraries\ReminderService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Fire in-app (and web-push) notifications for every user's due reminders.
 * Intended for a scheduler / cron, e.g. every minute:
 *
 *   * * * * * php /path/to/spark reminders:notify
 */
class RemindersNotify extends BaseCommand
{
    protected $group       = 'Reminders';
    protected $name        = 'reminders:notify';
    protected $description = 'Send notifications for reminders that have become due.';

    public function run(array $params)
    {
        $db = Database::connect();

        // Companies that currently have at least one un-notified, due reminder.
        $companyIds = $db->table('reminders')
            ->distinct()->select('company_id')
            ->where('status', 'pending')
            ->where('notified', 0)
            ->where('deleted_at', null)
            ->where('COALESCE(snoozed_until, remind_at) <= ' . $db->escape(date('Y-m-d H:i:s')), null, false)
            ->get()->getResultArray();

        $service = new ReminderService();
        $total   = 0;
        foreach ($companyIds as $c) {
            $total += $service->fireDueForCompany($c['company_id'] === null ? null : (int) $c['company_id']);
        }

        CLI::write(CLI::color("Fired {$total} reminder notification(s).", 'green'));
        return 0;
    }
}
