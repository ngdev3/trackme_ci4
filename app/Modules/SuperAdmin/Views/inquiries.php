<?php
/** Super Admin — public inquiry / contact-form submissions. Rendered inside layout.php. */
$counts   = $counts ?? ['new' => 0, 'read' => 0, 'closed' => 0];
$status   = $status ?? '';
$subjectMeta = [
    'general'     => ['General', 'secondary'],
    'pricing'     => ['Pricing', 'primary'],
    'demo'        => ['Demo', 'info'],
    'support'     => ['Support', 'warning'],
    'partnership' => ['Partnership', 'success'],
];
$statusMeta = ['new' => ['New', 'danger'], 'read' => ['Read', 'primary'], 'closed' => ['Closed', 'secondary']];
$filters = ['' => 'All', 'new' => 'New', 'read' => 'Read', 'closed' => 'Closed'];
?>
<div class="row g-3">
    <div class="col-12">
        <div class="row g-2">
            <?php foreach ([['New', $counts['new'], 'bi-envelope-exclamation', 'danger'], ['Read', $counts['read'], 'bi-envelope-open', 'primary'], ['Closed', $counts['closed'], 'bi-check2-circle', 'secondary']] as [$l, $v, $ic, $col]): ?>
                <div class="col-6 col-md-4">
                    <div class="card h-100"><div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-<?= $col ?>"><i class="bi <?= $ic ?>"></i></span>
                            <div><div class="small text-muted"><?= $l ?></div><div class="fw-bold fs-6"><?= number_format((int) $v) ?></div></div>
                        </div>
                    </div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-chat-left-text me-1"></i> Inquiries</h3>
                <form class="d-flex gap-2" method="get">
                    <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        <?php foreach ($filters as $k => $v): ?>
                            <option value="<?= esc($k, 'attr') ?>" <?= $status === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr>
                            <th>When</th><th>From</th><th>Subject</th><th>Message</th><th>Status</th><th class="text-end">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4"><i class="bi bi-inbox fs-4 d-block mb-1"></i>No inquiries yet.</td></tr>
                        <?php else: foreach ($rows as $r):
                            [$sLabel, $sColor] = $subjectMeta[$r['subject']] ?? ['—', 'secondary'];
                            [$stLabel, $stColor] = $statusMeta[$r['status']] ?? ['—', 'secondary'];
                        ?>
                            <tr>
                                <td class="text-nowrap small"><?= esc(date('d M Y, H:i', strtotime($r['created_at']))) ?></td>
                                <td>
                                    <strong><?= esc($r['name']) ?></strong>
                                    <div class="small"><a href="mailto:<?= esc($r['email'], 'attr') ?>"><?= esc($r['email']) ?></a></div>
                                    <?php if (! empty($r['phone'])): ?><div class="small text-muted"><i class="bi bi-telephone"></i> <?= esc($r['phone']) ?></div><?php endif; ?>
                                    <?php if (! empty($r['company'])): ?><div class="small text-muted"><i class="bi bi-building"></i> <?= esc($r['company']) ?></div><?php endif; ?>
                                </td>
                                <td><span class="badge text-bg-<?= esc($sColor) ?>"><?= esc($sLabel) ?></span></td>
                                <td style="max-width:360px"><div class="small" style="white-space:pre-wrap"><?= esc($r['message']) ?></div></td>
                                <td><span class="badge text-bg-<?= esc($stColor) ?>"><?= esc($stLabel) ?></span></td>
                                <td class="text-end text-nowrap">
                                    <a href="mailto:<?= esc($r['email'], 'attr') ?>?subject=<?= rawurlencode('Re: your inquiry to HissabKitaab') ?>" class="btn btn-sm btn-outline-primary" title="Reply"><i class="bi bi-reply"></i></a>
                                    <?php foreach ([['read', 'Mark read', 'bi-envelope-open', 'outline-secondary'], ['closed', 'Close', 'bi-check2', 'outline-success']] as [$st, $t, $ic, $cls]): ?>
                                        <?php if ($r['status'] !== $st): ?>
                                            <form action="<?= site_url('admin/inquiries/status/' . $r['id']) ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?><input type="hidden" name="status" value="<?= $st ?>">
                                                <button class="btn btn-sm btn-<?= $cls ?>" title="<?= $t ?>"><i class="bi <?= $ic ?>"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
