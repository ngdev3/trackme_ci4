<link href="<?= base_url(); ?>assets/global/css/components-rounded.css" rel="stylesheet" type="text/css" />
<?php
$r = $result ?? false;
$is_edit = (bool) $r;
$status_value   = $r ? $r->status : (set_value('status') ?: 'open');
$priority_value = $r ? $r->priority : (set_value('priority') ?: 'medium');
$assigned_value = $r ? $r->assigned_to : set_value('assigned_to');
$due_value      = $r && $r->due_date ? $r->due_date : set_value('due_date');
?>

<style>
    .task-form-page { background:#f6f7fb; min-height:100vh; padding:18px 0 36px; color:#1f2937; }
    .task-form-shell { max-width:1180px; margin:0 auto; }
    .task-form-hero { background:#fff; border:1px solid #d9dee8; border-radius:8px; margin-bottom:14px; box-shadow:0 8px 24px rgba(31,41,55,.06); overflow:hidden; }
    .task-form-strip { height:6px; background:linear-gradient(90deg,#2557a7 0%,#00a3bf 42%,#22a06b 73%,#f5b041 100%); }
    .task-form-hero-body { padding:18px 20px 20px; display:flex; align-items:flex-start; justify-content:space-between; gap:18px; }
    .task-form-key { display:inline-flex; align-items:center; gap:8px; border:1px solid #d8e0eb; background:#f1f5f9; color:#334155; border-radius:4px; padding:7px 10px; font-size:12px; font-weight:800; text-transform:uppercase; margin-bottom:12px; }
    .task-form-title { color:#111827; font-size:28px; line-height:1.22; font-weight:800; margin:0 0 8px; }
    .task-form-subtitle { color:#64748b; font-size:13px; margin:0; }
    .task-form-actions-top { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
    .task-form-actions-top .btn, .task-form-footer .btn { border-radius:4px; font-weight:700; }
    .task-form-grid { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:14px; align-items:start; }
    .task-form-main { display:flex; flex-direction:column; gap:14px; min-width:0; }
    .task-form-side { display:flex; flex-direction:column; gap:14px; position:sticky; top:76px; min-width:0; }
    .task-card { background:#fff; border:1px solid #d9dee8; border-radius:8px; box-shadow:0 6px 18px rgba(31,41,55,.045); overflow:hidden; }
    .task-card-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 20px; border-bottom:1px solid #e8ecf2; background:#fbfcfe; }
    .task-card-title { color:#111827; font-size:14px; font-weight:800; margin:0; display:flex; align-items:center; gap:8px; }
    .task-card-title i { color:#64748b; }
    .task-card-body { padding:18px 20px; }
    .task-form-group { margin-bottom:17px; }
    .task-form-group:last-child { margin-bottom:0; }
    .task-label { display:flex; align-items:center; justify-content:space-between; gap:10px; color:#334155; font-size:12px; font-weight:800; text-transform:uppercase; margin-bottom:8px; }
    .task-label small { color:#94a3b8; font-size:11px; font-weight:700; text-transform:none; }
    .task-required { color:#d92d20; }
    .task-input, .task-select, .task-textarea { width:100%; border:1px solid #cfd7e3; border-radius:6px; background:#fff; color:#111827; font-size:14px; box-shadow:none; transition:border-color .15s, box-shadow .15s; }
    .task-input, .task-select { height:42px; padding:9px 11px; }
    .task-textarea { min-height:170px; resize:vertical; padding:12px 13px; line-height:1.6; }
    .task-input:focus, .task-select:focus, .task-textarea:focus { outline:0; border-color:#2557a7; box-shadow:0 0 0 3px rgba(37,87,167,.13); }
    .task-field-help { color:#64748b; font-size:12px; margin-top:7px; }
    .task-side-list { margin:0; padding:0; list-style:none; }
    .task-side-list li { display:grid; grid-template-columns:94px minmax(0,1fr); gap:12px; padding:13px 0; border-bottom:1px solid #edf1f6; }
    .task-side-list li:first-child { padding-top:0; }
    .task-side-list li:last-child { border-bottom:0; padding-bottom:0; }
    .task-side-label { color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; }
    .task-side-value { color:#111827; font-size:13px; font-weight:700; overflow-wrap:anywhere; }
    .task-chip-row { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
    .task-chip { display:inline-flex; align-items:center; gap:6px; border:1px solid #d9dee8; background:#fbfcfe; color:#334155; border-radius:4px; padding:7px 9px; font-size:12px; font-weight:800; }
    .task-chip i { color:#64748b; }
    .task-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:16px 20px; border-top:1px solid #e8ecf2; background:#fbfcfe; }
    .task-footer-note { color:#64748b; font-size:12px; font-weight:700; }
    .task-form-footer { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    @media (max-width:991px) {
        .task-form-grid { grid-template-columns:1fr; }
        .task-form-side { position:static; }
    }
    @media (max-width:575px) {
        .task-form-page { padding-top:12px; }
        .task-form-hero-body { flex-direction:column; padding-left:14px; padding-right:14px; }
        .task-form-actions-top { justify-content:flex-start; }
        .task-form-title { font-size:23px; }
        .task-card-header, .task-card-body, .task-footer { padding-left:14px; padding-right:14px; }
        .task-side-list li { grid-template-columns:1fr; gap:3px; }
        .task-form-footer { width:100%; }
        .task-form-footer .btn { flex:1 1 auto; }
    }
</style>

<main class="main-content task-form-page">
    <div id="mainContent">
        <div class="container-fluid task-form-shell">
            <?= isset($validation) && $validation->getErrors() ? '<div class="alert alert-danger">' . $validation->listErrors() . '</div>' : ''; ?>
            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>

            <form method="post" action="">
                <?= csrf_field() ?>

                <div class="task-form-hero">
                    <div class="task-form-strip"></div>
                    <div class="task-form-hero-body">
                        <div>
                            <div class="task-form-key"><i class="fa fa-bug"></i> <?= $is_edit ? 'Edit issue' : 'Create issue'; ?></div>
                            <h1 class="task-form-title"><?= esc($page_title); ?></h1>
                            <p class="task-form-subtitle">Capture the work item, assign ownership, and set the workflow state.</p>
                            <div class="task-chip-row">
                                <span class="task-chip"><i class="fa fa-circle-o"></i> <?= ucwords(str_replace('_', ' ', $status_value)); ?></span>
                                <span class="task-chip"><i class="fa fa-flag"></i> <?= ucfirst($priority_value); ?> priority</span>
                                <span class="task-chip"><i class="fa fa-calendar"></i> <?= $due_value ? esc(date('d M Y', strtotime($due_value))) : 'No due date'; ?></span>
                            </div>
                        </div>
                        <div class="task-form-actions-top">
                            <a href="<?= base_url('task/task'); ?>" class="btn btn-sm btn-light"><i class="fa fa-arrow-left"></i> Back</a>
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-check"></i> <?= $is_edit ? 'Update Task' : 'Create Task'; ?></button>
                        </div>
                    </div>
                </div>

                <div class="task-form-grid">
                    <div class="task-form-main">
                        <section class="task-card">
                            <div class="task-card-header">
                                <h2 class="task-card-title"><i class="fa fa-pencil-square-o"></i> Issue Details</h2>
                            </div>
                            <div class="task-card-body">
                                <div class="task-form-group">
                                    <label class="task-label" for="task-title">
                                        <span>Title <span class="task-required">*</span></span>
                                        <small>Short and searchable</small>
                                    </label>
                                    <input id="task-title" type="text" name="title" class="task-input" required
                                        placeholder="Example: Billing invoice fails on submit"
                                        value="<?= esc($r ? $r->title : set_value('title')); ?>" />
                                </div>

                                <div class="task-form-group">
                                    <label class="task-label" for="task-description">
                                        <span>Description</span>
                                        <small>Steps, expected result, notes</small>
                                    </label>
                                    <textarea id="task-description" name="description" class="task-textarea" placeholder="Add context, reproduction steps, screenshots to attach later, or acceptance criteria..."><?= esc($r ? $r->description : set_value('description')); ?></textarea>
                                    <div class="task-field-help">This appears at the top of the task detail page for everyone following the issue.</div>
                                </div>
                            </div>
                        </section>

                        <section class="task-card">
                            <div class="task-card-header">
                                <h2 class="task-card-title"><i class="fa fa-random"></i> Workflow</h2>
                            </div>
                            <div class="task-card-body">
                                <div class="row">
                                    <div class="col-md-4 task-form-group">
                                        <label class="task-label" for="task-status"><span>Status</span></label>
                                        <select id="task-status" name="status" class="task-select">
                                            <?php foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'done' => 'Done', 'closed' => 'Closed'] as $k => $v): ?>
                                                <option value="<?= $k ?>" <?= ($status_value == $k) ? 'selected' : ''; ?>><?= $v ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 task-form-group">
                                        <label class="task-label" for="task-priority"><span>Priority</span></label>
                                        <select id="task-priority" name="priority" class="task-select">
                                            <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $k => $v): ?>
                                                <option value="<?= $k ?>" <?= ($priority_value == $k) ? 'selected' : ''; ?>><?= $v ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 task-form-group">
                                        <label class="task-label" for="task-due-date"><span>Due date</span></label>
                                        <input id="task-due-date" type="date" name="due_date" class="task-input" value="<?= esc($due_value); ?>" />
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="task-form-side">
                        <section class="task-card">
                            <div class="task-card-header">
                                <h2 class="task-card-title"><i class="fa fa-user-o"></i> Ownership</h2>
                            </div>
                            <div class="task-card-body">
                                <div class="task-form-group">
                                    <label class="task-label" for="task-assignee"><span>Assign To</span></label>
                                    <select id="task-assignee" name="assigned_to" class="task-select">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?= $u->id ?>" <?= ($assigned_value == $u->id) ? 'selected' : ''; ?>>
                                                <?= esc(trim($u->first_name . ' ' . $u->last_name)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="task-field-help">The assignee will receive task notifications when supported by the workflow.</div>
                                </div>
                            </div>
                        </section>

                        <section class="task-card">
                            <div class="task-card-header">
                                <h2 class="task-card-title"><i class="fa fa-info-circle"></i> Summary</h2>
                            </div>
                            <div class="task-card-body">
                                <ul class="task-side-list">
                                    <li>
                                        <span class="task-side-label">Mode</span>
                                        <span class="task-side-value"><?= $is_edit ? 'Update existing task' : 'New task'; ?></span>
                                    </li>
                                    <li>
                                        <span class="task-side-label">Status</span>
                                        <span class="task-side-value"><?= ucwords(str_replace('_', ' ', $status_value)); ?></span>
                                    </li>
                                    <li>
                                        <span class="task-side-label">Priority</span>
                                        <span class="task-side-value"><?= ucfirst($priority_value); ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="task-footer">
                                <span class="task-footer-note"><?= $is_edit ? 'Save changes to update this issue.' : 'Create the issue to start discussion.'; ?></span>
                                <div class="task-form-footer">
                                    <a href="<?= base_url('task/task'); ?>" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Update Task' : 'Create Task'; ?></button>
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>
            </form>
        </div>
    </div>
</main>
