<?php

namespace App\Models;

use CodeIgniter\Model;

class ReminderNotificationModel extends Model
{
    protected $table         = 'reminder_notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;
    protected $updatedField   = '';

    protected $allowedFields = ['reminder_id', 'user_id', 'company_id', 'notification_id', 'fired_for', 'is_read'];

    /** Record that a reminder occurrence was announced. */
    public function record(int $reminderId, int $userId, ?int $companyId, ?int $notificationId, string $firedFor): void
    {
        $this->insert([
            'reminder_id'     => $reminderId,
            'user_id'         => $userId,
            'company_id'      => $companyId,
            'notification_id' => $notificationId,
            'fired_for'       => $firedFor,
        ]);
    }
}
