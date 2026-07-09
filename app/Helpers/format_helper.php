<?php

/**
 * Formatting helpers using application defaults.
 */

if (! function_exists('indian_number_format')) {
    /**
     * Group an amount the Indian way: 12,34,567.89 (last three digits, then
     * pairs). Falls back cleanly for small numbers.
     */
    function indian_number_format(float $amount, int $decimals = 2): string
    {
        $s = number_format($amount, $decimals, '.', '');
        $parts = explode('.', $s);
        $int   = $parts[0];
        $dec   = $parts[1] ?? '';

        $last3 = substr($int, -3);
        $rest  = substr($int, 0, -3);
        if ($rest !== '') {
            $rest  = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $last3 = $rest . ',' . $last3;
        }
        return $decimals > 0 ? $last3 . '.' . $dec : $last3;
    }
}

if (! function_exists('money')) {
    /**
     * Format a monetary amount using the configured currency symbol, grouping
     * style and decimals. Pass $withSymbol = false for a bare grouped number.
     */
    function money($amount, bool $withSymbol = true): string
    {
        $symbol   = '₹';
        $grouping = 'indian';
        $decimals = 2;

        $value = (float) $amount;
        $neg   = $value < 0;
        $value = abs($value);

        $formatted = $grouping === 'international'
            ? number_format($value, $decimals)
            : indian_number_format($value, $decimals);

        $out = $withSymbol && $symbol !== '' ? $symbol . ' ' . $formatted : $formatted;
        return $neg ? '-' . $out : $out;
    }
}

if (! function_exists('fmt_date')) {
    /**
     * Format a date/timestamp using the configured date format (default d M Y).
     * Accepts a DateTimeInterface, a timestamp string, or a date string.
     */
    function fmt_date($value, ?string $format = null): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }
        $format ??= 'd M Y';
        try {
            $dt = $value instanceof \DateTimeInterface ? $value : new \DateTime((string) $value);
        } catch (\Exception $e) {
            return (string) $value;
        }
        return $dt->format($format);
    }
}

if (! function_exists('fmt_datetime')) {
    /**
     * Format a timestamp as "<date format> <time format>" (defaults d M Y h:i A).
     */
    function fmt_datetime($value, ?string $format = null): string
    {
        if ($format !== null) {
            return fmt_date($value, $format);
        }
        $df = 'd M Y';
        $tf = 'h:i A';
        return fmt_date($value, trim($df . ' ' . $tf));
    }
}
