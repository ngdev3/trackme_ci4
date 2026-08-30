<?php
/**
 * Format a date-ish value for display, treating empty / zero dates as blank.
 */
if (!function_exists('uperm_date')):
function uperm_date($value, $with_time = false)
{
    if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '—';
    }
    $ts = strtotime($value);
    if (!$ts) {
        return '—';
    }
    return date($with_time ? 'd M Y, h:i A' : 'd M Y', $ts);
}
endif;

$u = isset($selected_user) ? $selected_user : null;
$role = ($u && isset($role_map[(int) $u->user_type])) ? $role_map[(int) $u->user_type] : null;
$full_name = $u ? trim($u->first_name . ' ' . $u->last_name) : '';
if ($u && $full_name === '') { $full_name = $u->email; }
$initials = '';
if ($u) {
    $parts = preg_split('/\s+/', trim($full_name));
    $initials = strtoupper(substr(isset($parts[0]) ? $parts[0] : 'U', 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}
$status = $u ? $u->status : '';
$status_class = $status === 'Active' ? 'is-active' : ($status === 'Inactive' ? 'is-inactive' : 'is-deleted');
?>
<style>
    .uperm-page { padding: 24px; color: #18243c; }
    .uperm-shell { max-width: 1200px; margin: 0 auto; }

    .uperm-hero,
    .uperm-card {
        border: 1px solid var(--tm-line, #dce6f2);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 16px 38px rgba(24, 36, 60, .08);
    }

    .uperm-hero {
        margin-bottom: 16px;
        padding: 20px 24px;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(255, 255, 255, .9)),
            radial-gradient(circle at 96% 0, rgba(var(--tm-brand-rgb, 23, 105, 194), .13), transparent 34%);
    }

    .uperm-title { margin: 0; font-size: 24px; font-weight: 900; }
    .uperm-subtitle { margin: 6px 0 0; color: var(--tm-muted, #718096); font-size: 13px; font-weight: 700; line-height: 1.55; }

    .uperm-card { padding: 18px 20px; margin-bottom: 16px; }
    .uperm-card h3 { margin: 0 0 4px; font-size: 16px; font-weight: 900; }
    .uperm-card .uperm-card-sub { margin: 0 0 14px; color: var(--tm-muted, #718096); font-size: 12px; font-weight: 700; }

    .uperm-select, .uperm-input {
        width: 100%;
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid var(--tm-line, #dce6f2);
        border-radius: 8px;
        color: var(--tm-ink, #18243c);
        background: #fbfdff;
        font-weight: 700;
        outline: 0;
    }
    .uperm-select:focus, .uperm-input:focus {
        border-color: var(--tm-brand, #1769c2);
        box-shadow: 0 0 0 4px rgba(var(--tm-brand-rgb, 23, 105, 194), .12);
    }

    /* Profile header */
    .uperm-profile { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .uperm-avatar {
        width: 60px; height: 60px; flex: 0 0 60px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50%; color: #fff; font-size: 22px; font-weight: 900;
        background: linear-gradient(135deg, var(--tm-brand, #1769c2), var(--tm-brand-dark, #0c315f));
    }
    .uperm-profile-main { min-width: 0; flex: 1 1 auto; }
    .uperm-profile-name { font-size: 20px; font-weight: 900; }
    .uperm-profile-meta { margin-top: 3px; color: var(--tm-muted, #718096); font-size: 13px; font-weight: 700; }

    .uperm-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 900;
    }
    .uperm-badge.is-active { color: #15803d; background: #dcfce7; }
    .uperm-badge.is-inactive { color: #b45309; background: #fef3c7; }
    .uperm-badge.is-deleted { color: #7e22ce; background: #f3e8ff; }
    .uperm-badge:before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

    /* Details grid */
    .uperm-details {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 16px;
    }
    .uperm-detail {
        padding: 12px 14px; border: 1px solid var(--tm-line, #dce6f2);
        border-radius: 8px; background: #fbfdff;
    }
    .uperm-detail small { display: block; margin-bottom: 4px; color: var(--tm-muted, #718096); font-size: 11px; font-weight: 800; text-transform: uppercase; }
    .uperm-detail strong { display: block; color: var(--tm-ink, #18243c); font-size: 14px; font-weight: 800; word-break: break-word; }

    /* Account management cards */
    .uperm-manage-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .uperm-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .uperm-col-2 { grid-column: 1 / -1; }
    .uperm-input.area { min-height: 84px; resize: vertical; }

    .uperm-btn {
        min-height: 44px; border: 0; border-radius: 8px; padding: 10px 18px;
        color: #fff; background: var(--tm-brand, #1769c2); font-weight: 800; cursor: pointer;
    }
    .uperm-btn.is-ghost { color: var(--tm-brand-dark, #0c315f); background: var(--tm-brand-soft, #eaf3ff); }
    .uperm-btn.is-danger { background: #dc2626; }
    .uperm-btn.is-success { background: #16a34a; }
    .uperm-btn.is-warn { background: #d97706; }
    .uperm-field { margin-bottom: 12px; }
    .uperm-field label { display: block; margin-bottom: 6px; color: var(--tm-muted, #718096); font-size: 11px; font-weight: 900; text-transform: uppercase; }
    .uperm-inline { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .uperm-pwd-wrap { position: relative; }
    .uperm-pwd-toggle {
        position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
        border: 0; background: transparent; color: var(--tm-brand-dark, #0c315f);
        font-weight: 800; cursor: pointer; font-size: 12px;
    }
    .uperm-current-pwd {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 10px 12px; border: 1px dashed var(--tm-line, #dce6f2);
        border-radius: 8px; background: #f8fbff; margin-bottom: 12px;
    }
    .uperm-current-pwd code { color: #0f172a; font-weight: 800; font-size: 13px; }

    .uperm-note {
        display: flex; align-items: flex-start; gap: 10px; margin-bottom: 16px;
        padding: 12px 14px; border: 1px solid rgba(var(--tm-brand-rgb, 23, 105, 194), .18);
        border-radius: 8px; color: var(--tm-brand-dark, #0c315f); background: #fff;
        font-size: 13px; font-weight: 800; line-height: 1.5;
    }
    .uperm-note.is-warn { border-color: rgba(245, 158, 11, .35); color: #92400e; background: #fffbeb; }

    .uperm-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }

    .uperm-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
    .uperm-module {
        display: flex; align-items: center; gap: 10px; padding: 11px 13px;
        border: 1px solid var(--tm-line, #dce6f2); border-radius: 8px; background: #fbfdff;
        font-weight: 800; cursor: pointer;
    }
    .uperm-module input { width: 18px; height: 18px; accent-color: var(--tm-brand, #1769c2); }

    @media (max-width: 991px) {
        .uperm-details { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .uperm-manage-grid, .uperm-grid, .uperm-form-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 575px) {
        .uperm-page { padding: 14px; }
        .uperm-details { grid-template-columns: 1fr; }
    }
    /* Advanced user search */
    .uperm-search-row { display:grid; grid-template-columns: 2fr 1fr 1fr; gap:12px; margin:6px 0 14px; }
    .uperm-search-row input, .uperm-search-row select { height:46px; border:1px solid #dce6f2; border-radius:10px; padding:0 14px; font-size:14px; font-weight:700; color:#18243c; background:#fff; width:100%; }
    .uperm-search-row input:focus, .uperm-search-row select:focus { outline:none; border-color:#1769c2; box-shadow:0 0 0 3px rgba(23,105,194,.15); }
    /* Search-based dropdown: the user list floats below the search box and is
       hidden until the box is focused, so a selected user doesn't leave the whole
       list on screen. */
    .uperm-combo { position:relative; }
    .uperm-dropdown {
        position:absolute; left:0; right:0; top:calc(100% + 6px); z-index:60;
        background:#fff; border:1px solid #dce6f2; border-radius:12px; padding:8px;
        box-shadow:0 22px 48px -16px rgba(24,36,60,.36); display:none;
    }
    .uperm-combo.open .uperm-dropdown { display:block; }
    .uperm-userlist { max-height:340px; overflow:auto; }
    .uperm-user-item { display:flex; align-items:center; gap:12px; padding:11px 14px; border-bottom:1px solid #f1f5fb; text-decoration:none; color:#18243c; transition:background .12s ease; }
    .uperm-user-item:last-child { border-bottom:0; }
    .uperm-user-item:hover { background:#f4f8ff; }
    .uperm-user-item.active { background:#eaf3ff; box-shadow:inset 3px 0 0 #1769c2; }
    .uperm-user-av { width:38px; height:38px; flex:0 0 auto; border-radius:50%; background:linear-gradient(135deg,#1769c2,#0c4a94); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:14px; }
    .uperm-user-main { flex:1 1 auto; min-width:0; }
    .uperm-user-name { display:block; font-weight:800; }
    .uperm-user-sub { display:block; font-size:12px; color:#8593a8; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .uperm-user-role { font-size:11px; font-weight:900; text-transform:uppercase; color:#516174; background:#f3f6fb; padding:3px 9px; border-radius:999px; }
    .uperm-mini-badge { font-size:10px; font-weight:900; text-transform:uppercase; padding:3px 9px; border-radius:999px; }
    .uperm-mini-badge.is-active { background:#ddf7ed; color:#13734f; }
    .uperm-mini-badge.is-inactive { background:#fff4e0; color:#8a6500; }
    .uperm-search-empty { padding:22px; text-align:center; color:#8593a8; font-weight:800; }
    .uperm-search-count { margin:2px 2px 10px; font-size:12px; font-weight:800; color:#8593a8; }
    @media (max-width: 680px) { .uperm-search-row { grid-template-columns: 1fr; } }
</style>

<main class="main-content bgc-grey-100 uperm-page">
    <div id="mainContent">
        <div class="container-fluid uperm-shell">
            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>

            <section class="uperm-hero">
                <h1 class="uperm-title">User Management &amp; Permissions</h1>
                <p class="uperm-subtitle">Select a user to view their details, activate/deactivate the account, change the default template or password, and control which modules they can access. Super Admin always has complete access.</p>
            </section>

            <section class="uperm-card">
                <h3>Select User</h3>
                <p class="uperm-card-sub">Search by name, email or mobile, and filter by role or status. Only non Super-Admin users are listed.</p>

                <?php
                    $usel_label = '';
                    if (!empty($selected_user)) {
                        $usel_label = trim($selected_user->first_name . ' ' . $selected_user->last_name);
                        if ($usel_label === '') { $usel_label = (string) $selected_user->email; }
                    }
                    $uph = $usel_label !== ''
                        ? 'Selected: ' . $usel_label . '  —  click to search / change'
                        : 'Search name, email or mobile...';
                ?>
                <div class="uperm-combo" id="uCombo">
                <div class="uperm-search-row">
                    <input type="text" id="uSearch" placeholder="<?= htmlspecialchars($uph, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                    <select id="uRole">
                        <option value="">All Roles</option>
                        <?php foreach ($role_list as $r): ?>
                            <option value="<?= (int) $r->user_type; ?>"><?= htmlspecialchars($r->role_name, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="uStatus">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="uperm-dropdown" id="uDrop">
                <div class="uperm-search-count" id="uCount"></div>

                <div class="uperm-userlist" id="uList">
                    <?php foreach ($users as $opt):
                        $label = trim($opt->first_name . ' ' . $opt->last_name);
                        if ($label === '') { $label = $opt->email; }
                        $ini = strtoupper(substr($opt->first_name ?: ($opt->email ?: 'U'), 0, 1) . substr($opt->last_name, 0, 1));
                        $r = isset($role_map[(int) $opt->user_type]) ? $role_map[(int) $opt->user_type] : null;
                        $rname = $r ? $r->role_name : ('Type ' . (int) $opt->user_type);
                        $st = $opt->status === 'Active' ? 'Active' : 'Inactive';
                        $hay = strtolower(trim($label . ' ' . $opt->email . ' ' . $opt->mobile));
                    ?>
                        <a class="uperm-user-item <?= (int) $selected_user_id === (int) $opt->id ? 'active' : ''; ?>"
                           href="<?= base_url('admin/user_permissions') ?>?user_id=<?= (int) $opt->id; ?>"
                           data-search="<?= htmlspecialchars($hay, ENT_QUOTES, 'UTF-8'); ?>"
                           data-role="<?= (int) $opt->user_type; ?>"
                           data-status="<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="uperm-user-av"><?= htmlspecialchars($ini, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="uperm-user-main">
                                <span class="uperm-user-name"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="uperm-user-sub"><?= htmlspecialchars(($opt->email ?: 'no email') . ($opt->mobile ? ' · ' . $opt->mobile : ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                            <span class="uperm-user-role"><?= htmlspecialchars($rname, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="uperm-mini-badge <?= $st === 'Active' ? 'is-active' : 'is-inactive'; ?>"><?= $st; ?></span>
                        </a>
                    <?php endforeach; ?>
                    <div class="uperm-search-empty" id="uEmpty" style="display:none;">No matching users found.</div>
                </div>
                </div>
                </div>
            </section>

            <script>
            (function () {
                var combo = document.getElementById('uCombo');
                var search = document.getElementById('uSearch');
                var roleSel = document.getElementById('uRole');
                var statusSel = document.getElementById('uStatus');
                var list = document.getElementById('uList');
                var empty = document.getElementById('uEmpty');
                var count = document.getElementById('uCount');
                if (!list || !combo) { return; }
                var items = Array.prototype.slice.call(list.querySelectorAll('.uperm-user-item'));

                function apply() {
                    var q = (search.value || '').trim().toLowerCase();
                    var role = roleSel.value;
                    var st = statusSel.value;
                    var shown = 0;
                    items.forEach(function (el) {
                        var ok = (!q || el.getAttribute('data-search').indexOf(q) !== -1)
                              && (!role || el.getAttribute('data-role') === role)
                              && (!st || el.getAttribute('data-status') === st);
                        el.style.display = ok ? '' : 'none';
                        if (ok) { shown++; }
                    });
                    empty.style.display = shown ? 'none' : 'block';
                    count.textContent = shown + ' user' + (shown === 1 ? '' : 's') + ' found';
                }
                function openDrop() { combo.classList.add('open'); }
                function closeDrop() { combo.classList.remove('open'); }

                // Open the dropdown only on interaction; it starts closed so a
                // selected user no longer leaves the whole list on screen.
                search.addEventListener('focus', openDrop);
                search.addEventListener('click', openDrop);
                search.addEventListener('input', function () { apply(); openDrop(); });
                roleSel.addEventListener('change', function () { apply(); openDrop(); });
                statusSel.addEventListener('change', function () { apply(); openDrop(); });
                search.addEventListener('keydown', function (e) { if (e.keyCode === 27) { closeDrop(); this.blur(); } });
                document.addEventListener('click', function (e) { if (!combo.contains(e.target)) { closeDrop(); } });

                apply();   // pre-compute the count/filter; dropdown stays closed until focused
            })();
            </script>

            <?php if ($u): ?>

                <!-- Profile + details -->
                <section class="uperm-card">
                    <div class="uperm-profile">
                        <span class="uperm-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="uperm-profile-main">
                            <div class="uperm-profile-name"><?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="uperm-profile-meta">
                                <?= $role ? htmlspecialchars($role->role_name . ' — ' . $role->job_title, ENT_QUOTES, 'UTF-8') : 'User Type ' . (int) $u->user_type; ?>
                            </div>
                        </div>
                        <span class="uperm-badge <?= $status_class; ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>

                    <div class="uperm-details">
                        <div class="uperm-detail"><small>Email</small><strong><?= htmlspecialchars($u->email ?: '—', ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <div class="uperm-detail"><small>Mobile</small><strong><?= htmlspecialchars($u->mobile ?: '—', ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <div class="uperm-detail"><small>Role / User Type</small><strong><?= $role ? htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8') : '—'; ?> (Type <?= (int) $u->user_type; ?>)</strong></div>
                        <div class="uperm-detail"><small>Assigned Template</small><strong>
                            <?php if ($current_template): ?>
                                <?= htmlspecialchars(($current_template->firm_name ? $current_template->firm_name . ' — ' : '') . $current_template->template_name . ' (ID ' . $current_template->template_id . ')', ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>—<?php endif; ?>
                        </strong></div>
                        <div class="uperm-detail"><small>Created</small><strong><?= uperm_date($u->added_date, true); ?></strong></div>
                        <div class="uperm-detail"><small>Last Updated</small><strong><?= uperm_date(isset($u->updated_date) ? $u->updated_date : ''); ?></strong></div>
                        <div class="uperm-detail"><small>Last Login</small><strong><?= uperm_date(isset($u->last_login) ? $u->last_login : '', true); ?></strong></div>
                        <div class="uperm-detail"><small>PAN</small><strong><?= htmlspecialchars(isset($u->pan_number) && $u->pan_number ? $u->pan_number : '—', ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <div class="uperm-detail"><small>User ID</small><strong>#<?= (int) $u->id; ?></strong></div>
                    </div>
                </section>

                <!-- Quick actions -->
                <section class="uperm-card">
                    <h3>Quick Actions</h3>
                    <p class="uperm-card-sub">One-click controls. Full editing is in the form below.</p>
                    <div class="uperm-inline">
                        <form method="post" action="<?= base_url('admin/user_permissions') ?>" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= (int) $u->id; ?>">
                            <input type="hidden" name="form_action" value="status">
                            <input type="hidden" name="status" value="<?= $status === 'Active' ? 'Inactive' : 'Active'; ?>">
                            <button type="submit" class="uperm-btn <?= $status === 'Active' ? 'is-danger' : 'is-success'; ?>">
                                <?= $status === 'Active' ? 'Deactivate User' : 'Activate User'; ?>
                            </button>
                        </form>
                        <a href="#upermPasswordCard" class="uperm-btn is-warn" style="text-decoration:none;display:inline-flex;align-items:center;">Change Password</a>
                    </div>

                    <form method="post" action="<?= base_url('admin/user_permissions') ?>" style="margin-top:16px;">
                        <input type="hidden" name="user_id" value="<?= (int) $u->id; ?>">
                        <input type="hidden" name="form_action" value="template">
                        <div class="uperm-field" style="margin-bottom:0;">
                            <label>Change Default Template</label>
                            <div class="uperm-inline">
                                <select class="uperm-select" name="default_firm" style="max-width:520px;">
                                    <option value="">-- Select template --</option>
                                    <?php foreach ($templates as $tpl):
                                        $tlabel = 'ID-' . $tpl->template_id . ' — ' . $tpl->template_name;
                                        if (!empty($tpl->firm_name)) { $tlabel .= ' (' . $tpl->firm_name . ')'; }
                                    ?>
                                        <option value="<?= (int) $tpl->template_id; ?>" <?= (int) $u->default_firm === (int) $tpl->template_id ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($tlabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="uperm-btn">Apply</button>
                            </div>
                        </div>
                    </form>
                </section>

                <!-- Edit user details -->
                <section class="uperm-card">
                    <h3>Edit User Details</h3>
                    <p class="uperm-card-sub">Update name, login email, mobile, role, status and remark. Email is the login username.</p>
                    <form method="post" action="<?= base_url('admin/user_permissions') ?>" onsubmit="return upermValidateProfile();">
                        <input type="hidden" name="user_id" value="<?= (int) $u->id; ?>">
                        <input type="hidden" name="form_action" value="profile">
                        <div class="uperm-form-grid">
                            <div class="uperm-field">
                                <label>First Name *</label>
                                <input class="uperm-input" id="upFirst" name="first_name" value="<?= htmlspecialchars($u->first_name, ENT_QUOTES, 'UTF-8'); ?>" placeholder="First name">
                            </div>
                            <div class="uperm-field">
                                <label>Last Name</label>
                                <input class="uperm-input" name="last_name" value="<?= htmlspecialchars($u->last_name, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Last name">
                            </div>
                            <div class="uperm-field">
                                <label>Email (login username) *</label>
                                <input class="uperm-input" id="upEmail" type="email" name="email" value="<?= htmlspecialchars($u->email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="name@example.com">
                            </div>
                            <div class="uperm-field">
                                <label>Mobile *</label>
                                <input class="uperm-input" id="upMobile" name="mobile" value="<?= htmlspecialchars(isset($u->mobile) ? $u->mobile : '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Mobile number">
                            </div>
                            <div class="uperm-field">
                                <label>Role</label>
                                <select class="uperm-select" name="user_type">
                                    <?php foreach ($role_list as $r): ?>
                                        <option value="<?= (int) $r->user_type; ?>" <?= (int) $u->user_type === (int) $r->user_type ? 'selected' : ''; ?>>
                                            Type <?= (int) $r->user_type; ?> - <?= htmlspecialchars($r->role_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="uperm-field">
                                <label>Status</label>
                                <select class="uperm-select" name="status">
                                    <option value="Active" <?= $status === 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="uperm-field uperm-col-2">
                                <label>Remark <span style="font-weight:600;text-transform:none;color:#94a3b8;">(also used to store the plain password)</span></label>
                                <textarea class="uperm-input area" name="remark" placeholder="Remark"><?= htmlspecialchars(isset($u->remark) ? $u->remark : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                        <div class="uperm-inline" style="margin-top:14px;">
                            <button type="submit" class="uperm-btn">Save Changes</button>
                        </div>
                    </form>
                </section>

                <!-- Password -->
                <section class="uperm-card" id="upermPasswordCard">
                    <h3>Password</h3>
                    <p class="uperm-card-sub">Set a new password for this user. The plain password is stored in the remark (same as other users) and the encrypted password is used for login.</p>

                    <div class="uperm-current-pwd">
                        <span>Current password (from remark):
                            <code id="upermCurrentPwd" data-pwd="<?= htmlspecialchars(isset($u->remark) ? $u->remark : '', ENT_QUOTES, 'UTF-8'); ?>"><?= (isset($u->remark) && $u->remark !== '') ? '••••••••' : 'Not available'; ?></code>
                        </span>
                        <?php if (isset($u->remark) && $u->remark !== ''): ?>
                            <button type="button" class="uperm-btn is-ghost" id="upermRevealCurrent" style="min-height:34px;padding:6px 12px;">Show</button>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="<?= base_url('admin/user_permissions') ?>" onsubmit="return upermValidatePwd(this);">
                        <input type="hidden" name="user_id" value="<?= (int) $u->id; ?>">
                        <input type="hidden" name="form_action" value="password">
                        <div class="uperm-field">
                            <label>New Password (min 6 characters)</label>
                            <div class="uperm-pwd-wrap">
                                <input type="password" class="uperm-input" name="new_password" id="upermNewPwd" autocomplete="new-password" placeholder="Enter new password">
                                <button type="button" class="uperm-pwd-toggle" id="upermToggleNew">Show</button>
                            </div>
                        </div>
                        <div class="uperm-inline">
                            <button type="submit" class="uperm-btn is-warn">Change Password</button>
                        </div>
                    </form>

                    <?php $force_on = isset($u->is_reuired_to_change_password) && (int) $u->is_reuired_to_change_password === 1; ?>
                    <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--tm-line,#dce6f2);">
                        <h3 style="font-size:15px;margin:0 0 4px;">Force Password Change</h3>
                        <p class="uperm-card-sub">When required, the user is sent to the change-password screen at login and <strong>cannot access anything</strong> until they set a new password.</p>
                        <div class="uperm-inline" style="align-items:center;">
                            <span class="uperm-badge <?= $force_on ? 'is-inactive' : 'is-active'; ?>">
                                <?= $force_on ? 'Required at next login' : 'Not required'; ?>
                            </span>
                            <form method="post" action="<?= base_url('admin/user_permissions') ?>" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= (int) $u->id; ?>">
                                <input type="hidden" name="form_action" value="force_password">
                                <input type="hidden" name="force_flag" value="<?= $force_on ? 0 : 1; ?>">
                                <button type="submit" class="uperm-btn <?= $force_on ? 'is-ghost' : 'is-danger'; ?>">
                                    <?= $force_on ? 'Remove requirement' : 'Require change at next login'; ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php $app_on = !isset($u->app_access) || (int) $u->app_access === 1; ?>
                    <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--tm-line,#dce6f2);">
                        <h3 style="font-size:15px;margin:0 0 4px;"><i class="ti-mobile"></i> Mobile App Access</h3>
                        <p class="uperm-card-sub">Control whether this user can log in to the <strong>mobile app</strong>. Blocking here does not affect their web-panel access &mdash; only the Android/iOS app login is refused.</p>
                        <div class="uperm-inline" style="align-items:center;">
                            <span class="uperm-badge <?= $app_on ? 'is-active' : 'is-inactive'; ?>">
                                <?= $app_on ? 'App access allowed' : 'App access blocked'; ?>
                            </span>
                            <form method="post" action="<?= base_url('admin/user_permissions') ?>" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= (int) $u->id; ?>">
                                <input type="hidden" name="form_action" value="mobile_access">
                                <input type="hidden" name="app_access" value="<?= $app_on ? 0 : 1; ?>">
                                <button type="submit" class="uperm-btn <?= $app_on ? 'is-danger' : 'is-success'; ?>">
                                    <?= $app_on ? 'Block mobile app' : 'Allow mobile app'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Module permissions (existing functionality) -->
                <?php if (!$has_config): ?>
                    <div class="uperm-note is-warn">
                        <i class="ti-info-alt"></i>
                        <span>This user has not been customized yet, so they currently inherit their role defaults (Type <?= (int) $u->user_type; ?>). Saving below switches them to per-user permissions.</span>
                    </div>
                <?php else: ?>
                    <div class="uperm-note">
                        <i class="ti-check-box"></i>
                        <span>This user is on custom per-user permissions. Only the checked modules below are accessible to them.</span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('admin/user_permissions') ?>">
                    <input type="hidden" name="user_id" value="<?= (int) $u->id; ?>">

                    <section class="uperm-card">
                        <div class="uperm-toolbar">
                            <div>
                                <h3 style="margin:0;">Module Access</h3>
                                <p class="uperm-card-sub" style="margin:4px 0 0;">Tick the modules this user is allowed to open.</p>
                            </div>
                            <div>
                                <button type="button" class="uperm-btn is-ghost" onclick="upermToggleAll(true)">Select all</button>
                                <button type="button" class="uperm-btn is-ghost" onclick="upermToggleAll(false)">Clear all</button>
                            </div>
                        </div>

                        <div class="uperm-grid">
                            <?php foreach ($modules as $key => $name):
                                $row = isset($user_permissions[$key]) ? $user_permissions[$key] : null;
                                $checked = $row && (int) $row->can_view === 1 ? 'checked' : '';
                            ?>
                                <label class="uperm-module">
                                    <input type="checkbox" class="uperm-check" name="modules[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>]" value="1" <?= $checked; ?>>
                                    <span><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <div class="uperm-toolbar">
                        <button type="submit" class="uperm-btn"><i class="ti-save"></i> Save Permissions</button>
                        <?php if ($has_config): ?>
                            <button type="submit" name="reset" value="1" class="uperm-btn is-danger" onclick="return crConfirmNav(this, 'Reset this user to role defaults? Their custom permissions will be removed.');"><i class="ti-back-left"></i> Reset to role defaults</button>
                        <?php endif; ?>
                    </div>
                </form>

                <script>
                    function upermToggleAll(state) {
                        var boxes = document.querySelectorAll('.uperm-check');
                        for (var i = 0; i < boxes.length; i++) { boxes[i].checked = state; }
                    }
                    function upermValidatePwd(form) {
                        var v = (document.getElementById('upermNewPwd').value || '').trim();
                        if (v.length < 6) { showToast('warning', 'Password must be at least 6 characters.'); return false; }
                        showConfirm('Change password', "Change this user's password?", function () { form.submit(); });
                        return false;
                    }
                    function upermValidateProfile() {
                        var first = (document.getElementById('upFirst').value || '').trim();
                        var email = (document.getElementById('upEmail').value || '').trim();
                        var mobile = (document.getElementById('upMobile').value || '').trim();
                        if (!first) { alert('First name is required.'); return false; }
                        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { alert('Please enter a valid email.'); return false; }
                        if (!mobile) { alert('Mobile is required.'); return false; }
                        return true;
                    }
                    (function () {
                        var toggleNew = document.getElementById('upermToggleNew');
                        if (toggleNew) {
                            toggleNew.addEventListener('click', function () {
                                var inp = document.getElementById('upermNewPwd');
                                if (inp.type === 'password') { inp.type = 'text'; toggleNew.innerText = 'Hide'; }
                                else { inp.type = 'password'; toggleNew.innerText = 'Show'; }
                            });
                        }
                        var revealCur = document.getElementById('upermRevealCurrent');
                        if (revealCur) {
                            revealCur.addEventListener('click', function () {
                                var el = document.getElementById('upermCurrentPwd');
                                if (revealCur.innerText === 'Show') { el.innerText = el.getAttribute('data-pwd'); revealCur.innerText = 'Hide'; }
                                else { el.innerText = '••••••••'; revealCur.innerText = 'Show'; }
                            });
                        }
                    })();
                </script>

            <?php endif; ?>
        </div>
    </div>
</main>
