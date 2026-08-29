<?php
/** Super Admin — coupon usage trail (who redeemed what). In layout.php.
 * Shared list design (cust-* — canonical Customers look). */
$rows     = $rows ?? [];
$stats    = $stats ?? [];
$q        = $q ?? '';
$coupon   = $coupon ?? null;
$couponId = $couponId ?? null;
?>
<div class="cust-page">

    <!-- Hero -->
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Coupon Usage</h4>
            <p class="cust-subtitle">Every discount and redeem code applied at checkout — who used it, when and the benefit.</p>
        </div>
        <div class="cust-hero-actions">
            <form class="cust-search" method="get" role="search">
                <?php if ($couponId): ?><input type="hidden" name="coupon_id" value="<?= (int) $couponId ?>"><?php endif; ?>
                <i class="bi bi-search cust-search-ic"></i>
                <input type="search" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Code / customer / order…" autocomplete="off">
                <?php if ($q !== ''): ?><a href="<?= site_url('admin/coupons/log' . ($couponId ? '?coupon_id=' . (int) $couponId : '')) ?>" class="cust-search-clear" title="Clear"><i class="bi bi-x-lg"></i></a><?php endif; ?>
            </form>
        </div>
    </section>

    <!-- Snapshot stat cards -->
    <section class="cust-snap-grid">
        <div class="cust-snap"><span class="cust-snap-ic ic-blue"><i class="bi bi-ticket-perforated-fill"></i></span>
            <div><p class="cust-snap-label">Total redemptions</p><p class="cust-snap-value"><?= number_format((int) ($stats['total'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-blue"><i class="bi bi-percent"></i></span>
            <div><p class="cust-snap-label">Discounts used</p><p class="cust-snap-value"><?= number_format((int) ($stats['discounts'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-green"><i class="bi bi-gift-fill"></i></span>
            <div><p class="cust-snap-label">Redeem codes used</p><p class="cust-snap-value"><?= number_format((int) ($stats['redeems'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-amber"><i class="bi bi-cash-stack"></i></span>
            <div><p class="cust-snap-label">Total discounted</p><p class="cust-snap-value">&#8377;<?= number_format((float) ($stats['total_discount'] ?? 0), 2) ?></p></div></div>
    </section>

    <!-- Table panel -->
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">Redemption Records</h5>
                <p class="cust-table-note"><?= (int) ($stats['total_days'] ?? 0) ?> free days granted across all redeem codes.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($coupon): ?><span class="cust-search-tag"><i class="bi bi-ticket-perforated"></i> <?= esc($coupon['code']) ?> <a href="<?= site_url('admin/coupons/log') ?>" class="ms-1 text-decoration-none" title="Clear filter"><i class="bi bi-x-lg"></i></a></span><?php endif; ?>
                <span class="cust-total-tag"><i class="bi bi-clock-history"></i> <?= number_format((int) ($stats['total'] ?? 0)) ?> total</span>
            </div>
        </div>

        <div class="cust-table-wrap">
            <table class="cust-table">
                <thead><tr>
                    <th class="text-start" style="width:140px">When</th>
                    <th class="text-start" style="width:230px">Customer</th>
                    <th class="text-start" style="width:130px">Coupon</th>
                    <th class="text-start" style="width:110px">Type</th>
                    <th class="text-end" style="width:130px">Benefit</th>
                    <th class="text-start">Plan</th>
                    <th class="text-start">Order</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="cust-empty"><i class="bi bi-ticket-perforated"></i><div>No coupon redemptions yet<?= $q !== '' ? ' for “' . esc($q) . '”' : '' ?>.</div></td></tr>
                <?php else: foreach ($rows as $r):
                    $isRedeem = ($r['kind'] === 'redeem');
                    $benefit = $isRedeem
                        ? ((int) $r['days_granted'] . ' days')
                        : ('&#8377;' . number_format((float) $r['amount_discounted'], 2) . ' off');
                ?>
                    <tr>
                        <td class="text-start"><span class="cust-muted text-nowrap"><?= esc(date('d M y, H:i', strtotime($r['created_at']))) ?></span></td>
                        <td class="text-start">
                            <div class="fw-semibold"><?= esc($r['customer_name'] ?? ('#' . $r['customer_id'])) ?></div>
                            <?php if (!empty($r['customer_email'])): ?><div class="small cust-muted"><?= esc($r['customer_email']) ?></div><?php endif; ?>
                        </td>
                        <td class="text-start"><strong><?= esc($r['coupon_code'] ?? '—') ?></strong></td>
                        <td class="text-start"><span class="badge text-bg-<?= $isRedeem ? 'success' : 'info' ?>"><?= $isRedeem ? 'Redeem' : 'Discount' ?></span></td>
                        <td class="text-end fw-bold"><?= $benefit ?></td>
                        <td class="text-start"><span class="cust-muted"><?= esc($r['plan_name'] ?? '—') ?></span></td>
                        <td class="text-start"><span class="cust-muted"><?= esc($r['order_id'] ?? '—') ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
