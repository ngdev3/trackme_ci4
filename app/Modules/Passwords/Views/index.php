<?php
/** Password Manager list. */
$revealBase = site_url('passwords/reveal/');
$hasFilters = ($search !== '' || $category !== '');
?>

<section class="password-shell">
    <div class="password-hero">
        <div>
            <span class="password-eyebrow"><i class="bi bi-shield-lock"></i> Secure vault</span>
            <h2>Password Manager</h2>
            <p>Store, find, reveal, copy and manage company credentials from one protected workspace.</p>
        </div>
        <div class="password-hero-actions">
            <a href="<?= site_url('passwords/list') ?>" class="btn btn-light border">
                <i class="bi bi-list-check me-1"></i>Password List
            </a>
            <?php if (! empty($canAdd)): ?>
                <a href="<?= site_url('passwords/add') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add Password
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="password-tabs">
        <a href="<?= site_url('passwords/list') ?>" class="password-tab active"><i class="bi bi-list-check"></i>Password List</a>
        <?php if (! empty($canAdd)): ?>
            <a href="<?= site_url('passwords/add') ?>" class="password-tab"><i class="bi bi-plus-circle"></i>Add Password</a>
        <?php endif; ?>
    </div>

    <div class="password-panel">
        <form method="get" action="<?= site_url('passwords/list') ?>" class="password-filter">
            <div class="password-search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" placeholder="Search title, website, username or category"
                       value="<?= esc($search, 'attr') ?>">
            </div>
            <select name="category" class="form-select">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= esc($c, 'attr') ?>" <?= $category === $c ? 'selected' : '' ?>><?= esc($c) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
            <?php if ($hasFilters): ?>
                <a href="<?= site_url('passwords/list') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
            <?php endif; ?>
        </form>

        <?php if (empty($rows)): ?>
            <div class="password-empty">
                <span><i class="bi bi-shield-lock"></i></span>
                <h3>No password entries<?= $hasFilters ? ' found' : ' yet' ?></h3>
                <p><?= $hasFilters ? 'Try a different search or clear the filters.' : 'Add your first company credential to start building the vault.' ?></p>
                <?php if (! empty($canAdd)): ?>
                    <a href="<?= site_url('passwords/add') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Password</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive password-table-wrap">
                <table class="table table-hover align-middle password-table">
                    <thead>
                        <tr>
                            <th>Credential</th>
                            <th class="d-none d-lg-table-cell">Website / App</th>
                            <th class="d-none d-md-table-cell">Username</th>
                            <th>Password</th>
                            <th class="d-none d-xl-table-cell">Category</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $token = hid($r['id']);
                            $website = trim((string) (isset($r['website']) ? $r['website'] : ''));
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('passwords/view/' . $token) ?>" class="password-title"><?= esc($r['title']) ?></a>
                                    <div class="password-sub d-lg-none"><?= $website !== '' ? esc($website) : 'No website' ?></div>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php if ($website !== ''): ?>
                                        <span class="password-url"><?= esc($website) ?></span>
                                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?= esc(isset($r['username']) ? $r['username'] : '') ?: '<span class="text-muted">-</span>' ?>
                                </td>
                                <td>
                                    <div class="password-secret">
                                        <code data-pw-field="<?= esc($token, 'attr') ?>">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</code>
                                        <button type="button" class="btn btn-sm btn-icon pw-toggle" data-id="<?= esc($token, 'attr') ?>" title="Show / hide password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-icon pw-copy" data-id="<?= esc($token, 'attr') ?>" title="Copy password">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="d-none d-xl-table-cell">
                                    <?php if (! empty($r['category'])): ?>
                                        <span class="password-badge"><?= esc($r['category']) ?></span>
                                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="password-actions">
                                        <a href="<?= site_url('passwords/view/' . $token) ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                        <?php if (! empty($canEdit)): ?>
                                            <a href="<?= site_url('passwords/edit/' . $token) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <?php endif; ?>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= site_url('passwords/delete/' . $token) ?>" method="post" onsubmit="return confirm('Delete this password entry? This cannot be undone.');">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($pager)): ?>
                <div class="password-pager"><?= $pager->links() ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    const REVEAL = <?= json_encode($revealBase) ?>;
    const notify = function (type, message) {
        if (window.erpNotify) window.erpNotify(type, message);
    };

    async function fetchPassword(id) {
        const res = await fetch(REVEAL + encodeURIComponent(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) throw new Error('reveal failed');
        const data = await res.json();
        return data.password || '';
    }

    document.querySelectorAll('.pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = btn.dataset.id;
            const field = document.querySelector('[data-pw-field="' + CSS.escape(id) + '"]');
            const icon = btn.querySelector('i');
            if (field.dataset.shown === '1') {
                field.innerHTML = '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;';
                field.dataset.shown = '0';
                icon.className = 'bi bi-eye';
                return;
            }
            try {
                field.textContent = await fetchPassword(id);
                field.dataset.shown = '1';
                icon.className = 'bi bi-eye-slash';
            } catch (e) {
                notify('error', 'Unable to reveal password.');
            }
        });
    });

    document.querySelectorAll('.pw-copy').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const icon = btn.querySelector('i');
            try {
                await navigator.clipboard.writeText(await fetchPassword(btn.dataset.id));
                icon.className = 'bi bi-clipboard-check text-success';
                notify('success', 'Password copied.');
            } catch (e) {
                icon.className = 'bi bi-clipboard-x text-danger';
                notify('error', 'Unable to copy password.');
            }
            setTimeout(function () { icon.className = 'bi bi-clipboard'; }, 1500);
        });
    });
})();
</script>
