<?php
/** Password add/edit form. Rendered inside layout.php. */
$err = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc($errors[$k]) . '</div>' : '';
$v   = fn ($k, $d = '') => esc(old($k, $row[$k] ?? $d));
$isEdit  = ($mode ?? '') === 'edit';
$action  = $isEdit ? site_url('passwords/update/' . $row['id']) : site_url('passwords/store');
$curCat  = old('category', $row['category'] ?? '');
?>
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock fs-5"></i>
                <h3 class="card-title mb-0"><?= $isEdit ? 'Edit Password' : 'Add Password' ?></h3>
            </div>

            <form action="<?= $action ?>" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Title / Name <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required autofocus
                                   value="<?= $v('title') ?>" placeholder="e.g. Company Gmail">
                            <?= $err('title') ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Website / App Name</label>
                            <input type="text" name="website" class="form-control" value="<?= $v('website') ?>" placeholder="e.g. gmail.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username / Email</label>
                            <input type="text" name="username" class="form-control" value="<?= $v('username') ?>" placeholder="e.g. accounts@company.com">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Password <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                            <div class="input-group">
                                <input type="password" name="password" id="pwInput" class="form-control"
                                       <?= $isEdit ? '' : 'required' ?>
                                       value="<?= $isEdit ? esc($currentPass ?? '') : '' ?>"
                                       placeholder="<?= $isEdit ? 'Leave blank to keep current password' : 'Enter a password' ?>">
                                <button class="btn btn-outline-secondary" type="button" id="pwShow" title="Show / hide"><i class="bi bi-eye"></i></button>
                                <button class="btn btn-outline-secondary" type="button" id="pwGen" title="Generate strong password"><i class="bi bi-magic"></i></button>
                            </div>
                            <?= $err('password') ?>
                            <?php if ($isEdit): ?><div class="form-text">Leave unchanged to keep the existing password.</div><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" list="pwCategories"
                                   value="<?= esc($curCat, 'attr') ?>" placeholder="Choose or type…">
                            <datalist id="pwCategories">
                                <?php foreach ($presetCats as $c): ?>
                                    <option value="<?= esc($c, 'attr') ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes (recovery info, security questions, etc.)"><?= $v('notes') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= site_url('passwords') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Cancel</a>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i><?= $isEdit ? 'Save Changes' : 'Save Password' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('pwInput');
    document.getElementById('pwShow').addEventListener('click', function () {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
    document.getElementById('pwGen').addEventListener('click', function () {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%&*?';
        let out = '';
        const arr = new Uint32Array(16);
        (window.crypto || window.msCrypto).getRandomValues(arr);
        for (let i = 0; i < 16; i++) out += chars[arr[i] % chars.length];
        input.value = out;
        input.type = 'text';
        document.querySelector('#pwShow i').className = 'bi bi-eye-slash';
    });
})();
</script>
