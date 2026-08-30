<?php

/**
 * PDF theme resolver — CI4 port of application/helpers/pdf_theme_helper.php.
 * Resolves a firm's PDF colour theme (aa_pdf_theme) for a date + document
 * module; falls back to the global theme, then to defaults. Managed by
 * admin/pdf_theme (not yet ported → aa_pdf_theme is typically empty → defaults).
 */

use Config\Database;

if (! function_exists('pdf_theme_defaults')) {
    function pdf_theme_defaults(): array
    {
        return [
            'primary' => '#2f57a6', 'accent' => '#e0902a', 'total' => '#2f9e6f', 'title_alt' => '#17a2b8',
            'seal' => '#2f7d52', 'ink' => '#26313f', 'muted' => '#7b8794', 'border' => '#cdd9ec',
            'header_bg' => '#eef3fb', 'header_text' => '#3a4a63', 'hero_text' => '#ffffff', 'title_text' => '#ffffff',
            'th_text' => '#ffffff', 'total_text' => '#ffffff', 'watermark_col' => '#e9eef9',
            'watermark' => 1, 'seal_on' => 1, '_theme_name' => '',
        ];
    }
}

if (! function_exists('pdf_theme_sanitize_color')) {
    function pdf_theme_sanitize_color($c, $fallback = '#2f57a6')
    {
        $c = trim((string) $c);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : $fallback;
    }
}

if (! function_exists('pdf_theme_modules')) {
    function pdf_theme_modules(): array
    {
        return [
            'invoice' => 'Bill of Supply', 'taxinvoice' => 'Tax Invoice', 'cd_note' => 'Credit / Debit Note',
            'delivery_challan' => 'Delivery Challan', 'uninvoice' => 'Unregistered BOS', 'payment_receipt' => 'Purchase from Farmer',
        ];
    }
}

if (! function_exists('pdf_theme_config')) {
    function pdf_theme_config($templateId, $date = null, $module = 'invoice'): array
    {
        $out = pdf_theme_defaults();
        $db  = Database::connect();
        if (! $db->tableExists('aa_pdf_theme')) {
            return $out;
        }
        $date   = ($date && $date !== '0000-00-00') ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $module = trim((string) $module);
        $hasModules = $db->fieldExists('modules', 'aa_pdf_theme');

        foreach ([(int) $templateId, 0] as $tid) {
            $b = $db->table('aa_pdf_theme')
                ->where('status', 'Active')
                ->where('template_id', $tid)
                ->groupStart()->where('valid_from IS NULL', null, false)->orWhere('valid_from <=', $date)->groupEnd()
                ->groupStart()->where('valid_to IS NULL', null, false)->orWhere('valid_to >=', $date)->groupEnd();
            if ($module !== '' && $hasModules) {
                $b->groupStart()
                    ->where("(modules IS NULL OR modules = '' OR FIND_IN_SET('" . $db->escapeString($module) . "', modules) > 0)", null, false)
                    ->groupEnd();
            }
            $row = $b->orderBy('valid_from', 'desc')->orderBy('id', 'desc')->limit(1)->get()->getRow();
            if ($row) {
                $cfg = json_decode((string) $row->config, true);
                if (is_array($cfg)) {
                    foreach (['primary','accent','total','title_alt','seal','ink','muted','border','header_bg','header_text','hero_text','title_text','th_text','total_text','watermark_col'] as $k) {
                        if (isset($cfg[$k])) {
                            $out[$k] = pdf_theme_sanitize_color($cfg[$k], $out[$k]);
                        }
                    }
                    $out['watermark'] = isset($cfg['watermark']) ? (int) ! empty($cfg['watermark']) : $out['watermark'];
                    $out['seal_on']   = isset($cfg['seal_on']) ? (int) ! empty($cfg['seal_on']) : $out['seal_on'];
                }
                $out['_theme_name'] = $row->theme_name;
                return $out;
            }
        }
        return $out;
    }
}
