<?php /** Reminders list with view tabs + filters. Rendered inside layout.php. */ ?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-alarm me-1"></i> Reminders</h3>
        <div class="d-flex gap-2">
            <a href="<?= site_url('reminders/calendar') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-calendar3"></i> Calendar</a>
            <?php if (can($moduleCode, 'add')): ?>
                <a href="<?= site_url('reminders/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Reminder</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body pb-0">
        <!-- View tabs -->
        <ul class="nav nav-pills gap-1 mb-3 flex-wrap">
            <?php
            $tabs = [
                'all'       => ['All', null],
                'today'     => ['Today', $counts['today']],
                'upcoming'  => ['Upcoming', $counts['upcoming']],
                'overdue'   => ['Overdue', $counts['overdue']],
                'completed' => ['Completed', null],
            ];
            foreach ($tabs as $key => [$label, $count]):
            ?>
                <li class="nav-item">
                    <a class="nav-link py-1 px-3 <?= $view === $key ? 'active' : '' ?>" href="<?= site_url('reminders?view=' . $key) ?>">
                        <?= esc($label) ?>
                        <?php if ($count !== null && $count > 0): ?><span class="badge text-bg-danger ms-1"><?= $count ?></span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Filters -->
        <form class="d-flex flex-wrap gap-2 mb-3" method="get">
            <input type="hidden" name="view" value="<?= esc($view) ?>">
            <input type="search" name="q" value="<?= esc($search) ?>" class="form-control form-control-sm" style="max-width:220px" placeholder="Search reminders...">
            <select name="priority" class="form-select form-select-sm" style="max-width:150px" onchange="this.form.submit()">
                <option value="">All priorities</option>
                <?php foreach (['high', 'medium', 'low'] as $p): ?>
                    <option value="<?= $p ?>" <?= $priority === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th>Reminder</th><th>Due</th><th>Priority</th><th>Repeat</th><th>Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-4">No reminders found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <?php $status = $model->displayStatus($r); $due = $r['snoozed_until'] ?: $r['remind_at']; ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= esc($r['title']) ?></div>
                            <?php if (! empty($r['description'])): ?><small class="text-muted"><?= esc(character_limiter($r['description'], 70)) ?></small><?php endif; ?>
                            <?php if (! empty($r['attach_module'])): ?>
                                <div><small class="text-muted"><i class="bi bi-paperclip"></i> <?= esc(ucfirst($r['attach_module'])) ?><?= $r['attach_ref'] ? ' · ' . esc($r['attach_ref']) : '' ?></small></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= esc(date('d M Y', strtotime($due))) ?></div>
                            <small class="text-muted"><?= esc(date('H:i', strtotime($due))) ?><?= ! empty($r['snoozed_until']) ? ' (snoozed)' : '' ?></small>
                        </td>
                        <td><?= priority_badge($r['priority']) ?></td>
                        <td><small><?= $r['repeat_type'] === 'none' ? '—' : esc(ucfirst($r['repeat_type'])) ?><?= $r['repeat_type'] === 'custom' ? ' (' . (int) $r['repeat_interval'] . 'd)' : '' ?></small></td>
                        <td><?= reminder_status_badge($status) ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <?php if ($r['status'] !== 'completed' && can($moduleCode, 'edit')): ?>
                                    <form action="<?= site_url('reminders/complete/' . $r['id']) ?>" method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-outline-success" title="Mark complete"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Snooze"><i class="bi bi-clock"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php foreach (['10' => '10 minutes', '30' => '30 minutes', '60' => '1 hour', '1440' => '1 day'] as $m => $lbl): ?>
                                            <li>
                                                <form action="<?= site_url('reminders/snooze/' . $r['id']) ?>" method="post" class="px-2">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="minutes" value="<?= $m ?>">
                                                    <button class="dropdown-item" type="submit">Snooze <?= $lbl ?></button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php if (can($moduleCode, 'edit')): ?>
                                    <a href="<?= site_url('reminders/edit/' . $r['id']) ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if (can($moduleCode, 'delete')): ?>
                                    <form action="<?= site_url('reminders/delete/' . $r['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this reminder?');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pager)): ?><div class="card-footer d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</div>
