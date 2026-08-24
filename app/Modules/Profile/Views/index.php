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

<!-- Danger zone: self-service account deletion request -->
<div class="row justify-content-center mt-3">
    <div class="col-lg-10">
        <div class="card border-danger">
            <div class="card-header bg-danger-subtle d-flex align-items-center">
                <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>
                <h3 class="card-title mb-0 text-danger">Delete my account</h3>
            </div>
            <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <p class="text-secondary small mb-0" style="max-width:640px">
                    Requesting deletion permanently removes your account and <strong>all</strong> your data — firms,
                    transactions, rokad, reports, subscriptions and more. This can’t be undone. Your request is reviewed
                    by our team before anything is deleted.
                </p>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                    <i class="bi bi-trash3 me-1"></i>Request account deletion
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('profile/request-deletion') ?>" id="deleteAccountForm" data-no-validate>
            <?= csrf_field() ?>
            <div class="modal-header" style="background:#fdecec">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-octagon-fill me-1"></i> Request account deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">This will submit a request to <strong>permanently delete your account and all data</strong>. A member of our team reviews every request before anything is removed.</p>
                <label class="form-label">Reason <span class="text-muted">(optional)</span></label>
                <textarea name="reason" class="form-control mb-3" rows="3" maxlength="1000" placeholder="Tell us why you're leaving (optional)"></textarea>
                <label class="form-label">Type <code class="text-danger">DELETE</code> to confirm</label>
                <input type="text" id="deleteAccountConfirm" class="form-control" autocomplete="off" placeholder="DELETE">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="deleteAccountSubmit" disabled><i class="bi bi-send me-1"></i> Submit request</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var input = document.getElementById('deleteAccountConfirm');
    var btn = document.getElementById('deleteAccountSubmit');
    if (!input || !btn) return;
    input.addEventListener('input', function () { btn.disabled = input.value.trim().toUpperCase() !== 'DELETE'; });
})();
</script>
