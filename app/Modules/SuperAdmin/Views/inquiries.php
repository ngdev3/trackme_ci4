<?php
/** Super Admin — public inquiry / contact-form submissions. Rendered inside layout.php.
 * Shared list design (cust-* — canonical Customers look). */
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
$total   = (int) $counts['new'] + (int) $counts['read'] + (int) $counts['closed'];
?>
<div class="cust-page">

    <!-- Hero -->
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Inquiries</h4>
            <p class="cust-subtitle">Contact-form submissions from the public site — reply, mark read, or close.</p>
        </div>
        <div class="cust-hero-actions">
            <form method="get" role="search" class="cust-len">
                <label for="inqStatus">Status</label>
                <select name="status" id="inqStatus" class="cust-len-select" data-autosubmit>
                    <?php foreach ($filters as $k => $v): ?>
                        <option value="<?= esc($k, 'attr') ?>" <?= $status === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </section>

    <!-- Snapshot stat cards -->
    <section class="cust-snap-grid">
        <div class="cust-snap"><span class="cust-snap-ic ic-blue"><i class="bi bi-chat-left-text-fill"></i></span>
            <div><p class="cust-snap-label">Total</p><p class="cust-snap-value"><?= number_format($total) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-red"><i class="bi bi-envelope-exclamation-fill"></i></span>
            <div><p class="cust-snap-label">New</p><p class="cust-snap-value"><?= number_format((int) $counts['new']) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-blue"><i class="bi bi-envelope-open-fill"></i></span>
            <div><p class="cust-snap-label">Read</p><p class="cust-snap-value"><?= number_format((int) $counts['read']) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-gray"><i class="bi bi-check2-circle"></i></span>
            <div><p class="cust-snap-label">Closed</p><p class="cust-snap-value"><?= number_format((int) $counts['closed']) ?></p></div></div>
    </section>

    <!-- Table panel -->
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">Inquiry Records</h5>
                <p class="cust-table-note">Newest first. Reply opens the full thread; status controls mark read or close.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($status !== ''): ?><span class="cust-search-tag"><i class="bi bi-funnel"></i> <?= esc($filters[$status] ?? $status) ?></span><?php endif; ?>
                <span class="cust-total-tag"><i class="bi bi-chat-left-text"></i> <?= number_format($total) ?> total</span>
            </div>
        </div>

        <div class="cust-table-wrap">
            <table class="cust-table">
                <thead><tr>
                    <th class="text-start" style="width:150px">When</th>
                    <th class="text-start" style="width:240px">From</th>
                    <th class="text-start" style="width:120px">Subject</th>
                    <th class="text-start">Message</th>
                    <th class="text-center" style="width:110px">Status</th>
                    <th class="text-end" style="width:170px">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="cust-empty"><i class="bi bi-inbox"></i><div>No inquiries yet<?= $status !== '' ? ' with status “' . esc($filters[$status] ?? $status) . '”' : '' ?>.</div></td></tr>
                <?php else: foreach ($rows as $r):
                    [$sLabel, $sColor] = $subjectMeta[$r['subject']] ?? ['—', 'secondary'];
                    [$stLabel, $stColor] = $statusMeta[$r['status']] ?? ['—', 'secondary'];
                ?>
                    <tr>
                        <td class="text-start"><span class="cust-muted text-nowrap"><?= esc(date('d M Y, H:i', strtotime($r['created_at']))) ?></span></td>
                        <td class="text-start">
                            <strong><?= esc($r['name']) ?></strong>
                            <div class="small"><a href="mailto:<?= esc($r['email'], 'attr') ?>"><?= esc($r['email']) ?></a></div>
                            <?php if (! empty($r['phone'])): ?><div class="small cust-muted"><i class="bi bi-telephone"></i> <?= esc($r['phone']) ?></div><?php endif; ?>
                            <?php if (! empty($r['company'])): ?><div class="small cust-muted"><i class="bi bi-building"></i> <?= esc($r['company']) ?></div><?php endif; ?>
                        </td>
                        <td class="text-start"><span class="badge text-bg-<?= esc($sColor) ?>"><?= esc($sLabel) ?></span></td>
                        <td class="text-start" style="max-width:360px"><div class="small" style="white-space:pre-wrap"><?= esc($r['message']) ?></div></td>
                        <td class="text-center"><span class="badge text-bg-<?= esc($stColor) ?>"><?= esc($stLabel) ?></span></td>
                        <td class="text-end">
                            <div class="cust-row-actions">
                                <a href="<?= site_url('admin/inquiries/' . $r['id']) ?>" class="cust-act act-sub" title="View & reply"><i class="bi bi-chat-dots"></i></a>
                                <?php foreach ([['read', 'Mark read', 'bi-envelope-open', 'act-mail'], ['closed', 'Close', 'bi-check2', 'act-login']] as [$st, $t, $ic, $cls]): ?>
                                    <?php if ($r['status'] !== $st): ?>
                                        <form action="<?= site_url('admin/inquiries/status/' . $r['id']) ?>" method="post">
                                            <?= csrf_field() ?><input type="hidden" name="status" value="<?= $st ?>">
                                            <button class="cust-act <?= $cls ?>" title="<?= $t ?>"><i class="bi <?= $ic ?>"></i></button>
                                        </form>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
