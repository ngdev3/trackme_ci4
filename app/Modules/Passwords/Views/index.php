<?php
/** Password Manager listing — search, category filter, reveal/copy. Rendered inside layout.php. */
$revealBase = site_url('passwords/reveal/');
?>

<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="bi bi-shield-lock me-1"></i> Password Manager</h3>
        <?php if (! empty($canAdd)): ?>
            <a href="<?= site_url('passwords/create') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Password
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <!-- Search + category filter -->
        <form method="get" action="<?= site_url('passwords') ?>" class="row g-2 mb-3">
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Search title, website, username…"
                           value="<?= esc($search, 'attr') ?>">
                </div>
            </div>
            <div class="col-8 col-md-4">
                <select name="category" class="form-select">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= esc($c, 'attr') ?>" <?= $category === $c ? 'selected' : '' ?>><?= esc($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </form>

        <?php if (empty($rows)): ?>
            <div class="text-center text-secondary py-5">
                <i class="bi bi-shield-lock fs-1 d-block mb-2"></i>
                <p class="mb-0">No password entries<?= ($search !== '' || $category !== '') ? ' match your filter' : ' yet' ?>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th class="d-none d-md-table-cell">Website / App</th>
                            <th class="d-none d-lg-table-cell">Username</th>
                            <th>Password</th>
                            <th class="d-none d-md-table-cell">Category</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('passwords/view/' . $r['id']) ?>" class="fw-semibold text-decoration-none"><?= esc($r['title']) ?></a>
                                    <div class="d-lg-none small text-muted"><?= esc($r['username'] ?? '') ?></div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php if (! empty($r['website'])): ?>
                                        <span class="text-truncate d-inline-block" style="max-width: 220px;"><?= esc($r['website']) ?></span>
                                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                </td>
                                <td class="d-none d-lg-table-cell"><?= esc($r['username'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <code class="pw-mask" data-pw-field="<?= esc($r['id']) ?>">••••••••</code>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-secondary pw-toggle"
                                                data-id="<?= esc($r['id']) ?>" title="Show / hide"><i class="bi bi-eye"></i></button>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-secondary pw-copy"
                                                data-id="<?= esc($r['id']) ?>" title="Copy password"><i class="bi bi-clipboard"></i></button>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php if (! empty($r['category'])): ?>
                                        <span class="badge text-bg-light border"><?= esc($r['category']) ?></span>
                                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= site_url('passwords/view/' . $r['id']) ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                    <?php if (! empty($canEdit)): ?>
                                        <a href="<?= site_url('passwords/edit/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                    <?php if (! empty($canDelete)): ?>
                                        <form action="<?= site_url('passwords/delete/' . $r['id']) ?>" method="post" class="d-inline"
                                              onsubmit="return confirm('Delete this password entry? This cannot be undone.');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($pager)): ?><div class="mt-3 d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const REVEAL = <?= json_encode($revealBase) ?>;

    async function fetchPassword(id) {
        const res = await fetch(REVEAL + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) throw new Error('reveal failed');
        const data = await res.json();
        return data.password || '';
    }

    // Show / hide
    document.querySelectorAll('.pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = btn.dataset.id;
            const field = document.querySelector('[data-pw-field="' + id + '"]');
            const icon = btn.querySelector('i');
            if (field.dataset.shown === '1') {
                field.textContent = '••••••••';
                field.dataset.shown = '0';
                icon.className = 'bi bi-eye';
                return;
            }
            try {
                field.textContent = await fetchPassword(id);
                field.dataset.shown = '1';
                icon.className = 'bi bi-eye-slash';
            } catch (e) { field.textContent = '(error)'; }
        });
    });

    // Copy
    document.querySelectorAll('.pw-copy').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const icon = btn.querySelector('i');
            try {
                const pw = await fetchPassword(btn.dataset.id);
                await navigator.clipboard.writeText(pw);
                icon.className = 'bi bi-clipboard-check text-success';
                setTimeout(function () { icon.className = 'bi bi-clipboard'; }, 1500);
            } catch (e) {
                icon.className = 'bi bi-clipboard-x text-danger';
                setTimeout(function () { icon.className = 'bi bi-clipboard'; }, 1500);
            }
        });
    });
})();
</script>
