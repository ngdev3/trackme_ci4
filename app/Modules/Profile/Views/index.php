<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-person me-1"></i> Profile Details</h3></div>
            <form action="<?= site_url('profile/update') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <?= view('components/form_field', ['name' => 'name', 'label' => 'Full Name', 'value' => old_value('name', $row), 'required' => true, 'errors' => $errors, 'icon' => 'bi bi-person']) ?>
                    <?= view('components/form_field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => old_value('email', $row), 'required' => true, 'errors' => $errors, 'icon' => 'bi bi-envelope']) ?>
                    <?= view('components/form_field', ['name' => 'mobile', 'label' => 'Mobile', 'value' => old_value('mobile', $row), 'errors' => $errors, 'icon' => 'bi bi-phone']) ?>
                    <div class="mb-0">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= esc($row['username']) ?>" disabled>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button></div>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-shield-lock me-1"></i> Change Password</h3></div>
            <form action="<?= site_url('profile/password') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <?= view('components/form_field', ['name' => 'current_password', 'label' => 'Current Password', 'type' => 'password', 'errors' => $errors, 'required' => true, 'icon' => 'bi bi-lock']) ?>
                    <?= view('components/form_field', ['name' => 'new_password', 'label' => 'New Password', 'type' => 'password', 'errors' => $errors, 'required' => true, 'icon' => 'bi bi-key', 'help' => 'Minimum 8 characters.']) ?>
                    <?= view('components/form_field', ['name' => 'confirm_password', 'label' => 'Confirm New Password', 'type' => 'password', 'errors' => $errors, 'required' => true, 'icon' => 'bi bi-key']) ?>
                </div>
                <div class="card-footer"><button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Update Password</button></div>
            </form>
        </div>
    </div>
</div>

