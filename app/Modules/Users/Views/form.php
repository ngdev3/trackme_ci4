<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<form class="erp-form-page" action="<?= $mode === 'edit' ? site_url('users/update/' . $row['id']) : site_url('users/store') ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card erp-panel">
                <div class="card-header erp-panel-title">
                    <span class="panel-icon"><i class="bi bi-person-vcard"></i></span>
                    <h3 class="card-title mb-0">Registration & Identity</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'name', 'label' => 'Full Name', 'value' => old_value('name', $row), 'required' => true, 'errors' => $errors, 'icon' => 'bi bi-person']) ?></div>
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'username', 'label' => 'Username', 'value' => old_value('username', $row), 'required' => true, 'errors' => $errors, 'icon' => 'bi bi-at']) ?></div>
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => old_value('email', $row), 'required' => true, 'errors' => $errors, 'icon' => 'bi bi-envelope']) ?></div>
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'mobile', 'label' => 'Mobile', 'value' => old_value('mobile', $row), 'errors' => $errors, 'icon' => 'bi bi-phone']) ?></div>
                        <div class="col-md-6">
                            <?= view('components/form_field', [
                                'name' => 'password', 'label' => 'Password', 'type' => 'password', 'errors' => $errors,
                                'icon' => 'bi bi-lock',
                                'help' => $mode === 'edit' ? 'Leave blank to keep current password.' : 'Minimum 8 characters.',
                                'required' => $mode === 'create',
                            ]) ?>
                        </div>
                        <?php if (! empty($showRoleType)): ?>
                            <div class="col-md-6"><?= view('components/form_field', ['name' => 'user_type_id', 'label' => 'User Type', 'type' => 'select', 'options' => $typeOptions, 'value' => old_value('user_type_id', $row), 'errors' => $errors]) ?></div>
                        <?php endif; ?>
                    </div>
                    <?= view('components/form_field', ['name' => 'status', 'label' => 'Active', 'type' => 'checkbox', 'value' => old_value('status', $row, 1), 'errors' => $errors]) ?>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save User</button>
                    <a href="<?= site_url('users') ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Profile image -->
            <div class="card erp-panel">
                <div class="card-header erp-panel-title">
                    <span class="panel-icon"><i class="bi bi-image"></i></span>
                    <h3 class="card-title mb-0">Profile Image</h3>
                </div>
                <div class="card-body text-center">
                    <?php if (! empty($row['profile_image'])): ?>
                        <img src="<?= base_url('uploads/users/' . $row['profile_image']) ?>" class="avatar-lg mb-2" alt="avatar">
                    <?php else: ?>
                        <i class="bi bi-person-circle display-1 text-secondary"></i>
                    <?php endif; ?>
                    <?= view('components/form_field', ['name' => 'profile_image', 'label' => 'Upload image', 'type' => 'file', 'errors' => $errors, 'attrs' => ['accept' => 'image/*']]) ?>
                </div>
            </div>

            <?php if (! empty($showRoleType)): ?>
                <!-- Roles -->
                <div class="card erp-panel">
                    <div class="card-header erp-panel-title">
                        <span class="panel-icon"><i class="bi bi-shield-check"></i></span>
                        <h3 class="card-title mb-0">Roles</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($roles)): ?>
                            <p class="text-secondary mb-0">No roles available.</p>
                        <?php else: foreach ($roles as $role): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="<?= $role['id'] ?>"
                                    id="role_<?= $role['id'] ?>" <?= in_array((int) $role['id'], $assignedRoles, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="role_<?= $role['id'] ?>"><?= esc($role['name']) ?></label>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Security & Access -->
            <div class="card erp-panel">
                <div class="card-header erp-panel-title">
                    <span class="panel-icon"><i class="bi bi-shield-lock"></i></span>
                    <h3 class="card-title mb-0">Security &amp; Access</h3>
                </div>
                <div class="card-body">
                    <?php
                        $mustChange = old('must_change_password', ($row['must_change_password'] ?? 0));
                        $mobileOn   = old('mobile_login_enabled', ($row['mobile_login_enabled'] ?? 1));
                        $pushOn     = old('web_push_enabled', ($row['web_push_enabled'] ?? 1));
                    ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="must_change_password" value="1"
                               id="must_change_password" <?= (int) $mustChange === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="must_change_password">Force password change on next login</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="mobile_login_enabled" value="1"
                               id="mobile_login_enabled" <?= (int) $mobileOn === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mobile_login_enabled">Allow mobile app / API login</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="web_push_enabled" value="1"
                               id="web_push_enabled" <?= (int) $pushOn === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="web_push_enabled">Allow web push notifications</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Module-based access (direct per-user grants) -->
    <div class="card erp-panel mt-4">
        <div class="card-header erp-panel-title d-flex justify-content-between align-items-center">
            <span><span class="panel-icon"><i class="bi bi-grid-3x3-gap"></i></span>
            <h3 class="card-title mb-0 d-inline-block">Module Access</h3></span>
            <small class="text-secondary"><?= ! empty($showRoleType) ? "Grants below are added on top of the user's roles." : 'Direct per-user module grants.' ?></small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered perm-matrix align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:220px">Module</th>
                            <?php foreach ($permissions as $p): ?>
                                <th class="text-center text-capitalize">
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
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input perm-check"
                                               data-perm="<?= $p['id'] ?>"
                                               name="perm[<?= $m['id'] ?>][]" value="<?= $p['id'] ?>"
                                               <?= isset($grantedPerms[(int) $m['id']][$p['code']]) ? 'checked' : '' ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<script>
    // Column select-all toggles for the per-user module matrix.
    document.querySelectorAll('.col-check').forEach(function (col) {
        col.addEventListener('change', function () {
            var perm = this.getAttribute('data-perm');
            document.querySelectorAll('.perm-check[data-perm="' + perm + '"]').forEach(function (cb) {
                cb.checked = col.checked;
            });
        });
    });
</script>
