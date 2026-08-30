<?php
/**
 * Applies the web-app Settings (theme colour + alert/toast colours) server-side,
 * so they take effect for every user without a per-browser localStorage step.
 * Included from partials/head after app.css so these rules win.
 */
// Validate a hex colour so it can be written into CSS/JS without escaping
// mangling the value; fall back to a safe default when malformed.
$color = static function (string $val, string $default): string {
    return preg_match('/^#[0-9a-fA-F]{3,8}$/', $val) ? $val : $default;
};
$hexToRgb = static function (string $hex): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $n = @hexdec($hex ?: '0d6efd');
    return (($n >> 16) & 255) . ', ' . (($n >> 8) & 255) . ', ' . ($n & 255);
};

$primary   = $color((string) setting('primary_color', '#0d6efd'), '#0d6efd');
$secondary = $color((string) setting('secondary_color', '#6610f2'), '#6610f2');
$font      = $color((string) setting('font_color', '#1f2a3d'), '#1f2a3d');
$bg        = $color((string) setting('background_color', '#eef2f8'), '#eef2f8');
$sidebar   = $color((string) setting('sidebar_color', '#0e1626'), '#0e1626');
$header    = $color((string) setting('header_color', '#ffffff'), '#ffffff');
$themeMode = in_array(setting('theme_mode', 'system'), ['light', 'dark', 'system'], true) ? setting('theme_mode', 'system') : 'system';

$toastSuccess = '#198754';
$toastError   = '#dc3545';
$toastWarning = '#f59f00';
$toastInfo    = '#0d6efd';
$swalConfirm  = '#0d6efd';
$swalCancel   = '#6c757d';
$vapidPublicKey = '';
try {
    $vapidPublicKey = \App\Libraries\WebPush::ensureVapidKeys()['publicKey'];
} catch (\Throwable $e) {
    log_message('error', 'Unable to ensure VAPID keys: ' . $e->getMessage());
}
?>
<script nonce="{csp-script-nonce}">
    window.ERP_APPEARANCE = {
        theme_mode: <?= json_encode($themeMode) ?>,
        font_color: <?= json_encode($font) ?>,
        background_color: <?= json_encode($bg) ?>,
        primary_color: <?= json_encode($primary) ?>,
        secondary_color: <?= json_encode($secondary) ?>,
        sidebar_color: <?= json_encode($sidebar) ?>,
        header_color: <?= json_encode($header) ?>
    };
    window.ERP_SETTINGS = {
        primaryColor: <?= json_encode($primary) ?>,
        accentColor:  <?= json_encode($secondary) ?>,
        weatherCity:  'New Delhi',
        weatherUnits: 'metric',
        webPushEnabled: true,
        vapidPublicKey: <?= json_encode($vapidPublicKey) ?>,
        alertColors: {
            alert:   '#0d6efd',
            prompt:  '#0d6efd',
            confirm: '#f59f00',
            swalConfirm: <?= json_encode($swalConfirm) ?>,
            swalCancel:  <?= json_encode($swalCancel) ?>
        }
    };
</script>
<style nonce="{csp-style-nonce}" id="erp-app-settings">
    :root {
        --bs-primary: <?= $primary ?>;
        --bs-primary-rgb: <?= $hexToRgb($primary) ?>;
        --bs-secondary: <?= $secondary ?>;
        --bs-secondary-rgb: <?= $hexToRgb($secondary) ?>;
        --bs-link-color: <?= $primary ?>;
        --bs-body-color: <?= $font ?>;
        --bs-body-color-rgb: <?= $hexToRgb($font) ?>;
        --bs-body-bg: <?= $bg ?>;
        --bs-body-bg-rgb: <?= $hexToRgb($bg) ?>;
        --erp-primary: <?= $primary ?>;
        --erp-primary-rgb: <?= $hexToRgb($primary) ?>;
        --erp-secondary: <?= $secondary ?>;
        --erp-accent: <?= $secondary ?>;
        --erp-ink: <?= $font ?>;
        --erp-app-bg: <?= $bg ?>;
        --erp-sidebar-custom: <?= $sidebar ?>;
        --erp-sidebar-1: color-mix(in srgb, <?= $sidebar ?> 92%, #fff);
        --erp-sidebar-2: color-mix(in srgb, <?= $sidebar ?> 80%, #000);
        --erp-header-bg: <?= $header ?>;
    }
    /* Toastr colours */
    #toast-container > .toast-success { background-color: <?= $toastSuccess ?> !important; }
    #toast-container > .toast-error   { background-color: <?= $toastError ?> !important; }
    #toast-container > .toast-warning { background-color: <?= $toastWarning ?> !important; }
    #toast-container > .toast-info    { background-color: <?= $toastInfo ?> !important; }
    /* SweetAlert2 buttons */
    .swal2-styled.swal2-confirm { background-color: <?= $swalConfirm ?> !important; }
    .swal2-styled.swal2-cancel  { background-color: <?= $swalCancel ?> !important; }
</style>
