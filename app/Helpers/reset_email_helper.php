<?php

if (! function_exists('send_password_reset_email')) {
    /**
     * Email a password-reset link for the given raw token. The link opens the
     * web reset page (works for both website and mobile-app users — the app's
     * "forgot password" just triggers this email and the user taps the link).
     *
     * Delivery goes through the SendGrid-backed Mailer (see App\Libraries\Mailer).
     * Returns true when the mail was accepted for delivery, false otherwise; never
     * throws, so a mail misconfiguration can't break the forgot-password response.
     */
    function send_password_reset_email(string $email, string $rawToken): bool
    {
        $link = site_url('reset-password/' . $rawToken);

        try {
            return service('mailer')->passwordReset($email, $link, 60);
        } catch (\Throwable $e) {
            log_message('error', 'Password reset email exception for {email}: {msg}', [
                'email' => $email,
                'msg'   => $e->getMessage(),
            ]);
            return false;
        }
    }
}
