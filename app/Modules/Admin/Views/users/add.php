<?php
$result  = isset($result) ? $result : null;
$errors  = isset($errors) && is_array($errors) ? $errors : [];
$is_edit = ($result && ! empty($result->id));
$v = function ($field, $fallback = '') use ($result) {
    if (isset($_POST[$field]) && $_POST[$field] !== '') {
        return $_POST[$field];
    }
    return isset($result->$field) ? $result->$field : $fallback;
};
$err = function ($f) use ($errors) { return isset($errors[$f]) ? esc($errors[$f]) : ''; };
$status_val = $v('status', 'Active');
?>
<style>
    .uform-page { padding: 24px; color: #18243c; }
    .uform-shell { max-width: 1040px; margin: 0 auto; }
    .uform-hero { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        margin-bottom: 16px; padding: 20px 24px; border: 1px solid #dce6f2; border-radius: 10px;
        background: linear-gradient(135deg, rgba(255,255,255,.98), rgba(255,255,255,.9)), radial-gradient(circle at 96% 0, rgba(23,105,194,.13), transparent 34%);
        box-shadow: 0 16px 38px rgba(24,36,60,.08); }
    .uform-hero h1 { margin: 0; font-size: 23px; font-weight: 900; }
    .uform-hero p { margin: 6px 0 0; color: #718096; font-size: 13px; font-weight: 700; }
    .uform-back { color: #0c315f !important; font-weight: 800; text-decoration: none; }
    .uform-card { padding: 20px 22px; margin-bottom: 16px; border: 1px solid #dce6f2; border-radius: 10px; background: #fff; box-shadow: 0 16px 38px rgba(24,36,60,.08); }
    .uform-card h3 { margin: 0 0 4px; font-size: 16px; font-weight: 900; }
    .uform-card .uform-sub { margin: 0 0 16px; color: #718096; font-size: 12px; font-weight: 700; }
    .uform-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px; }
    .uform-field.full { grid-column: 1 / -1; }
    .uform-field label { display: block; margin-bottom: 6px; color: #33435c; font-size: 12px; font-weight: 800; }
    .uform-field label .req { color: #dc2626; }
    .uform-field .form-control, .uform-field input, .uform-field select, .uform-field textarea {
        width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #dce6f2; border-radius: 8px; color: #18243c; background: #fbfdff; font-weight: 600; outline: 0; }
    .uform-field textarea { min-height: 84px; resize: vertical; }
    .uform-field input:focus, .uform-field select:focus, .uform-field textarea:focus { border-color: #1769c2; box-shadow: 0 0 0 4px rgba(23,105,194,.12); }
    .uform-pwd { position: relative; }
    .uform-pwd .toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: #0c315f; font-weight: 800; font-size: 12px; cursor: pointer; }
    .uform-err { display: block; min-height: 16px; margin-top: 5px; color: #dc2626; font-size: 12px; font-weight: 700; }
    .uform-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .uform-btn { min-height: 46px; padding: 12px 22px; border: 0; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .uform-btn.primary { color: #fff; background: #1769c2; box-shadow: 0 10px 22px rgba(23,105,194,.22); }
    .uform-btn.ghost { color: #40506b; background: #fff; border: 1px solid #dce6f2; text-decoration: none; display: inline-flex; align-items: center; }
    @media (max-width: 767px) { .uform-page { padding: 14px; } .uform-grid { grid-template-columns: 1fr; } }
</style>

<main class="main-content bgc-grey-100 uform-page">
    <div id="mainContent">
        <div class="container-fluid uform-shell">

            <section class="uform-hero">
                <div>
                    <h1><?= $is_edit ? 'Edit User' : 'Add New User'; ?></h1>
                    <p><?= $is_edit ? 'Update the details for user #' . (int) $result->id : 'Create a staff account with role, default firm and login details.'; ?></p>
                </div>
                <a href="<?php echo base_url('admin/users/listing'); ?>" class="uform-back"><i class="fa fa-arrow-left"></i> Back to list</a>
            </section>

            <?php echo form_open_multipart(current_url(), ['id' => 'ciatyform_id']); ?>

            <section class="uform-card">
                <h3>Basic Information</h3>
                <p class="uform-sub">Personal and contact details of the user.</p>
                <div class="uform-grid">
                    <div class="uform-field">
                        <label>First Name <span class="req">*</span></label>
                        <input type="text" id="first_name" name="first_name" maxlength="100" class="form-control" placeholder="First Name" value="<?= esc($v('first_name')); ?>">
                        <span class="uform-err"><?= $err('first_name'); ?></span>
                    </div>
                    <div class="uform-field">
                        <label>Last Name <span class="req">*</span></label>
                        <input type="text" id="last_name" name="last_name" maxlength="100" class="form-control" placeholder="Last Name" value="<?= esc($v('last_name')); ?>">
                        <span class="uform-err"><?= $err('last_name'); ?></span>
                    </div>
                    <div class="uform-field">
                        <label>Email <span class="req">*</span></label>
                        <input type="email" id="email" name="email" maxlength="100" class="form-control" placeholder="Email" value="<?= esc($v('email')); ?>">
                        <span class="uform-err"><?= $err('email'); ?></span>
                    </div>
                    <div class="uform-field">
                        <label>Mobile <span class="req">*</span></label>
                        <input type="text" id="mobile" name="mobile" maxlength="100" class="form-control" placeholder="10 digit mobile" value="<?= esc($v('mobile')); ?>">
                        <span class="uform-err"><?= $err('mobile'); ?></span>
                    </div>
                    <div class="uform-field">
                        <label>PAN Number <span class="req">*</span></label>
                        <input type="text" id="pan_number" name="pan_number" maxlength="100" class="form-control" placeholder="PAN Number" value="<?= esc($v('pan_number')); ?>">
                        <span class="uform-err"><?= $err('pan_number'); ?></span>
                    </div>
                    <div class="uform-field">
                        <label>Address <span class="req">*</span></label>
                        <input type="text" id="address" name="address" maxlength="100" class="form-control" placeholder="Address" value="<?= esc($v('address')); ?>">
                        <span class="uform-err"><?= $err('address'); ?></span>
                    </div>
                </div>
            </section>

            <section class="uform-card">
                <h3>Access &amp; Role</h3>
                <p class="uform-sub">Login password, role and the firm this user opens by default.</p>
                <div class="uform-grid">
                    <div class="uform-field">
                        <label>Password <?= $is_edit ? '' : '<span class="req">*</span>'; ?></label>
                        <div class="uform-pwd">
                            <input type="password" id="password" name="password" maxlength="100" class="form-control" placeholder="<?= $is_edit ? 'Leave blank to keep current' : 'Password'; ?>">
                            <button type="button" class="toggle" id="pwdToggle">Show</button>
                        </div>
                        <span class="uform-err"><?= $err('password'); ?></span>
                    </div>
                    <div class="uform-field">
                        <label>User Type <span class="req">*</span></label>
                        <?php $selected_user_type = $v('user_type'); ?>
                        <select id="user_type" name="user_type" class="form-control">
                            <option value="">Select User Type</option>
                            <?php if (! empty($role_types)) { foreach ($role_types as $role) { ?>
                                <option value="<?= (int) $role->user_type; ?>" <?= (string) $selected_user_type === (string) $role->user_type ? 'selected' : ''; ?>>
                                    Type <?= (int) $role->user_type; ?> - <?= esc($role->role_name); ?> (<?= esc($role->job_title); ?>)
                                </option>
                            <?php } } ?>
                        </select>
                        <span class="uform-err"><?= $err('user_type'); ?></span>
                    </div>
                    <div class="uform-field">
                        <label>Default Financial Year / Firm <span class="req">*</span></label>
                        <select name="financial_year" id="financial_year" class="form-control">
                            <option value="">-- Select Financial Year --</option>
                            <?php if (! empty($fy)) { foreach ($fy as $fyrow): ?>
                                <option value="<?= $fyrow->template_id; ?>" <?= (isset($result->default_firm) && $result->default_firm == $fyrow->template_id) ? 'selected' : ''; ?>>
                                    <?= esc($fyrow->FY . ' || ' . ($fyrow->firm_name ?? '')); ?>
                                </option>
                            <?php endforeach; } ?>
                        </select>
                        <span class="uform-err"><?= $err('financial_year'); ?></span>
                    </div>
                    <div class="uform-field">
                        <label>Status <span class="req">*</span></label>
                        <select id="status" class="form-control" name="status">
                            <option value="Active" <?= $status_val === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?= $status_val === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <span class="uform-err"><?= $err('status'); ?></span>
                    </div>
                </div>
            </section>

            <div class="uform-actions">
                <button type="submit" class="uform-btn primary"><i class="fa fa-save"></i> <?= $is_edit ? 'Update User' : 'Create User'; ?></button>
                <a href="<?php echo base_url('admin/users/listing'); ?>" class="uform-btn ghost">Cancel</a>
            </div>

            <?php echo form_close(); ?>
        </div>
    </div>
</main>

<script>
    (function () {
        var t = document.getElementById('pwdToggle');
        if (t) {
            t.addEventListener('click', function () {
                var p = document.getElementById('password');
                if (p.type === 'password') { p.type = 'text'; t.innerText = 'Hide'; }
                else { p.type = 'password'; t.innerText = 'Show'; }
            });
        }
    })();
    if (window.jQuery && $.fn.validate) {
        $('#ciatyform_id').validate({
            rules: {
                first_name: { required: true, minlength: 2 }, last_name: { required: true, minlength: 2 },
                email: { required: true, email: true },
                mobile: { required: true, digits: true, minlength: 10, maxlength: 10 },
                pan_number: { required: true, minlength: 10, maxlength: 10 },
                address: { required: true }, user_type: { required: true }, financial_year: { required: true }
            },
            errorElement: 'span', errorClass: 'text-danger'
        });
    }
</script>
