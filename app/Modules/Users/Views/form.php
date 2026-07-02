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
                        <div class="col-md-6"><?= view('components/form_field', ['name' => 'user_type_id', 'label' => 'User Type', 'type' => 'select', 'options' => $typeOptions, 'value' => old_value('user_type_id', $row), 'errors' => $errors]) ?></div>
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
        </div>
    </div>
</form>
