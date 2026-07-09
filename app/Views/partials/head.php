<?php
/**
 * <head> contents — shared by the master layout.
 * Page-specific CSS is appended by the layout from $css (array of URLs).
 */
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title ?? 'Dashboard') ?> &middot; <?= esc(setting('app_name', 'ERP Admin')) ?></title>

<link rel="icon" type="image/svg+xml" href="<?= erp_asset('assets/img/favicon.svg') ?>">

<meta name="csrf-token" content="<?= csrf_hash() ?>" data-name="<?= csrf_token() ?>">

<?php
$headColor = static function (string $val, string $default): string {
    return preg_match('/^#[0-9a-fA-F]{6}$/', $val) ? $val : $default;
};
$headMode = in_array(setting('theme_mode', 'light'), ['light', 'dark', 'system'], true) ? setting('theme_mode', 'light') : 'light';
$headAppearance = [
    'theme_mode'       => $headMode,
    'font_color'       => $headColor((string) setting('font_color', '#1f2a3d'), '#1f2a3d'),
    'background_color' => $headColor((string) setting('background_color', '#eef2f8'), '#eef2f8'),
    'primary_color'    => $headColor((string) setting('primary_color', '#0d6efd'), '#0d6efd'),
    'secondary_color'  => $headColor((string) setting('secondary_color', '#6610f2'), '#6610f2'),
    'sidebar_color'    => $headColor((string) setting('sidebar_color', '#0e1626'), '#0e1626'),
    'header_color'     => $headColor((string) setting('header_color', '#ffffff'), '#ffffff'),
];
?>
<!-- Prevent theme flash: apply server-backed user appearance before paint -->
<script>
    window.APP_BASE_URL = <?= json_encode(rtrim(site_url(), '/')) ?>;
    window.ERP_APPEARANCE = <?= json_encode($headAppearance) ?>;
    (function () {
        var a = window.ERP_APPEARANCE || {};
        var mode = a.theme_mode === 'system'
            ? (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : (a.theme_mode === 'dark' ? 'dark' : 'light');
        var hx = function (h) { var m = String(h || '').replace('#', ''); var n = parseInt(m, 16); return (n >> 16 & 255) + ', ' + (n >> 8 & 255) + ', ' + (n & 255); };
        var r = document.documentElement;
        r.setAttribute('data-bs-theme', mode);
        r.setAttribute('data-erp-appearance-mode', a.theme_mode || 'light');
        r.style.setProperty('--bs-primary', a.primary_color);
        r.style.setProperty('--bs-primary-rgb', hx(a.primary_color));
        r.style.setProperty('--bs-secondary', a.secondary_color);
        r.style.setProperty('--bs-secondary-rgb', hx(a.secondary_color));
        r.style.setProperty('--erp-primary', a.primary_color);
        r.style.setProperty('--erp-primary-rgb', hx(a.primary_color));
        r.style.setProperty('--erp-secondary', a.secondary_color);
        r.style.setProperty('--erp-accent', a.secondary_color);
        r.style.setProperty('--erp-ink', a.font_color);
        r.style.setProperty('--erp-app-bg', a.background_color);
        r.style.setProperty('--erp-header-bg', a.header_color);
        r.style.setProperty('--erp-sidebar-custom', a.sidebar_color);
    })();
</script>

<!-- Gate the page until the chosen UI language has been applied (no untranslated flash) -->
<?= $this->include('partials/lang_boot') ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= erp_asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= erp_asset('assets/vendor/bootstrap/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= erp_asset('assets/vendor/adminlte/adminlte.min.css') ?>">
<link rel="stylesheet" href="<?= erp_asset('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
<link rel="stylesheet" href="<?= erp_asset('assets/vendor/toastr/toastr.min.css') ?>">
<link rel="stylesheet" href="<?= erp_asset('assets/vendor/flatpickr/flatpickr.min.css') ?>">
<link rel="stylesheet" href="<?= erp_asset('assets/css/app.css') ?>">
<link rel="stylesheet" href="<?= erp_asset('assets/css/i18n.css') ?>">

<!-- Server-driven web-app settings: theme colour + alert/toast colours -->
<?= $this->include('partials/app_settings') ?>
