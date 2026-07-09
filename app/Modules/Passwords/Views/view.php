<?php
/** Password details with reveal and copy controls. */
$token = hid($row['id']);
$revealUrl = site_url('passwords/reveal/' . $token);
$website = trim((string) (isset($row['website']) ? $row['website'] : ''));
$link = $website !== '' && preg_match('#^https?://#i', $website) ? $website : ($website !== '' ? 'https://' . $website : '');
$username = trim((string) (isset($row['username']) ? $row['username'] : ''));
$created = ! empty($row['created_at']) ? date('d M Y, H:i', strtotime($row['created_at'])) : '-';
$updated = ! empty($row['updated_at']) ? date('d M Y, H:i', strtotime($row['updated_at'])) : '-';
?>

<section class="password-shell password-view-shell">
    <div class="password-hero password-detail-hero">
        <div>
            <span class="password-eyebrow"><i class="bi bi-shield-check"></i> Credential detail</span>
            <h2><?= esc($row['title']) ?></h2>
            <p><?= $website !== '' ? esc($website) : 'Stored company credential' ?></p>
        </div>
        <div class="password-hero-actions">
            <a href="<?= site_url('passwords/list') ?>" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
            <?php if (! empty($canEdit)): ?>
                <a href="<?= site_url('passwords/edit/' . $token) ?>" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="password-view-grid">
        <div class="password-panel password-detail-main">
            <div class="password-detail-head">
                <div class="password-avatar"><i class="bi bi-key-fill"></i></div>
                <div>
                    <h3><?= esc($row['title']) ?></h3>
                    <div class="password-detail-tags">
                        <?php if (! empty($row['category'])): ?>
                            <span class="password-badge"><?= esc($row['category']) ?></span>
                        <?php endif; ?>
                        <span class="password-badge soft">ID <?= esc($token) ?></span>
                    </div>
                </div>
            </div>

            <div class="password-detail-cards">
                <div class="password-info-card">
                    <span>Website / App</span>
                    <?php if ($website !== ''): ?>
                        <a href="<?= esc($link, 'attr') ?>" target="_blank" rel="noopener">
                            <?= esc($website) ?> <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    <?php else: ?>
                        <strong>-</strong>
                    <?php endif; ?>
                </div>

                <div class="password-info-card">
                    <span>Username / Email</span>
                    <div class="password-copy-line">
                        <strong id="uname"><?= $username !== '' ? esc($username) : '-' ?></strong>
                        <?php if ($username !== ''): ?>
                            <button type="button" class="btn btn-sm btn-icon" id="unameCopy" title="Copy username"><i class="bi bi-clipboard"></i></button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="password-info-card password-info-card-wide">
                    <span>Password</span>
                    <div class="password-view-secret">
                        <code id="pwField" data-shown="0">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</code>
                        <button type="button" class="btn btn-outline-secondary" id="pwToggle"><i class="bi bi-eye me-1"></i>Show</button>
                        <button type="button" class="btn btn-outline-secondary" id="pwCopy"><i class="bi bi-clipboard me-1"></i>Copy</button>
                    </div>
                </div>
            </div>

            <div class="password-notes">
                <h3><i class="bi bi-card-text me-1"></i>Notes</h3>
                <?php if (! empty($row['notes'])): ?>
                    <p><?= nl2br(esc($row['notes'])) ?></p>
                <?php else: ?>
                    <p class="text-muted mb-0">No notes added for this credential.</p>
                <?php endif; ?>
            </div>
        </div>

        <aside class="password-detail-side">
            <div class="password-panel">
                <h3 class="password-side-title">Record Info</h3>
                <dl class="password-meta">
                    <dt>Created by</dt>
                    <dd><?= esc(isset($creator) ? $creator : ('User #' . (isset($row['created_by']) ? $row['created_by'] : '-'))) ?></dd>
                    <dt>Created</dt>
                    <dd><?= esc($created) ?></dd>
                    <dt>Last updated</dt>
                    <dd><?= esc($updated) ?></dd>
                </dl>
            </div>

            <div class="password-panel">
                <h3 class="password-side-title">Actions</h3>
                <div class="d-grid gap-2">
                    <?php if (! empty($canEdit)): ?>
                        <a href="<?= site_url('passwords/edit/' . $token) ?>" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit Password</a>
                    <?php endif; ?>
                    <?php if (! empty($canDelete)): ?>
                        <form action="<?= site_url('passwords/delete/' . $token) ?>" method="post" onsubmit="return confirm('Delete this password entry? This cannot be undone.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-outline-danger w-100"><i class="bi bi-trash me-1"></i>Delete</button>
                        </form>
                    <?php endif; ?>
                    <a href="<?= site_url('passwords/list') ?>" class="btn btn-outline-secondary"><i class="bi bi-list-check me-1"></i>Password List</a>
                </div>
            </div>
        </aside>
    </div>
</section>

<script>
(function () {
    const REVEAL = <?= json_encode($revealUrl) ?>;
    let cached = null;
    const notify = function (type, message) {
        if (window.erpNotify) window.erpNotify(type, message);
    };

    async function getPw() {
        if (cached !== null) return cached;
        const res = await fetch(REVEAL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) throw new Error('reveal failed');
        const data = await res.json();
        cached = data.password || '';
        return cached;
    }

    const field = document.getElementById('pwField');
    document.getElementById('pwToggle').addEventListener('click', async function () {
        const icon = this.querySelector('i');
        if (field.dataset.shown === '1') {
            field.innerHTML = '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;';
            field.dataset.shown = '0';
            icon.className = 'bi bi-eye me-1';
            this.lastChild.nodeValue = 'Show';
            return;
        }
        try {
            field.textContent = await getPw();
            field.dataset.shown = '1';
            icon.className = 'bi bi-eye-slash me-1';
            this.lastChild.nodeValue = 'Hide';
        } catch (e) {
            notify('error', 'Unable to reveal password.');
        }
    });

    document.getElementById('pwCopy').addEventListener('click', async function () {
        const icon = this.querySelector('i');
        try {
            await navigator.clipboard.writeText(await getPw());
            icon.className = 'bi bi-clipboard-check me-1 text-success';
            notify('success', 'Password copied.');
        } catch (e) {
            icon.className = 'bi bi-clipboard-x me-1 text-danger';
            notify('error', 'Unable to copy password.');
        }
        setTimeout(function () { icon.className = 'bi bi-clipboard me-1'; }, 1500);
    });

    const uCopy = document.getElementById('unameCopy');
    if (uCopy) {
        uCopy.addEventListener('click', async function () {
            const icon = this.querySelector('i');
            try {
                await navigator.clipboard.writeText(document.getElementById('uname').textContent.trim());
                icon.className = 'bi bi-clipboard-check text-success';
                notify('success', 'Username copied.');
            } catch (e) {
                icon.className = 'bi bi-clipboard-x text-danger';
                notify('error', 'Unable to copy username.');
            }
            setTimeout(function () { icon.className = 'bi bi-clipboard'; }, 1500);
        });
    }
})();
</script>
