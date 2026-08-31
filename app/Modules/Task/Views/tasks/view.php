<?php
/**
 * Task detail + Jira-style activity / comments thread (CI4 port).
 * $result = task row, $comments = nested comment tree, $current_uid = me
 */
$status_badges   = ['open' => 'badge-secondary', 'in_progress' => 'badge-info', 'done' => 'badge-success', 'closed' => 'badge-dark'];
$priority_badges = ['low' => 'tk-priority-low', 'medium' => 'tk-priority-medium', 'high' => 'tk-priority-high', 'urgent' => 'tk-priority-urgent'];
$assignee_name   = $result->assignee_first ? trim($result->assignee_first . ' ' . $result->assignee_last) : 'Unassigned';
$creator_name    = trim($result->creator_first . ' ' . $result->creator_last);
$created_at      = ! empty($result->added_date) ? date('d M Y, h:i A', strtotime($result->added_date)) : 'Not available';
$due_at          = ! empty($result->due_date) ? date('d M Y', strtotime($result->due_date)) : 'No due date';

if (! function_exists('task_comment_count')) {
    function task_comment_count($items)
    {
        $total = 0;
        foreach ((array) $items as $item) {
            $total++;
            $total += ! empty($item->replies) ? task_comment_count($item->replies) : 0;
        }
        return $total;
    }
}
$comment_total = task_comment_count($comments);

if (! function_exists('render_comment')) {
    /** Render one comment and its full reply tree. */
    function render_comment($c, $current_uid, $is_reply = false)
    {
        $initials   = strtoupper(mb_substr($c->name !== '' ? $c->name : 'U', 0, 1));
        $can_delete = ($c->user_id == $current_uid); // admins additionally allowed server-side
        ?>
        <div class="tk-comment <?= $is_reply ? 'tk-reply' : ''; ?>" data-id="<?= $c->comment_id; ?>">
            <div class="tk-avatar"><?= esc($initials); ?></div>
            <div class="tk-body">
                <div class="tk-meta">
                    <span class="tk-name"><?= esc($c->name !== '' ? $c->name : 'User'); ?></span>
                    <span class="tk-role"><?= esc($c->role); ?></span>
                    <span class="tk-time"><?= date('d M Y, h:i A', strtotime($c->added_date)); ?></span>
                </div>
                <div class="tk-text"><?= nl2br(esc($c->comment_text)); ?></div>

                <?php if (! empty($c->attachments)): ?>
                    <div class="tk-attachments">
                        <?php foreach ($c->attachments as $a):
                            $is_img = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $a->file_path); ?>
                            <?php if ($is_img): ?>
                                <a href="<?= $a->file_url; ?>" target="_blank" class="tk-att-img">
                                    <img src="<?= $a->file_url; ?>" alt="<?= esc($a->file_name); ?>" />
                                </a>
                            <?php else: ?>
                                <a href="<?= $a->file_url; ?>" target="_blank" class="tk-att-file">
                                    <i class="fa fa-paperclip"></i> <?= esc($a->file_name); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="tk-actions">
                    <a href="javascript:void(0)" class="tk-reply-btn" data-id="<?= $c->comment_id; ?>">Reply</a>
                    <?php if ($can_delete): ?>
                        <a href="javascript:void(0)" class="tk-del-btn" data-id="<?= $c->comment_id; ?>">Delete</a>
                    <?php endif; ?>
                </div>

                <?php if (! empty($c->replies)): ?>
                    <div class="tk-replies">
                        <?php foreach ($c->replies as $r) {
                            render_comment($r, $current_uid, true);
                        } ?>
                    </div>
                <?php endif; ?>

                <div class="tk-reply-form" id="reply-form-<?= $c->comment_id; ?>" style="display:none;"></div>
            </div>
        </div>
        <?php
    }
}
?>

