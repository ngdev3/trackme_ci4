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
$accent    = $color((string) setting('accent_color', '#6610f2'), '#6610f2');
$themeMode = setting('theme_mode', 'light') === 'dark' ? 'dark' : 'light';

$toastSuccess = $color((string) setting('toast_success_color', '#198754'), '#198754');
$toastError   = $color((string) setting('toast_error_color', '#dc3545'), '#dc3545');
$toastWarning = $color((string) setting('toast_warning_color', '#ffc107'), '#ffc107');
$toastInfo    = $color((string) setting('toast_info_color', '#0dcaf0'), '#0dcaf0');
$swalConfirm  = $color((string) setting('sweetalert_confirm_color', '#198754'), '#198754');
$swalCancel   = $color((string) setting('sweetalert_cancel_color', '#dc3545'), '#dc3545');
?>
<script>
    // Server-driven theme mode (falls back to the client's saved choice via head.php).
    (function () {
        var mode = <?= json_encode($themeMode) ?>;
        if (!localStorage.getItem('erp-theme') && mode) {
            document.documentElement.setAttribute('data-bs-theme', mode);
        }
    })();
    window.ERP_SETTINGS = {
        primaryColor: <?= json_encode($primary) ?>,
        accentColor:  <?= json_encode($accent) ?>,
        weatherCity:  <?= json_encode((string) setting('weather_city', 'New Delhi')) ?>,
        weatherUnits: <?= json_encode((string) setting('weather_units', 'metric')) ?>,
        webPushEnabled: <?= setting('web_push_enabled', '1') === '1' ? 'true' : 'false' ?>,
        vapidPublicKey: <?= json_encode((string) global_setting('vapid_public_key', '')) ?>,
        alertColors: {
            alert:   <?= json_encode((string) setting('alert_color', '#0d6efd')) ?>,
            prompt:  <?= json_encode((string) setting('prompt_color', '#6610f2')) ?>,
            confirm: <?= json_encode((string) setting('confirm_color', '#fd7e14')) ?>,
            swalConfirm: <?= json_encode($swalConfirm) ?>,
            swalCancel:  <?= json_encode($swalCancel) ?>
        }
    };
</script>
<style id="erp-app-settings">
    :root {
        --bs-primary: <?= $primary ?>;
        --bs-primary-rgb: <?= $hexToRgb($primary) ?>;
        --bs-link-color: <?= $primary ?>;
        --erp-primary: <?= $primary ?>;
        --erp-primary-rgb: <?= $hexToRgb($primary) ?>;
        --erp-accent: <?= $accent ?>;
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
