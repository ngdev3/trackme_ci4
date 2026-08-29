<?php
/** Password Manager list. */
$revealBase = site_url('passwords/reveal/');
$hasFilters = ($search !== '' || $category !== '');
$fmtDate = static fn ($d) => $d ? date('d M Y', strtotime($d)) : '-';
$totalRows = (int) ($stats['total'] ?? 0);
?>

<div class="cust-page">

    <!-- Hero -->
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Password Manager</h4>
            <p class="cust-subtitle">Your company's encrypted credential vault — reveal, copy and manage secrets securely.</p>
        </div>
        <div class="cust-hero-actions">
            <a href="<?= site_url('passwords/list') ?>" class="cust-btn cust-btn-ghost" title="Password List"><i class="bi bi-list-check"></i> List</a>
            <?php if (! empty($canAdd)): ?>
                <a href="<?= site_url('passwords/add') ?>" class="cust-btn cust-btn-primary"><i class="bi bi-plus-lg"></i> Add Password</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Snapshot stat cards -->
    <section class="cust-snap-grid">
        <div class="cust-snap"><span class="cust-snap-ic ic-blue"><i class="bi bi-shield-lock-fill"></i></span>
            <div><p class="cust-snap-label">Total</p><p class="cust-snap-value"><?= number_format($totalRows) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-violet"><i class="bi bi-tags-fill"></i></span>
            <div><p class="cust-snap-label">Categories</p><p class="cust-snap-value"><?= number_format((int) ($stats['categories'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-green"><i class="bi bi-globe"></i></span>
            <div><p class="cust-snap-label">With Website</p><p class="cust-snap-value"><?= number_format((int) ($stats['websites'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-gray"><i class="bi bi-clock-history"></i></span>
            <div><p class="cust-snap-label">Last Updated</p><p class="cust-snap-value" style="font-size:15px"><?= esc($fmtDate($stats['last_updated'] ?? null)) ?></p></div></div>
    </section>

    <!-- Table panel -->
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">Credentials</h5>
                <p class="cust-table-note">Passwords are encrypted at rest; reveal or copy only when you need them.</p>
            </div>
            <span class="cust-total-tag"><i class="bi bi-key"></i> <?= number_format($totalRows) ?> total</span>
        </div>

        <div class="cust-tabletools">
            <form method="get" action="<?= site_url('passwords/list') ?>" class="cust-find" role="search">
                <label for="pwSearch">Search:</label>
                <div class="cust-find-box">
                    <i class="bi bi-search"></i>
                    <input type="search" id="pwSearch" name="q" placeholder="Search credentials…" value="<?= esc($search, 'attr') ?>" autocomplete="off">
                </div>
            </form>
            <form method="get" action="<?= site_url('passwords/list') ?>" class="cust-len">
                <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= esc($search, 'attr') ?>"><?php endif; ?>
                <label for="pwCat">Category</label>
                <select name="category" id="pwCat" class="cust-len-select" data-autosubmit>
                    <option value="">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= esc($c, 'attr') ?>" <?= $category === $c ? 'selected' : '' ?>><?= esc($c) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($hasFilters): ?><a href="<?= site_url('passwords/list') ?>" class="cust-find-clear" title="Clear filters"><i class="bi bi-x-lg"></i></a><?php endif; ?>
            </form>
        </div>

        <?php if (empty($rows)): ?>
            <div class="cust-table-wrap"><table class="cust-table"><tbody>
                <tr><td class="cust-empty"><i class="bi bi-shield-lock"></i>
                    <div>No password entries<?= $hasFilters ? ' found' : ' yet' ?>.</div>
                    <div class="mt-1 small"><?= $hasFilters ? 'Try a different search or clear the filters.' : 'Add your first company credential to start building the vault.' ?></div>
                    <?php if (! empty($canAdd) && ! $hasFilters): ?><div class="mt-2"><a href="<?= site_url('passwords/add') ?>" class="cust-btn cust-btn-primary"><i class="bi bi-plus-lg"></i> Add Password</a></div><?php endif; ?>
                </td></tr>
            </tbody></table></div>
        <?php else: ?>
            <div class="cust-table-wrap">
                <table class="cust-table">
                    <thead>
                        <tr>
                            <th class="text-start">Credential</th>
                            <th class="text-start">Website / App</th>
                            <th class="text-start">Username</th>
                            <th class="text-start">Password</th>
                            <th class="text-start">Category</th>
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
                                <td class="text-start">
                                    <div class="cust-name">
                                        <span class="cust-snap-ic ic-blue" style="width:32px;height:32px;font-size:14px;border-radius:8px"><i class="bi bi-key"></i></span>
                                        <span>
                                            <a href="<?= site_url('passwords/view/' . $token) ?>" class="fw-semibold text-decoration-none"><?= esc($r['title']) ?></a>
                                            <small class="d-block cust-muted">ID <?= esc($token) ?></small>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-start">
                                    <?php if ($website !== ''): ?><span class="cust-muted"><?= esc($website) ?></span><?php else: ?><span class="cust-muted">—</span><?php endif; ?>
                                </td>
                                <td class="text-start">
                                    <?= $username !== '' ? esc($username) : '<span class="cust-muted">—</span>' ?>
                                </td>
                                <td class="text-start">
                                    <div class="cust-row-actions" style="justify-content:flex-start">
                                        <code data-pw-field="<?= esc($token, 'attr') ?>">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</code>
                                        <button type="button" class="cust-act act-view pw-toggle" data-id="<?= esc($token, 'attr') ?>" title="Show / hide password"><i class="bi bi-eye"></i></button>
                                        <button type="button" class="cust-act act-mail pw-copy" data-id="<?= esc($token, 'attr') ?>" title="Copy password"><i class="bi bi-clipboard"></i></button>
                                    </div>
                                </td>
                                <td class="text-start">
                                    <?php if (! empty($r['category'])): ?><span class="cust-planpill"><?= esc($r['category']) ?></span><?php else: ?><span class="cust-muted">—</span><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="cust-row-actions">
                                        <a href="<?= site_url('passwords/view/' . $token) ?>" class="cust-act act-view" title="View"><i class="bi bi-eye"></i></a>
                                        <?php if (! empty($canEdit)): ?>
                                            <a href="<?= site_url('passwords/edit/' . $token) ?>" class="cust-act act-edit" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <?php endif; ?>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= site_url('passwords/delete/' . $token) ?>" method="post" data-no-validate data-confirm="This password entry will be deleted. This cannot be undone." data-confirm-title="Delete password?" data-confirm-btn="Yes, delete" data-confirm-icon="error">
                                                <?= csrf_field() ?>
                                                <button class="cust-act act-del" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($pager)): ?><div class="cust-pager-bar"><?= $pager->links() ?></div><?php endif; ?>
        <?php endif; ?>
    </section>
</div>

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
