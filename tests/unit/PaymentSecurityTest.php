<?php

use App\Libraries\Cashfree;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regression tests for the Cashfree webhook signature check — the gate that
 * decides whether an inbound "payment succeeded" call is trusted enough to
 * activate a paid subscription. A weakening here (accepting an unsigned or
 * tampered webhook) would let anyone activate a plan without paying, so these
 * lock the behaviour in.
 *
 * @internal
 */
final class PaymentSecurityTest extends CIUnitTestCase
{
    private const SECRET = 'cfsk_ma_test_regression_secret';

    private function cashfree(string $secret = self::SECRET): Cashfree
    {
        $cfg            = new \Config\Cashfree();
        $cfg->secretKey = $secret;

        return new Cashfree($cfg);
    }

    /** Cashfree's scheme: base64( HMAC-SHA256( timestamp . rawBody, secret ) ). */
    private function sign(string $timestamp, string $body, string $secret = self::SECRET): string
    {
        return base64_encode(hash_hmac('sha256', $timestamp . $body, $secret, true));
    }

    public function testValidSignatureIsAccepted(): void
    {
        $ts   = '1735500000';
        $body = '{"data":{"order":{"order_id":"SUB1P2T3"},"payment":{"payment_status":"SUCCESS"}}}';

        $this->assertTrue(
            $this->cashfree()->verifyWebhookSignature($ts, $body, $this->sign($ts, $body)),
            'A correctly signed webhook must be accepted.'
        );
    }

    public function testTamperedBodyIsRejected(): void
    {
        $ts       = '1735500000';
        $body     = '{"amount":100}';
        $goodSig  = $this->sign($ts, $body);
        $tampered = '{"amount":1}'; // attacker lowers the amount but reuses the signature

        $this->assertFalse(
            $this->cashfree()->verifyWebhookSignature($ts, $tampered, $goodSig),
            'A body that does not match the signature must be rejected.'
        );
    }

    public function testWrongSecretIsRejected(): void
    {
        $ts   = '1735500000';
        $body = '{"ok":true}';
        // Signed with a different secret than the server holds.
        $foreignSig = $this->sign($ts, $body, 'someone_elses_secret');

        $this->assertFalse(
            $this->cashfree()->verifyWebhookSignature($ts, $body, $foreignSig),
            'A signature made with the wrong secret must be rejected.'
        );
    }

    public function testEmptySignatureOrSecretIsRejected(): void
    {
        $ts   = '1735500000';
        $body = '{"ok":true}';

        $this->assertFalse(
            $this->cashfree()->verifyWebhookSignature($ts, $body, ''),
            'An empty signature must never verify.'
        );
        $this->assertFalse(
            $this->cashfree('')->verifyWebhookSignature($ts, $body, $this->sign($ts, $body)),
            'With no configured secret, verification must fail closed.'
        );
    }
}
