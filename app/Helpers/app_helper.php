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

if (! function_exists('session_lock_minutes')) {
    /** Global web-panel auto-lock timeout in minutes (0 = disabled). */
    function session_lock_minutes(): int
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $db = \Config\Database::connect();
        if (! $db->tableExists('aa_session_settings')) {
            return $cached = 0;
        }
        $row = $db->table('aa_session_settings')->orderBy('id', 'asc')->limit(1)->get()->getRow();
        return $cached = $row ? max(0, (int) $row->lock_after_minutes) : 0;
    }
}

if (! function_exists('recent_switched_templates')) {
    /** Recent DISTINCT firms/FYs this user switched to (for the Change Firm popup). */
    function recent_switched_templates($user_id = null, int $limit = 6): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('aa_template_switch_log')) {
            return [];
        }
        $b = $db->table('aa_template_switch_log as tsl')
            ->select("tsl.template_id,
                MAX(tsl.selected_at) AS last_selected,
                COUNT(*) AS switch_count,
                fn.name AS firm_name, t.FY, t.template_name, t.product_type,
                (SELECT COUNT(*) FROM aa_rokad r WHERE r.template_id = tsl.template_id AND r.status <> 'Delete') AS entry_count", false)
            ->join('aa_template as t', 't.template_id = tsl.template_id', 'left')
            ->join('firm_name as fn', 'fn.id = t.firm_name_id', 'left')
            ->where('tsl.template_id IS NOT NULL', null, false);
        if ($user_id !== null) {
            $b->where('tsl.user_id', (int) $user_id);
        }
        return $b->groupBy('tsl.template_id')->orderBy('last_selected', 'desc')->limit($limit)->get()->getResult();
    }
}

if (! function_exists('notificationData')) {
    /** Insert one row into the `notification` table (FY/product/template scoped). */
    function notificationData(array $data, string $type): int
    {
        $db  = \Config\Database::connect();
        $not = [];
        $not['msg_global'] = ! empty($data['msg_global']) ? 1 : 0;
        $not['name']       = ! empty($data['name'])
            ? $data['name']
            : (($data['type'] ?? '') . ' <b>' . ($data['module_title'] ?? '') . '</b> ' . ($data['module_name'] ?? ''));
        $not['action'] = $data['action'] ?? '';
        $not['remark'] = $data['remark']
            ?? (($data['type'] ?? '') . ' ' . ($data['module_title'] ?? '') . ' ' . ($data['module_name'] ?? '') . ' ' . $type . ' by ' . ($data['user_name'] ?? ''));
        $not['is_seen']      = 0;
        $not['status']       = 'Active';
        $not['user_id']      = ! empty($data['user_id']) ? $data['user_id'] : currentuserinfo()->id;
        $not['updated_date'] = date('Y-m-d H:i:s');
        $not['added_date']   = date('Y-m-d H:i:s');
        $not['FY']           = fy()->FY;
        $not['product_type'] = fy()->product_type;
        $not['template_id']  = fy()->template_id;
        $db->table('notification')->insert($not);
        return (int) $db->insertID();
    }
}

if (! function_exists('notify')) {
    /** Convenience wrapper to raise an in-app notification from anywhere. */
    function notify(string $message, string $action = '', array $opts = []): int
    {
        return notificationData([
            'name'       => $message,
            'action'     => $action,
            'msg_global' => ! empty($opts['msg_global']) ? 1 : 0,
            'user_id'    => ! empty($opts['user_id']) ? $opts['user_id'] : currentuserinfo()->id,
            'remark'     => $opts['remark'] ?? strip_tags($message),
        ], $opts['event'] ?? 'added');
    }
}

if (! function_exists('BLACKLIST_SEARCH_USER_IDS_DB')) {
    /** True if $search_id is an actively blacklisted account (aa_blacklist_search). */
    function BLACKLIST_SEARCH_USER_IDS_DB($search_id): bool
    {
        $db = \Config\Database::connect();
        return $db->table('aa_blacklist_search')
            ->where('status', 'Active')
            ->where('search_id', $search_id)
            ->countAllResults() > 0;
    }
}

if (! function_exists('BlackList_Search_USER_IDS')) {
    /** CI3-compatible alias: is this account blocked from being viewed/searched? */
    function BlackList_Search_USER_IDS($allowed_users): bool
    {
        return BLACKLIST_SEARCH_USER_IDS_DB($allowed_users);
    }
}

if (! function_exists('unread_notifcations')) {
    /** Count of the current user's unseen notifications (CI3 parity). */
    function unread_notifcations(): int
    {
        $u = currentuserinfo();
        if (empty($u->id)) {
            return 0;
        }
        return (int) \Config\Database::connect()->table('notification')
            ->where('user_id', $u->id)
            ->where('is_seen', '0')
            ->countAllResults();
    }
}

