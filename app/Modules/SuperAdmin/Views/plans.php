<?php /** Super Admin — subscription plans. Rendered inside layout.php. */ ?>
<div class="card">
    <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-card-checklist me-1"></i> Subscription Plans</h3></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr>
                    <th>Plan</th><th>Code</th><th>Price</th><th>Cycle</th><th>Max Firms</th><th>Max Users</th><th>Status</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-secondary py-4">No plans defined.</td></tr>
                <?php else: foreach ($rows as $p): ?>
                    <tr>
                        <td class="fw-semibold"><?= esc($p['name']) ?></td>
                        <td><code><?= esc($p['code']) ?></code></td>
                        <td>&#8377;<?= esc(number_format((float) $p['price'], 2)) ?></td>
                        <td class="text-capitalize"><?= esc($p['billing_cycle']) ?></td>
                        <td><?= $p['max_firms'] === null ? 'Unlimited' : (int) $p['max_firms'] ?></td>
                        <td><?= $p['max_users'] === null ? 'Unlimited' : (int) $p['max_users'] ?></td>
                        <td><?= (int) $p['status'] === 1 ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Off</span>' ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
