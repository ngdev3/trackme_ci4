<?php
/**
 * <head> contents — shared by the master layout.
 * Page-specific CSS is appended by the layout from $css (array of URLs).
 */
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title ?? 'Dashboard') ?> &middot; ERP Admin</title>

<meta name="csrf-token" content="<?= csrf_hash() ?>" data-name="<?= csrf_token() ?>">

<!-- Prevent theme flash: apply saved appearance + custom colours before paint -->
<script>
    window.APP_BASE_URL = <?= json_encode(rtrim(site_url(), '/')) ?>;
    (function () {
        var t = localStorage.getItem('erp-theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', t);
        try {
            var c = JSON.parse(localStorage.getItem('erp-custom-theme'));
            if (c) {
                var r = document.documentElement, hx = function (h){var m=h.replace('#','');if(m.length===3)m=m[0]+m[0]+m[1]+m[1]+m[2]+m[2];var n=parseInt(m,16);return (n>>16&255)+', '+(n>>8&255)+', '+(n&255);};
                if (c.primary){r.style.setProperty('--bs-primary',c.primary);r.style.setProperty('--bs-primary-rgb',hx(c.primary));r.style.setProperty('--bs-link-color',c.primary);r.style.setProperty('--erp-primary',c.primary);r.style.setProperty('--erp-primary-rgb',hx(c.primary));}
                if (c.secondary){r.style.setProperty('--bs-secondary',c.secondary);r.style.setProperty('--erp-secondary',c.secondary);}
                if (c.bg){r.style.setProperty('--erp-app-bg',c.bg);r.style.setProperty('--bs-body-bg',c.bg);}
                if (c.font){r.style.setProperty('--erp-ink',c.font);r.style.setProperty('--bs-body-color',c.font);}
            }
        } catch (e) {}
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/adminlte/adminlte.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/toastr/toastr.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
