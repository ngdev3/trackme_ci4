<?php
/**
 * top_nav.php — EXACT port of the CI3 top navigation bar (application/views/layout.php
 * header block, lines ~3250-3399) + the Change Firm modal (elements/setting.php) and
 * the notification dropdown (elements/notification.php). Markup/classes/behaviour are
 * byte-identical to CI3; only the framework calls are adapted to CI4:
 *   $this->session->userdata()  -> session()->get()
 *   $this->load->view('elements/x') -> $this->include('...\elements\x')
 *   html_escape() -> esc();  admin/logout -> admin/auth/logout
 * The header CSS lives (verbatim) in public/assets/css/admin_shell.css; behaviours in
 * public/assets/js/top_nav.js. Rendered by app/Views/layouts/admin.php.
 */
helper(['url', 'app', 'permission']);

// Session-window figures (CI3 used $this->config->item('sess_expiration'), 90000 fallback).
$trackme_session_timeout = (int) (config('Session')->expiration ?? 0);
$trackme_session_timeout = $trackme_session_timeout > 0 ? $trackme_session_timeout : 90000;
$web_lock_minutes = function_exists('session_lock_minutes') ? (int) session_lock_minutes() : 0;

// Firms / financial years for the Change Firm modal (CI3 passed $this->datawert['fy']).
$fy = [];
try {
    $fy = \Config\Database::connect()->table('aa_template as atp')
        ->select('atp.template_id, atp.FY, atp.track_name, atp.template_name, atp.product_type, frn.name as firm_name')
        ->join('firm_name as frn', 'frn.id = atp.firm_name_id', 'left')
        ->where('atp.status', 'Active')
        ->orderBy('frn.name', 'asc')->orderBy('atp.FY', 'desc')
        ->get()->getResult();
} catch (\Throwable $e) {
    $fy = [];
}
?>
<div class="header navbar">
    <div class="header-container">
        <ul class="nav-left">
            <li><a id="sidebar-toggle" class="sidebar-toggle tm-has-tooltip" href="javascript:void(0);" data-tooltip="Toggle sidebar menu" aria-label="Toggle sidebar menu"><i
                        class="ti-menu"></i></a></li>

        </ul>
        <div class="advanced-feature-note">
            <i class="ti-info-alt"></i>
            <span>Advanced features are inside the profile dropdown.</span>
        </div>
        <div class="erp-global-search" id="erpGlobalSearch">
            <div class="erp-global-search-field">
                <i class="ti-search"></i>
                <input type="search" id="erpGlobalSearchInput" placeholder="Search ERP option..." autocomplete="off" aria-label="Search ERP option">
                <button type="button" class="erp-global-search-clear" id="erpGlobalSearchClear" aria-label="Clear ERP search"><i class="ti-close"></i></button>
            </div>
            <div class="erp-search-suggestions" id="erpGlobalSearchSuggestions"></div>
        </div>
        <style>
            .badge {
                color: #fff;
                background: #e5484d;
                font-size: 11px;
                border-radius: 999px;
            }
        </style>
        <?php
        $session_started_at = (int) session()->get('session_started_at');
        if (empty($session_started_at)) {
            $session_started_at = time();
            session()->set('session_started_at', $session_started_at);
        }
        $session_expires_at = (int) session()->get('session_expires_at');
        if (empty($session_expires_at)) {
            $session_expires_at = $session_started_at + $trackme_session_timeout;
            session()->set('session_expires_at', $session_expires_at);
        } elseif ($trackme_session_timeout > 0 && $session_expires_at < ($session_started_at + $trackme_session_timeout)) {
            $session_expires_at = $session_started_at + $trackme_session_timeout;
            session()->set('session_expires_at', $session_expires_at);
        }
        ?>
        <ul class="nav-right">
            <?php
            // Change Firm is core navigation — visible to anyone who can view
            // Setting AND to view-only users (they must switch firms to view their
            // data even without Setting access). change_fy_id is gate-exempt.
            $__can_switch_firm = (! function_exists('erp_current_user_can') || erp_current_user_can('setting', 'view'));
            if (! $__can_switch_firm && function_exists('erp_user_is_view_only') && function_exists('currentuserinfo')) {
                $__sw_cu = currentuserinfo();
                $__can_switch_firm = (is_object($__sw_cu) && isset($__sw_cu->id) && erp_user_is_view_only((int) $__sw_cu->id));
            }
            ?>
            <?php if ($__can_switch_firm): ?>
            <li class="dropdown fy-block">
                <a href="javascript:void(0)" class="dropdown-toggle no-after tm-has-tooltip" data-toggle="modal" data-target="#exampleModal" data-tooltip="Change firm / financial year" aria-label="Change firm or financial year">
                    <span class="fy-content">
                        <span title="<?= esc(@fy()->firm_name) ?>"><?= @fy()->firm_name ?></span>
                        <span class="fy-blue">ID-</span>
                        <span title="<?= esc(@fy()->template_id.'_'.@fy()->track_name) ?>"><?= ucfirst(@fy()->template_id.'_'.@fy()->track_name) ?></span>
                    </span>
                </a>
            </li>
            <?php endif; ?>
            <li class="dropdown notification-block">
                <?= $this->include('\App\Modules\Admin\Views\elements\notification') ?>
            </li>
            <li class="session-block">
                <div class="session-meter tm-has-tooltip" id="sessionMeter"
                    data-started-at="<?= $session_started_at ?>"
                    data-timeout="<?= $trackme_session_timeout ?>"
                    data-expires-at="<?= $session_expires_at ?>"
                    data-logout-url="<?= base_url('admin/auth/logout') ?>"
                    data-renew-url="<?= base_url('admin/auth/renew_session') ?>"
                    data-tooltip="Session time: active and remaining"
                    aria-label="Session time active and remaining">
                    <span class="session-meter-icon"><i class="ti-timer"></i></span>
                    <span class="session-meter-copy">
                        <span class="session-meter-label">Active</span>
                        <span class="session-meter-value" id="sessionActiveTime">00:00:00</span>
                        <span class="session-meter-label session-meter-remaining-label">Left</span>
                        <span class="session-meter-value session-meter-remaining-value" id="sessionTimeLeft">00:00:00</span>
                    </span>
                </div>
            </li>
            <li class="dropdown profile-block">
                <a href="#" class="dropdown-toggle no-after peers ai-c lh-1 flex-wrap tm-has-tooltip" data-toggle="dropdown" data-tooltip="Profile menu" aria-label="Profile menu">
                    <div class="peer mR-10"><img class="w-2r bdrs-50p" style="width:2rem;height:2rem;object-fit:cover;" src="<?php echo (!empty(@currentuserinfo()->profile_image)) ? base_url('uploads/profile_image/' . currentuserinfo()->profile_image) : base_url('assets/images/dp.jpg'); ?>" onerror="this.onerror=null;this.src='<?= base_url('assets/images/dp.jpg') ?>';" alt=""></div>
                    <div class="peer"><span
                            class="fsz-sm c-grey-900"><?= ucfirst(@currentuserinfo()->first_name . ' ' . @currentuserinfo()->last_name) ?></span>
                    </div>
                    <span class="arrow"><i class="ti-angle-down my_cls"></i></span>
                </a>
                <?php
                if (!empty($_SESSION['user_type'])) {
                    ?>
                    <ul class="dropdown-menu fsz-sm profile-menu">
                        <li class="profile-tools-title">Panel Tools</li>
                        <li class="profile-clock-tool">
                            <div class="top-clock-widget tm-has-tooltip" data-tooltip="Current date and time" aria-label="Current date and time">
                                <span class="top-clock-icon"><i class="ti-calendar"></i></span>
                                <span class="top-clock-copy">
                                    <span class="top-clock-date" id="topNavDate">--</span>
                                    <span class="top-clock-time" id="topNavTime">--:--</span>
                                </span>
                            </div>
                        </li>
                        <li class="profile-theme-tool theme-block">
                            <div class="theme-menu">
                                <div class="theme-presets-title">Preset Themes</div>
                                <div class="theme-presets" id="themePresets">
                                    <button type="button" class="theme-swatch" title="Ocean Blue" style="--sw:#1769c2;--sw2:#0c315f" onclick="trackmeSetPreset('#1769c2','#18243c')"></button>
                                    <button type="button" class="theme-swatch" title="Emerald" style="--sw:#16835d;--sw2:#0f513b" onclick="trackmeSetPreset('#16835d','#14342a')"></button>
                                    <button type="button" class="theme-swatch" title="Violet" style="--sw:#6956d9;--sw2:#3d2f91" onclick="trackmeSetPreset('#6956d9','#241f45')"></button>
                                    <button type="button" class="theme-swatch" title="Rose" style="--sw:#d63d5c;--sw2:#8f1f37" onclick="trackmeSetPreset('#d63d5c','#3d1722')"></button>
                                    <button type="button" class="theme-swatch" title="Sunset" style="--sw:#e85d2c;--sw2:#9c3a16" onclick="trackmeSetPreset('#e85d2c','#3f1e10')"></button>
                                    <button type="button" class="theme-swatch" title="Aqua" style="--sw:#00a7c4;--sw2:#055d72" onclick="trackmeSetPreset('#00a7c4','#0c3944')"></button>
                                    <button type="button" class="theme-swatch" title="Royal" style="--sw:#8257c9;--sw2:#452d74" onclick="trackmeSetPreset('#8257c9','#2c2447')"></button>
                                    <button type="button" class="theme-swatch" title="Crimson" style="--sw:#e23b3b;--sw2:#8f1f1f" onclick="trackmeSetPreset('#e23b3b','#3d1414')"></button>
                                </div>
                                <div class="theme-picker-title">Custom Colors</div>
                                <div class="theme-picker-field">
                                    <label for="themePrimaryColor">Primary Color</label>
                                    <input type="color" id="themePrimaryColor" value="#1769c2" oninput="trackmeApplyCustomTheme()" onchange="trackmeApplyCustomTheme()">
                                </div>
                                <div class="theme-picker-field">
                                    <label for="themeFontColor">Font Color</label>
                                    <input type="color" id="themeFontColor" value="#18243c" oninput="trackmeApplyCustomTheme()" onchange="trackmeApplyCustomTheme()">
                                </div>
                                <button type="button" class="theme-reset" id="themeResetBtn" onclick="trackmeResetTheme()">Reset Theme</button>
                            </div>
                        </li>
                        <li class="web-lock-block profile-tool-row">
                            <a href="javascript:void(0)" id="webLockNowBtn" class="tm-has-tooltip" data-tooltip="Lock web panel" aria-label="Lock web panel"><i class="ti-lock"></i><span>Lock Web Panel</span></a>
                        </li>
                        <li role="separator" class="divider"></li>
                        <li><a href="<?= base_url('admin/profile') ?>" class="d-b td-n pY-5 bgcH-grey-100 c-grey-700"><i
                                    class="ti-settings mR-10"></i> <span>My Profile</span></a></li>
                        <?php if (@currentuserinfo()->id == 1) { ?>
                            <li><a href="<?= base_url('admin/dashboard/renewable') ?>"
                                    class="d-b td-n pY-5 bgcH-grey-100 c-grey-700"><i class="ti-user mR-10"></i> <span>My
                                        Renewable</span></a></li>
                        <?php } ?>
                        <li><a href="<?= base_url('admin/profile/reset_password') ?>"
                                class="d-b td-n pY-5 bgcH-grey-100 c-grey-700"><i class="ti-settings mR-10"></i>
                                <span>Change Password</span></a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="<?= base_url('admin/auth/logout') ?>" class="d-b td-n pY-5 bgcH-grey-100 c-grey-700"><i
                                    class="ti-power-off mR-10"></i> <span>Logout</span></a></li>
                    </ul>
                    <?php
                }
                ?>

            </li>
        </ul>
    </div>
