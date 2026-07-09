<?php /** Rokad search results. Rendered inside layout.php. */ ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-search me-1"></i> Search results for &ldquo;<?= esc($q) ?>&rdquo;</h3>
        <a href="<?= site_url('rokad') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Rokad</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Date</th><th>Particular</th><th class="text-end">Jama</th><th class="text-end">Naam</th><th>Remarks</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-4">No matching transactions.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><a href="<?= site_url('rokad?date=' . $r['entry_date']) ?>"><?= esc(date('d-m-Y', strtotime($r['entry_date']))) ?></a></td>
                        <td class="fw-semibold"><?= esc($r['particular']) ?></td>
                        <td class="text-end text-success"><?= (float) $r['jama'] > 0 ? number_format((float) $r['jama'], 2) : '' ?></td>
                        <td class="text-end text-danger"><?= (float) $r['naam'] > 0 ? number_format((float) $r['naam'], 2) : '' ?></td>
                        <td><small class="text-muted"><?= esc($r['remarks'] ?: '') ?></small></td>
                        <td class="text-end"><a href="<?= site_url('rokad?date=' . $r['entry_date']) ?>" class="btn btn-sm btn-outline-secondary py-0">Open day</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
