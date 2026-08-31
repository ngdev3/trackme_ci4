<?php

/**
 * Accounting helper — CI4 port of application/helpers/accounting_helper (core).
 *
 * The project's ledgers are CREDIT-POSITIVE: a positive net means the account
 * is a Creditor (Cr / we owe them), negative means Debtor (Dr / they owe us).
 * These two helpers are the shared vocabulary for every report + the account
 * picker balance chips. (More of the CI3 helper ports incrementally as needed.)
 */

if (! function_exists('acc_side_from_balance')) {
    /**
     * Classify a credit-positive net balance into {net, abs, side, status}.
     * side: 'Cr' (creditor) | 'Dr' (debtor) | 'Nil'.
     */
    function acc_side_from_balance($net_cr, $eps = 0.005): array
    {
        $net = (float) $net_cr;
        if (abs($net) <= $eps) {
            return ['net' => 0.0, 'abs' => 0.0, 'side' => 'Nil', 'status' => 'Nil'];
        }
        if ($net > 0) {
            return ['net' => $net, 'abs' => $net, 'side' => 'Cr', 'status' => 'Creditor'];
        }
        return ['net' => $net, 'abs' => abs($net), 'side' => 'Dr', 'status' => 'Debtor'];
    }
}

if (! function_exists('acc_money')) {
    /** Format an amount as "1,23,456.00" (Indian grouping) for report display. */
    function acc_money($amount): string
    {
        $amount  = (float) $amount;
        $neg     = $amount < 0;
        $amount  = number_format(abs($amount), 2, '.', '');
        $parts   = explode('.', $amount);
        $intpart = $parts[0];
        $dec     = $parts[1] ?? '00';
        $last3   = strlen($intpart) > 3 ? substr($intpart, -3) : $intpart;
        $rest    = strlen($intpart) > 3 ? substr($intpart, 0, -3) : '';
        if ($rest !== '') {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $out  = $rest . ',' . $last3;
        } else {
            $out = $last3;
        }
        return ($neg ? '-' : '') . $out . '.' . $dec;
    }
}
