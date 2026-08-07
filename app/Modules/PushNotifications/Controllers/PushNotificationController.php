<?php

namespace Modules\PushNotifications\Controllers;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\UserModel;
use Config\Services;

class PushNotificationController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'settings'];

    public function index()
    {
        return $this->render('index', [
            'title'      => 'Push Notification Center',
            'breadcrumb' => [['label' => 'Push Notification Center']],
            'templates'  => $this->templates(),
            'users'      => $this->usersWithDevices(),
        ]);
    }

    public function send()
    {
        $target = (string) $this->request->getPost('target');
        $selected = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('users')))));
        $title = trim((string) $this->request->getPost('title'));
        $message = trim((string) $this->request->getPost('message'));
        $type = (string) ($this->request->getPost('type') ?: 'info');
        $priority = (string) ($this->request->getPost('priority') ?: 'normal');
        $actionUrl = trim((string) $this->request->getPost('action_url'));

        if ($title === '' || $message === '') {
            return redirect()->back()->withInput()->with('error', 'Title and message are required.');
        }
        if (! in_array($type, NotificationModel::TYPES, true)) {
            $type = 'info';
        }
        if (! in_array($priority, NotificationModel::PRIORITIES, true)) {
            $priority = 'normal';
        }

        $userIds = $this->resolveRecipients($target, $selected);
        if ($userIds === []) {
            return redirect()->back()->withInput()->with('error', 'No users matched your recipient selection.');
        }

        $sent = 0;
        $notifier = Services::notifier();
        foreach ($userIds as $userId) {
            $id = $notifier->user((int) $userId, $title, $message, [
                'type'       => $type,
                'priority'   => $priority,
                'module'     => 'push_notifications',
                'action_url' => $actionUrl !== '' ? $actionUrl : site_url('notifications'),
            ]);
            if ($id) {
                $sent++;
            }
        }

        if (function_exists('activity_log')) {
            activity_log('Push Notifications', 'Add', 'Sent template push notification to ' . $sent . ' user(s)');
        }

        return redirect()->to(site_url('push-notifications'))
            ->with('success', 'Notification queued for ' . $sent . ' user(s). Devices receive push if they are subscribed.');
    }

    private function usersWithDevices(): array
    {
        return (new UserModel())
            ->select('users.id, users.name, users.email, users.mobile, users.account_type, users.status, COUNT(push_subscriptions.id) AS device_count')
            ->join('push_subscriptions', 'push_subscriptions.user_id = users.id', 'left')
            ->where('users.deleted_at', null)
            ->groupBy('users.id, users.name, users.email, users.mobile, users.account_type, users.status')
            ->orderBy('device_count', 'DESC')
            ->orderBy('users.name', 'ASC')
            ->findAll(500);
    }

    private function resolveRecipients(string $target, array $selected): array
    {
        $users = (new UserModel())
            ->select('users.id')
            ->where('users.deleted_at', null)
            ->where('users.status', 1);

        if ($target === 'selected') {
            return $selected;
        }
        if ($target === 'customers') {
            $users->where('users.account_type', 'customer');
        } elseif ($target === 'firm_users') {
            $users->where('users.account_type', 'firm_user');
        } elseif ($target === 'with_devices') {
            $users->join('push_subscriptions', 'push_subscriptions.user_id = users.id', 'inner')
                ->groupBy('users.id');
        }

        return array_map('intval', array_column($users->findAll(5000), 'id'));
    }

    private function templates(): array
    {
        return [
            'plan_expiry' => [
                'name'       => 'Subscription Expiry Reminder',
                'type'       => 'warning',
                'priority'   => 'high',
                'title'      => 'Your HissabKitaab plan needs attention',
                'message'    => 'Your subscription is close to expiry. Renew now to keep premium features active.',
                'action_url' => site_url('subscription'),
            ],
            'payment_success' => [
                'name'       => 'Payment Confirmation',
                'type'       => 'success',
                'priority'   => 'normal',
                'title'      => 'Payment received successfully',
                'message'    => 'Thank you. Your payment has been recorded and your plan benefits are active.',
                'action_url' => site_url('subscription/transactions'),
            ],
            'maintenance' => [
                'name'       => 'Maintenance Alert',
                'type'       => 'system_update',
                'priority'   => 'high',
                'title'      => 'Scheduled maintenance notice',
                'message'    => 'HissabKitaab may be briefly unavailable during scheduled maintenance. Please save your work.',
                'action_url' => site_url('notifications'),
            ],
            'security' => [
                'name'       => 'Security Advisory',
                'type'       => 'warning',
                'priority'   => 'critical',
                'title'      => 'Please review your recent account activity',
                'message'    => 'We noticed important account activity. Open HissabKitaab and review your login history.',
                'action_url' => site_url('my-login-history'),
            ],
            'custom' => [
                'name'       => 'Custom Message',
                'type'       => 'info',
                'priority'   => 'normal',
                'title'      => '',
                'message'    => '',
                'action_url' => site_url('notifications'),
            ],
        ];
    }
}
