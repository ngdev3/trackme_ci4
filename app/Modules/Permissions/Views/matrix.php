<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<div class="cust-page">
    <div class="cust-hero">
        <div>
            <p class="cust-subtitle mb-1"><i class="bi bi-key"></i> Access Control</p>
            <h1 class="cust-title">Permissions - <?= esc($role['name']) ?></h1>
            <p class="cust-subtitle">Manage module permissions in one compact matrix.</p>
        </div>
        <div class="cust-hero-actions">
            <a href="<?= site_url('permissions') ?>" class="cust-btn cust-btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
            <?php if (can('permissions', 'edit')): ?>
                <button type="submit" form="permMatrixForm" class="cust-btn cust-btn-primary"><i class="bi bi-save"></i> Save Permissions</button>
            <?php endif; ?>
        </div>
    </div>

    <form id="permMatrixForm" action="<?= site_url('permissions/save/' . $role['id']) ?>" method="post">
    <?= csrf_field() ?>
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h3 class="cust-table-title"><i class="bi bi-shield-lock"></i> Permission Matrix</h3>
                <p class="cust-table-note">Toggle permission access by module.</p>
            </div>
            <?php if (can('permissions', 'edit')): ?>
                <button type="submit" class="cust-btn cust-btn-primary"><i class="bi bi-save"></i> Save Permissions</button>
            <?php endif; ?>
        </div>
        <div class="p-3">
            <?php if ((int) $role['is_superadmin'] === 1): ?>
                <div class="alert alert-dark"><i class="bi bi-info-circle me-1"></i>
                    This is a <strong>Super Admin</strong> role — it bypasses all permission checks automatically.
                    Grants below are optional/ignored.
                </div>
            <?php endif; ?>

            <div class="cust-table-wrap">
                <table class="cust-table perm-matrix align-middle">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <?php foreach ($permissions as $p): ?>
                                <th class="text-capitalize">
                                    <?= esc($p['name']) ?><br>
                                    <input type="checkbox" class="form-check-input col-check" data-perm="<?= $p['id'] ?>" title="Toggle column">
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $m): ?>
                            <?php $isParent = empty($m['parent_id']); ?>
                            <tr class="<?= $isParent ? 'table-active' : '' ?>">
                                <td class="<?= $isParent ? 'fw-bold' : 'ps-4' ?>">
                                    <i class="<?= esc($m['icon']) ?> me-1"></i><?= esc($m['name']) ?>
                                    <code class="ms-1 small text-secondary"><?= esc($m['code']) ?></code>
                                </td>
                                <?php foreach ($permissions as $p): ?>
                                    <td>
                                        <input type="checkbox" class="form-check-input perm-check"
                                               data-perm="<?= $p['id'] ?>"
                                               name="perm[<?= $m['id'] ?>][]" value="<?= $p['id'] ?>"
                                               <?= isset($granted[(int) $m['id']][$p['code']]) ? 'checked' : '' ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="cust-pager-bar">
            <?php if (can('permissions', 'edit')): ?>
                <button type="submit" class="cust-btn cust-btn-primary"><i class="bi bi-save"></i> Save Permissions</button>
            <?php endif; ?>
            <a href="<?= site_url('permissions') ?>" class="cust-btn cust-btn-ghost">Back</a>
        </div>
    </section>
    </form>
</div>



<script nonce="{csp-script-nonce}">
    // Column select-all toggles.
    document.querySelectorAll('.col-check').forEach(function (col) {
        col.addEventListener('change', function () {
            const perm = this.getAttribute('data-perm');
            document.querySelectorAll('.perm-check[data-perm="' + perm + '"]').forEach(function (cb) {
                cb.checked = col.checked;
            });
        });
    });
</script>
