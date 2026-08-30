<?php

/**
 * app_helper — CI4 port of the CI3 function_helper.php public surface that
 * controllers/views call constantly. Thin wrappers over FyContext + CI4
 * services so ported code keeps the same call sites.
 *
 * Only the hot, cross-cutting functions are stubbed here in P0. The rest of
 * function_helper.php (notify, mail, OTP, salary cron, flashdata/toast) is
 * ported in its owning phase — add here as each lands.
 */

if (! function_exists('fy')) {
    /** CI3 fy() -> the active aa_template ROW object (fy()->template_id, ->FY, ->firm_name…). */
    function fy()
    {
        return service('fyContext')->fyRow();
    }
}

if (! function_exists('currentuserinfo')) {
    /** CI3 currentuserinfo() -> the logged-in user row object (or null). */
    function currentuserinfo()
    {
        return service('fyContext')->userInfo();
    }
}

if (! function_exists('erp_is_super_admin')) {
    /** CI3 erp_is_super_admin(). */
    function erp_is_super_admin(): bool
    {
        return service('fyContext')->isSuperAdmin();
    }
}

if (! function_exists('flash_toast')) {
    /**
     * CI3 flash_toast() bridge. In CI4 we stash a toast in flashdata; the
     * layout reads it. Kept API-compatible: (message, type, title).
     */
    function flash_toast(string $message, string $type = 'success', string $title = ''): void
    {
        session()->setFlashdata('cr_toast', [
            'message' => $message,
            'type'    => $type,
            'title'   => $title,
        ]);
    }
}

if (! function_exists('getIndianCurrency')) {
    /** Amount → Indian words (Rupees … Paise Only). Verbatim CI3 port. */
    function getIndianCurrency($number)
    {
        $decimal = round($number - ($no = floor($number)), 2) * 100;
        $hundred = null;
        $digits_length = strlen((string) $no);
        $i = 0;
        $str = [];
        $words = [0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
            7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
            18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
            60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'];
        $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore', 'Arab', 'Kharab'];
        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' And ' : null;
                $digit_name = $digits[$counter] ?? '';
                $str[] = ($number < 21) ? $words[$number] . ' ' . $digit_name . $plural . ' ' . $hundred
                    : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digit_name . $plural . ' ' . $hundred;
            } else {
                $str[] = null;
            }
        }
        $Rupees = implode('', array_reverse($str));
        $paise = ($decimal) ? '.' . ($words[$decimal / 10] . ' ' . $words[$decimal % 10]) . ' Paise' : '';
        return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise . ' Only';
    }
}

if (! function_exists('getFirmDetails')) {
    /** Per-firm master (firm_name + aa_template) for the current firm, cached. */
    function getFirmDetails()
    {
        helper('cr_cache');
        $firmId = (int) (currentuserinfo()->default_firm ?? 0);
        return cr_remember('firm_details_' . $firmId, 600, function () use ($firmId) {
            return \Config\Database::connect()->table('aa_template atp')
                ->select('ftp.*, atp.*, atp.template_id as id')
                ->join('firm_name ftp', 'atp.firm_name_id = ftp.id', 'left')
                ->where('atp.template_id', $firmId)
                ->get()->getRow();
        });
    }
}

if (! function_exists('ID_encode')) {
    /** CI3 id obfuscation for URLs: rand4 . (id+19) . rand4. */
    function ID_encode($id)
    {
        return $id ? (random_int(1111, 9999) . ($id + 19) . random_int(1111, 9999)) : '';
    }
}

if (! function_exists('ID_decode')) {
    /** Reverse ID_encode: strip 4 digits each side, subtract 19. */
    function ID_decode($encodedId)
    {
        if (! $encodedId) {
            return '';
        }
        $id = substr((string) $encodedId, 4, strlen((string) $encodedId) - 8);
        return $id - 19;
    }
}

if (! function_exists('get_hsn_code')) {
    /** Active HSN/product master (cached master lookup, invalidated on HSN CRUD). */
    function get_hsn_code()
    {
        helper('cr_cache'); // ensure cr_remember() is available regardless of caller
        return cr_remember('mst_hsn_active', 600, function () {
            $rows = \Config\Database::connect()->table('hsn_codes')->where('status', 'Active')->get()->getResult();
            return $rows ?: false;
        });
    }
}

if (! function_exists('_layout')) {
    /**
     * CI3 _layout($data) -> render a feature view inside the admin shell.
     * Usage in a ported controller:  return _layout('invoice/listing', $data);
     * (Kept as a helper so ported controllers read almost identically.)
     */
    function _layout(string $view, array $data = []): string
    {
        $vars = $data;
        $vars['contentView'] = $view;   // the feature view to inject
        $vars['contentData'] = $data;   // its data (forwarded by the layout)
        return view('layouts/admin', $vars);
    }
}
