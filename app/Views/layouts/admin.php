<?php
/**
 * Admin shell — CI4 port of application/views/layout.php.
 * DECISION (§6.12a): keep the legacy Metronic theme. This uses the SAME
 * Metronic assets (copied verbatim into public/assets) + the extracted custom
 * CSS (admin_shell.css = the CI3 inline <style> block, byte-for-byte), so the
 * look is identical. The 20+ header widgets (session-lock, network, clock,
 * global search, notifications, theme switcher, change-firm) and the full dual
 * RBAC menu port incrementally; this is the clean, working core.
 *
 * Render a feature view inside this shell from a controller:
 *     return _layout('Admin\invoice\listing', $data);
 */
helper(['url', 'app', 'permission']);
$ctx  = service('fyContext');
$user = $ctx->userInfo();
$firm = $ctx->fyRow();
$contentView = $contentView ?? null;
$toast = session()->getFlashdata('cr_toast');
?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <title><?= esc($title ?? 'C R Industries ERP') ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta name="robots" content="noindex, nofollow" />

    <script src="<?= base_url('assets/global/plugins/jquery-3.3.1.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/jquery-migrate.min.js') ?>"></script>

    <link href="<?= base_url('assets/admin/assets/css/bootstrap.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/admin/assets/css/layout.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/global/plugins/font-awesome/css/font-awesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/global/plugins/simple-line-icons/simple-line-icons.min.css') ?>" rel="stylesheet">
    <!-- CI3 plugin CSS (Change Firm modal, forms, pickers, tables, alerts) -->
    <link href="<?= base_url('assets/global/plugins/select2/select2.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/global/plugins/bootstrap-fileinput/bootstrap-fileinput.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/datepicker/jquery-ui.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/datepicker/yearpicker.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/jquery-alert-dialogs/css/jquery.alerts.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/acc_picker.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/hsn_picker.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/admin_shell.css') ?>" rel="stylesheet">
</head>

<body class="app page-header-fixed page-sidebar-closed-hide-logo">

    <div>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-inner">
                <div class="sidebar-logo">
                    <div class="peers ai-c fxw-nw">
                        <div class="peer peer-greed">
                            <a class="sidebar-link td-n" href="<?= base_url('admin/dashboard') ?>">
                                <div class="peers ai-c fxw-nw">
                                    <div class="peer">
                                        <div class="logo"><img src="<?= base_url('assets/images/logo.png') ?>" alt=""></div>
                                    </div>
                                    <div class="peer peer-greed">
                                        <h5 class="lh-1 mB-0 logo-text"><?= esc($firm->firm_name ?? 'C R Industries') ?></h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <?= $this->include('\App\Modules\Admin\Views\elements\left_menu') ?>
            </div>
        </div>
        <div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>

        <!-- Page container -->
        <div class="page-container">
            <?= $this->include('\App\Modules\Admin\Views\elements\top_nav') ?>

            <div class="page-content" style="padding-top:0;">
                <?php if ($contentView !== null): ?>
                    <?= view($contentView, $contentData ?? []) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($toast): ?>
        <script>window.__CR_TOAST__ = <?= json_encode($toast) ?>;</script>
    <?php endif; ?>

    <script src="<?= base_url('assets/admin/assets/js/vendor.js') ?>"></script>
    <script src="<?= base_url('assets/admin/assets/js/bundle.js') ?>"></script>
    <script src="<?= base_url('assets/global/scripts/metronic.js') ?>"></script>

    <!-- CI3 plugin JS (jQuery is in <head>; Bootstrap comes from vendor.js). -->
    <script src="<?= base_url('assets/global/plugins/jquery-ui/jquery-ui.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/jquery.blockui.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/jquery.cokie.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/uniform/jquery.uniform.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/select2/select2.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/bootstrap-select/bootstrap-select.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/jquery-multi-select/js/jquery.multi-select.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/datatables/media/js/jquery.dataTables.min.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js') ?>"></script>
    <script src="<?= base_url('assets/global/plugins/bootstrap-fileinput/bootstrap-fileinput.js') ?>"></script>
    <script src="<?= base_url('assets/datepicker/yearpicker.js') ?>"></script>
    <script src="<?= base_url('assets/select/fSelect.js') ?>"></script>
    <script src="<?= base_url('assets/jquery-alert-dialogs/js/jquery.alerts.js') ?>"></script>
    <script src="<?= base_url('assets/admin/layout4/scripts/layout.js') ?>"></script>
    <script src="<?= base_url('assets/admin/pages/scripts/table-managed.js') ?>"></script>
    <script src="<?= base_url('assets/admin/pages/scripts/form-samples.js') ?>"></script>
    <script src="<?= base_url('assets/admin/pages/scripts/components-dropdowns.js') ?>"></script>
    <script src="<?= base_url('assets/js/cr_notify.js') ?>"></script>
    <script src="<?= base_url('assets/js/acc_picker.js') ?>"></script>
    <script src="<?= base_url('assets/js/hsn_picker.js') ?>"></script>
    <script src="<?= base_url('assets/js/app_i18n.js') ?>"></script>

    <script src="<?= base_url('assets/js/top_nav.js') ?>"></script>
    <script src="<?= base_url('assets/js/web_lock.js') ?>"></script>
    <script>
        // Sidebar dropdown accordion (Metronic layout4).
        $(function () {
            $('.sidebar-menu li.dropdown > a.dropdown-toggle').on('click', function (e) {
                e.preventDefault();
                $(this).parent('li').toggleClass('open').siblings('.dropdown.open').removeClass('open');
            });
        });
        // Robust collapse/expand — EXACT CI3 fix (application/views/layout.php). The
        // sidebar collapses via body.app.is-collapsed. The theme bundle also toggles
        // is-collapsed AND fires a synthetic 'resize' ~300ms after a click, which would
        // snap the sidebar back on desktop; clone-rebinding the toggle drops the bundle
        // handlers so a single clean toggle wins.
        (function () {
            var app = document.querySelector('body.app');
            var sidebar = document.querySelector('.sidebar');
            var backdrop = document.getElementById('mobileSidebarBackdrop');
            if (!app || !sidebar || !backdrop) { return; }

            function isMobileSidebar() { return window.matchMedia && window.matchMedia('(max-width: 991px)').matches; }
            function closeMobileSidebar() { if (isMobileSidebar()) { app.classList.remove('is-collapsed'); } }

            backdrop.addEventListener('click', closeMobileSidebar);
            backdrop.addEventListener('touchstart', function (event) { event.preventDefault(); closeMobileSidebar(); }, { passive: false });

            document.addEventListener('click', function (event) {
                if (!isMobileSidebar() || !app.classList.contains('is-collapsed')) { return; }
                if (sidebar.contains(event.target) || event.target.closest('.sidebar-toggle')) { return; }
                closeMobileSidebar();
            });

            var wasMobile = isMobileSidebar();
            window.addEventListener('resize', function () {
                var nowMobile = isMobileSidebar();
                if (wasMobile && !nowMobile) { app.classList.remove('is-collapsed'); }
                wasMobile = nowMobile;
            });

            [].slice.call(document.querySelectorAll('.sidebar-toggle')).forEach(function (el) {
                var fresh = el.cloneNode(true);
                el.parentNode.replaceChild(fresh, el);
                fresh.addEventListener('click', function (e) {
                    e.preventDefault();
                    app.classList.toggle('is-collapsed');
                });
            });
        })();
    </script>
</body>
</html>