</div>
<?= view('\App\Modules\Admin\Views\elements\setting', ['fy' => $fy]) ?>

<!-- Web-panel inactivity lock (driven by public/assets/js/web_lock.js) -->
<div class="web-lock-overlay" id="webLockOverlay"
    data-lock-minutes="<?= (int) $web_lock_minutes ?>"
    data-unlock-url="<?= base_url('admin/auth/unlock_web_lock') ?>"
    data-logout-url="<?= base_url('admin/auth/logout') ?>"
    data-login-url="<?= base_url('admin/auth/login') ?>">
    <div class="web-lock-card">
        <div class="web-lock-head">
            <span class="web-lock-icon"><i class="ti-lock"></i></span>
            <h3>Web Panel Locked</h3>
            <p>This panel is locked because there was no activity for <?= (int) $web_lock_minutes ?> minutes. Enter your login password to continue working.</p>
        </div>
        <div class="web-lock-body">
            <form id="webLockForm" autocomplete="off">
                <label for="webLockPassword">Login Password</label>
                <input type="password" id="webLockPassword" class="web-lock-input" placeholder="Enter login password">
                <div class="web-lock-error" id="webLockError"></div>
                <div class="web-lock-actions">
                    <button type="button" class="btn btn-default" id="webLockLogoutBtn">Logout</button>
                    <button type="submit" class="btn btn-primary" id="webLockUnlockBtn">Unlock Panel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="network-toast" id="networkToast">
    <span class="network-toast-icon"><i class="ti-unlink"></i></span>
    <span>
        <strong>Internet connection disconnected</strong>
        Connect it to use the ERP again.
    </span>
</div>
