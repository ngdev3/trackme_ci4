<?php

if (! function_exists('send_activation_email')) {
    /**
     * Email a one-click account-activation link for a new signup. The link opens
     * the web activation page (/activate/{token}). Activation is link-only; the
     * legacy $code parameter is retained for signature compatibility but unused.
     *
     * Delivery goes through the SendGrid-backed Mailer. Never throws — a mail
     * misconfiguration must not break the register response.
     */
    function send_activation_email(string $email, string $rawToken, string $code = ''): bool
    {
        $link = site_url('activate/' . $rawToken);

        try {
            return service('mailer')->accountActivation($email, $link, $code, 48);
        } catch (\Throwable $e) {
            log_message('error', 'Activation email exception for {email}: {msg}', [
                'email' => $email,
                'msg'   => $e->getMessage(),
            ]);
            return false;
        }
    }
}
