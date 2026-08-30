<?php
/**
 * Master layout (slice template).
 *
 * Controllers render a page with only its name + data via
 * BaseController::render(), e.g.:
 *
 *     return $this->render('index', [
 *         'title'      => 'Users',
 *         'breadcrumb' => [['label' => 'Users']],
 *         'rows'       => $rows,
 *     ]);
 *
 * The controller resolves the page to its module's view and renders it into
 * $content; this layout stitches header + navbar + sidebar + content + footer.
 *
 * Optional data keys:
 *   $title       string   page + browser title
 *   $breadcrumb  array    items for components/breadcrumb
 *   $content     string   pre-rendered page HTML (set by render())
 *   $css         array    extra stylesheet URLs (head)
 *   $js          array    extra script URLs (before </body>)
 *   $inline_js   string   inline JS appended after page scripts
 */
$flashToasts = [];
foreach (['success', 'error', 'warning', 'info'] as $flashType) {
    if ($flashMessage = session()->getFlashdata($flashType)) {
        $flashToasts[$flashType] = $flashMessage;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->include('partials/head') ?>
    <!-- Shared data-table + hover-card design system (used by every module's listing) -->
    <link rel="stylesheet" href="<?= erp_asset('assets/css/erp-table.css') ?>">
    <!-- Shared list/index design system (cust-* components — canonical Customers look) -->
    <link rel="stylesheet" href="<?= erp_asset('assets/css/erp-list.css') ?>">
    <?php foreach (($css ?? []) as $href): ?>
        <link rel="stylesheet" href="<?= erp_asset($href) ?>">
    <?php endforeach; ?>
</head>
<body class="layout-fixed sidebar-expand-lg">

<!-- Top page-loading progress bar -->
<div id="pageLoader" class="page-loader"><div class="page-loader-bar"></div></div>

<?php if (session('impersonator_id')): ?>
    <div class="impersonate-bar">
        <span><i class="bi bi-incognito me-1"></i> You are accessing <strong><?= esc(session('user_name')) ?></strong>'s account
            &mdash; signed in as Super&nbsp;Admin <strong><?= esc(session('impersonator_name')) ?></strong>.</span>
        <a href="<?= site_url('impersonate/stop') ?>" class="btn btn-sm btn-light fw-semibold"><i class="bi bi-box-arrow-left me-1"></i>Return to Super Admin</a>
    </div>
<?php endif; ?>

<?php $__cu = function_exists('current_user') ? current_user() : null; ?>
<?php if ($__cu && empty($__cu['email_verified_at']) && ($__cu['account_type'] ?? '') !== 'super_admin' && ! session('impersonator_id')): ?>
    <div class="verify-bar">
        <span><i class="bi bi-envelope-exclamation"></i> Please validate your email <strong><?= esc($__cu['email']) ?></strong> within a week to keep using your account without restrictions.</span>
        <form action="<?= site_url('verify-email/resend') ?>" method="post">
            <?= csrf_field() ?>
            <button type="submit"><i class="bi bi-arrow-repeat"></i> Resend link</button>
        </form>
    </div>
    <style nonce="{csp-style-nonce}">
    .verify-bar{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;padding:9px 16px;background:linear-gradient(90deg,#fff4e0,#ffe9c7);color:#8a5a00;font-size:13.5px;font-weight:600;border-bottom:1px solid #f6dcae}
    .verify-bar .bi{font-size:16px}
    .verify-bar form{margin:0}
    .verify-bar button{border:0;border-radius:8px;padding:5px 12px;background:#b7791f;color:#fff;font-weight:700;font-size:12.5px;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
    .verify-bar button:hover{background:#8a5a00}
    </style>
<?php endif; ?>

<div class="app-wrapper">

    <?= $this->include('partials/navbar') ?>
    <?= $this->include('partials/sidebar') ?>

    <main class="app-main">
        <div class="erp-info-strip">
            <i class="bi bi-info-circle"></i>
            <span>Advanced features are inside the profile dropdown.</span>
        </div>

        <!-- Content header / breadcrumb -->
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h3 class="mb-0"><?= esc($page_title ?? ($title ?? 'Dashboard')) ?></h3>
                    </div>
                    <div class="col-sm-6">
                        <?php if (! empty($breadcrumb)): ?>
                            <?= view('components/breadcrumb', ['items' => $breadcrumb]) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content (sliced page) -->
        <div class="app-content">
            <div class="container-fluid">
                <?php
                // Subscription lifecycle banner. When a paid plan / trial has lapsed
                // access silently downgrades to the Basic floor (data is preserved),
                // so tell the customer clearly and give them a way to fix it. Also
                // warns during the final days of an active trial.
                helper('subscription');
                if (function_exists('is_super_admin_account') && ! is_super_admin_account()):
                    $subSt      = sub_state();
                    $expReason  = $subSt['reason'] ?? '';
                    $subExpTs   = ! empty($subSt['expires_at']) ? strtotime((string) $subSt['expires_at']) : null;
                    $subDaysOut = $subExpTs ? (int) ceil(($subExpTs - time()) / 86400) : null;
                    if (in_array($expReason, ['expired', 'trial_expired'], true)):
                        $wasTrial = $expReason === 'trial_expired';
                ?>
                    <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
                        <span><i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <?php if ($wasTrial): ?>
                                Your <strong>free trial has ended</strong> — premium features are now locked. Subscribe to unlock them again (your data is safe).
                            <?php else: ?>
                                Your <strong>subscription has expired</strong> — premium features are now locked. Renew to restore full access (your data is safe).
                            <?php endif; ?>
                        </span>
                        <a href="<?= site_url('subscription') ?>" class="btn btn-sm btn-warning"><i class="bi bi-gem me-1"></i> <?= $wasTrial ? 'Subscribe now' : 'Renew now' ?></a>
                    </div>
                <?php elseif ($expReason === 'trial' && $subDaysOut !== null && $subDaysOut <= 3): ?>
                    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
                        <span><i class="bi bi-hourglass-split me-1"></i>
                            Your free trial ends in <strong><?= max(0, $subDaysOut) ?> day<?= $subDaysOut === 1 ? '' : 's' ?></strong>. Subscribe now to keep premium features without interruption.
                        </span>
                        <a href="<?= site_url('subscription') ?>" class="btn btn-sm btn-primary"><i class="bi bi-gem me-1"></i> View plans</a>
                    </div>
                <?php endif; endif; ?>
                <?= $content ?? '' ?>
            </div>
        </div>
    </main>

    <?= $this->include('partials/footer') ?>
</div>


<script src="<?= erp_asset('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= erp_asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= erp_asset('assets/vendor/adminlte/adminlte.min.js') ?>"></script>
<script src="<?= erp_asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= erp_asset('assets/vendor/toastr/toastr.min.js') ?>"></script>
<script src="<?= erp_asset('assets/vendor/flatpickr/flatpickr.min.js') ?>"></script>
<script src="<?= erp_asset('assets/js/theme.js') ?>"></script>
<script src="<?= erp_asset('assets/js/notify.js') ?>"></script>
<script src="<?= erp_asset('assets/js/webpush.js') ?>"></script>
<script nonce="{csp-script-nonce}">
window.ERP_BASE = '<?= rtrim(site_url(), '/') ?>/';
// Refresh a form's CSRF token from the server, then submit — so long-open or
// stale pages (e.g. auto-submitting camera/audio capture inputs) never 403.
window.erpFreshSubmit = function (form) {
    fetch(window.ERP_BASE + 'csrf-token', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
            if (j && j.token) {
                var fld = form.querySelector('input[name="' + (j.name || 'csrf_test_name') + '"]')
                       || form.querySelector('input[type="hidden"][value][name*="csrf"]');
                if (fld) { fld.value = j.token; }
                var m = document.querySelector('meta[name="csrf-token"]'); if (m) { m.setAttribute('content', j.token); }
            }
        })
        .catch(function () {})
        .finally(function () { form.submit(); });
};
</script>
<script src="<?= erp_asset('assets/js/reminder-popup.js') ?>"></script>
<script src="<?= erp_asset('assets/js/app.js') ?>"></script>

<!-- Surface server flash messages as bottom-right toasts -->
<?php if (! empty($flashToasts)): ?>
    <script nonce="{csp-script-nonce}">
        (function () {
            var messages = <?= json_encode($flashToasts) ?>;
            var show = function () {
                if (!window.erpNotify) { return; }
                Object.keys(messages).forEach(function (type) {
                    erpNotify(type, messages[type]);
                });
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', show);
            } else {
                show();
            }
        })();
    </script>
<?php endif; ?>

<!-- Shared table hover-card engine (renders #erpTip for any .erp-hover[data-tip]) -->
<script src="<?= erp_asset('assets/js/erp-table.js') ?>"></script>
<!-- Page-specific scripts -->
<?php foreach (($js ?? []) as $src): ?>
    <script src="<?= erp_asset($src) ?>"></script>
<?php endforeach; ?>
<?php if (! empty($inline_js)): ?>
    <script nonce="{csp-script-nonce}"><?= $inline_js ?></script>
<?php endif; ?>
</body>
</html>
