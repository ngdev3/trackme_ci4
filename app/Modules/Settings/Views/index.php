<?php /** Sliced content — rendered inside app/Views/layout.php. */ ?>
<?php
    $val = static fn (string $k, $d = '') => esc($settings[$k] ?? $d);
    $isChecked = static fn (string $w) => ! empty($widgets[$w]);
    $canEdit = can('settings', 'edit');
?>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-appearance" type="button"><i class="bi bi-palette me-1"></i>Appearance</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-localization" type="button"><i class="bi bi-globe2 me-1"></i>Localization</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-alerts" type="button"><i class="bi bi-bell me-1"></i>Alerts &amp; Toasts</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-weather" type="button"><i class="bi bi-cloud-sun me-1"></i>Weather</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-widgets" type="button"><i class="bi bi-grid-1x2 me-1"></i>Widgets</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-push" type="button"><i class="bi bi-broadcast me-1"></i>Web Push</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-menu" type="button"><i class="bi bi-list-ol me-1"></i>Menu Order</button></li>
</ul>

<form action="<?= site_url('settings/save') ?>" method="post">
    <?= csrf_field() ?>
    <div class="tab-content">
        <!-- APPEARANCE -->
        <div class="tab-pane fade show active" id="tab-appearance">
            <?php if (session('is_superadmin')): ?>
                <div class="card erp-panel border-primary mb-3">
                    <div class="card-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-shield-lock me-1 text-primary"></i>Application Name</label>
                            <input type="text" name="app_name" class="form-control" maxlength="60"
                                   value="<?= esc(setting('app_name', 'ERP Admin')) ?>" placeholder="ERP Admin">
                            <div class="form-text">Super&nbsp;Admin only. Renames the app everywhere — title bar, sidebar, login screen &amp; footer.</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="card erp-panel">
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Theme mode</label>
                        <select name="theme_mode" class="form-select">
                            <?php foreach (['light' => 'Light', 'dark' => 'Dark'] as $k => $lbl): ?>
                                <option value="<?= $k ?>" <?= ($settings['theme_mode'] ?? 'light') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sidebar theme</label>
                        <select name="sidebar_theme" class="form-select">
                            <?php foreach (['dark' => 'Dark', 'light' => 'Light'] as $k => $lbl): ?>
                                <option value="<?= $k ?>" <?= ($settings['sidebar_theme'] ?? 'dark') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Primary colour</label>
                        <input type="color" name="primary_color" class="form-control form-control-color" value="<?= $val('primary_color', '#0d6efd') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Accent colour</label>
                        <input type="color" name="accent_color" class="form-control form-control-color" value="<?= $val('accent_color', '#6610f2') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- LOCALIZATION -->
        <div class="tab-pane fade" id="tab-localization">
            <div class="card erp-panel mb-3">
                <div class="card-header erp-panel-title"><h3 class="card-title mb-0"><i class="bi bi-currency-exchange me-1"></i> Currency &amp; Numbers</h3></div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Currency symbol</label>
                        <input type="text" name="currency_symbol" id="currency_symbol" class="form-control" maxlength="4" value="<?= $val('currency_symbol', '₹') ?>" placeholder="₹">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Currency code</label>
                        <input type="text" name="currency_code" class="form-control text-uppercase" maxlength="3" value="<?= $val('currency_code', 'INR') ?>" placeholder="INR">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Number grouping</label>
                        <select name="number_grouping" id="number_grouping" class="form-select">
                            <?php foreach (['indian' => 'Indian (12,34,567)', 'international' => 'International (1,234,567)'] as $k => $lbl): ?>
                                <option value="<?= $k ?>" <?= ($settings['number_grouping'] ?? 'indian') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Decimal places</label>
                        <select name="currency_decimals" id="currency_decimals" class="form-select">
                            <?php foreach ([0, 1, 2, 3] as $d): ?>
                                <option value="<?= $d ?>" <?= (int) ($settings['currency_decimals'] ?? 2) === $d ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-secondary mb-0 py-2">
                            <i class="bi bi-eye me-1"></i> Preview: <strong id="money-preview">₹ 12,34,567.89</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card erp-panel">
                <div class="card-header erp-panel-title"><h3 class="card-title mb-0"><i class="bi bi-calendar-week me-1"></i> Date &amp; Time</h3></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date format</label>
                        <select name="date_format" class="form-select">
                            <?php
                            $today = new DateTime();
                            $dateFormats = ['d M Y', 'd-m-Y', 'd/m/Y', 'Y-m-d', 'm/d/Y', 'D, d M Y'];
                            $curDate = $settings['date_format'] ?? 'd M Y';
                            foreach ($dateFormats as $f): ?>
                                <option value="<?= esc($f, 'attr') ?>" <?= $curDate === $f ? 'selected' : '' ?>><?= esc($today->format($f)) ?> &nbsp;(<?= esc($f) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Time format</label>
                        <select name="time_format" class="form-select">
                            <?php
                            $timeFormats = ['h:i A' => '12-hour (03:45 PM)', 'H:i' => '24-hour (15:45)'];
                            $curTime = $settings['time_format'] ?? 'h:i A';
                            foreach ($timeFormats as $f => $lbl): ?>
                                <option value="<?= esc($f, 'attr') ?>" <?= $curTime === $f ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Timezone</label>
                        <select name="timezone" class="form-select">
                            <?php
                            $tzList = ['Asia/Kolkata', 'UTC', 'Asia/Dubai', 'Asia/Karachi', 'Asia/Kathmandu', 'Asia/Dhaka', 'Europe/London', 'America/New_York', 'America/Los_Angeles', 'Asia/Singapore', 'Australia/Sydney'];
                            $curTz = $settings['timezone'] ?? 'Asia/Kolkata';
                            if (! in_array($curTz, $tzList, true)) { array_unshift($tzList, $curTz); }
                            foreach ($tzList as $tz): ?>
                                <option value="<?= esc($tz, 'attr') ?>" <?= $curTz === $tz ? 'selected' : '' ?>><?= esc($tz) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Used for all displayed dates &amp; timestamps.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ALERTS -->
        <div class="tab-pane fade" id="tab-alerts">
            <div class="card erp-panel">
                <div class="card-body">
                    <p class="text-secondary">Colours for toast messages and dialog buttons across the app.</p>
                    <div class="row g-3">
                        <?php
                        $colorFields = [
                            'toast_success_color' => 'Toast — Success', 'toast_error_color' => 'Toast — Error',
                            'toast_warning_color' => 'Toast — Warning', 'toast_info_color' => 'Toast — Info',
                            'alert_color' => 'Alert', 'prompt_color' => 'Prompt', 'confirm_color' => 'Confirmation',
                            'sweetalert_confirm_color' => 'SweetAlert — Confirm', 'sweetalert_cancel_color' => 'SweetAlert — Cancel',
                        ];
                        foreach ($colorFields as $k => $lbl): ?>
                            <div class="col-md-3">
                                <label class="form-label small"><?= $lbl ?></label>
                                <input type="color" name="<?= $k ?>" class="form-control form-control-color" value="<?= $val($k, '#0d6efd') ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-3" onclick="erpNotify('success','This is a success toast');erpNotify('error','This is an error toast');">
                        <i class="bi bi-eye me-1"></i> Preview toasts
                    </button>
                </div>
            </div>
        </div>

        <!-- WEATHER -->
        <div class="tab-pane fade" id="tab-weather">
            <div class="card erp-panel">
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Default weather city</label>
                        <input type="text" name="weather_city" class="form-control" value="<?= $val('weather_city', 'New Delhi') ?>" placeholder="e.g. New Delhi">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Units</label>
                        <select name="weather_units" class="form-select">
                            <option value="metric" <?= ($settings['weather_units'] ?? 'metric') === 'metric' ? 'selected' : '' ?>>Celsius (metric)</option>
                            <option value="imperial" <?= ($settings['weather_units'] ?? '') === 'imperial' ? 'selected' : '' ?>>Fahrenheit (imperial)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- WIDGETS -->
        <div class="tab-pane fade" id="tab-widgets">
            <div class="card erp-panel">
                <div class="card-body">
                    <p class="text-secondary">Choose which sections appear on the dashboard.</p>
                    <?php
                    $widgetLabels = [
                        'stats' => 'Statistics cards', 'weather' => 'Weather widget',
                        'recent_logins' => 'Recent logins', 'activity' => 'Activity feed',
                        'notifications' => 'Notifications panel',
                    ];
                    foreach ($widgetLabels as $w => $lbl): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="widgets[]" value="<?= $w ?>" id="w_<?= $w ?>" <?= $isChecked($w) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="w_<?= $w ?>"><?= $lbl ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- WEB PUSH -->
        <div class="tab-pane fade" id="tab-push">
            <div class="card erp-panel">
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="web_push_enabled" value="1" id="web_push_enabled" <?= ($settings['web_push_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="web_push_enabled">Enable browser web-push notifications</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">VAPID subject (contact)</label>
                        <input type="text" name="vapid_subject" class="form-control" value="<?= $val('vapid_subject', 'mailto:admin@erp.test') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">VAPID public key</label>
                        <input type="text" class="form-control" value="<?= esc($settings['vapid_public_key'] ?? '') ?>" readonly
                               placeholder="Not generated yet — use the button below">
                    </div>
                    <p class="small text-secondary mb-3">The public key is exposed to the browser to create push subscriptions. The private key never leaves the server.</p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm" onclick="window.erpEnablePush && erpEnablePush();">
                            <i class="bi bi-bell me-1"></i> Enable on this browser
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.erpDisablePush && erpDisablePush();">
                            <i class="bi bi-bell-slash me-1"></i> Disable on this browser
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($canEdit): ?>
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Settings</button>
        </div>
    <?php endif; ?>
</form>

<!-- VAPID generation is a separate action (regenerates keys) -->
<?php if ($canEdit): ?>
<form action="<?= site_url('settings/generate-vapid') ?>" method="post" class="mt-2"
      onsubmit="return confirm('Generate new VAPID keys? Existing browser subscriptions will stop receiving pushes until they re-subscribe.');">
    <?= csrf_field() ?>
    <div id="push" class="d-none"></div>
    <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-key me-1"></i> Generate / Regenerate VAPID keys</button>
</form>
<?php endif; ?>

<!-- MENU ORDER (separate form) -->
<div class="tab-content mt-0">
    <div id="menu" style="height:0;overflow:hidden"></div>
</div>
<div class="card erp-panel mt-4" id="menu-card" style="display:none">
    <div class="card-header erp-panel-title"><h3 class="card-title mb-0"><i class="bi bi-list-ol me-1"></i> Sidebar Menu Order</h3></div>
    <div class="card-body">
        <form action="<?= site_url('settings/menu') ?>" method="post">
            <?= csrf_field() ?>
            <p class="text-secondary">Set the display order of modules in the sidebar (lower numbers appear first). Use the arrows to reorder.</p>
            <table class="table table-sm align-middle" id="menuTable">
                <thead><tr><th style="width:120px">Order</th><th>Module</th><th style="width:120px"></th></tr></thead>
                <tbody>
                    <?php foreach ($modules as $m): $isParent = empty($m['parent_id']); ?>
                        <tr>
                            <td><input type="number" class="form-control form-control-sm sort-input" name="sort[<?= $m['id'] ?>]" value="<?= (int) $m['sort_order'] ?>" style="width:90px"></td>
                            <td class="<?= $isParent ? 'fw-bold' : 'ps-4' ?>"><i class="<?= esc($m['icon']) ?> me-1"></i><?= esc($m['name']) ?> <code class="small text-secondary"><?= esc($m['code']) ?></code></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary move-up"><i class="bi bi-arrow-up"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary move-down"><i class="bi bi-arrow-down"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($canEdit): ?>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Menu Order</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
(function () {
    // Reveal the menu-order card only when its tab is active.
    var menuTabBtn = document.querySelector('[data-bs-target="#tab-menu"]');
    var menuCard   = document.getElementById('menu-card');
    if (menuTabBtn) {
        menuTabBtn.addEventListener('shown.bs.tab', function () { menuCard.style.display = ''; });
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (b) {
            if (b !== menuTabBtn) { b.addEventListener('shown.bs.tab', function () { menuCard.style.display = 'none'; }); }
        });
    }
    // Up/down reordering renumbers the visible sort inputs.
    function renumber() {
        document.querySelectorAll('#menuTable tbody .sort-input').forEach(function (inp, i) { inp.value = (i + 1) * 10; });
    }
    // Live currency preview mirrors the money() PHP helper.
    function indianGroup(intStr) {
        var last3 = intStr.slice(-3), rest = intStr.slice(0, -3);
        if (rest !== '') { rest = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ','); return rest + ',' + last3; }
        return last3;
    }
    function updateMoneyPreview() {
        var sym = (document.getElementById('currency_symbol') || {}).value || '';
        var grp = (document.getElementById('number_grouping') || {}).value || 'indian';
        var dec = parseInt((document.getElementById('currency_decimals') || {}).value || '2', 10);
        var n = 1234567.891;
        var fixed = n.toFixed(dec);
        var parts = fixed.split('.');
        var intPart = grp === 'international'
            ? parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',')
            : indianGroup(parts[0]);
        var out = intPart + (parts[1] ? '.' + parts[1] : '');
        var el = document.getElementById('money-preview');
        if (el) { el.textContent = (sym ? sym + ' ' : '') + out; }
    }
    ['currency_symbol', 'number_grouping', 'currency_decimals'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener('input', updateMoneyPreview); el.addEventListener('change', updateMoneyPreview); }
    });
    updateMoneyPreview();

    document.getElementById('menuTable').addEventListener('click', function (e) {
        var btn = e.target.closest('.move-up, .move-down');
        if (!btn) { return; }
        var row = btn.closest('tr'), tbody = row.parentNode;
        if (btn.classList.contains('move-up') && row.previousElementSibling) {
            tbody.insertBefore(row, row.previousElementSibling);
        } else if (btn.classList.contains('move-down') && row.nextElementSibling) {
            tbody.insertBefore(row.nextElementSibling, row);
        }
        renumber();
    });
})();
</script>
