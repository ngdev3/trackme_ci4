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
    <?php foreach (($css ?? []) as $href): ?>
        <link rel="stylesheet" href="<?= erp_asset($href) ?>">
    <?php endforeach; ?>
</head>
<body class="layout-fixed sidebar-expand-lg">
<!-- Language loader (hides the page until the chosen language applies) + first-visit chooser -->
<?= $this->include('partials/lang_widgets') ?>

<!-- Top page-loading progress bar -->
<div id="pageLoader" class="page-loader"><div class="page-loader-bar"></div></div>

<?php if (session('impersonator_id')): ?>
    <div class="impersonate-bar">
        <span><i class="bi bi-incognito me-1"></i> You are accessing <strong><?= esc(session('user_name')) ?></strong>'s account
            &mdash; signed in as Super&nbsp;Admin <strong><?= esc(session('impersonator_name')) ?></strong>.</span>
        <a href="<?= site_url('impersonate/stop') ?>" class="btn btn-sm btn-light fw-semibold"><i class="bi bi-box-arrow-left me-1"></i>Return to Super Admin</a>
    </div>
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
                <?= $content ?? '' ?>
            </div>
        </div>
    </main>

    <?= $this->include('partials/footer') ?>
</div>

<!-- In-app language translator (whole-UI, 12 languages) -->
<div id="google_translate_element" aria-hidden="true"></div>
<script>
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,hi,zh-CN,es,ar,fr,bn,pt,ru,ur,de,ja',
            autoDisplay: false
        }, 'google_translate_element');
    }
</script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script src="<?= erp_asset('assets/js/i18n.js') ?>"></script>

<script src="<?= erp_asset('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= erp_asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= erp_asset('assets/vendor/adminlte/adminlte.min.js') ?>"></script>
<script src="<?= erp_asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= erp_asset('assets/vendor/toastr/toastr.min.js') ?>"></script>
<script src="<?= erp_asset('assets/vendor/flatpickr/flatpickr.min.js') ?>"></script>
<script src="<?= erp_asset('assets/js/theme.js') ?>"></script>
<script src="<?= erp_asset('assets/js/notify.js') ?>"></script>
<script src="<?= erp_asset('assets/js/webpush.js') ?>"></script>
<script>
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
    <script>
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

<!-- Page-specific scripts -->
<?php foreach (($js ?? []) as $src): ?>
    <script src="<?= erp_asset($src) ?>"></script>
<?php endforeach; ?>
<?php if (! empty($inline_js)): ?>
    <script><?= $inline_js ?></script>
<?php endif; ?>
</body>
</html>
