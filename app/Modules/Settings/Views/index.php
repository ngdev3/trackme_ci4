<?php
/** Minimal Settings page. */
$canEdit = can('settings', 'edit');
$av = static fn (string $k) => esc($appearance[$k] ?? ($appearanceDefaults[$k] ?? ''), 'attr');
$mode = $appearance['theme_mode'] ?? 'system';
?>

<div class="settings-minimal">
    <?php if (session('is_superadmin')): ?>
        <form action="<?= site_url('settings/save') ?>" method="post" class="card erp-panel mb-3">
            <?= csrf_field() ?>
            <div class="card-header erp-panel-title">
                <h3 class="card-title mb-0"><i class="bi bi-shield-lock me-1 text-primary"></i>Application</h3>
            </div>
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Application Name</label>
                    <input type="text" name="app_name" class="form-control" maxlength="60"
                           value="<?= esc(setting('app_name', 'ERP Admin')) ?>" placeholder="ERP Admin">
                </div>
                <?php if ($canEdit): ?>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Save Name</button>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>

    <div class="appearance-shell" id="appearanceStudio"
         data-save-url="<?= site_url('settings/appearance') ?>"
         data-reset-url="<?= site_url('settings/appearance/reset') ?>">
        <div class="appearance-workspace">
            <section class="appearance-editor erp-panel">
                <div class="appearance-head">
                    <div>
                        <span class="appearance-kicker"><i class="bi bi-palette2"></i> Personal appearance</span>
                        <h3>Appearance</h3>
                        <p>System Default follows your operating system automatically. Changes preview instantly and save to your login.</p>
                    </div>
                    <span class="badge text-bg-primary">Live preview</span>
                </div>

                <div class="appearance-group">
                    <label class="form-label fw-semibold">Theme Mode</label>
                    <div class="appearance-mode" role="radiogroup" aria-label="Theme mode">
                        <?php foreach (['system' => ['System Default', 'bi-laptop'], 'light' => ['Light', 'bi-sun'], 'dark' => ['Dark', 'bi-moon-stars']] as $k => $meta): ?>
                            <label class="appearance-mode-option <?= $mode === $k ? 'active' : '' ?>">
                                <input type="radio" name="theme_mode" value="<?= esc($k, 'attr') ?>" data-appearance-field <?= $mode === $k ? 'checked' : '' ?>>
                                <i class="bi <?= esc($meta[1]) ?>"></i>
                                <span><?= esc($meta[0]) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="appearance-grid">
                    <?php
                    $colorControls = [
                        'font_color' => ['Font Color', 'bi-type'],
                        'background_color' => ['Background Color', 'bi-window'],
                        'primary_color' => ['Primary Color', 'bi-record-circle'],
                        'secondary_color' => ['Secondary / Accent Color', 'bi-stars'],
                        'sidebar_color' => ['Sidebar Color', 'bi-layout-sidebar'],
                        'header_color' => ['Header / Navbar Color', 'bi-layout-text-window-reverse'],
                    ];
                    foreach ($colorControls as $key => [$label, $icon]): ?>
                        <label class="appearance-color">
                            <span><i class="bi <?= esc($icon) ?>"></i><?= esc($label) ?></span>
                            <input type="color" name="<?= esc($key, 'attr') ?>" value="<?= $av($key) ?>" data-appearance-field>
                            <code data-color-value="<?= esc($key, 'attr') ?>"><?= esc($appearance[$key] ?? $appearanceDefaults[$key]) ?></code>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="appearance-actions">
                    <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-primary" id="appearanceSave"><i class="bi bi-cloud-check me-1"></i>Save Appearance</button>
                        <button type="button" class="btn btn-outline-danger" id="appearanceReset"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Default</button>
                    <?php endif; ?>
                </div>
            </section>

            <aside class="appearance-preview erp-panel">
                <div class="appearance-preview-top">
                    <span></span><span></span><span></span>
                </div>
                <div class="appearance-preview-body">
                    <div class="appearance-preview-sidebar">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <i class="bi bi-receipt"></i>
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="appearance-preview-main">
                        <div class="preview-card wide"></div>
                        <div class="preview-row"><span></span><span></span><span></span></div>
                        <button type="button" class="btn btn-primary btn-sm">Primary action</button>
                        <button type="button" class="btn btn-outline-primary btn-sm">Secondary action</button>
                    </div>
                </div>
            </aside>
        </div>

        <section class="appearance-presets erp-panel">
            <div class="appearance-section-title">
                <h3>Professional Presets</h3>
                <p>Apply a curated palette in one click, then fine-tune any color.</p>
            </div>
            <div class="appearance-preset-grid">
                <?php foreach ($appearancePresets as $preset): ?>
                    <button type="button" class="appearance-preset" data-appearance-preset='<?= esc(json_encode($preset), 'attr') ?>'>
                        <span class="preset-strip">
                            <i style="background: <?= esc($preset['primary_color'], 'attr') ?>"></i>
                            <i style="background: <?= esc($preset['secondary_color'], 'attr') ?>"></i>
                            <i style="background: <?= esc($preset['sidebar_color'], 'attr') ?>"></i>
                            <i style="background: <?= esc($preset['header_color'], 'attr') ?>"></i>
                        </span>
                        <strong><?= esc($preset['name']) ?></strong>
                        <small><?= esc($preset['theme_mode'] === 'system' ? 'System Default' : ucfirst($preset['theme_mode'])) ?></small>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
