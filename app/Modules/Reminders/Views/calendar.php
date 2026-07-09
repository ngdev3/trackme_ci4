<?php
/** Month calendar of reminders. Rendered inside layout.php. */
$first      = strtotime($ym . '-01');
$daysIn     = (int) date('t', $first);
$startDow   = (int) date('w', $first);            // 0 (Sun) .. 6 (Sat)
$prevYm     = date('Y-m', strtotime('-1 month', $first));
$nextYm     = date('Y-m', strtotime('+1 month', $first));
$todayJ     = (date('Y-m') === $ym) ? (int) date('j') : 0;
$priColors  = ['high' => '#dc3545', 'medium' => '#fd7e14', 'low' => '#6c757d'];
?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= site_url('reminders/calendar?ym=' . $prevYm) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
            <h3 class="card-title mb-0"><?= esc(date('F Y', $first)) ?></h3>
            <a href="<?= site_url('reminders/calendar?ym=' . $nextYm) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('reminders/calendar') ?>" class="btn btn-sm btn-outline-secondary">Today</a>
            <a href="<?= site_url('reminders') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-ul"></i> List</a>
            <?php if (can($moduleCode, 'add')): ?>
                <a href="<?= site_url('reminders/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-bordered mb-0 text-center" style="table-layout:fixed">
                <thead>
                    <tr class="small text-muted">
                        <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d): ?>
                            <th class="fw-normal"><?= $d ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $cell = 0;
                    $day  = 1;
                    $totalCells = $startDow + $daysIn;
                    $rows = (int) ceil($totalCells / 7);
                    for ($r = 0; $r < $rows; $r++): ?>
                        <tr style="height:110px">
                            <?php for ($c = 0; $c < 7; $c++): $cell++; ?>
                                <?php if ($cell <= $startDow || $day > $daysIn): ?>
                                    <td class="bg-body-tertiary"></td>
                                <?php else: ?>
                                    <td class="text-start align-top <?= $day === $todayJ ? 'bg-primary-subtle' : '' ?>" style="overflow:hidden">
                                        <div class="small fw-bold <?= $day === $todayJ ? 'text-primary' : 'text-muted' ?>"><?= $day ?></div>
                                        <?php foreach ($byDay[$day] ?? [] as $rm): ?>
                                            <?php $st = $model->displayStatus($rm); $col = $priColors[$rm['priority']] ?? '#6c757d'; ?>
                                            <a href="<?= site_url('reminders/edit/' . $rm['id']) ?>"
                                               class="d-block text-truncate text-decoration-none small mb-1 px-1 rounded"
                                               style="background:<?= $col ?>1a;border-left:3px solid <?= $col ?>;<?= $rm['status'] === 'completed' ? 'text-decoration:line-through;opacity:.6;' : '' ?>"
                                               title="<?= esc($rm['title'], 'attr') ?> — <?= esc(date('H:i', strtotime($rm['remind_at'])), 'attr') ?>">
                                                <span class="text-body"><?= esc(date('H:i', strtotime($rm['remind_at']))) ?> <?= esc($rm['title']) ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </td>
                                <?php $day++; endif; ?>
                            <?php endfor; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