<style>
    .tk-page { background:#f6f7fb; min-height:100vh; padding:18px 0 36px; color:#1f2937; }
    .tk-shell { max-width:1280px; margin:0 auto; }
    .tk-hero { background:#fff; border:1px solid #d9dee8; border-radius:8px; margin-bottom:14px; box-shadow:0 8px 24px rgba(31,41,55,.06); }
    .tk-hero-strip { height:6px; background:linear-gradient(90deg,#2557a7 0%,#00a3bf 42%,#22a06b 73%,#f5b041 100%); border-radius:8px 8px 0 0; }
    .tk-hero-content { padding:18px 20px 20px; }
    .tk-hero-top { display:flex; align-items:center; justify-content:space-between; gap:16px; margin:0 0 16px; }
    .tk-issue-mark { min-width:78px; height:34px; border-radius:4px; background:#f1f5f9; color:#334155; display:inline-flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; border:1px solid #d8e0eb; }
    .tk-hero-actions { display:flex; align-items:center; justify-content:flex-end; gap:8px; flex-wrap:wrap; }
    .tk-hero-actions .btn { border-radius:4px; font-weight:700; }
    .tk-task-title { font-size:28px; line-height:1.22; font-weight:800; color:#111827; margin:0 0 14px; overflow-wrap:anywhere; }
    .tk-task-meta { display:flex; flex-wrap:wrap; align-items:center; gap:8px; color:#64748b; font-size:13px; }
    .tk-task-meta span { display:inline-flex; align-items:center; gap:6px; }
    .tk-status-pill, .tk-priority-pill { display:inline-flex; align-items:center; gap:7px; border-radius:4px; padding:7px 10px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0; border:1px solid transparent; }
    .tk-status-pill.badge-secondary { background:#eef2f7; color:#475569; border-color:#dbe3ee; }
    .tk-status-pill.badge-info { background:#e6f2ff; color:#2557a7; border-color:#b8d8ff; }
    .tk-status-pill.badge-success { background:#e3fcef; color:#216e4e; border-color:#baf3db; }
    .tk-status-pill.badge-dark { background:#253858; color:#fff; border-color:#253858; }
    .tk-priority-low { background:#e3fcef; color:#216e4e; border-color:#baf3db; }
    .tk-priority-medium { background:#fff6d6; color:#7f5f01; border-color:#f8df8e; }
    .tk-priority-high { background:#ffebe6; color:#ae2e24; border-color:#ffbdad; }
    .tk-priority-urgent { background:#ffe2e0; color:#7f1d1d; border-color:#ffaaa5; }
    .tk-layout { display:grid; grid-template-columns:minmax(0,1fr) 360px; gap:14px; align-items:start; }
    .tk-main-stack { display:flex; flex-direction:column; gap:14px; min-width:0; }
    .tk-side-stack { display:flex; flex-direction:column; gap:14px; min-width:0; position:sticky; top:76px; }
    .tk-panel { background:#fff; border:1px solid #d9dee8; border-radius:8px; box-shadow:0 6px 18px rgba(31,41,55,.045); overflow:hidden; }
    .tk-panel-body { padding:18px 20px; }
    .tk-panel-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 20px; border-bottom:1px solid #e8ecf2; background:#fbfcfe; }
    .tk-section-title { font-size:14px; font-weight:800; color:#111827; margin:0; display:flex; align-items:center; gap:8px; }
    .tk-section-title i { color:#64748b; }
    .tk-count { color:#64748b; font-size:12px; font-weight:700; white-space:nowrap; }
    .tk-task-desc { color:#334155; font-size:14px; line-height:1.7; margin:0; white-space:pre-wrap; overflow-wrap:anywhere; }
    .tk-description-empty { color:#94a3b8; font-style:italic; font-size:13px; }
    .tk-side-list { margin:0; padding:0; list-style:none; }
    .tk-side-list li { display:grid; grid-template-columns:104px minmax(0,1fr); gap:12px; padding:13px 0; border-bottom:1px solid #edf1f6; }
    .tk-side-list li:first-child { padding-top:0; }
    .tk-side-list li:last-child { border-bottom:0; padding-bottom:0; }
    .tk-side-label { color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; }
    .tk-side-value { color:#111827; font-size:13px; font-weight:700; overflow-wrap:anywhere; }
    .tk-person { display:flex; align-items:center; gap:9px; min-width:0; }
    .tk-mini-avatar { width:30px; height:30px; border-radius:50%; background:#e6f2ff; color:#2557a7; display:flex; align-items:center; justify-content:center; flex:0 0 30px; font-weight:800; border:1px solid #cfe3ff; }
    .tk-composer { border-bottom:1px solid #e8ecf2; padding:16px 20px; background:#fff; }
    .tk-composer textarea { width:100%; border:1px solid #cfd7e3; border-radius:6px; padding:12px 13px; resize:vertical; min-height:82px; color:#111827; background:#fff; transition:border-color .15s, box-shadow .15s; }
    .tk-composer textarea:focus { outline:0; border-color:#2557a7; box-shadow:0 0 0 3px rgba(37,87,167,.13); }
    .tk-composer .tk-tools { margin-top:10px; display:flex; align-items:center; gap:10px; }
    .tk-composer .btn { border-radius:4px; font-weight:700; }
    .tk-reply-form { margin-top:12px; }
    .tk-reply-form .tk-composer { border:1px solid #d9dee8; border-radius:6px; padding:12px; background:#fff; }
    .tk-reply-form .tk-composer textarea { min-height:62px; background:#fbfcfe; }
    .tk-feed { padding:18px 20px 20px; background:#fff; }
    .tk-comment { display:flex; gap:12px; margin-bottom:18px; position:relative; }
    .tk-comment:last-child { margin-bottom:0; }
    .tk-reply { margin-top:14px; margin-bottom:0; }
    .tk-replies { margin-top:14px; border-left:2px solid #d9e2ef; padding-left:14px; }
    .tk-avatar { width:38px; height:38px; border-radius:50%; background:#2557a7; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; flex:0 0 38px; border:2px solid #fff; box-shadow:0 0 0 1px #d9dee8; }
    .tk-reply .tk-avatar { width:32px; height:32px; flex-basis:32px; background:#6b46c1; }
    .tk-body { flex:1; min-width:0; background:#f8fafc; border:1px solid #e1e7ef; border-radius:8px; padding:12px 14px; }
    .tk-meta { display:flex; align-items:center; flex-wrap:wrap; gap:6px 8px; margin-bottom:6px; }
    .tk-name { font-weight:800; color:#111827; }
    .tk-role { background:#edf4ff; color:#2557a7; font-size:11px; padding:2px 7px; border-radius:4px; font-weight:800; }
    .tk-time { color:#94a3b8; font-size:12px; }
    .tk-text { color:#334155; font-size:14px; line-height:1.6; overflow-wrap:anywhere; }
    .tk-attachments { margin-top:10px; display:flex; flex-wrap:wrap; gap:8px; }
    .tk-att-img img { max-width:190px; max-height:142px; border-radius:6px; border:1px solid #d9dee8; background:#fff; object-fit:cover; }
    .tk-att-file { display:inline-flex; align-items:center; gap:7px; background:#fff; border:1px solid #d9dee8; border-radius:6px; padding:7px 10px; font-size:13px; color:#334155; max-width:100%; overflow-wrap:anywhere; }
    .tk-actions { margin-top:9px; display:flex; flex-wrap:wrap; gap:12px; }
    .tk-actions a { font-size:12px; color:#64748b; font-weight:800; margin:0; }
    .tk-actions a:hover { color:#2557a7; text-decoration:none; }
    .tk-empty { color:#94a3b8; font-style:italic; padding:4px 0; font-size:13px; }
    .tk-kpi-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:16px; }
    .tk-kpi { border:1px solid #e0e6ef; border-radius:6px; padding:11px 12px; background:#fbfcfe; min-width:0; }
    .tk-kpi-label { color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; margin-bottom:5px; }
    .tk-kpi-value { color:#111827; font-size:14px; font-weight:800; overflow-wrap:anywhere; }
    @media (max-width:991px) {
        .tk-layout { grid-template-columns:1fr; }
        .tk-side-stack { position:static; }
    }
    @media (max-width:575px) {
        .tk-page { padding-top:12px; }
        .tk-hero-content, .tk-panel-body, .tk-panel-header, .tk-composer, .tk-feed { padding-left:14px; padding-right:14px; }
        .tk-hero-top { align-items:flex-start; flex-direction:column; }
        .tk-hero-actions { justify-content:flex-start; }
        .tk-task-title { font-size:22px; }
        .tk-kpi-grid { grid-template-columns:1fr; }
        .tk-side-list li { grid-template-columns:1fr; gap:3px; }
        .tk-composer .tk-tools { align-items:flex-start; flex-direction:column; }
        .tk-composer .tk-tools button { margin-left:0 !important; }
    }
</style>

<main class="main-content tk-page">
    <div id="mainContent">
        <div class="container-fluid tk-shell">

            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>

            <div class="tk-hero">
                <div class="tk-hero-strip"></div>
                <div class="tk-hero-content">
                    <div class="tk-hero-top">
                        <div class="tk-issue-mark">#<?= (int) $result->task_id; ?></div>
                        <div class="tk-hero-actions">
                            <?php if ($result->status !== 'done' && $result->status !== 'closed'): ?>
                                <button type="button" id="tk-mark-done" class="btn btn-sm btn-success">
                                    <i class="fa fa-check"></i> Mark as Done
                                </button>
                            <?php else: ?>
                                <span class="badge badge-success" style="font-size:13px;padding:7px 10px;">
                                    <i class="fa fa-check"></i> <?= ucwords(str_replace('_', ' ', $result->status)); ?>
                                </span>
                            <?php endif; ?>
                            <a href="<?= base_url('task/task/edit/' . ID_encode($result->task_id)); ?>" class="btn btn-sm btn-outline-primary"><i class="fa fa-pencil"></i> Edit</a>
                            <a href="<?= base_url('task/task'); ?>" class="btn btn-sm btn-light"><i class="fa fa-arrow-left"></i> Back</a>
                        </div>
                    </div>
                    <h1 class="tk-task-title"><?= esc($result->title); ?></h1>
                    <div class="tk-task-meta">
                        <span class="tk-status-pill <?= $status_badges[$result->status] ?? 'badge-secondary'; ?>">
                            <i class="fa fa-circle"></i> <?= ucwords(str_replace('_', ' ', $result->status)); ?>
                        </span>
                        <span class="tk-priority-pill <?= $priority_badges[$result->priority] ?? 'tk-priority-medium'; ?>">
                            <i class="fa fa-flag"></i> <?= ucfirst($result->priority); ?>
                        </span>
                        <span><i class="fa fa-comments-o"></i> <?= (int) $comment_total; ?> updates</span>
                        <span><i class="fa fa-clock-o"></i> Created <?= esc($created_at); ?></span>
                    </div>
                    <div class="tk-kpi-grid">
                        <div class="tk-kpi">
                            <div class="tk-kpi-label">Assignee</div>
                            <div class="tk-kpi-value"><?= esc($assignee_name); ?></div>
                        </div>
                        <div class="tk-kpi">
                            <div class="tk-kpi-label">Due date</div>
                            <div class="tk-kpi-value"><?= esc($due_at); ?></div>
                        </div>
                        <div class="tk-kpi">
                            <div class="tk-kpi-label">Reporter</div>
                            <div class="tk-kpi-value"><?= esc($creator_name !== '' ? $creator_name : 'Unknown'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tk-layout">
                <div class="tk-main-stack">
                    <section class="tk-panel">
                        <div class="tk-panel-header">
                            <h2 class="tk-section-title"><i class="fa fa-align-left"></i> Description</h2>
                        </div>
                        <div class="tk-panel-body">
                            <?php if (trim($result->description) !== ''): ?>
                                <div class="tk-task-desc"><?= esc($result->description); ?></div>
                            <?php else: ?>
                                <div class="tk-description-empty">No description has been added yet.</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="tk-panel">
                        <div class="tk-panel-header">
                            <h2 class="tk-section-title"><i class="fa fa-comments-o"></i> Activity</h2>
                            <span class="tk-count"><?= (int) $comment_total; ?> comments and replies</span>
                        </div>

                        <div class="tk-composer">
                            <form id="tk-main-form" enctype="multipart/form-data">
                                <input type="hidden" name="task_id" value="<?= $result->task_id; ?>" />
                                <input type="hidden" name="parent_id" value="0" />
                                <textarea name="comment_text" rows="2" placeholder="Share an update or ask a question..."></textarea>
                                <div class="tk-tools">
                                    <label class="btn btn-sm btn-outline-secondary mB-0" style="margin:0;">
                                        <i class="fa fa-paperclip"></i> Attach
                                        <input type="file" name="attachment[]" multiple style="display:none;" onchange="tkShowFiles(this,'tk-main-files')" />
                                    </label>
                                    <span id="tk-main-files" class="text-muted" style="font-size:12px;"></span>
                                    <button type="submit" class="btn btn-sm btn-primary" style="margin-left:auto;"><i class="fa fa-send"></i> Comment</button>
                                </div>
                            </form>
                        </div>

                        <div id="tk-comments" class="tk-feed">
                            <?php if (empty($comments)): ?>
                                <div class="tk-empty" id="tk-empty">No comments yet. Start the discussion.</div>
                            <?php else: ?>
                                <?php foreach ($comments as $c) {
                                    render_comment($c, $current_uid);
                                } ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <div class="tk-side-stack">
                    <aside class="tk-panel">
                        <div class="tk-panel-header">
                            <h2 class="tk-section-title"><i class="fa fa-list-ul"></i> Issue Details</h2>
                        </div>
                        <div class="tk-panel-body">
                            <ul class="tk-side-list">
                                <li>
                                    <span class="tk-side-label">Assignee</span>
                                    <span class="tk-side-value tk-person">
                                        <span class="tk-mini-avatar"><?= esc(strtoupper(substr($assignee_name !== 'Unassigned' ? $assignee_name : 'U', 0, 1))); ?></span>
                                        <?= esc($assignee_name); ?>
                                    </span>
                                </li>
                                <li>
                                    <span class="tk-side-label">Reporter</span>
                                    <span class="tk-side-value tk-person">
                                        <span class="tk-mini-avatar"><?= esc(strtoupper(substr($creator_name !== '' ? $creator_name : 'U', 0, 1))); ?></span>
                                        <?= esc($creator_name !== '' ? $creator_name : 'Unknown'); ?>
                                    </span>
                                </li>
                                <li>
                                    <span class="tk-side-label">Status</span>
                                    <span class="tk-side-value"><?= ucwords(str_replace('_', ' ', $result->status)); ?></span>
                                </li>
                                <li>
                                    <span class="tk-side-label">Priority</span>
                                    <span class="tk-side-value"><?= ucfirst($result->priority); ?></span>
                                </li>
                                <li>
                                    <span class="tk-side-label">Due date</span>
                                    <span class="tk-side-value"><?= esc($due_at); ?></span>
                                </li>
                            </ul>
                        </div>
                    </aside>

                    <aside class="tk-panel">
                        <div class="tk-panel-header">
                            <h2 class="tk-section-title"><i class="fa fa-bar-chart"></i> Tracking</h2>
                        </div>
                        <div class="tk-panel-body">
                            <ul class="tk-side-list">
                                <li>
                                    <span class="tk-side-label">Task ID</span>
                                    <span class="tk-side-value">#<?= (int) $result->task_id; ?></span>
                                </li>
                                <li>
                                    <span class="tk-side-label">Updates</span>
                                    <span class="tk-side-value"><?= (int) $comment_total; ?></span>
                                </li>
                                <li>
                                    <span class="tk-side-label">Created</span>
                                    <span class="tk-side-value"><?= esc($created_at); ?></span>
                                </li>
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    var TK = {
        taskId: <?= (int) $result->task_id; ?>,
        currentUid: <?= (int) $current_uid; ?>,
        csrfName: "<?= csrf_token() ?>",
        csrfHash: "<?= csrf_hash() ?>",
        addUrl: "<?= site_url('task/task/comment_add') ?>",
        delUrl: "<?= site_url('task/task/comment_delete') ?>",
        getUrl: "<?= site_url('task/task/get_comments') ?>",
        statusUrl: "<?= site_url('task/task/set_status') ?>",
        encId: "<?= ID_encode($result->task_id) ?>"
    };

    function tkNotifyErr(msg) { if (window.showToast) { showToast('error', msg); } else { alert(msg); } }

    // Mark the task as Done.
    $('#tk-mark-done').on('click', function () {
        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: TK.statusUrl, type: 'POST', dataType: 'json',
            data: TK.csrfName + '=' + TK.csrfHash + '&id=' + TK.encId + '&status=done',
            success: function (res) {
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    tkNotifyErr(res.error_msg || 'Could not update status');
                    btn.prop('disabled', false);
                }
            },
            error: function () { btn.prop('disabled', false); }
        });
    });

    function tkShowFiles(input, target) {
        var names = [];
        for (var i = 0; i < input.files.length; i++) names.push(input.files[i].name);
        document.getElementById(target).textContent = names.join(', ');
    }

    function tkEscape(s) {
        return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function tkRenderComment(c, isReply) {
        var initials = (c.name || 'U').charAt(0).toUpperCase();
        var atts = '';
        (c.attachments || []).forEach(function (a) {
            if (/\.(jpg|jpeg|png|gif|webp)$/i.test(a.file_path)) {
                atts += '<a href="' + a.file_url + '" target="_blank" class="tk-att-img"><img src="' + a.file_url + '"></a>';
            } else {
                atts += '<a href="' + a.file_url + '" target="_blank" class="tk-att-file"><i class="fa fa-paperclip"></i> ' + tkEscape(a.file_name) + '</a>';
            }
        });
        var canDelete = (c.user_id == TK.currentUid);
        var actions = '<a href="javascript:void(0)" class="tk-reply-btn" data-id="' + c.comment_id + '">Reply</a>';
        if (canDelete) actions += '<a href="javascript:void(0)" class="tk-del-btn" data-id="' + c.comment_id + '">Delete</a>';

        var replies = '';
        if (c.replies && c.replies.length) {
            replies = '<div class="tk-replies">';
            c.replies.forEach(function (r) { replies += tkRenderComment(r, true); });
            replies += '</div>';
        }
        var replyForm = '<div class="tk-reply-form" id="reply-form-' + c.comment_id + '" style="display:none;"></div>';

        return '<div class="tk-comment ' + (isReply ? 'tk-reply' : '') + '" data-id="' + c.comment_id + '">' +
            '<div class="tk-avatar">' + tkEscape(initials) + '</div>' +
            '<div class="tk-body">' +
            '<div class="tk-meta"><span class="tk-name">' + tkEscape(c.name || 'User') + '</span>' +
            '<span class="tk-role">' + tkEscape(c.role || '') + '</span>' +
            '<span class="tk-time">' + tkEscape(c.added_date) + '</span></div>' +
            '<div class="tk-text">' + tkEscape(c.comment_text).replace(/\n/g, '<br>') + '</div>' +
            (atts ? '<div class="tk-attachments">' + atts + '</div>' : '') +
            '<div class="tk-actions">' + actions + '</div>' + replies + replyForm +
            '</div></div>';
    }

    function tkRenderAll(tree) {
        var html = '';
        if (!tree || !tree.length) {
            html = '<div class="tk-empty" id="tk-empty">No comments yet. Start the discussion.</div>';
        } else {
            tree.forEach(function (c) { html += tkRenderComment(c, false); });
        }
        $('#tk-comments').html(html);
    }

    // Post a comment / reply (FormData so attachments ride along).
    function tkSubmit(form) {
        var fd = new FormData(form);
        fd.append(TK.csrfName, TK.csrfHash);
        $.ajax({
            url: TK.addUrl, type: 'POST', data: fd, processData: false, contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    tkRenderAll(res.data);
                } else {
                    tkNotifyErr(res.error_msg || 'Could not post comment');
                }
            }
        });
        return false;
    }

    $('#tk-main-form').on('submit', function () { return tkSubmit(this); });

    // Inline reply box.
    $(document).on('click', '.tk-reply-btn', function () {
        var id = $(this).data('id');
        var box = $('#reply-form-' + id);
        if (box.is(':visible')) { box.hide().empty(); return; }
        box.html(
            '<form class="tk-reply-submit tk-composer" enctype="multipart/form-data" style="margin-top:10px;">' +
            '<input type="hidden" name="task_id" value="' + TK.taskId + '">' +
            '<input type="hidden" name="parent_id" value="' + id + '">' +
            '<textarea name="comment_text" rows="2" placeholder="Reply..."></textarea>' +
            '<div class="tk-tools">' +
            '<label class="btn btn-sm btn-outline-secondary" style="margin:0;"><i class="fa fa-paperclip"></i> Attach' +
            '<input type="file" name="attachment[]" multiple style="display:none;" onchange="tkShowFiles(this,\'rf-' + id + '\')"></label>' +
            '<span id="rf-' + id + '" class="text-muted" style="font-size:12px;"></span>' +
            '<button type="submit" class="btn btn-sm btn-primary" style="margin-left:auto;">Reply</button>' +
            '</div></form>'
        ).show();
    });

    $(document).on('submit', '.tk-reply-submit', function () { return tkSubmit(this); });

    // Delete a comment.
    $(document).on('click', '.tk-del-btn', function () {
        var id = $(this).data('id');
        var doDelete = function () {
            $.ajax({
                url: TK.delUrl, type: 'POST', dataType: 'json',
                data: TK.csrfName + '=' + TK.csrfHash + '&comment_id=' + id,
                success: function (res) {
                    if (res.status === 'success') {
                        $.ajax({
                            url: TK.getUrl, type: 'POST', dataType: 'json',
                            data: TK.csrfName + '=' + TK.csrfHash + '&task_id=' + TK.taskId,
                            success: function (r) { tkRenderAll(r.data); }
                        });
                    } else {
                        tkNotifyErr(res.error_msg || 'Could not delete');
                    }
                }
            });
        };
        if (window.showConfirm) {
            showConfirm('Delete comment', 'Delete this comment?', doDelete, null, { type: 'warning', okText: 'Delete' });
        } else if (confirm('Delete this comment?')) {
            doDelete();
        }
    });
</script>
