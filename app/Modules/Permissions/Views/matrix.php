<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<form action="<?= site_url('permissions/save/' . $role['id']) ?>" method="post">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="bi bi-key me-1"></i> Permissions — <?= esc($role['name']) ?></h3>
            <?php if (can('permissions', 'edit')): ?>
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i> Save Permissions</button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ((int) $role['is_superadmin'] === 1): ?>
                <div class="alert alert-dark"><i class="bi bi-info-circle me-1"></i>
                    This is a <strong>Super Admin</strong> role — it bypasses all permission checks automatically.
                    Grants below are optional/ignored.
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered perm-matrix align-middle">
                    <thead class="table-light">
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
        <div class="card-footer d-flex gap-2">
            <?php if (can('permissions', 'edit')): ?>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Permissions</button>
            <?php endif; ?>
            <a href="<?= site_url('permissions') ?>" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>
</form>



<script>
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

