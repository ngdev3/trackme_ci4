<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

/**
 * Send a real transactional email through the configured SendGrid Mailer, so you
 * can confirm the API key + verified sender work end-to-end.
 *
 *   php spark email:test you@example.com            (sends the "welcome" email)
 *   php spark email:test you@example.com otp
 *   php spark email:test you@example.com subscription
 *   php spark email:test you@example.com reset
 *   php spark email:test you@example.com changed
 *
 * Reads sendgrid.apiKey / email.fromEmail / email.fromName from the environment.
 * Prints whether SendGrid accepted the message (true) — then check the inbox and
 * the SendGrid Activity Feed. On false, see writable/logs for the reason.
 */
class EmailTest extends BaseCommand
{
    protected $group       = 'Email';
    protected $name        = 'email:test';
    protected $description  = 'Send a test transactional email via SendGrid.';
    protected $usage        = 'email:test <to> [welcome|otp|subscription|reset|changed]';
    protected $arguments    = [
        'to'   => 'Recipient email address (required).',
        'type' => 'Which email to send. Default: welcome.',
    ];

    public function run(array $params)
    {
        $to   = $params[0] ?? CLI::prompt('Recipient email');
        $type = strtolower($params[1] ?? 'welcome');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            CLI::error('Not a valid email address: ' . $to);
            return 1;
        }

        // Warn early if the environment is not configured (Mailer would no-op).
        if ((string) env('sendgrid.apiKey', '') === '' || (string) env('email.fromEmail', '') === '') {
            CLI::error('SendGrid is not configured. Set sendgrid.apiKey and email.fromEmail in .env.');
            return 1;
        }

        $mailer = Services::mailer();
        CLI::write('Sending "' . $type . '" from ' . env('email.fromEmail') . ' to ' . $to . ' ...', 'yellow');

        $ok = match ($type) {
            'otp'          => $mailer->emailOtp($to, '123456', 10),
            'subscription' => $mailer->subscriptionPurchase($to, 'Test User', [
                'plan'       => 'Premium',
                'amount'     => 499,
                'currency'   => '₹',
                'invoice_no' => 'INV/2026-27/0007',
                'expires_at' => date('Y-m-d', strtotime('+1 year')),
            ]),
            'reset'   => $mailer->passwordReset($to, site_url('reset-password/TEST-TOKEN-1234567890'), 60),
            'changed' => $mailer->passwordChanged($to, 'Test User'),
            default   => $mailer->welcome($to, 'Test User'),
        };

        if ($ok) {
            CLI::write('✔ SendGrid accepted the message. Check the inbox and the SendGrid Activity Feed.', 'green');
            return 0;
        }

        CLI::error('Send failed. Check writable/logs/ for the SendGrid error (bad key, unverified sender, etc.).');
        return 1;
    }
}
