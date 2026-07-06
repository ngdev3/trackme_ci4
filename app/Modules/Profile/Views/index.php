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

<?php if (! empty($oauthProviders)): ?>
    <?php
    $providerIcons = [
        'google'    => 'bi-google',
        'apple'     => 'bi-apple',
        'microsoft' => 'bi-microsoft',
        'facebook'  => 'bi-facebook',
        'github'    => 'bi-github',
    ];
    $hasPassword = ! empty($row['password']);
    ?>
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-link-45deg me-1"></i> Connected Accounts</h3></div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Connect a social account to sign in with one click. You can disconnect it anytime (as long as you have a password set).</p>
                    <?php foreach ($oauthProviders as $key => $p): ?>
                        <?php
                        $label     = $p['label'] ?? ucfirst($key);
                        $connected = ! empty($row['provider_id']) && ($row['auth_provider'] ?? '') === $key;
                        $isEnabled = ! empty($oauthEnabled[$key]);
                        ?>
                        <div class="d-flex align-items-center justify-content-between border rounded p-3 mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi <?= esc($providerIcons[$key] ?? 'bi-box-arrow-in-right') ?> fs-4"></i>
                                <div>
                                    <strong><?= esc($label) ?></strong>
                                    <div class="small">
                                        <?php if ($connected): ?>
                                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Connected</span>
                                        <?php elseif (! $isEnabled): ?>
                                            <span class="text-muted">Not configured on this server</span>
                                        <?php else: ?>
                                            <span class="text-muted">Not connected</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <?php if ($connected): ?>
                                    <form action="<?= site_url('account/unlink/' . $key) ?>" method="post" class="d-inline"
                                          onsubmit="return confirm('Disconnect your <?= esc($label, 'attr') ?> account?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                            <?= $hasPassword ? '' : 'disabled title="Set a password first to disconnect"' ?>>
                                            <i class="bi bi-x-circle me-1"></i>Disconnect
                                        </button>
                                    </form>
                                <?php elseif ($isEnabled): ?>
                                    <a href="<?= site_url('account/link/' . $key) ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-plus-circle me-1"></i>Connect
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled>Connect</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

