<?php

/**
 * Invoice seal helper — CI4 port of application/helpers/invoice_helper.php.
 * HMAC-SHA256 tamper-proof verification code over the invoice's key fields.
 * Framework-agnostic except the secret source.
 *
 * NOTE: to make seals VERIFY across CI3 and CI4 (same DB), invoice_seal_secret()
 * must return the SAME value both apps use. CI3 uses config_item('encryption_key').
 * Set the identical key in CI4 .env (encryption.key) before wiring invoice_verify
 * on CI4 (P6); until then the fallback keeps CI4 self-consistent for display.
 */

if (! function_exists('invoice_seal_secret')) {
    function invoice_seal_secret(): string
    {
        $s = (string) (config('Encryption')->key ?? '');
        return $s !== '' ? $s : 'CR-IND-INVOICE-SEAL';
    }
}

if (! function_exists('invoice_seal_canonical')) {
    function invoice_seal_canonical($d): string
    {
        $g = static function ($k) use ($d) {
            if (is_array($d)) {
                return $d[$k] ?? '';
            }
            if (is_object($d)) {
                return $d->$k ?? '';
            }
            return '';
        };
        return implode('|', [
            'CRINV', $g('FY'), $g('invoice_id'),
            trim((string) $g('contact_person_name')), trim((string) $g('product_name')),
            $g('quantity'), $g('rate'), $g('total_invoice'), $g('hsn_code'),
        ]);
    }
}

if (! function_exists('invoice_seal_code_raw')) {
    function invoice_seal_code_raw($d): string
    {
        return strtoupper(substr(hash_hmac('sha256', invoice_seal_canonical($d), invoice_seal_secret()), 0, 16));
    }
}

if (! function_exists('invoice_seal_code_fmt')) {
    function invoice_seal_code_fmt($d): string
    {
        return implode('-', str_split(invoice_seal_code_raw($d), 4));
    }
}

if (! function_exists('invoice_seal_matches')) {
    function invoice_seal_matches($d, $given): bool
    {
        $expected = invoice_seal_code_raw($d);
        $given    = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $given));
        return hash_equals($expected, $given);
    }
}

if (! function_exists('pdf_tax_normalize')) {
    function pdf_tax_normalize($d)
    {
        if (! is_array($d)) {
            return $d;
        }
        if (isset($d['tax_total_invoice']) && $d['tax_total_invoice'] !== '') {
            $d['total_invoice'] = $d['tax_total_invoice'];
        }
        if (isset($d['tax_cgst_amount'])) {
            $d['cgst_amount'] = $d['tax_cgst_amount'];
        }
        if (isset($d['tax_sgst_amount'])) {
            $d['sgst_amount'] = $d['tax_sgst_amount'];
        }
        if (! isset($d['invoice_remark']) || $d['invoice_remark'] === '') {
            $d['invoice_remark'] = $d['remark'] ?? '';
        }
        $d['type_of_invoice'] = 1;
        if (! isset($d['invoice_id']) || $d['invoice_id'] === '') {
            $d['invoice_id'] = (isset($d['tax_invoice_fy_id']) && $d['tax_invoice_fy_id'] !== '')
                ? $d['tax_invoice_fy_id'] : ($d['tax_invoice_id'] ?? '');
        }
        foreach (['cgst', 'sgst', 'igst', 'igst_amount', 'tax_gst_amount', 'cgst_amount', 'sgst_amount',
            'freight', 'others', 'uom', 'hsn_code', 'truck_no', 'driver_name', 'bos_id', 'amount',
            'del_name', 'del_purchaser_address', 'del_purchaser_gst_no', 'del_state', 'del_state_code'] as $k) {
            if (! isset($d[$k])) {
                $d[$k] = '';
            }
        }
        if (! isset($d['delivery_at_account'])) {
            $d['delivery_at_account'] = 0;
        }
        return $d;
    }
}
