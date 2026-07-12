<?php

use Config\Services;

if (! function_exists('send_password_reset_email')) {
    /**
     * Email a password-reset link for the given raw token. The link opens the
     * web reset page (works for both website and mobile-app users — the app's
     * "forgot password" just triggers this email and the user taps the link).
     *
     * Requires SMTP to be configured in the environment (email.* keys). Returns
     * true when the mail was accepted for delivery, false otherwise; never throws,
     * so a mail misconfiguration can't break the forgot-password response.
     */
    function send_password_reset_email(string $email, string $rawToken): bool
    {
        $link = site_url('reset-password/' . $rawToken);
        $app  = env('email.fromName', 'HissabKitaab');

        $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#152033">'
            . '<h2 style="color:#0f766e;margin:0 0 8px">Reset your password</h2>'
            . '<p>We received a request to reset the password for your ' . esc($app) . ' account.</p>'
            . '<p style="margin:22px 0"><a href="' . esc($link, 'attr') . '" '
            . 'style="background:#0f766e;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:bold;display:inline-block">Reset password</a></p>'
            . '<p style="color:#5d687c;font-size:13px">Or paste this link into your browser:<br>'
            . '<a href="' . esc($link, 'attr') . '">' . esc($link) . '</a></p>'
            . '<p style="color:#5d687c;font-size:13px">This link expires in 1 hour. If you did not request this, you can safely ignore this email.</p>'
            . '</div>';

        try {
            $mail = Services::email();
            $from = (string) env('email.fromEmail', '');
            if ($from !== '') {
                $mail->setFrom($from, $app);
            }
            $mail->setTo($email);
            $mail->setSubject('Reset your ' . $app . ' password');
            $mail->setMailType('html');
            $mail->setMessage($html);

            if (! $mail->send(false)) {
                log_message('error', 'Password reset email failed for {email}: {err}', [
                    'email' => $email,
                    'err'   => $mail->printDebugger(['headers']),
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'Password reset email exception for {email}: {msg}', [
                'email' => $email,
                'msg'   => $e->getMessage(),
            ]);
            return false;
        }
    }
}
