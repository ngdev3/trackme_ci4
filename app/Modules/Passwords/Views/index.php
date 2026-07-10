<?php
/** Password Manager list. */
$revealBase = site_url('passwords/reveal/');
$hasFilters = ($search !== '' || $category !== '');
$fmtDate = static fn ($d) => $d ? date('d M Y', strtotime($d)) : '-';
$totalRows = (int) ($stats['total'] ?? 0);
?>

<section class="password-workspace">
    <div class="password-toolbar">
        <div>
            <div class="password-kicker"><i class="bi bi-shield-lock"></i> Password vault</div>
            <h2>Password Manager</h2>
        </div>
        <div class="password-toolbar-actions">
            <a href="<?= site_url('passwords/list') ?>" class="btn btn-outline-secondary btn-sm" title="Password List">
                <i class="bi bi-list-check"></i>
            </a>
            <?php if (! empty($canAdd)): ?>
                <a href="<?= site_url('passwords/add') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Add Password
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="password-stats">
        <div class="password-stat">
            <span>Total</span>
            <strong><?= number_format($totalRows) ?></strong>
        </div>
        <div class="password-stat">
            <span>Categories</span>
            <strong><?= number_format((int) ($stats['categories'] ?? 0)) ?></strong>
        </div>
        <div class="password-stat">
            <span>With Website</span>
            <strong><?= number_format((int) ($stats['websites'] ?? 0)) ?></strong>
        </div>
        <div class="password-stat">
            <span>Last Updated</span>
            <strong><?= esc($fmtDate($stats['last_updated'] ?? null)) ?></strong>
        </div>
    </div>

    <div class="password-panel password-list-panel">
        <form method="get" action="<?= site_url('passwords/list') ?>" class="password-filter">
            <label class="password-search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" placeholder="Search credentials"
                       value="<?= esc($search, 'attr') ?>">
            </label>
            <select name="category" class="form-select form-select-sm">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= esc($c, 'attr') ?>" <?= $category === $c ? 'selected' : '' ?>><?= esc($c) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm" type="submit" title="Apply filters"><i class="bi bi-funnel"></i></button>
            <?php if ($hasFilters): ?>
                <a href="<?= site_url('passwords/list') ?>" class="btn btn-outline-secondary btn-sm" title="Clear filters"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>

        <?php if (empty($rows)): ?>
            <div class="password-empty">
                <span><i class="bi bi-shield-lock"></i></span>
                <h3>No password entries<?= $hasFilters ? ' found' : ' yet' ?></h3>
                <p><?= $hasFilters ? 'Try a different search or clear the filters.' : 'Add your first company credential to start building the vault.' ?></p>
                <?php if (! empty($canAdd)): ?>
                    <a href="<?= site_url('passwords/add') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Password</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive password-table-wrap">
                <table class="table align-middle password-table">
                    <thead>
                        <tr>
                            <th>Credential</th>
                            <th>Website / App</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Category</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $token = hid($r['id']);
                            $website = trim((string) (isset($r['website']) ? $r['website'] : ''));
                            $username = trim((string) (isset($r['username']) ? $r['username'] : ''));
                            ?>
                            <tr>
                                <td class="password-credential-cell">
                                    <span class="password-row-icon"><i class="bi bi-key"></i></span>
                                    <span>
                                        <a href="<?= site_url('passwords/view/' . $token) ?>" class="password-title"><?= esc($r['title']) ?></a>
                                        <small>ID <?= esc($token) ?></small>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($website !== ''): ?>
                                        <span class="password-url"><?= esc($website) ?></span>
                                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                </td>
                                <td>
                                    <?= $username !== '' ? esc($username) : '<span class="text-muted">-</span>' ?>
                                </td>
                                <td>
                                    <div class="password-secret">
                                        <code data-pw-field="<?= esc($token, 'attr') ?>">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</code>
                                        <button type="button" class="btn btn-sm btn-icon pw-toggle" data-id="<?= esc($token, 'attr') ?>" title="Show / hide password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-icon pw-copy" data-id="<?= esc($token, 'attr') ?>" title="Copy password">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <?php if (! empty($r['category'])): ?>
                                        <span class="password-badge"><?= esc($r['category']) ?></span>
                                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="password-actions">
                                        <a href="<?= site_url('passwords/view/' . $token) ?>" class="btn btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                                        <?php if (! empty($canEdit)): ?>
                                            <a href="<?= site_url('passwords/edit/' . $token) ?>" class="btn btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <?php endif; ?>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= site_url('passwords/delete/' . $token) ?>" method="post" onsubmit="return confirm('Delete this password entry? This cannot be undone.');">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-icon text-danger" title="Delete"><i class="bi bi-trash"></i></button>
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
                field.innerHTML = '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;';
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
