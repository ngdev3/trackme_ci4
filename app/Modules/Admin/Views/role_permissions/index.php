<style>
    .role-page {
        padding: 24px;
        color: #18243c;
    }

    .role-shell {
        max-width: 1440px;
        margin: 0 auto;
    }

    .role-hero,
    .role-card {
        border: 1px solid var(--tm-line, #dce6f2);
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 16px 38px rgba(24, 36, 60, .08);
    }

    .role-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
        padding: 22px 24px;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(255, 255, 255, .9)),
            radial-gradient(circle at 96% 0, rgba(var(--tm-brand-rgb, 23, 105, 194), .13), transparent 34%);
    }

    .role-title {
        margin: 0;
        color: var(--tm-ink, #18243c);
        font-size: 25px;
        font-weight: 900;
    }

    .role-subtitle {
        margin: 6px 0 0;
        color: var(--tm-muted, #718096);
        font-size: 13px;
        font-weight: 700;
        line-height: 1.55;
    }

    .role-save-btn {
        min-height: 42px;
        border: 0;
        border-radius: 8px;
        padding: 10px 16px;
        color: #fff;
        background: var(--tm-brand, #1769c2);
        font-weight: 900;
        box-shadow: 0 10px 22px rgba(var(--tm-brand-rgb, 23, 105, 194), .22);
    }

    .role-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 16px;
        padding: 12px 14px;
        border: 1px solid rgba(var(--tm-brand-rgb, 23, 105, 194), .18);
        border-radius: 8px;
        color: var(--tm-brand-dark, #0c315f);
        background: #fff;
        font-size: 13px;
        font-weight: 800;
    }

    .role-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 16px;
    }

    .role-card {
        padding: 18px;
    }

    .role-card h3 {
        margin: 0 0 14px;
        color: var(--tm-ink, #18243c);
        font-size: 16px;
        font-weight: 900;
    }

    .role-form-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .role-field {
        margin-bottom: 12px;
    }

    .role-field label {
        display: block;
        margin-bottom: 6px;
        color: var(--tm-muted, #718096);
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .role-field input,
    .role-field textarea,
    .role-field select {
        width: 100%;
        min-height: 40px;
        padding: 9px 11px;
        border: 1px solid var(--tm-line, #dce6f2);
        border-radius: 8px;
        color: var(--tm-ink, #18243c);
        background: #fbfdff;
        font-weight: 800;
        outline: 0;
    }

    .role-field textarea {
        min-height: 78px;
        resize: vertical;
    }

    .permission-card {
        overflow: hidden;
    }

    .permission-head {
        padding: 18px 20px;
        border-bottom: 1px solid #edf2f7;
    }

    .permission-head h3 {
        margin: 0;
        color: var(--tm-ink, #18243c);
        font-size: 17px;
        font-weight: 900;
    }

    .permission-table-wrap {
        overflow-x: auto;
    }

    .permission-table {
        width: 100%;
        min-width: 860px;
        margin: 0;
    }

    .permission-table th,
    .permission-table td {
        padding: 10px 12px !important;
        vertical-align: middle !important;
        border-color: #edf2f7 !important;
    }

    .permission-table th {
        color: var(--tm-muted, #718096);
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        background: #f8fbff;
    }

    .permission-table td:first-child {
        color: var(--tm-ink, #18243c);
        font-weight: 900;
    }

    .permission-check {
        width: 18px;
        height: 18px;
        accent-color: var(--tm-brand, #1769c2);
    }

    @media (max-width: 767px) {
        .role-page {
            padding: 14px;
        }

        .role-hero {
            align-items: stretch;
            flex-direction: column;
        }

        .role-grid,
        .role-form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="main-content bgc-grey-100 role-page">
    <div id="mainContent">
        <div class="container-fluid role-shell">
            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>
            <form method="post">
                <section class="role-hero">
                    <div>
                        <h1 class="role-title">Roles & Permissions</h1>
                        <p class="role-subtitle">Define each user type's role, job responsibility, and access permissions for ERP modules.</p>
                    </div>
                    <button type="submit" class="role-save-btn"><i class="ti-save"></i> Save Permissions</button>
                </section>

                <div class="role-note">
                    <i class="ti-info-alt"></i>
                    <span>User type 1 is treated as Administrator and always has full system access.</span>
                </div>

                <section class="role-grid">
                    <?php foreach ($roles as $role) { ?>
                        <div class="role-card">
                            <h3>User Type <?= (int) $role->user_type; ?></h3>
                            <div class="role-form-row">
                                <div class="role-field">
                                    <label>Role Name</label>
                                    <input type="text" name="roles[<?= (int) $role->user_type; ?>][role_name]" value="<?= htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="role-field">
                                    <label>Job Title</label>
                                    <input type="text" name="roles[<?= (int) $role->user_type; ?>][job_title]" value="<?= htmlspecialchars($role->job_title, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="role-field">
                                <label>Job Description</label>
                                <textarea name="roles[<?= (int) $role->user_type; ?>][job_description]"><?= htmlspecialchars($role->job_description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="role-field">
                                <label>Status</label>
                                <select name="roles[<?= (int) $role->user_type; ?>][status]">
                                    <option value="Active" <?= $role->status === 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?= $role->status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    <?php } ?>
                </section>

                <section class="role-card permission-card">
                    <div class="permission-head">
                        <h3>Module Permission Matrix</h3>
                    </div>
                    <div class="permission-table-wrap">
                        <table class="table table-bordered permission-table">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <?php foreach ($roles as $role) { ?>
                                        <th colspan="4"><?= htmlspecialchars($role->role_name, ENT_QUOTES, 'UTF-8'); ?> (Type <?= (int) $role->user_type; ?>)</th>
                                    <?php } ?>
                                </tr>
                                <tr>
                                    <th></th>
                                    <?php foreach ($roles as $role) { ?>
                                        <th>View</th>
                                        <th>Add</th>
                                        <th>Edit</th>
                                        <th>Delete</th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modules as $module_key => $module_name) { ?>
                                    <tr>
                                        <td><?= htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <?php foreach ($roles as $role) {
                                            $user_type = (int) $role->user_type;
                                            $row = isset($permissions[$user_type][$module_key]) ? $permissions[$user_type][$module_key] : null;
                                            foreach (array('can_view', 'can_add', 'can_edit', 'can_delete') as $permission_key) {
                                                $checked = $row && (int) $row->{$permission_key} === 1 ? 'checked' : '';
                                                ?>
                                                <td class="text-center">
                                                    <input type="checkbox" class="permission-check" name="permissions[<?= $user_type; ?>][<?= htmlspecialchars($module_key, ENT_QUOTES, 'UTF-8'); ?>][<?= $permission_key; ?>]" value="1" <?= $checked; ?>>
                                                </td>
                                            <?php }
                                        } ?>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </form>
        </div>
    </div>
</main>
