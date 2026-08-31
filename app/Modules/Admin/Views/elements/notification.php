<?php
$notif_items  = function_exists('recent_notifications') ? recent_notifications(10) : array();
$notif_unread = function_exists('unread_notifcations') ? (int) unread_notifcations() : 0;
?>
<style>
    .notification-block .notif-menu {
        right: 0;
        left: auto;
        width: 360px;
        max-width: 92vw;
        padding: 0;
        border: 1px solid #dce6f2;
        border-radius: 10px;
        box-shadow: 0 18px 40px rgba(24, 36, 60, .18);
        overflow: hidden;
    }
    .notif-menu .notif-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 16px; background: #18243c; color: #fff;
        font-size: 13px; font-weight: 800;
    }
    .notif-menu .notif-head-count {
        background: #1769c2; color: #fff; font-size: 11px; font-weight: 800;
        padding: 2px 9px; border-radius: 20px;
    }
    .notif-menu .notif-scroll { max-height: 360px; overflow-y: auto; padding: 0; margin: 0; list-style: none; }
    .notif-menu .notif-list { padding: 0; margin: 0; list-style: none; }
    .notif-menu .notif-item { border-bottom: 1px solid #eef2f7; }
    .notif-menu .notif-item > a {
        display: flex; align-items: flex-start; gap: 11px;
        padding: 11px 16px; text-decoration: none; color: #26374f; transition: background .15s ease;
    }
    .notif-menu .notif-item > a:hover { background: #f7fafc; }
    .notif-menu .notif-item.is-unread > a { background: #eef6ff; }
    .notif-menu .notif-item.is-unread > a:hover { background: #e3f0ff; }
    .notif-menu .notif-icon {
        flex: 0 0 auto; width: 32px; height: 32px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: #e6f0fb; color: #1769c2; font-size: 14px;
    }
    .notif-menu .notif-item.is-unread .notif-icon { background: #1769c2; color: #fff; }
    .notif-menu .notif-body { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
    .notif-menu .notif-text {
        font-size: 12.5px; font-weight: 700; line-height: 1.4; color: #26374f;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .notif-menu .notif-user { font-size: 11px; font-weight: 800; color: #1769c2; }
    .notif-menu .notif-user i { margin-right: 2px; }
    .notif-menu .notif-time { font-size: 11px; font-weight: 700; color: #94a3b8; }
    .notif-menu .notif-ago { color: #b6c0cd; font-weight: 700; }
    .notif-menu .notif-empty { padding: 26px 16px; text-align: center; color: #94a3b8; font-size: 13px; font-weight: 700; }
    .notif-menu .notif-foot { padding: 0; border-top: 1px solid #eef2f7; }
    .notif-menu .notif-foot a {
        display: block; padding: 12px 16px; text-align: center;
        color: #1769c2; font-size: 12.5px; font-weight: 800; text-decoration: none; background: #fff;
    }
    .notif-menu .notif-foot a:hover { background: #f7fafc; }
</style>

<a href="javascript:void(0)" class="dropdown-toggle no-after notification-toggle tm-has-tooltip" data-toggle="dropdown" data-tooltip="Notifications" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
    <span class="notification-badge">
        <i class="ti-bell"></i>
        <?php if ($notif_unread > 0): ?>
            <span class="notification-count"><?php echo $notif_unread > 99 ? '99+' : $notif_unread; ?></span>
        <?php endif; ?>
    </span>
</a>
<ul class="dropdown-menu notif-menu">
    <li class="notif-head">
        <span>Notifications</span>
        <span class="notif-head-count"><?php echo $notif_unread; ?> new</span>
    </li>
    <li>
        <ul class="notif-scroll">
            <?php if (empty($notif_items)): ?>
                <li class="notif-empty">You have no notifications</li>
            <?php else: foreach ($notif_items as $n): ?>
                <li class="notif-item <?php echo $n->is_seen ? '' : 'is-unread'; ?>">
                    <a href="<?php echo base_url('admin/notification/read/' . $n->id); ?>">
                        <span class="notif-icon"><i class="ti-bell"></i></span>
                        <span class="notif-body">
                            <span class="notif-text"><?php echo $n->name; ?></span>
                            <?php if (!empty($n->user_name)): ?>
                                <span class="notif-user"><i class="ti-user"></i> <?php echo htmlspecialchars($n->user_name, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <span class="notif-time">
                                <i class="ti-time"></i>
                                <?php echo date('d M Y, h:i A', strtotime($n->added_date)); ?>
                                <span class="notif-ago">&middot; <?php echo notif_time_ago($n->added_date); ?></span>
                            </span>
                        </span>
                    </a>
                </li>
            <?php endforeach; endif; ?>
        </ul>
    </li>
    <li class="notif-foot">
        <a href="<?php echo base_url('admin/notification/listing'); ?>">See all notifications</a>
    </li>
</ul>

<script>
(function () {
    var $block = $('.notification-block');
    if (!$block.length || typeof jQuery === 'undefined') { return; }

    var markUrl  = '<?php echo base_url('admin/notification/mark_seen'); ?>';
    var hasUnread = <?php echo $notif_unread > 0 ? 'true' : 'false'; ?>;

    // When the dropdown opens, flag the current user's notifications read and
    // zero the badge. Keeps the "new" highlight for this view; it clears on the
    // next page load.
    function markSeen() {
        if (!hasUnread) { return; }
        hasUnread = false;
        $.get(markUrl).always(function () {
            $block.find('.notification-count').remove();
            $block.find('.notif-head-count').text('0 new');
        });
    }

    // Bootstrap 3/4 fire shown.bs.dropdown; the click fallback covers either.
    $block.on('shown.bs.dropdown', markSeen);
    $block.find('.notification-toggle').on('click', function () { setTimeout(markSeen, 60); });
})();
</script>
