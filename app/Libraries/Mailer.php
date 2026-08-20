<?php

namespace App\Libraries;

use SendGrid;
use SendGrid\Mail\Mail;

/**
 * Transactional email for the ERP — website and mobile app share this one path.
 *
 * Delivery goes through SendGrid's Web API (the sendgrid/sendgrid SDK). The API
 * key and the From identity are read from the environment, never hard-coded:
 *
 *   sendgrid.apiKey = SG.xxxxxxxx...        (required to actually send)
 *   email.fromEmail = no-reply@yourdomain    (a SendGrid-verified sender)
 *   email.fromName  = Hissab-Kitaab
 *
 * Every public method returns true when SendGrid accepted the message for
 * delivery and false otherwise. Nothing here ever throws: a mail misconfig must
 * not break signup, a password change, or a billing callback. When the API key
 * is absent the message is logged and the method returns false, so local/dev
 * environments keep working without SendGrid configured.
 */
class Mailer
{
    private string $apiKey;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->apiKey    = (string) env('sendgrid.apiKey', '');
        $this->fromEmail = (string) env('email.fromEmail', '');
        $this->fromName  = (string) env('email.fromName', brand_name());
    }

    /** The app/brand name used inside every template and subject line. */
    public function appName(): string
    {
        return $this->fromName !== '' ? $this->fromName : brand_name();
    }

    // -----------------------------------------------------------------
    // Public: one method per transactional email
    // -----------------------------------------------------------------

    /** Welcome a brand-new account right after it is created. */
    public function welcome(string $to, string $name): bool
    {
        $app  = $this->appName();
        $html = $this->render('welcome', [
            'app'       => $app,
            'name'      => $name !== '' ? $name : 'there',
            'url'       => site_url('dashboard'),
            'preheader' => 'Your ' . $app . ' account is ready — add your first firm to get started.',
        ]);
        return $this->send($to, 'Welcome to ' . $app . ' 🎉', $html);
    }

    /** Send a signup email-verification one-time code. */
    public function emailOtp(string $to, string $code, int $ttlMinutes = 10): bool
    {
        $html = $this->render('otp', [
            'app'       => $this->appName(),
            'code'      => $code,
            'ttl'       => $ttlMinutes,
            'preheader' => 'Your verification code is ' . $code . ' (valid ' . $ttlMinutes . ' minutes).',
        ]);
        return $this->send($to, $code . ' is your ' . $this->appName() . ' verification code', $html);
    }

    /** Confirm a subscription/plan purchase with the amount + validity. */
    public function subscriptionPurchase(string $to, string $name, array $details): bool
    {
        $app  = $this->appName();
        $html = $this->render('subscription', [
            'app'       => $app,
            'name'      => $name !== '' ? $name : 'there',
            'plan'      => (string) ($details['plan'] ?? ''),
            'amount'    => $details['amount'] ?? null,
            'currency'  => (string) ($details['currency'] ?? '₹'),
            'invoiceNo' => (string) ($details['invoice_no'] ?? ''),
            'expiresAt' => (string) ($details['expires_at'] ?? ''),
            'url'       => site_url('subscription/transactions'),
            'preheader' => 'Payment received — your ' . (string) ($details['plan'] ?? '') . ' plan is now active.',
        ]);
        return $this->send($to, 'Your ' . $app . ' subscription is active', $html);
    }

    /** Email a password-reset link (opens the web reset page). */
    public function passwordReset(string $to, string $link, int $ttlMinutes = 60): bool
    {
        $html = $this->render('reset', [
            'app'       => $this->appName(),
            'link'      => $link,
            'ttl'       => $ttlMinutes,
            'preheader' => 'Reset your password — this link expires in ' . $ttlMinutes . ' minutes.',
        ]);
        return $this->send($to, 'Reset your ' . $this->appName() . ' password', $html);
    }

    /**
     * One-click account-activation email for a new email/password signup. The
     * link activates the account in the browser; the optional 6-digit code lets
     * the user activate from inside the app instead.
     */
    public function accountActivation(string $to, string $link, string $code = '', int $ttlHours = 48): bool
    {
        $html = $this->render('activate', [
            'app'       => $this->appName(),
            'link'      => $link,
            'code'      => $code,
            'ttl'       => $ttlHours,
            'preheader' => 'Activate your ' . $this->appName() . ' account.',
        ]);
        return $this->send($to, 'Activate your ' . $this->appName() . ' account', $html);
    }

    /**
     * Send the owner a FINAL report of a company that was just permanently
     * deleted — entry/account totals and a clear "cannot be recovered" notice.
     * `$stats` comes from send_company_deletion_report() (company_report helper).
     */
    public function companyDeleted(string $to, string $name, string $companyName, array $stats): bool
    {
        $app  = $this->appName();
        $html = $this->render('company_deleted', [
            'app'         => $app,
            'name'        => $name !== '' ? $name : 'there',
            'companyName' => $companyName,
            'stats'       => $stats,
            'preheader'   => 'Final report for "' . $companyName . '" — permanently deleted and not recoverable.',
        ]);
        return $this->send($to, 'Final report — "' . $companyName . '" was permanently deleted', $html);
    }

    /** Confirm that the account password was just changed. */
    public function passwordChanged(string $to, string $name): bool
    {
        $app  = $this->appName();
        $html = $this->render('password_changed', [
            'app'       => $app,
            'name'      => $name !== '' ? $name : 'there',
            'when'      => date('d M Y, H:i'),
            'url'       => site_url('forgot-password'),
            'preheader' => 'Security notice: your ' . $app . ' password was just changed.',
        ]);
        return $this->send($to, 'Your ' . $app . ' password was changed', $html);
    }

    /**
     * Send a new temporary password an admin set for the user (support flow).
     * The user is expected to change it on next login.
     */
    public function temporaryPassword(string $to, string $name, string $password): bool
    {
        $app  = $this->appName();
        $html = $this->render('temp_password', [
            'app'       => $app,
            'name'      => $name !== '' ? $name : 'there',
            'password'  => $password,
            'url'       => site_url('login'),
            'preheader' => 'A new temporary password was set for your ' . $app . ' account.',
        ]);
        return $this->send($to, 'Your new ' . $app . ' password', $html);
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * Low-level send. Returns true on a 2xx from SendGrid. Never throws.
     */
    private function send(string $to, string $subject, string $html): bool
    {
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            log_message('error', 'Mailer: refusing to send to invalid address "{to}"', ['to' => $to]);
            return false;
        }
        if ($this->apiKey === '' || $this->fromEmail === '') {
            log_message('error', 'Mailer: SendGrid not configured (sendgrid.apiKey / email.fromEmail); "{subj}" to {to} not sent.', [
                'subj' => $subject,
                'to'   => $to,
            ]);
            return false;
        }

        try {
            $mail = new Mail();
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->setSubject($subject);
            $mail->addTo($to);
            $mail->addContent('text/html', $html);
            $mail->addContent('text/plain', $this->toPlainText($html));

            $sg   = new SendGrid($this->apiKey);
            $resp = $sg->send($mail);

            $code = $resp->statusCode();
            if ($code >= 200 && $code < 300) {
                return true;
            }

            log_message('error', 'Mailer: SendGrid returned {code} for "{subj}" to {to}: {body}', [
                'code' => $code,
                'subj' => $subject,
                'to'   => $to,
                'body' => (string) $resp->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            log_message('error', 'Mailer: SendGrid exception for "{subj}" to {to}: {msg}', [
                'subj' => $subject,
                'to'   => $to,
                'msg'  => $e->getMessage(),
            ]);
            return false;
        }
    }

    /** Render a branded email body from app/Views/emails/<name>.php. */
    private function render(string $name, array $data): string
    {
        return view('emails/' . $name, $data + ['app' => $this->appName()]);
    }

    /** Crude HTML→text fallback so the plain part isn't empty. */
    private function toPlainText(string $html): string
    {
        $text = preg_replace('/<(head|style|script)\b[^>]*>.*?<\/\1>/is', '', $html);
        $text = preg_replace('/<br\s*\/?>/i', "\n", (string) $text);
        $text = preg_replace('/<\/(p|div|h[1-6]|tr|li)>/i', "\n", (string) $text);
        $text = trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES, 'UTF-8'));
        return (string) preg_replace('/\n{3,}/', "\n\n", $text);
    }
}
