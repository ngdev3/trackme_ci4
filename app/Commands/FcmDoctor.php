<?php

namespace App\Commands;

use App\Libraries\Fcm;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Diagnose the Firebase Cloud Messaging (native push) configuration end-to-end
 * without needing a device: validates fcm.serviceAccount, mints a real OAuth
 * token, and attempts one send, then explains the result in plain language.
 *
 *   php spark fcm:doctor                      (probe with a dummy token)
 *   php spark fcm:doctor --token <realToken>  (probe a real device token)
 */
class FcmDoctor extends BaseCommand
{
    protected $group       = 'Push';
    protected $name        = 'fcm:doctor';
    protected $description = 'Check the Firebase Cloud Messaging (native push) configuration.';
    protected $usage       = 'fcm:doctor [--token <fcmDeviceToken>]';
    protected $options      = ['--token' => 'A real FCM device token to test a live delivery.'];

    public function run(array $params): int
    {
        $token = (string) (CLI::getOption('token') ?? '');
        $fcm   = new Fcm();
        $r     = $token !== '' ? $fcm->probe($token) : $fcm->probe();

        CLI::write('Firebase Cloud Messaging — configuration check', 'yellow');
        CLI::write(str_repeat('-', 52));
        CLI::write('Service account : ' . ($r['configured'] ? CLI::color('configured', 'green') : CLI::color('MISSING', 'red')));
        CLI::write('Project id      : ' . ($r['project_id'] ?? '(none)'));
        CLI::write('Client email    : ' . ($r['client_email'] ?? '(none)'));
        CLI::write('OAuth token     : ' . ($r['token_minted'] ? CLI::color('minted OK', 'green') : CLI::color('FAILED', 'red')));
        if ($r['send_status'] !== null) {
            CLI::write('Send HTTP status: ' . $r['send_status']);
        }
        if (! empty($r['send_body'])) {
            CLI::write('Send response   : ' . $r['send_body']);
        }
        if (! empty($r['error'])) {
            CLI::error('Error: ' . $r['error']);
        }
        CLI::write(str_repeat('-', 52));

        // Verdict.
        if (! $r['configured']) {
            CLI::error('Not configured. Set fcm.serviceAccount in .env to a Firebase service-account JSON.');
            $this->howToFix();
            return 1;
        }
        if (! $r['token_minted']) {
            CLI::error('Could not authenticate the service account. Check the JSON is a valid service-account key.');
            return 1;
        }

        $status = (int) ($r['send_status'] ?? 0);
        if ($status === 403) {
            CLI::error('403 Permission denied: this service account is NOT authorised to send on project "' . $r['project_id'] . '".');
            CLI::write('This is the classic wrong-project key: fcm.serviceAccount must be generated FROM the same', 'light_red');
            CLI::write('Firebase project as the app\'s google-services.json (package com.crind.hissabkitaab).', 'light_red');
            $this->howToFix();
            return 1;
        }
        if (in_array($status, [400, 404], true)) {
            CLI::write('Auth + project are correct. The test token was rejected (expected for a dummy/dead token).', 'green');
            CLI::write('Native push is configured correctly. Pass --token <realDeviceToken> to test a live delivery.', 'green');
            return 0;
        }
        if ($status >= 200 && $status < 300) {
            CLI::write('Delivered! FCM accepted the message. Native push is fully working.', 'green');
            return 0;
        }

        CLI::write('Unexpected status ' . $status . ' — inspect the response body above.', 'yellow');
        return 1;
    }

    private function howToFix(): void
    {
        CLI::newLine();
        CLI::write('How to fix:', 'yellow');
        CLI::write('  1. Firebase Console → project "hissabkitaab-fa33f" → Project settings → Service accounts.');
        CLI::write('  2. "Generate new private key" → save the JSON (e.g. writable/keys/fcm-service-account.json).');
        CLI::write('  3. Set  fcm.serviceAccount = \'<absolute path to that JSON>\'  in .env, then re-run this command.');
        CLI::write('  (Ensure the "Firebase Cloud Messaging API (V1)" is enabled for that project — it is by default.)');
        CLI::newLine();
    }
}
