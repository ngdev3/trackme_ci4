<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>

<!-- ============================ Top: identity + score ============================ -->
<div class="row g-3">
    <!-- Avatar / photo upload -->
    <div class="col-lg-5 col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-person-badge me-1"></i> Profile Photo</h3></div>
            <div class="card-body text-center">
                <div class="profile-avatar-wrap mx-auto mb-3">
                    <?= user_avatar($row, 'profile-avatar-lg', 'bi-person') ?>
                </div>
                <h4 class="mb-0"><?= esc(! empty($row['name']) ? $row['name'] : 'Unnamed User') ?></h4>
                <div class="text-muted small mb-1"><?= esc($row['email'] ?? '') ?></div>
                <span class="badge text-bg-light border"><?= esc(ucwords(str_replace('_', ' ', (string) ($row['account_type'] ?? 'member')))) ?></span>

                <form action="<?= site_url('profile/avatar') ?>" method="post" enctype="multipart/form-data" class="mt-3">
                    <?= csrf_field() ?>
                    <input type="file" name="avatar" id="avatarInput" accept="image/png,image/jpeg,image/webp,image/gif" class="form-control mb-2" required>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i> Upload Photo</button>
                        <?php if (! empty($row['profile_image'])): ?>
                            <button type="submit" formaction="<?= site_url('profile/avatar/remove') ?>" class="btn btn-outline-danger btn-sm"
                                    formnovalidate data-confirm="Your profile photo will be removed." data-confirm-title="Remove photo?" data-confirm-btn="Yes, remove">
                                <i class="bi bi-trash me-1"></i> Remove
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="form-text">JPG, PNG, WEBP or GIF · max 4 MB.</div>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile completeness score -->
    <div class="col-lg-7 col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-speedometer2 me-1"></i> Profile Score</h3>
                <span class="badge text-bg-<?= esc($score['color']) ?>"><?= esc($score['label']) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <?php
                        $pct   = (int) $score['percent'];
                        $deg   = round($pct * 3.6);
                        $ringC = 'var(--bs-' . $score['color'] . ', #0d6efd)';
                        ?>
                        <div class="score-ring" style="background: conic-gradient(<?= $ringC ?> <?= $deg ?>deg, var(--erp-border, #e5e7eb) 0deg);">
                            <div class="score-ring-hole">
                                <span class="score-ring-pct"><?= esc($pct) ?>%</span>
                                <span class="score-ring-sub"><?= esc($score['done']) ?>/<?= esc($score['total']) ?> done</span>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <p class="text-muted small mb-2">Complete your profile to reach 100%. Each item below improves your score.</p>
                        <ul class="score-checklist list-unstyled mb-0">
                            <?php foreach ($score['items'] as $item): ?>
                                <li class="d-flex align-items-start gap-2 <?= $item['done'] ? 'is-done' : '' ?>">
                                    <i class="bi <?= $item['done'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?>"></i>
                                    <span>
                                        <span class="fw-semibold"><?= esc($item['label']) ?></span>
                                        <?php if (! $item['done']): ?>
                                            <small class="d-block text-muted"><?= esc($item['hint']) ?></small>
                                        <?php endif; ?>
                                    </span>
                                    <span class="ms-auto badge rounded-pill text-bg-light border"><?= esc($item['weight']) ?> pts</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================ Details + password ============================ -->
<div class="row g-3 mt-1">
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
        <div class="card" id="change-password">
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
                                          data-no-validate data-confirm="Your <?= esc($label, 'attr') ?> account will be disconnected." data-confirm-title="Disconnect account?" data-confirm-btn="Yes, disconnect" data-confirm-icon="warning">
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
