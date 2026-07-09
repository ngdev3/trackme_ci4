<?php
/** Add/Edit firm user. Rendered inside layout.php. */
$err  = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc(is_array($errors[$k]) ? implode(' ', $errors[$k]) : $errors[$k]) . '</div>' : '';
$role = $membership['role'] ?? old('role');
$firmModules = ['dashboard' => 'Dashboard', 'rokad' => 'Rokad Parcha', 'accounting' => 'Accounting', 'sales' => 'Sales', 'purchase' => 'Purchase', 'inventory' => 'Inventory', 'gst' => 'GST', 'reports' => 'Reports', 'notes' => 'Notes', 'reminders' => 'Reminders', 'firm_users' => 'Firm Users', 'firm_settings' => 'Firm Settings'];
$action = $mode === 'edit' ? site_url('firm-users/update/' . $user['id']) : site_url('firm-users/store');
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-person-gear me-1"></i> <?= esc($title) ?></h3></div>
            <form action="<?= $action ?>" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= esc($user['name'] ?? old('name')) ?>">
                            <?= $err('name') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required value="<?= esc($user['email'] ?? old('email')) ?>">
                            <?= $err('email') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile</label>
                            <input type="text" name="mobile" class="form-control" value="<?= esc($user['mobile'] ?? old('mobile')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <?= $mode === 'edit' ? '<span class="text-muted small">(leave blank to keep)</span>' : '<span class="text-danger">*</span>' ?></label>
                            <input type="password" name="password" class="form-control" <?= $mode === 'edit' ? '' : 'required' ?> placeholder="Min 8 characters">
                            <?= $err('password') ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" id="firmRole" class="form-select" required>
                                <option value="">— Select Role —</option>
                                <?php foreach (firm_roles() as $code => $label): ?>
                                    <option value="<?= esc($code, 'attr') ?>" <?= $role === $code ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $err('role') ?>
                            <div class="form-text">Admin has full firm access. Other roles default to the modules below; tick boxes to override.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" <?= (int) ($membership['status'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= isset($membership['status']) && (int) $membership['status'] === 0 ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Module Permissions <span class="text-muted small">(optional override)</span></label>
                            <div class="row g-2">
                                <?php foreach ($firmModules as $code => $label): ?>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= esc($code, 'attr') ?>"
                                                   id="perm_<?= esc($code, 'attr') ?>" <?= in_array($code, $overrides, true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="perm_<?= esc($code, 'attr') ?>"><?= esc($label) ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= site_url('firm-users') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i> Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>
