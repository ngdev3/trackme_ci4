<?php
/**
 * top_nav.php — CI4 port of the CI3 top navigation bar (application/views/layout.php
 * header block). Reuses the already-ported header CSS in admin_shell.css and the
 * behaviours in public/assets/js/top_nav.js. Rendered by app/Views/layouts/admin.php.
 *
 * Expects (from the layout): $user (current user), $firm (fy row).
 */
helper(['url', 'app', 'permission']);

// Self-sufficient: derive the current user + firm from fyContext (CI4
// $this->include() does not inherit the parent view's local variables).
$ctx  = service('fyContext');
$user = $ctx->userInfo();
$firm = $ctx->fyRow();

$sessionTimeout = 3600; // 1h active-session window (display + soft auto-logout)
$startedAt = (int) session()->get('session_started_at');
if (empty($startedAt)) {
    $startedAt = time();
    session()->set('session_started_at', $startedAt);
}
$expiresAt = $startedAt + $sessionTimeout;

$notifItems  = function_exists('recent_notifications') ? recent_notifications(10) : [];
$notifUnread = function_exists('unread_notifcations') ? (int) unread_notifcations() : 0;

$uName  = trim((string) (($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
$uImg   = ! empty($user->profile_image)
    ? base_url('uploads/profile_image/' . $user->profile_image)
    : base_url('assets/images/dp.jpg');
$firmName = $firm->firm_name ?? '';
$tid      = $firm->template_id ?? '';
$trackNm  = $firm->track_name ?? ($firm->FY ?? '');
?>
<div class="header navbar">
    <div class="header-container">
        <ul class="nav-left">
            <li><a id="sidebar-toggle" class="sidebar-toggle tm-has-tooltip" href="javascript:void(0);" data-tooltip="Toggle sidebar menu" aria-label="Toggle sidebar menu"><i class="ti-menu"></i></a></li>
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

        <ul class="nav-right">
            <?php if (! function_exists('erp_current_user_can') || erp_current_user_can('setting', 'view')): ?>
            <li class="dropdown fy-block">
                <a href="<?= base_url('admin/setting/listing') ?>" class="no-after tm-has-tooltip" data-tooltip="Change firm / financial year" aria-label="Change firm or financial year">
                    <span class="fy-content">
                        <span title="<?= esc($firmName) ?>"><?= esc($firmName) ?></span>
                        <span class="fy-blue">ID-</span>
                        <span title="<?= esc($tid . '_' . $trackNm) ?>"><?= esc(ucfirst($tid . '_' . $trackNm)) ?></span>
                    </span>
                </a>
            </li>
            <?php endif; ?>

            <li class="dropdown notification-block">
                <a href="javascript:void(0)" class="dropdown-toggle no-after notification-toggle tm-has-tooltip" data-toggle="dropdown" data-tooltip="Notifications" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                    <span class="notification-badge">
                        <i class="ti-bell"></i>
                        <?php if ($notifUnread > 0): ?>
                            <span class="notification-count"><?= $notifUnread > 99 ? '99+' : $notifUnread ?></span>
                        <?php endif; ?>
                    </span>
                </a>
                <ul class="dropdown-menu notif-menu">
                    <li class="notif-head">
                        <span>Notifications</span>
                        <span class="notif-head-count"><?= $notifUnread ?> new</span>
                    </li>
                    <li>
                        <ul class="notif-scroll">
                            <?php if (empty($notifItems)): ?>
                                <li class="notif-empty">You have no notifications</li>
                            <?php else: foreach ($notifItems as $n): ?>
                                <li class="notif-item <?= empty($n->is_seen) ? 'is-unread' : '' ?>">
                                    <a href="<?= base_url('admin/notification/read/' . $n->id) ?>">
                                        <span class="notif-icon"><i class="ti-bell"></i></span>
                                        <span class="notif-body">
                                            <span class="notif-text"><?= $n->name /* stored HTML, matches CI3 */ ?></span>
                                            <?php if (! empty($n->user_name)): ?>
                                                <span class="notif-user"><i class="ti-user"></i> <?= esc($n->user_name) ?></span>
                                            <?php endif; ?>
                                            <span class="notif-time">
                                                <i class="ti-time"></i>
                                                <?= ! empty($n->added_date) ? date('d M Y, h:i A', strtotime($n->added_date)) : '' ?>
                                                <span class="notif-ago">&middot; <?= function_exists('notif_time_ago') ? notif_time_ago($n->added_date ?? '') : '' ?></span>
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; endif; ?>
                        </ul>
                    </li>
                    <li class="notif-foot">
                        <a href="<?= base_url('admin/notification/listing') ?>">See all notifications</a>
                    </li>
                </ul>
            </li>

            <li class="session-block">
                <div class="session-meter tm-has-tooltip" id="sessionMeter"
                    data-started-at="<?= $startedAt ?>"
                    data-timeout="<?= $sessionTimeout ?>"
                    data-expires-at="<?= $expiresAt ?>"
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
                    <div class="peer mR-10"><img class="w-2r bdrs-50p" style="width:2rem;height:2rem;object-fit:cover;" src="<?= $uImg ?>" alt=""></div>
                    <div class="peer"><span class="fsz-sm c-grey-900"><?= esc(ucfirst($uName ?: 'User')) ?></span></div>
                    <span class="arrow"><i class="ti-angle-down my_cls"></i></span>
                </a>
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
                    <li role="separator" class="divider"></li>
                    <li><a href="<?= base_url('admin/profile') ?>" class="d-b td-n pY-5 bgcH-grey-100 c-grey-700"><i class="ti-settings mR-10"></i> <span>My Profile</span></a></li>
                    <li><a href="<?= base_url('admin/profile/reset_password') ?>" class="d-b td-n pY-5 bgcH-grey-100 c-grey-700"><i class="ti-settings mR-10"></i> <span>Change Password</span></a></li>
                    <li role="separator" class="divider"></li>
                    <li><a href="<?= base_url('admin/auth/logout') ?>" class="d-b td-n pY-5 bgcH-grey-100 c-grey-700"><i class="ti-power-off mR-10"></i> <span>Logout</span></a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>

<!-- Session expiry warning (5-minute) — driven by top_nav.js -->
<div class="session-warning-modal" id="sessionWarningModal">
    <div class="session-warning-card">
        <div class="session-warning-title"><i class="ti-alarm-clock"></i> Session expiring soon</div>
        <p>Your session will end in <strong id="sessionWarningLeft">05:00</strong>. Stay signed in?</p>
        <div class="session-warning-error" id="sessionRenewError"></div>
        <div class="session-warning-actions">
            <button type="button" class="btn btn-primary" id="sessionRenewBtn">Renew 5 Minutes</button>
            <button type="button" class="btn btn-default" id="sessionLogoutNowBtn">Logout now</button>
        </div>
    </div>
</div>

<script>
// Bell: mark the current user's notifications seen when the dropdown opens.
(function () {
    if (typeof jQuery === 'undefined') { return; }
    var $block = jQuery('.notification-block');
    if (!$block.length) { return; }
    var markUrl = '<?= base_url('admin/notification/mark_seen') ?>';
    var hasUnread = <?= $notifUnread > 0 ? 'true' : 'false' ?>;
    function markSeen() {
        if (!hasUnread) { return; }
        hasUnread = false;
        jQuery.get(markUrl).always(function () {
            $block.find('.notification-count').remove();
            $block.find('.notif-head-count').text('0 new');
        });
    }
    $block.on('shown.bs.dropdown', markSeen);
    $block.find('.notification-toggle').on('click', function () { setTimeout(markSeen, 60); });
})();
</script>
