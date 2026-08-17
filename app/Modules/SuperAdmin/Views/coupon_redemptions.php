<?php
/** Super Admin — coupon usage trail (who redeemed what). In layout.php. */
$rows     = $rows ?? [];
$stats    = $stats ?? [];
$q        = $q ?? '';
$coupon   = $coupon ?? null;
$couponId = $couponId ?? null;
?>
<div class="row g-3">
    <!-- Stat tiles -->
    <div class="col-12">
        <div class="row g-2">
            <?php
            $tiles = [
                ['Total redemptions', number_format((int) ($stats['total'] ?? 0)), 'bi-ticket-perforated', 'primary'],
                ['Discounts used', number_format((int) ($stats['discounts'] ?? 0)), 'bi-percent', 'info'],
                ['Redeem codes used', number_format((int) ($stats['redeems'] ?? 0)), 'bi-gift', 'success'],
                ['Total discounted', '&#8377;' . number_format((float) ($stats['total_discount'] ?? 0), 2), 'bi-cash-stack', 'warning'],
                ['Free days granted', number_format((int) ($stats['total_days'] ?? 0)), 'bi-calendar-plus', 'secondary'],
            ];
            foreach ($tiles as [$label, $val, $icon, $color]): ?>
                <div class="col-6 col-md">
                    <div class="card h-100"><div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-<?= $color ?>"><i class="bi <?= $icon ?>"></i></span>
                            <div><div class="small text-muted"><?= $label ?></div><div class="fw-bold fs-6"><?= $val ?></div></div>
                        </div>
                    </div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <h3 class="card-title mb-0">
                    <i class="bi bi-clock-history me-1"></i> Coupon Usage
                    <?php if ($coupon): ?>
                        <span class="badge text-bg-primary ms-1"><?= esc($coupon['code']) ?></span>
                        <a href="<?= site_url('admin/coupons/log') ?>" class="small ms-1">(clear filter)</a>
                    <?php endif; ?>
                </h3>
                <form class="d-flex gap-2" method="get">
                    <?php if ($couponId): ?><input type="hidden" name="coupon_id" value="<?= (int) $couponId ?>"><?php endif; ?>
                    <input type="search" name="q" value="<?= esc($q, 'attr') ?>" class="form-control form-control-sm" placeholder="Code / customer / order" style="width:240px">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr>
                            <th>When</th><th>Customer</th><th>Coupon</th><th>Type</th>
                            <th class="text-end">Benefit</th><th>Plan</th><th>Order</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No coupon redemptions yet.</td></tr>
                        <?php else: foreach ($rows as $r):
                            $isRedeem = ($r['kind'] === 'redeem');
                            $benefit = $isRedeem
                                ? ((int) $r['days_granted'] . ' days')
                                : ('&#8377;' . number_format((float) $r['amount_discounted'], 2) . ' off');
                        ?>
                            <tr>
                                <td class="small text-muted text-nowrap"><?= esc(date('d M y, H:i', strtotime($r['created_at']))) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc($r['customer_name'] ?? ('#' . $r['customer_id'])) ?></div>
                                    <?php if (!empty($r['customer_email'])): ?><div class="small text-muted"><?= esc($r['customer_email']) ?></div><?php endif; ?>
                                </td>
                                <td><strong><?= esc($r['coupon_code'] ?? '—') ?></strong></td>
                                <td><span class="badge text-bg-<?= $isRedeem ? 'success' : 'info' ?>"><?= $isRedeem ? 'Redeem' : 'Discount' ?></span></td>
                                <td class="text-end fw-semibold"><?= $benefit ?></td>
                                <td class="small"><?= esc($r['plan_name'] ?? '—') ?></td>
                                <td class="small text-muted"><?= esc($r['order_id'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
