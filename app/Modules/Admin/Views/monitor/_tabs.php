<?php
$active   = isset($active) ? $active : 'overview';
$is_super = isset($is_super) ? $is_super : false;
$f        = isset($filters) ? $filters : array('from' => '', 'to' => '', 'user' => 0);
$ulist    = isset($users) ? $users : array();

// Tab definitions: key => [label, icon, super_only]
$tabs = array(
    'overview'  => array('Overview', 'ti-dashboard', false),
    'scores'    => array('Activity Scores', 'ti-medall', true),
    'traffic'   => array('Page Traffic', 'ti-bar-chart', false),
    'entries'   => array('Entry Audit', 'ti-shield', true),
    'logins'    => array('Logins', 'ti-key', false),
    'login_security' => array('Login Security', 'ti-lock', false),
    'timeline'  => array('Timeline', 'ti-time', false),
    'ip_intel'  => array('IP & Location', 'ti-location-pin', false),
    'anomalies' => array('Anomalies', 'ti-alert', false),
    'retention' => array('Retention', 'ti-trash', true),
);

// Carry the date/user filter across tabs.
$qs = http_build_query(array('from' => $f['from'], 'to' => $f['to'], 'user' => $f['user'] ? $f['user'] : ''));
?>
<div class="mon-hero">
    <div class="mon-hero-l">
        <div class="mon-hero-ic"><i class="ti-pulse"></i></div>
        <div>
            <h1 class="mon-title">Activity &amp; Audit Monitor
                <small>Page traffic &middot; logins &middot; entry audit (IP + location) &middot; anomalies &mdash; in one place</small>
            </h1>
        </div>
    </div>
    <form class="mon-filter" method="get" action="<?= base_url('admin/monitor/' . $active) ?>">
        <div><label>From</label><input type="date" name="from" value="<?= html_escape($f['from']) ?>"></div>
        <div><label>To</label><input type="date" name="to" value="<?= html_escape($f['to']) ?>"></div>
        <div><label>User</label>
            <select name="user">
                <option value="">All users</option>
                <?php foreach ($ulist as $u): ?>
                    <option value="<?= (int) $u->id ?>" <?= ((int) $f['user'] === (int) $u->id) ? 'selected' : '' ?>><?= html_escape($u->nm) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>&nbsp;</label><button type="submit" class="mon-go"><i class="ti-filter"></i> Apply</button></div>
    </form>
</div>

<div class="mon-tabs">
    <?php foreach ($tabs as $key => $t):
        if ($t[2] && !$is_super) { continue; } // hide super-only tabs from non-super users
        $url = base_url('admin/monitor/' . $key) . ($qs ? ('?' . $qs) : '');
    ?>
        <a class="mon-tab <?= $active === $key ? 'on' : '' ?>" href="<?= $url ?>">
            <i class="<?= $t[1] ?>"></i> <?= html_escape($t[0]) ?>
            <?php if ($t[2]): ?><i class="lock ti-lock" title="Super Admin only"></i><?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
