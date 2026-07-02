<?php /** Sliced content — rendered inside app/Views/layout.php via BaseController::render(). */ ?>
<div class="card">
    <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-key me-1"></i> Role Permissions</h3></div>
    <div class="card-body">
        <p class="text-secondary">Choose a role to configure which modules and actions it can access.</p>
        <div class="row g-3">
            <?php foreach ($roles as $role): ?>
                <div class="col-md-4">
                    <div class="card h-100 border">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><?= esc($role['name']) ?></h5>
                                <small class="text-secondary"><?= esc($role['description']) ?></small>
                                <?php if ((int) $role['is_superadmin'] === 1): ?>
                                    <div class="mt-1"><span class="badge text-bg-dark">Super Admin — full access</span></div>
                                <?php endif; ?>
                            </div>
                            <a href="<?= site_url('permissions/matrix/' . $role['id']) ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-sliders"></i> Manage
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

