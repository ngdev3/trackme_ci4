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
            <select name="priority" class="form-select form-select-sm" style="max-width:150px" data-autosubmit>
                <option value="">All priorities</option>
                <?php foreach (['high', 'medium', 'low'] as $p): ?>
                    <option value="<?= $p ?>" <?= $priority === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="erp-tbl-wrap">
            <table class="erp-tbl auto">
                <thead><tr>
                    <th class="text-start">Reminder</th><th class="text-start">Due</th><th class="text-start">Priority</th><th class="text-start">Repeat</th><th class="text-start">Status</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="erp-empty"><i class="bi bi-alarm"></i><div>No reminders found.</div></td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <?php $status = $model->displayStatus($r); $due = $r['snoozed_until'] ?: $r['remind_at']; ?>
                    <tr>
                        <td class="text-start">
                            <?= erp_cell_name((string) $r['title'], [
                                'type' => 'Reminder', 'icon' => 'alarm',
                                'accent' => $r['status'] === 'completed' ? 'green' : 'blue',
                                'chips' => array_values(array_filter([
                                    ! empty($r['priority']) ? ['t' => ucfirst((string) $r['priority']), 'ic' => 'flag-fill'] : null,
                                    ['t' => ucfirst((string) $status), 'ic' => 'check2-circle', 'ok' => $r['status'] === 'completed'],
                                ])),
                                'rows' => array_values(array_filter([
                                    ['ic' => 'calendar-event', 'l' => 'Due', 'v' => date('d M Y, H:i', strtotime($due)) . (! empty($r['snoozed_until']) ? ' (snoozed)' : '')],
                                    $r['repeat_type'] !== 'none' ? ['ic' => 'arrow-repeat', 'l' => 'Repeat', 'v' => ucfirst((string) $r['repeat_type'])] : null,
                                    ! empty($r['description']) ? ['ic' => 'card-text', 'l' => 'Description', 'v' => (string) $r['description']] : null,
                                    ! empty($r['attach_module']) ? ['ic' => 'paperclip', 'l' => 'Linked to', 'v' => ucfirst((string) $r['attach_module']) . ($r['attach_ref'] ? ' · ' . $r['attach_ref'] : '')] : null,
                                ])),
                                'foot' => 'Reminder #' . $r['id'],
                            ], ['green' => $r['status'] === 'completed']) ?>
                            <?php if (! empty($r['description'])): ?><div class="mt-1"><small class="erp-muted"><?= esc(character_limiter($r['description'], 70)) ?></small></div><?php endif; ?>
                            <?php if (! empty($r['attach_module'])): ?>
                                <div><small class="erp-muted"><i class="bi bi-paperclip"></i> <?= esc(ucfirst($r['attach_module'])) ?><?= $r['attach_ref'] ? ' · ' . esc($r['attach_ref']) : '' ?></small></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-start">
                            <div><?= esc(date('d M Y', strtotime($due))) ?></div>
                            <small class="erp-muted"><?= esc(date('H:i', strtotime($due))) ?><?= ! empty($r['snoozed_until']) ? ' (snoozed)' : '' ?></small>
                        </td>
                        <td class="text-start"><?= priority_badge($r['priority']) ?></td>
                        <td class="text-start"><span class="erp-muted"><?= $r['repeat_type'] === 'none' ? '—' : esc(ucfirst($r['repeat_type'])) ?><?= $r['repeat_type'] === 'custom' ? ' (' . (int) $r['repeat_interval'] . 'd)' : '' ?></span></td>
                        <td class="text-start"><?= reminder_status_badge($status) ?></td>
                        <td class="text-end">
                            <div class="erp-actions">
                                <?php if ($r['status'] !== 'completed' && can($moduleCode, 'edit')): ?>
                                    <form action="<?= site_url('reminders/complete/' . $r['id']) ?>" method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="erp-act green" title="Mark complete"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="erp-act slate" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="Snooze"><i class="bi bi-clock"></i></button>
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
                                    </div>
                                <?php endif; ?>
                                <?php if (can($moduleCode, 'edit')): ?>
                                    <a href="<?= site_url('reminders/edit/' . $r['id']) ?>" class="erp-act" title="Edit"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if (can($moduleCode, 'delete')): ?>
                                    <form action="<?= site_url('reminders/delete/' . $r['id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="This reminder will be deleted." data-confirm-title="Delete reminder?" data-confirm-btn="Yes, delete">
                                        <?= csrf_field() ?>
                                        <button class="erp-act red" title="Delete"><i class="bi bi-trash"></i></button>
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