if (! function_exists('recent_notifications')) {
    /** The current user's most recent notifications incl. broadcasts (CI3 parity). */
    function recent_notifications(int $limit = 10): array
    {
        $u = currentuserinfo();
        if (empty($u->id)) {
            return [];
        }
        return \Config\Database::connect()->table('notification n')
            ->select("n.*, TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as user_name", false)
            ->join('users u', 'u.id = n.user_id', 'left')
            ->groupStart()
                ->where('n.user_id', $u->id)
                ->orWhere('n.msg_global', 1)
            ->groupEnd()
            ->orderBy('n.id', 'desc')
            ->limit($limit)
            ->get()->getResult();
    }
}

if (! function_exists('notif_time_ago')) {
    /** Human-friendly "x minutes ago" string (CI3 parity). */
    function notif_time_ago($datetime): string
    {
        $ts = strtotime((string) $datetime);
        if (! $ts) {
            return '';
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'just now';
        }
        $units = [31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
        foreach ($units as $secs => $label) {
            if ($diff >= $secs) {
                $n = floor($diff / $secs);
                return $n . ' ' . $label . ($n > 1 ? 's' : '') . ' ago';
            }
        }
        return 'just now';
    }
}

if (! function_exists('fy_date_range')) {
    /** [start,end] Y-m-d for a financial year (Apr 1 – Mar 31). CI3 parity. */
    function fy_date_range($fy = null): array
    {
        if ($fy === null) { $fy = fy(); }
        $label = ($fy && isset($fy->FY)) ? (string) $fy->FY : '';
        if (! preg_match('/(\d{4})\D+(\d{4})/', $label, $m)) {
            return ['', ''];
        }
        return [$m[1] . '-04-01', $m[2] . '-03-31'];
    }
}

if (! function_exists('fy_clamp_date')) {
    /** Pull a Y-m-d date inside the current FY window (untouched if FY unknown). */
    function fy_clamp_date($ymd, $fy = null)
    {
        [$start, $end] = fy_date_range($fy);
        if ($start === '' || $ymd === '') { return $ymd; }
        if ($ymd < $start) { return $start; }
        if ($ymd > $end)   { return $end; }
        return $ymd;
    }
}

if (! function_exists('html_escape')) {
    /**
     * CI3-compatibility shim: html_escape() → CI4 esc(). Lets faithfully-ported
     * CI3 views (which used html_escape) run unchanged. Handles arrays like CI3.
     */
    function html_escape($var)
    {
        if (is_array($var)) {
            return array_map('html_escape', $var);
        }
        return esc((string) $var);
    }
}

if (! function_exists('form_error')) {
    /**
     * CI3-compatibility shim: return the validation error for a field (empty if
     * none), wrapped in the given markup. Reads CI4 validation errors from the
     * session (set via redirect()->withInput()->with('errors', ...)).
     */
    function form_error($field = '', $open = '<span class="help-block" style="color:red">', $close = '</span>')
    {
        $errors = session('errors');
        if (is_array($errors) && ! empty($errors[$field])) {
            return $open . esc($errors[$field]) . $close;
        }
        return '';
    }
}

if (! function_exists('get_flashdata')) {
    /**
     * CI3-compatibility shim: render session flash messages (success/error/
     * warning/info) as Bootstrap alerts. Lets ported CI3 views that echo
     * get_flashdata() run unchanged.
     */
    function get_flashdata()
    {
        $s = session();
        $map = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
        $out = '';
        foreach ($map as $key => $cls) {
            $m = $s->getFlashdata($key);
            if (! empty($m)) {
                $out .= '<div class="alert alert-' . $cls . '" role="alert">' . $m . '</div>';
            }
        }
        return $out;
    }
}

if (! function_exists('gstin_state_master')) {
    /** GST state-code master (2-digit → state name). CI3 gstin_helper parity. */
    function gstin_state_master(): array
    {
        return [
            '01' => 'Jammu & Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab',
            '04' => 'Chandigarh', '05' => 'Uttarakhand', '06' => 'Haryana',
            '07' => 'Delhi', '08' => 'Rajasthan', '09' => 'Uttar Pradesh',
            '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh',
            '13' => 'Nagaland', '14' => 'Manipur', '15' => 'Mizoram',
            '16' => 'Tripura', '17' => 'Meghalaya', '18' => 'Assam',
            '19' => 'West Bengal', '20' => 'Jharkhand', '21' => 'Odisha',
            '22' => 'Chhattisgarh', '23' => 'Madhya Pradesh', '24' => 'Gujarat',
            '25' => 'Daman & Diu', '26' => 'Dadra & Nagar Haveli',
            '27' => 'Maharashtra', '28' => 'Andhra Pradesh (Old)',
            '29' => 'Karnataka', '30' => 'Goa', '31' => 'Lakshadweep',
            '32' => 'Kerala', '33' => 'Tamil Nadu', '34' => 'Puducherry',
            '35' => 'Andaman & Nicobar Islands', '36' => 'Telangana',
            '37' => 'Andhra Pradesh', '38' => 'Ladakh', '97' => 'Other Territory',
        ];
    }
}
