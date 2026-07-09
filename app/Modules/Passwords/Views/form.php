<?php
/** Password add/edit form. */
$err = function ($k) use ($errors) {
    return isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc($errors[$k]) . '</div>' : '';
};
$v = function ($k, $d = '') use ($row) {
    return esc(old($k, isset($row[$k]) ? $row[$k] : $d), 'attr');
};
$isEdit = (isset($mode) ? $mode : '') === 'edit';
$token = $isEdit ? hid($row['id']) : null;
$action = $isEdit ? site_url('passwords/update/' . $token) : site_url('passwords/store');
$curCat = old('category', isset($row['category']) ? $row['category'] : '');
?>

<section class="password-shell">
    <div class="password-hero">
        <div>
            <span class="password-eyebrow"><i class="bi bi-key"></i> <?= $isEdit ? 'Update credential' : 'New credential' ?></span>
            <h2><?= $isEdit ? 'Edit Password' : 'Add Password' ?></h2>
            <p><?= $isEdit ? 'Update account details without exposing the saved password unless you choose to change it.' : 'Add a company credential with encrypted storage and quick future access.' ?></p>
        </div>
        <div class="password-hero-actions">
            <a href="<?= site_url('passwords/list') ?>" class="btn btn-light border"><i class="bi bi-list-check me-1"></i>Password List</a>
        </div>
    </div>

    <div class="password-tabs">
        <a href="<?= site_url('passwords/list') ?>" class="password-tab"><i class="bi bi-list-check"></i>Password List</a>
        <a href="<?= site_url('passwords/add') ?>" class="password-tab <?= $isEdit ? '' : 'active' ?>"><i class="bi bi-plus-circle"></i>Add Password</a>
    </div>

    <form action="<?= $action ?>" method="post" autocomplete="off" class="password-form">
        <?= csrf_field() ?>
        <div class="password-form-main">
            <div class="password-panel">
                <div class="password-section-head">
                    <div>
                        <h3>Credential Details</h3>
                        <p>Use a clear title and enough context so the team can identify this entry later.</p>
                    </div>
                    <i class="bi bi-shield-lock"></i>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Title / Name <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-lg <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                               required autofocus value="<?= $v('title') ?>" placeholder="Company Gmail">
                        <?= $err('title') ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Website / App Name</label>
                        <input type="text" name="website" class="form-control" value="<?= $v('website') ?>" placeholder="gmail.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username / Email</label>
                        <input type="text" name="username" class="form-control" value="<?= $v('username') ?>" placeholder="accounts@company.com">
                    </div>

                    <div class="col-lg-8">
                        <label class="form-label">Password <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                        <div class="input-group password-input-group">
                            <input type="password" name="password" id="pwInput"
                                   class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                   <?= $isEdit ? '' : 'required' ?>
                                   value="<?= $isEdit ? esc(isset($currentPass) ? $currentPass : '', 'attr') : '' ?>"
                                   placeholder="<?= $isEdit ? 'Leave blank to keep current password' : 'Enter a password' ?>">
                            <button class="btn btn-outline-secondary" type="button" id="pwShow" title="Show / hide password"><i class="bi bi-eye"></i></button>
                            <button class="btn btn-outline-secondary" type="button" id="pwGen" title="Generate strong password"><i class="bi bi-magic"></i></button>
                            <button class="btn btn-outline-secondary" type="button" id="pwCopy" title="Copy password"><i class="bi bi-clipboard"></i></button>
                        </div>
                        <?= $err('password') ?>
                        <?php if ($isEdit): ?>
                            <div class="form-text">Keep this field unchanged to preserve the existing password.</div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-control" list="pwCategories"
                               value="<?= esc($curCat, 'attr') ?>" placeholder="Choose or type">
                        <datalist id="pwCategories">
                            <?php foreach ($presetCats as $c): ?>
                                <option value="<?= esc($c, 'attr') ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="5" placeholder="Recovery notes, security questions, ownership details"><?= esc(old('notes', isset($row['notes']) ? $row['notes'] : '')) ?></textarea>
                    </div>
                </div>
            </div>

            <aside class="password-side">
                <div class="password-side-card">
                    <i class="bi bi-lock-fill"></i>
                    <h3>Encrypted Storage</h3>
                    <p>Passwords are encrypted before saving and only revealed on demand for users with access.</p>
                </div>
                <div class="password-side-card">
                    <i class="bi bi-check2-circle"></i>
                    <h3>Save Flow</h3>
                    <p><?= $isEdit ? 'After saving, you will return to this credential detail page.' : 'After saving, you will be redirected to the password list.' ?></p>
                </div>
            </aside>
        </div>

        <div class="password-form-actions">
            <a href="<?= site_url('passwords/list') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Cancel</a>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i><?= $isEdit ? 'Save Changes' : 'Save Password' ?></button>
        </div>
    </form>
</section>

<script>
(function () {
    const input = document.getElementById('pwInput');
    const notify = function (type, message) {
        if (window.erpNotify) window.erpNotify(type, message);
    };

    document.getElementById('pwShow').addEventListener('click', function () {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    document.getElementById('pwGen').addEventListener('click', function () {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%&*?';
        let out = '';
        const arr = new Uint32Array(18);
        (window.crypto || window.msCrypto).getRandomValues(arr);
        for (let i = 0; i < arr.length; i++) out += chars[arr[i] % chars.length];
        input.value = out;
        input.type = 'text';
        document.querySelector('#pwShow i').className = 'bi bi-eye-slash';
        notify('success', 'Strong password generated.');
    });

    document.getElementById('pwCopy').addEventListener('click', async function () {
        const icon = this.querySelector('i');
        try {
            await navigator.clipboard.writeText(input.value || '');
            icon.className = 'bi bi-clipboard-check text-success';
            notify('success', 'Password copied.');
        } catch (e) {
            icon.className = 'bi bi-clipboard-x text-danger';
            notify('error', 'Unable to copy password.');
        }
        setTimeout(function () { icon.className = 'bi bi-clipboard'; }, 1500);
    });
})();
</script>
