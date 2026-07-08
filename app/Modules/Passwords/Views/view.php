<?php
/** Password details (read-only) with on-demand reveal + copy. Rendered inside layout.php. */
$revealUrl = site_url('passwords/reveal/' . $row['id']);
$website   = (string) ($row['website'] ?? '');
$link      = $website !== '' && preg_match('#^https?://#i', $website) ? $website : ($website !== '' ? 'https://' . $website : '');
?>
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card shadow-sm">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="card-title mb-0"><i class="bi bi-shield-lock me-1"></i> <?= esc($row['title']) ?></h3>
                <?php if (! empty($row['category'])): ?>
                    <span class="badge text-bg-light border"><?= esc($row['category']) ?></span>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3 text-muted">Website / App</dt>
                    <dd class="col-sm-9">
                        <?php if ($website !== ''): ?>
                            <a href="<?= esc($link, 'attr') ?>" target="_blank" rel="noopener"><?= esc($website) ?> <i class="bi bi-box-arrow-up-right small"></i></a>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </dd>

                    <dt class="col-sm-3 text-muted">Username / Email</dt>
                    <dd class="col-sm-9 d-flex align-items-center gap-2">
                        <span id="uname"><?= esc($row['username'] ?? '') ?: '—' ?></span>
                        <?php if (! empty($row['username'])): ?>
                            <button type="button" class="btn btn-sm btn-link p-0 text-secondary" id="unameCopy" title="Copy username"><i class="bi bi-clipboard"></i></button>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-3 text-muted">Password</dt>
                    <dd class="col-sm-9">
                        <div class="d-flex align-items-center gap-2">
                            <code id="pwField" data-shown="0">••••••••••</code>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="pwToggle" title="Show / hide"><i class="bi bi-eye"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="pwCopy" title="Copy password"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </dd>

                    <?php if (! empty($row['notes'])): ?>
                        <dt class="col-sm-3 text-muted">Notes</dt>
                        <dd class="col-sm-9" style="white-space: pre-wrap;"><?= esc($row['notes']) ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-3 text-muted">Created by</dt>
                    <dd class="col-sm-9"><?= esc($creator ?? ('User #' . ($row['created_by'] ?? '—'))) ?></dd>

                    <dt class="col-sm-3 text-muted">Created</dt>
                    <dd class="col-sm-9"><?= esc($row['created_at'] ? date('d M Y, H:i', strtotime($row['created_at'])) : '—') ?></dd>

                    <dt class="col-sm-3 text-muted">Last updated</dt>
                    <dd class="col-sm-9"><?= esc($row['updated_at'] ? date('d M Y, H:i', strtotime($row['updated_at'])) : '—') ?></dd>
                </dl>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a href="<?= site_url('passwords') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
                <div class="d-flex gap-2">
                    <?php if (! empty($canEdit)): ?>
                        <a href="<?= site_url('passwords/edit/' . $row['id']) ?>" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <?php endif; ?>
                    <?php if (! empty($canDelete)): ?>
                        <form action="<?= site_url('passwords/delete/' . $row['id']) ?>" method="post"
                              onsubmit="return confirm('Delete this password entry? This cannot be undone.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const REVEAL = <?= json_encode($revealUrl) ?>;
    let cached = null;

    async function getPw() {
        if (cached !== null) return cached;
        const res = await fetch(REVEAL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        cached = data.password || '';
        return cached;
    }

    const field = document.getElementById('pwField');
    document.getElementById('pwToggle').addEventListener('click', async function () {
        const icon = this.querySelector('i');
        if (field.dataset.shown === '1') {
            field.textContent = '••••••••••';
            field.dataset.shown = '0';
            icon.className = 'bi bi-eye';
            return;
        }
        field.textContent = await getPw();
        field.dataset.shown = '1';
        icon.className = 'bi bi-eye-slash';
    });

    document.getElementById('pwCopy').addEventListener('click', async function () {
        const icon = this.querySelector('i');
        try { await navigator.clipboard.writeText(await getPw()); icon.className = 'bi bi-clipboard-check text-success'; }
        catch (e) { icon.className = 'bi bi-clipboard-x text-danger'; }
        setTimeout(function () { icon.className = 'bi bi-clipboard'; }, 1500);
    });

    const uCopy = document.getElementById('unameCopy');
    if (uCopy) uCopy.addEventListener('click', async function () {
        const icon = this.querySelector('i');
        try { await navigator.clipboard.writeText(document.getElementById('uname').textContent.trim()); icon.className = 'bi bi-clipboard-check text-success'; }
        catch (e) { icon.className = 'bi bi-clipboard-x text-danger'; }
        setTimeout(function () { icon.className = 'bi bi-clipboard'; }, 1500);
    });
})();
</script>
