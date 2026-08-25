<?php

/**
 * Small presentation helpers for reusable AdminLTE / Bootstrap 5 components.
 */

if (! function_exists('status_badge')) {
    /**
     * Render an Active/Inactive badge from a 0/1 status.
     */
    function status_badge($status): string
    {
        $active = (int) $status === 1;
        return '<span class="erp-status ' . ($active ? 'active' : 'inactive') . '">' . ($active ? 'Active' : 'Inactive') . '</span>';
    }
}

if (! function_exists('bool_badge')) {
    function bool_badge($value, string $yes = 'Yes', string $no = 'No'): string
    {
        return (int) $value === 1
            ? '<span class="erp-pill">' . esc($yes) . '</span>'
            : '<span class="erp-pill gray">' . esc($no) . '</span>';
    }
}

if (! function_exists('action_buttons')) {
    /**
     * Standard edit / delete / status-toggle button group for a table row.
     *
     * @param string $moduleCode used for permission gating
     * @param string $baseUrl    e.g. "users"
     * @param int    $id         row id
     */
    function action_buttons(string $moduleCode, string $baseUrl, int $id, array $opts = []): string
    {
        $html = '<div class="erp-actions">';

        if (can($moduleCode, 'edit') && ($opts['edit'] ?? true)) {
            $html .= '<a href="' . site_url("{$baseUrl}/edit/{$id}") . '" class="erp-act" title="Edit"><i class="bi bi-pencil-square"></i></a>';
        }
        if (can($moduleCode, 'delete') && ($opts['delete'] ?? true)) {
            $html .= '<button type="button" class="erp-act red btn-delete" '
                . 'data-url="' . site_url("{$baseUrl}/delete/{$id}") . '" title="Delete"><i class="bi bi-trash"></i></button>';
        }
        $html .= '</div>';
        return $html;
    }
}

if (! function_exists('erp_cell_name')) {
    /**
     * Render a primary "name" cell exactly like the Customers listing:
     * a round initial avatar + the name, which reveals a rich hover-card
     * (built by assets/js/erp-table.js from the generic $tip payload).
     *
     * @param string $name  the visible label (also the card title if $tip has none)
     * @param array  $tip   generic card payload: type, icon, accent, chips[], stats[], bar{}, rows[], foot
     * @param array  $opts  avatar (bool, default true) · green (bool) · badge (raw html appended) · initial (override)
     */
    function erp_cell_name(string $name, array $tip, array $opts = []): string
    {
        $tip['name'] = $tip['name'] ?? $name;
        $json  = esc(json_encode($tip, JSON_UNESCAPED_UNICODE), 'attr');
        $html  = '<div class="erp-cellname">';
        if ($opts['avatar'] ?? true) {
            $initial = $opts['initial'] ?? (strtoupper(mb_substr(trim($name), 0, 1)) ?: '?');
            $html .= '<span class="erp-avatar' . (! empty($opts['green']) ? ' green' : '') . '">' . esc($initial) . '</span>';
        }
        $html .= '<span class="erp-name-txt erp-hover" data-tip="' . $json . '">' . esc($name) . '</span>';
        if (! empty($opts['badge'])) { $html .= $opts['badge']; }
        return $html . '</div>';
    }
}

