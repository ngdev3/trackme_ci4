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
    <link href="<?= base_url('assets/admin/assets/css/bootstrap.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/admin/assets/css/layout.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/global/plugins/font-awesome/css/font-awesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/global/plugins/simple-line-icons/simple-line-icons.min.css') ?>" rel="stylesheet">
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

        <!-- Page container -->
        <div class="page-container">
            <div class="header navbar">
                <div class="header-container">
                    <ul class="nav-left">
                        <li><a id="sidebar-toggle" class="sidebar-toggle" href="javascript:void(0);"><i class="ti-menu"></i></a></li>
                    </ul>
                    <ul class="nav-right">
                        <li class="fy-block">
                            <span class="fy-content">
                                <i class="ti-briefcase"></i>&nbsp;
                                <strong><?= esc($firm->firm_name ?? '—') ?></strong>
                                &nbsp;·&nbsp; FY <?= esc($ctx->fyYear() ?? '—') ?>
                                &nbsp;·&nbsp; Firm #<?= esc($ctx->templateId() ?? '—') ?>
                            </span>
                        </li>
                        <li class="profile-block">
                            <span class="fy-content">
                                <i class="ti-user"></i>&nbsp;
                                <?= esc(trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) ?: 'User' ?>
                                <?php if ($ctx->isSuperAdmin()): ?><span class="label label-warning" style="margin-left:6px">SUPER</span><?php endif; ?>
                            </span>
                        </li>
                        <li class="web-lock-block">
                            <a href="<?= base_url('admin/auth/logout') ?>" title="Sign out"><i class="ti-power-off"></i></a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="page-content" style="padding-top:82px;">
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
    <script>
        // Minimal sidebar dropdown toggle (Metronic layout4 accordion) + collapse.
        $(function () {
            $('.sidebar-menu li.dropdown > a.dropdown-toggle').on('click', function (e) {
                e.preventDefault();
                $(this).parent('li').toggleClass('open').siblings('.dropdown.open').removeClass('open');
            });
            $('#sidebar-toggle, .sidebar-toggle').on('click', function (e) {
                e.preventDefault();
                $('body').toggleClass('sidebar-collapsed');
            });
        });
    </script>
</body>
</html>
