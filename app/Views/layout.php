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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= $this->include('partials/head') ?>
    <?php foreach (($css ?? []) as $href): ?>
        <link rel="stylesheet" href="<?= $href ?>">
    <?php endforeach; ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<!-- Top page-loading progress bar -->
<div id="pageLoader" class="page-loader"><div class="page-loader-bar"></div></div>

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
                <?= flash_alerts() ?>
                <?= $content ?? '' ?>
            </div>
        </div>
    </main>

    <?= $this->include('partials/footer') ?>
</div>

<?= $this->include('partials/theme_panel') ?>

<script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/adminlte/adminlte.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/toastr/toastr.min.js') ?>"></script>
<script src="<?= base_url('assets/js/theme.js') ?>"></script>
<script src="<?= base_url('assets/js/notify.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>

<!-- Surface server flash messages as toasts -->
<?php if ($m = session()->getFlashdata('success')): ?>
    <script>document.addEventListener('DOMContentLoaded',function(){erpNotify('success',<?= json_encode($m) ?>);});</script>
<?php endif; ?>
<?php if ($m = session()->getFlashdata('error')): ?>
    <script>document.addEventListener('DOMContentLoaded',function(){erpNotify('error',<?= json_encode($m) ?>);});</script>
<?php endif; ?>

<!-- Page-specific scripts -->
<?php foreach (($js ?? []) as $src): ?>
    <script src="<?= $src ?>"></script>
<?php endforeach; ?>
<?php if (! empty($inline_js)): ?>
    <script><?= $inline_js ?></script>
<?php endif; ?>
</body>
</html>