if (! function_exists('flash_alerts')) {
    /**
     * Render queued session flash messages as Bootstrap alerts.
     * (Also mirrored into JS toasts by the layout.)
     */
    function flash_alerts(): string
    {
        $session = session();
        $out     = '';
        $map     = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
        foreach ($map as $key => $class) {
            if ($msg = $session->getFlashdata($key)) {
                $out .= '<div class="alert alert-' . $class . ' alert-dismissible fade show" role="alert">'
                    . esc($msg)
                    . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
        return $out;
    }
}

if (! function_exists('erp_asset')) {
    /**
     * Build a public asset URL with a filemtime cache-buster for local files.
     *
     * This keeps development changes visible immediately without manual hard
     * refreshes, while still allowing browsers to cache each exact asset
     * version safely.
     */
    function erp_asset(string $path): string
    {
        $base = rtrim(base_url(), '/') . '/';
        if (str_starts_with($path, $base)) {
            $clean = substr($path, strlen($base));
            $url   = $path;
        } elseif (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        } else {
            $clean = ltrim($path, '/');
            $url   = base_url($clean);
        }

        $file  = FCPATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $clean);

        if (is_file($file)) {
            $url = preg_replace('/([?&])v=\d+(&?)/', '$1', $url);
            $url = rtrim($url, '?&');
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($file);
        }

        return $url;
    }
}

if (! function_exists('old_value')) {
    /**
     * Repopulate a form field: old input, else the model value, else default.
     */
    function old_value(string $field, $row = null, $default = '')
    {
        $old = old($field);
        if ($old !== null) {
            return $old;
        }
        if (is_array($row) && array_key_exists($field, $row)) {
            return $row[$field];
        }
        return $default;
    }
}

if (! function_exists('erp_languages')) {
    /**
     * The 12 UI languages offered by the in-app translator.
     * Keyed by Google-translate language code => native name / English name / flag.
     *
     * @return array<string, array{native:string, name:string, flag:string}>
     */
    function erp_languages(): array
    {
        // Aligned with the mobile app's supported languages (India-first). Every
        // code is a valid Google-Translate locale so the whole web UI translates
        // client-side via the googtrans cookie (see i18n.js + layout.php). Hinglish
        // has no Google-Translate locale, so it is mobile-only.
        return [
            'en' => ['native' => 'English',   'name' => 'English',   'flag' => '🇬🇧'],
            'hi' => ['native' => 'हिन्दी',      'name' => 'Hindi',     'flag' => '🇮🇳'],
            'mr' => ['native' => 'मराठी',       'name' => 'Marathi',   'flag' => '🇮🇳'],
            'gu' => ['native' => 'ગુજરાતી',    'name' => 'Gujarati',  'flag' => '🇮🇳'],
            'pa' => ['native' => 'ਪੰਜਾਬੀ',      'name' => 'Punjabi',   'flag' => '🇮🇳'],
            'ta' => ['native' => 'தமிழ்',       'name' => 'Tamil',     'flag' => '🇮🇳'],
            'te' => ['native' => 'తెలుగు',      'name' => 'Telugu',    'flag' => '🇮🇳'],
            'kn' => ['native' => 'ಕನ್ನಡ',       'name' => 'Kannada',   'flag' => '🇮🇳'],
            'bn' => ['native' => 'বাংলা',       'name' => 'Bengali',   'flag' => '🇮🇳'],
        ];
    }
}

if (! function_exists('user_avatar_url')) {
    /**
     * Resolve a user's avatar URL: an uploaded profile image wins over a social
     * provider picture. Returns null when the user has neither (caller shows a
     * fallback). Accepts any user array; defaults to the current user.
     */
    function user_avatar_url(?array $user = null): ?string
    {
        $user ??= function_exists('current_user') ? current_user() : null;
        if (! $user) {
            return null;
        }
        if (! empty($user['profile_image'])) {
            return base_url('uploads/users/' . $user['profile_image']);
        }
        if (! empty($user['avatar_url'])) {
            return $user['avatar_url'];
        }
        return null;
    }
}

if (! function_exists('user_avatar')) {
    /**
     * Render a user's avatar as an <img> when one exists, else a person-icon
     * fallback bubble. `$class` is applied to both so sizing stays consistent.
     */
    function user_avatar(?array $user = null, string $class = 'avatar-sm', string $fallbackIcon = 'bi-person'): string
    {
        $url = user_avatar_url($user);
        if ($url !== null) {
            return '<img src="' . esc($url, 'attr') . '" class="' . esc($class, 'attr') . '" alt="avatar" referrerpolicy="no-referrer">';
        }
        return '<span class="' . esc($class, 'attr') . ' avatar-fallback"><i class="bi ' . esc($fallbackIcon, 'attr') . '"></i></span>';
    }
}

if (! function_exists('profile_score')) {
    /**
     * Profile completeness score. Each criterion contributes its weight when
     * satisfied; the total is normalised to a 0-100 percentage with a strength
     * label. Returns the percent, label, colour and a per-item checklist so the
     * profile page can show what is still pending.
     *
     * @return array{percent:int,label:string,color:string,done:int,total:int,items:list<array{key:string,label:string,done:bool,hint:string,weight:int}>}
     */
    function profile_score(array $u): array
    {
        $has = static fn (string $k): bool => isset($u[$k]) && trim((string) $u[$k]) !== '';

        $criteria = [
            ['key' => 'name',    'label' => 'Full name added',        'weight' => 15, 'done' => $has('name'),
             'hint' => 'Add your full name.'],
            ['key' => 'email',   'label' => 'Email address added',    'weight' => 15, 'done' => $has('email'),
             'hint' => 'Add a valid email address.'],
            ['key' => 'mobile',  'label' => 'Mobile number added',    'weight' => 20, 'done' => $has('mobile'),
             'hint' => 'Add your mobile number.'],
            ['key' => 'avatar',  'label' => 'Profile photo uploaded', 'weight' => 25,
             'done' => $has('profile_image') || $has('avatar_url'),
             'hint' => 'Upload a profile photo.'],
            ['key' => 'password','label' => 'Password set',           'weight' => 15, 'done' => $has('password'),
             'hint' => 'Set a password so you can sign in without a social account.'],
            ['key' => 'social',  'label' => 'Social account linked',  'weight' => 10, 'done' => $has('provider_id'),
             'hint' => 'Link a Google account for one-click sign-in.'],
        ];

        return score_from_criteria($criteria);
    }
}

if (! function_exists('score_from_criteria')) {
    /**
     * Turn a weighted criteria list into a normalised score result. Each item is
     * `['label'=>, 'weight'=>int, 'done'=>bool, 'hint'=>?]`. Shared by
     * profile_score() and company_score().
     *
     * @param list<array{label:string,weight:int,done:bool,hint?:string,key?:string}> $criteria
     * @return array{percent:int,label:string,color:string,done:int,total:int,items:array}
     */
    function score_from_criteria(array $criteria): array
    {
        $total   = array_sum(array_column($criteria, 'weight'));
        $earned  = array_sum(array_map(static fn ($c) => $c['done'] ? $c['weight'] : 0, $criteria));
        $percent = $total > 0 ? (int) round($earned / $total * 100) : 0;
        $doneCnt = count(array_filter($criteria, static fn ($c) => $c['done']));

        if ($percent >= 100) {
            [$label, $color] = ['Complete', 'success'];
        } elseif ($percent >= 75) {
            [$label, $color] = ['Strong', 'primary'];
        } elseif ($percent >= 50) {
            [$label, $color] = ['Good', 'info'];
        } elseif ($percent >= 25) {
            [$label, $color] = ['Basic', 'warning'];
        } else {
            [$label, $color] = ['Weak', 'danger'];
        }

        return [
            'percent' => $percent,
            'label'   => $label,
            'color'   => $color,
            'done'    => $doneCnt,
            'total'   => count($criteria),
            'items'   => $criteria,
        ];
    }
}

if (! function_exists('company_score')) {
    /**
     * Company profile completeness score — how fully a firm's books/compliance
     * details are filled in. Mirrors profile_score() but for a company record.
     *
     * @return array{percent:int,label:string,color:string,done:int,total:int,items:array}
     */
    function company_score(array $c): array
    {
        $has     = static fn (string $k): bool => isset($c[$k]) && trim((string) $c[$k]) !== '';
        $gst     = strtoupper(trim((string) ($c['gst_number'] ?? '')));
        $gstOk   = $gst !== '' && preg_match(\App\Models\CompanyModel::GST_REGEX, $gst) === 1;

        $criteria = [
            ['key' => 'name',     'label' => 'Company name set',        'weight' => 10, 'done' => $has('name'),
             'hint' => 'Add the company / firm name.'],
            ['key' => 'fy',       'label' => 'Financial year set',      'weight' => 10, 'done' => $has('financial_year_from'),
             'hint' => 'Set the financial year start.'],
            ['key' => 'books',    'label' => 'Books beginning date set','weight' => 5,  'done' => $has('books_beginning_from'),
             'hint' => 'Set the books-beginning date.'],
            ['key' => 'state',    'label' => 'State selected',          'weight' => 10, 'done' => $has('state'),
             'hint' => 'Select the company state.'],
            ['key' => 'country',  'label' => 'Country selected',        'weight' => 5,  'done' => $has('country'),
             'hint' => 'Select the country.'],
            ['key' => 'gsttype',  'label' => 'GST registration type',   'weight' => 10, 'done' => $has('gst_registration_type'),
             'hint' => 'Choose the GST registration type.'],
            ['key' => 'gstno',    'label' => 'Valid GSTIN added',       'weight' => 20, 'done' => $gstOk,
             'hint' => 'Add a valid 15-character GSTIN.'],
            ['key' => 'biztype',  'label' => 'Business type set',       'weight' => 10, 'done' => $has('business_type'),
             'hint' => 'Select the business type.'],
            ['key' => 'mobile',   'label' => 'Contact mobile added',    'weight' => 10, 'done' => $has('mobile'),
             'hint' => 'Add a contact mobile number.'],
            ['key' => 'email',    'label' => 'Contact email added',     'weight' => 10, 'done' => $has('email'),
             'hint' => 'Add a contact email address.'],
        ];

        return score_from_criteria($criteria);
    }
}
