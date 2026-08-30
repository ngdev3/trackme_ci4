<?php
/** Customer subscription / upgrade page. Rendered inside layout.php. */
$state   = isset($state) ? $state : ['pro' => true, 'reason' => 'default'];
$plans   = isset($plans) ? $plans : [];
$planFeatures = isset($planFeatures) ? $planFeatures : [];
$locked  = isset($locked) ? $locked : [];
$gatewayReady = isset($gatewayReady) ? (bool) $gatewayReady : false;
$gatewayMode  = isset($gatewayMode) ? (string) $gatewayMode : 'sandbox';
$isPro   = ! empty($state['pro']);
$reason  = isset($state['reason']) ? (string) $state['reason'] : '';
$plan    = ! empty($state['plan']) ? (string) $state['plan'] : ($isPro ? 'Active access' : 'Free plan');
$expiresTs = ! empty($state['expires_at']) ? strtotime((string) $state['expires_at']) : null;
$expires = $expiresTs ? date('d M Y', $expiresTs) : null;
$daysLeft = $expiresTs ? (int) ceil(($expiresTs - time()) / 86400) : null;
$trialDays = isset($trialDays) ? (int) $trialDays : 180;
$trialProgress = ($daysLeft !== null && $trialDays > 0)
    ? max(0, min(100, (int) round((($trialDays - max(0, $daysLeft)) / $trialDays) * 100)))
    : ($isPro ? 100 : 0);

$statusMeta = [
    'paid'          => ['Paid plan active', 'success', 'bi-patch-check-fill'],
    'trial'         => ['Trial access', 'info', 'bi-stars'],
    'trial_expired' => ['Trial ended', 'warning', 'bi-hourglass-bottom'],
    'expired'       => ['Payment expired', 'danger', 'bi-exclamation-triangle-fill'],
    'free'          => ['Free plan', 'secondary', 'bi-lock-fill'],
    'none'          => ['Free plan', 'secondary', 'bi-lock-fill'],
    'superadmin'    => ['Super Admin access', 'primary', 'bi-shield-check'],
    'default'       => ['Active access', 'success', 'bi-check-circle-fill'],
];
$meta = isset($statusMeta[$reason]) ? $statusMeta[$reason] : ($isPro ? $statusMeta['default'] : $statusMeta['free']);
$cycleLabel = static function ($cycle) {
    $cycle = (string) $cycle;
    if ($cycle === 'yearly') { return 'per year'; }
    if ($cycle === 'monthly') { return 'per month'; }
    if ($cycle === 'lifetime') { return 'one-time'; }
    return 'per ' . str_replace('_', ' ', $cycle);
};
$featuresOf = static function ($features) {
    $features = trim((string) $features);
    if ($features === '') { return []; }
    $parts = preg_split('/[,;]+/', $features);
    return array_values(array_filter(array_map('trim', $parts), static function ($v) { return $v !== ''; }));
};
$fmtLimit = static function ($n, $label) {
    if ($n === null || $n === '' || (string) $n === '0') { return 'Unlimited ' . $label; }
    return number_format((int) $n) . ' ' . $label;
};
$upiId = trim((string) (function_exists('setting') ? setting('subscription_upi_id', setting('payment_upi_id', '')) : ''));
$upiName = trim((string) (function_exists('setting') ? setting('subscription_upi_name', setting('app_name', 'HisaabKitaab')) : 'HisaabKitaab'));
$waNumber = preg_replace('/\D+/', '', (string) (function_exists('setting') ? setting('support_whatsapp', '916393505070') : '916393505070'));
$waShown = (string) (function_exists('setting') ? setting('support_whatsapp_display', '+91 63935 05070') : '+91 63935 05070');
$userEmail = (string) (session('user_email') ?: session('email') ?: '');
$recommendedId = null;
foreach ($plans as $p) {
    if ($recommendedId === null || (float) $p['price'] > 0) {
        $recommendedId = (int) $p['id'];
    }
}
?>

<div class="sub-wrap">
    <section class="sub-hero">
        <div class="sub-hero-main">
            <span class="sub-kicker"><i class="bi bi-gem"></i> Subscription Center</span>
            <h1>Keep your HisaabKitaab workspace fully unlocked.</h1>
            <p>Manage your plan, understand what is available today, and see exactly what opens when your account is upgraded.</p>
            <div class="sub-actions">
                <a href="#plans" class="btn btn-light"><i class="bi bi-box-seam"></i> View packages</a>
                <a href="<?= site_url('subscription/transactions') ?>" class="btn btn-outline-light"><i class="bi bi-receipt"></i> Payment history</a>
                <a href="<?= site_url('help') ?>" class="btn btn-outline-light"><i class="bi bi-life-preserver"></i> Contact support</a>
            </div>
        </div>
        <div class="sub-status-card">
            <div class="sub-status-top">
                <span class="sub-status-icon text-bg-<?= esc($meta[1], 'attr') ?>"><i class="bi <?= esc($meta[2], 'attr') ?>"></i></span>
                <span class="badge text-bg-<?= esc($meta[1], 'attr') ?>"><?= esc($meta[0]) ?></span>
            </div>
            <h2><?= esc($plan) ?></h2>
            <?php if ($reason === 'trial' && $daysLeft !== null): ?>
                <p><strong><?= max(0, $daysLeft) ?></strong> day<?= $daysLeft === 1 ? '' : 's' ?> left in your <?= esc($trialDays) ?> day trial.</p>
            <?php elseif ($reason === 'paid' && $expires): ?>
                <p>Paid access is active until <strong><?= esc($expires) ?></strong>.</p>
            <?php elseif (! $isPro): ?>
                <p>Your account can still record Jama and Naam entries. Upgrade to unlock documents, exports and reports.</p>
            <?php else: ?>
                <p>All premium workspace features are currently available.</p>
            <?php endif; ?>
            <div class="sub-meter" aria-hidden="true"><span style="width: <?= esc((string) $trialProgress, 'attr') ?>%"></span></div>
            <div class="sub-status-foot">
                <?php if ($reason === 'paid'): ?>
                    <span>Plan: <?= esc($plan) ?></span>
                    <span><?= $expires ? 'Valid until ' . esc($expires) : 'Active' ?></span>
                <?php elseif ($reason === 'trial'): ?>
                    <span>Trial: <?= esc($trialDays) ?> days</span>
                    <span><?= $expires ? 'Until ' . esc($expires) : '' ?></span>
                <?php elseif ($isPro): ?>
                    <span>Full access</span>
                    <span>Active</span>
                <?php else: ?>
                    <span>Free plan</span>
                    <span>No active subscription</span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="sub-grid">
        <section class="sub-panel sub-access">
            <div class="sub-panel-head">
                <span><i class="bi bi-unlock"></i></span>
                <div>
                    <h2>Access Snapshot</h2>
                    <p>What your account can use right now.</p>
                </div>
            </div>
            <div class="sub-access-list">
                <div class="sub-access-item is-open"><i class="bi bi-check2-circle"></i><span>Jama / Naam cash book entries</span></div>
                <div class="sub-access-item is-open"><i class="bi bi-check2-circle"></i><span>Daily Rokadh Parcha view</span></div>
                <div class="sub-access-item <?= $isPro ? 'is-open' : 'is-locked' ?>"><i class="bi <?= $isPro ? 'bi-check2-circle' : 'bi-lock' ?>"></i><span>PDF, print and statement downloads</span></div>
                <div class="sub-access-item <?= $isPro ? 'is-open' : 'is-locked' ?>"><i class="bi <?= $isPro ? 'bi-check2-circle' : 'bi-lock' ?>"></i><span>Attachments, reports and opening balance</span></div>
            </div>
        </section>

        <section class="sub-panel sub-journey">
            <div class="sub-panel-head">
                <span><i class="bi bi-route"></i></span>
                <div>
                    <h2>Upgrade Path</h2>
                    <p>A simple guided path from package selection to account activation.</p>
                </div>
            </div>
            <div class="sub-path">
                <div class="sub-path-card">
                    <span>01</span><i class="bi bi-hand-index-thumb"></i>
                    <strong>Select plan</strong><small>Choose the package below.</small>
                </div>
                <div class="sub-path-card">
                    <span>02</span><i class="bi bi-qr-code-scan"></i>
                    <strong>Scan QR</strong><small>Pay by UPI instantly.</small>
                </div>
                <div class="sub-path-card">
                    <span>03</span><i class="bi bi-phone"></i>
                    <strong>Screenshot</strong><small>Save payment success proof.</small>
                </div>
                <div class="sub-path-card">
                    <span>04</span><i class="bi bi-whatsapp"></i>
                    <strong>WhatsApp</strong><small>Send proof to <?= esc($waShown) ?>.</small>
                </div>
                <div class="sub-path-card">
                    <span>05</span><i class="bi bi-unlock"></i>
                    <strong>Unlock</strong><small>Admin verifies and activates.</small>
                </div>
            </div>
        </section>
    </div>

    <section class="sub-panel sub-redeem">
        <div class="sub-panel-head">
            <span><i class="bi bi-gift"></i></span>
            <div>
                <h2>Have a code?</h2>
                <p>Redeem a gift or promo code to add free plan time to your account instantly.</p>
            </div>
        </div>
        <form method="post" action="<?= site_url('subscription/redeem') ?>" class="sub-redeem-form" id="redeemForm">
            <?= csrf_field() ?>
            <input type="text" name="coupon" class="form-control" placeholder="Enter code (e.g. WELCOME30)" autocomplete="off" required maxlength="40" style="text-transform:uppercase;">
            <button type="submit" class="btn btn-primary" id="redeemBtn"><i class="bi bi-unlock"></i> Redeem</button>
        </form>
    </section>

<!-- Blocking loader shown while a redeem code is being activated. -->
<div class="sub-loading" id="redeemLoading" hidden>
    <div class="sub-loading-card">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>
        <p>Activating your code…</p>
    </div>
</div>

    <?php if (! $isPro && ! empty($locked)): ?>
        <section class="sub-panel sub-locked">
            <div class="sub-panel-head">
                <span><i class="bi bi-lock-fill"></i></span>
                <div>
                    <h2>Locked On Free Access</h2>
                    <p>These are the premium tools you regain after activation.</p>
                </div>
            </div>
            <div class="sub-lock-grid">
                <?php foreach ($locked as $feature): ?>
                    <div class="sub-lock-card"><i class="bi bi-shield-lock"></i><span><?= esc($feature) ?></span></div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="sub-plans" id="plans">
        <div class="sub-section-title">
            <span class="sub-kicker"><i class="bi bi-box-seam"></i> Packages</span>
            <h2>Choose the access level for your firm.</h2>
        </div>

        <?php if (empty($plans)): ?>
            <div class="sub-panel text-center">
                <i class="bi bi-inbox fs-1 text-secondary"></i>
                <h3 class="mt-2">No packages are available right now.</h3>
                <p class="text-secondary mb-0">Please contact the administrator to activate a subscription manually.</p>
            </div>
        <?php else: ?>
            <div class="sub-plan-grid">
                <?php
                $activePlanId = isset($activePlanId) ? $activePlanId : null;
                $activeUntil  = isset($activeUntil) ? $activeUntil : null;
                foreach ($plans as $p):
                    $features = $planFeatures[(int) $p['id']] ?? $featuresOf(isset($p['features']) ? $p['features'] : '');
                    $price = (float) (isset($p['price']) ? $p['price'] : 0);
                    $isActive = $activePlanId !== null && (int) $p['id'] === (int) $activePlanId;
                    $isRecommended = ! $isActive && (int) $p['id'] === $recommendedId;
                ?>
                    <article class="sub-plan <?= $isActive ? 'is-active' : ($isRecommended ? 'is-recommended' : '') ?>">
                        <?php if ($isActive): ?>
                            <span class="sub-plan-ribbon sub-plan-ribbon-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                        <?php elseif ($isRecommended): ?>
                            <span class="sub-plan-ribbon">Best value</span>
                        <?php endif; ?>
                        <div class="sub-plan-head">
                            <div>
                                <h3><?= esc($p['name']) ?></h3>
                                <p><?= esc(ucwords(str_replace('_', ' ', (string) (isset($p['code']) ? $p['code'] : 'package')))) ?></p>
                            </div>
                            <i class="bi bi-gem"></i>
                        </div>
                        <div class="sub-price">
                            <span>&#8377;<?= esc(number_format($price, $price > 0 && $price < 100 ? 2 : 0)) ?></span>
                            <small><?= esc($cycleLabel(isset($p['billing_cycle']) ? $p['billing_cycle'] : 'yearly')) ?></small>
                        </div>
                        <div class="sub-limits">
                            <span><i class="bi bi-building"></i><?= esc($fmtLimit(isset($p['max_firms']) ? $p['max_firms'] : null, 'firms')) ?></span>
                            <span><i class="bi bi-people"></i><?= esc($fmtLimit(isset($p['max_users']) ? $p['max_users'] : null, 'users')) ?></span>
                        </div>
                        <ul class="sub-feature-list">
                            <?php if ($features === []): ?>
                                <li><i class="bi bi-check2"></i> Contact admin for package details.</li>
                            <?php else: foreach ($features as $f): ?>
                                <li><i class="bi bi-check2"></i><?= esc($f) ?></li>
                            <?php endforeach; endif; ?>
                        </ul>
                        <?php if ($isActive): ?>
                            <div class="sub-active-note">
                                <span class="badge text-bg-success w-100 py-2"><i class="bi bi-check-circle-fill me-1"></i> Active plan</span>
                                <?php if ($activeUntil): ?>
                                    <div class="text-center mt-2"><i class="bi bi-calendar-check"></i> Valid until <strong><?= esc(date('d M Y', strtotime($activeUntil))) ?></strong></div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <button type="button"
                                    class="btn <?= $isRecommended ? 'btn-primary' : 'btn-outline-primary' ?> w-100"
                                    data-select-plan
                                    data-plan-id="<?= esc((string) $p['id'], 'attr') ?>"
                                    data-plan="<?= esc($p['name'], 'attr') ?>"
                                    data-amount="<?= esc((string) $price, 'attr') ?>"
                                    data-cycle="<?= esc($cycleLabel(isset($p['billing_cycle']) ? $p['billing_cycle'] : 'yearly'), 'attr') ?>">
                                <i class="bi bi-lightning-charge"></i> Select &amp; pay
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <section class="sub-payment" id="payment" data-payment-panel hidden
                     data-upi-id="<?= esc($upiId, 'attr') ?>"
                     data-upi-name="<?= esc($upiName, 'attr') ?>"
                     data-wa-number="<?= esc($waNumber, 'attr') ?>"
                     data-user-email="<?= esc($userEmail, 'attr') ?>">
                <div class="sub-payment-info">
                    <span class="sub-kicker"><i class="bi bi-ticket-perforated"></i> Upgrade Pass</span>
                    <h3><span data-pay-plan>Selected plan</span> is ready for payment</h3>
                    <p>Use this pass like a small checkout desk: scan, pay, screenshot, WhatsApp. No confusing forms.</p>
                    <div class="sub-receipt">
                        <div><span>Package</span><strong data-receipt-plan>Selected plan</strong></div>
                        <div><span>Amount to pay</span><strong data-pay-amount>&#8377;0</strong></div>
                        <div><span>Billing</span><strong data-pay-cycle>Plan cycle</strong></div>
                        <div><span>Send proof to</span><strong><?= esc($waShown) ?></strong></div>
                    </div>
                    <div class="sub-coupon">
                        <label class="sub-coupon-label"><i class="bi bi-ticket-perforated"></i> Discount coupon</label>
                        <div class="sub-coupon-row">
                            <input type="text" class="form-control" data-coupon-input placeholder="Coupon code" autocomplete="off" maxlength="40" style="text-transform:uppercase;">
                            <button type="button" class="btn btn-outline-primary" data-coupon-apply>Apply</button>
                        </div>
                        <div class="sub-coupon-msg small" data-coupon-msg hidden></div>
                    </div>
                    <ol class="sub-pay-steps">
                        <li><strong>Scan</strong><span>Open PhonePe, GPay, Paytm or any UPI app and scan the QR.</span></li>
                        <li><strong>Pay</strong><span>Send the exact amount displayed on the pass.</span></li>
                        <li><strong>Screenshot</strong><span>Capture the successful payment screen.</span></li>
                        <li><strong>WhatsApp</strong><span>Tap the button and attach the screenshot before sending.</span></li>
                    </ol>
                    <?php if ($gatewayReady): ?>
                        <div class="sub-pay-online" data-cashfree-mode="<?= esc($gatewayMode, 'attr') ?>">
                            <button type="button" class="btn btn-primary btn-lg w-100" data-cashfree-pay>
                                <i class="bi bi-credit-card-2-front me-1"></i> Pay <span data-pay-amount-inline>&#8377;0</span> online — instant activation
                            </button>
                            <p class="sub-pay-online-note"><i class="bi bi-shield-lock me-1"></i>Secure card / UPI / netbanking via Cashfree. Your plan activates automatically on success.</p>
                            <div class="sub-pay-error text-danger small" data-pay-error hidden></div>
                        </div>
                        <div class="sub-pay-divider"><span>or pay manually</span></div>
                    <?php endif; ?>
                    <div class="sub-pay-actions">
                        <a href="#" class="btn btn-success" target="_blank" rel="noopener" data-wa-link>
                            <i class="bi bi-whatsapp"></i> Send screenshot on WhatsApp
                        </a>
                        <button type="button" class="btn btn-outline-secondary" data-copy-upi>
                            <i class="bi bi-clipboard"></i> Copy UPI ID
                        </button>
                    </div>
                </div>
                <div class="sub-qr-card">
                    <div class="sub-qr-top"><i class="bi bi-upc-scan"></i><span>Scan to pay</span></div>
                    <?php if ($upiId !== ''): ?>
                        <img src="" alt="UPI payment QR code" data-upi-qr>
                        <div class="sub-upi-id"><span>UPI ID</span><strong data-upi-label><?= esc($upiId) ?></strong></div>
                    <?php else: ?>
                        <div class="sub-qr-missing">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>UPI QR not configured</strong>
                            <span>Ask the administrator to set <code>subscription_upi_id</code> or <code>payment_upi_id</code>.</span>
                        </div>
                    <?php endif; ?>
                    <div class="sub-shot-card">
                        <i class="bi bi-camera"></i>
                        <span>After payment, send screenshot on WhatsApp.</span>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </section>
</div>

<style nonce="{csp-style-nonce}">
.sub-wrap { max-width: 1180px; margin: 0 auto; }
.sub-hero {
    display: grid; grid-template-columns: minmax(0, 1fr) minmax(300px, 380px); gap: 18px; align-items: stretch;
    margin-bottom: 18px; padding: 22px; border-radius: 8px; color: #fff;
    background: linear-gradient(135deg, #0f766e, #2563eb 52%, #7c3aed);
    box-shadow: 0 18px 40px rgba(15, 23, 42, .18);
}
.sub-hero-main { min-width: 0; padding: 8px 0; }
.sub-kicker { display: inline-flex; align-items: center; gap: 7px; font-size: .78rem; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; opacity: .9; }
.sub-hero h1 { max-width: 720px; margin: 8px 0 8px; font-size: clamp(1.75rem, 4vw, 3rem); line-height: 1.05; font-weight: 900; letter-spacing: 0; }
.sub-hero p { max-width: 680px; margin: 0; color: rgba(255,255,255,.84); font-size: 1rem; }
.sub-actions { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 18px; }
.sub-status-card { border: 1px solid rgba(255,255,255,.28); border-radius: 8px; padding: 18px; background: rgba(255,255,255,.14); backdrop-filter: blur(10px); }
.sub-status-top, .sub-status-foot { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.sub-status-icon { width: 42px; height: 42px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.sub-status-card h2 { margin: 18px 0 6px; font-size: 1.55rem; font-weight: 900; }
.sub-status-card p { min-height: 50px; color: rgba(255,255,255,.86); }
.sub-meter { height: 10px; overflow: hidden; border-radius: 999px; background: rgba(255,255,255,.24); }
.sub-meter span { display: block; height: 100%; border-radius: inherit; background: #fff; }
.sub-status-foot { margin-top: 9px; font-size: .78rem; color: rgba(255,255,255,.78); }
.sub-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(300px, .82fr); gap: 16px; margin-bottom: 16px; }
.sub-panel, .sub-plan {
    border: 1px solid var(--erp-border, #e4e9f2); border-radius: 8px; background: var(--erp-surface, #fff);
    padding: 18px; box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
}
.sub-panel-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
.sub-panel-head > span { width: 38px; height: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: var(--bs-primary); background: color-mix(in srgb, var(--bs-primary) 11%, var(--bs-body-bg)); }
.sub-panel h2, .sub-section-title h2 { margin: 0; color: var(--erp-ink, #1f2a3d); font-weight: 900; letter-spacing: 0; }
.sub-panel p, .sub-section-title { color: var(--erp-muted, #66748c); }
.sub-access-list { display: grid; gap: 9px; }
.sub-access-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; background: color-mix(in srgb, var(--bs-body-bg) 88%, var(--erp-surface, #fff)); font-weight: 700; }
.sub-access-item.is-open i { color: #16a34a; }
.sub-access-item.is-locked i { color: #dc2626; }
.sub-journey { overflow: hidden; }
.sub-path { display: grid; grid-template-columns: repeat(5, minmax(90px, 1fr)); gap: 8px; }
.sub-path-card {
    position: relative; min-height: 128px; padding: 12px; border: 1px solid var(--erp-border, #e4e9f2);
    border-radius: 8px; background: color-mix(in srgb, var(--bs-body-bg) 88%, var(--erp-surface, #fff));
}
.sub-path-card span { display: inline-flex; padding: 2px 7px; border-radius: 999px; color: #1d4ed8; background: rgba(37,99,235,.10); font-size: .7rem; font-weight: 900; }
.sub-path-card i { display: block; margin: 12px 0 8px; color: #0f766e; font-size: 1.35rem; }
.sub-path-card strong { display: block; color: var(--erp-ink, #1f2a3d); font-weight: 900; }
.sub-path-card small { display: block; color: var(--erp-muted, #66748c); line-height: 1.25; }
.sub-redeem { margin-bottom: 16px; }
.sub-redeem-form { display: flex; gap: 9px; flex-wrap: wrap; }
.sub-redeem-form input { max-width: 320px; }
.sub-loading { position: fixed; inset: 0; z-index: 20000; display: flex; align-items: center; justify-content: center; background: rgba(11, 35, 80, 0.42); backdrop-filter: blur(2px); }
.sub-loading[hidden] { display: none; }
.sub-loading-card { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 24px 30px; border-radius: 16px; background: #fff; box-shadow: 0 18px 50px rgba(0,0,0,.3); }
.sub-loading-card p { margin: 0; font-size: 13.5px; font-weight: 700; color: #18243c; }
.sub-coupon { margin: 4px 0 14px; padding: 12px; border: 1px dashed rgba(37,99,235,.30); border-radius: 8px; background: rgba(255,255,255,.6); }
.sub-coupon-label { display: inline-flex; align-items: center; gap: 6px; font-size: .78rem; font-weight: 850; text-transform: uppercase; color: #1d4ed8; margin-bottom: 7px; }
.sub-coupon-row { display: flex; gap: 8px; }
.sub-coupon-row input { max-width: 220px; }
.sub-coupon-msg { margin-top: 7px; font-weight: 700; }
.sub-coupon-msg.is-ok { color: #157347; }
.sub-coupon-msg.is-err { color: #dc2626; }
@media (max-width: 575.98px) {
    .sub-redeem-form input, .sub-coupon-row input { max-width: none; flex: 1 1 auto; }
}
.sub-locked { margin-bottom: 16px; }
.sub-lock-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px; }
.sub-lock-card { display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 8px; background: rgba(245, 158, 11, .10); color: #92400e; font-weight: 750; }
.sub-section-title { margin: 8px 2px 14px; }
.sub-section-title .sub-kicker { color: var(--bs-primary); opacity: 1; }
.sub-plan-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 16px; }
.sub-plan { position: relative; display: flex; flex-direction: column; min-height: 100%; overflow: hidden; }
.sub-plan.is-recommended { border-color: rgba(37, 99, 235, .38); box-shadow: 0 18px 34px rgba(37, 99, 235, .14); }
.sub-plan.is-active { border-color: rgba(21, 115, 71, .55); box-shadow: 0 18px 34px rgba(21, 115, 71, .16); background: linear-gradient(180deg, rgba(21,115,71,.06), transparent 42%); }
.sub-plan-ribbon-active { color: #fff; background: #157347; }
.sub-active-note { margin-top: auto; }
.sub-active-note > div { font-size: .82rem; color: #157347; }
.sub-plan.is-selected {
    border-color: rgba(22, 163, 74, .65);
    box-shadow: 0 20px 38px rgba(22, 163, 74, .18);
    transform: translateY(-2px);
}
.sub-plan.is-selected:before {
    content: "Selected"; position: absolute; top: 12px; left: 12px; padding: 4px 9px; border-radius: 999px;
    color: #15803d; background: rgba(34,197,94,.14); font-size: .72rem; font-weight: 900;
}
.sub-plan-ribbon { position: absolute; top: 12px; right: 12px; padding: 4px 9px; border-radius: 999px; color: #1d4ed8; background: rgba(37, 99, 235, .11); font-size: .72rem; font-weight: 900; }
.sub-plan-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding-right: 82px; }
.sub-plan-head h3 { margin: 0; font-size: 1.25rem; font-weight: 900; color: var(--erp-ink, #1f2a3d); }
.sub-plan-head p { margin: 2px 0 0; color: var(--erp-muted, #66748c); font-size: .82rem; }
.sub-plan-head > i { color: #7c3aed; font-size: 1.35rem; }
.sub-price { margin: 18px 0 10px; }
.sub-price span { display: block; font-size: 2.25rem; line-height: 1; font-weight: 950; color: var(--erp-ink, #1f2a3d); font-variant-numeric: tabular-nums; }
.sub-price small { color: var(--erp-muted, #66748c); font-weight: 750; }
.sub-limits { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 12px; }
.sub-limits span { display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px; border-radius: 999px; color: #0f766e; background: rgba(20, 184, 166, .10); font-size: .78rem; font-weight: 850; }
.sub-feature-list { margin: 0 0 16px; padding: 0; list-style: none; display: grid; gap: 8px; flex: 1 1 auto; }
.sub-feature-list li { display: flex; gap: 8px; color: var(--erp-ink, #1f2a3d); font-size: .9rem; }
.sub-feature-list i { color: #16a34a; flex: 0 0 auto; }
@media (max-width: 991.98px) {
    .sub-hero, .sub-grid { grid-template-columns: 1fr; }
}
@media (max-width: 575.98px) {
    .sub-hero, .sub-panel, .sub-plan { padding: 14px; }
    .sub-actions .btn { width: 100%; justify-content: center; }
    .sub-plan-head { padding-right: 0; }
    .sub-plan-ribbon { position: static; align-self: flex-start; margin-bottom: 10px; }
}
.sub-payment {
    display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; align-items: stretch;
    margin-top: 18px; padding: 18px; border: 1px solid rgba(22, 163, 74, .28); border-radius: 8px;
    background:
        linear-gradient(135deg, rgba(34,197,94,.11), rgba(37,99,235,.09)),
        radial-gradient(circle at 92% 18%, rgba(124,58,237,.14), transparent 32%),
        var(--erp-surface, #fff);
    box-shadow: 0 18px 38px rgba(15, 23, 42, .12);
}
.sub-payment h3 { margin: 6px 0; font-size: 1.45rem; font-weight: 900; color: var(--erp-ink, #1f2a3d); }
.sub-payment p { color: var(--erp-muted, #66748c); margin-bottom: 12px; }
.sub-receipt {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0; margin-bottom: 14px;
    border: 1px dashed rgba(37,99,235,.30); border-radius: 8px; overflow: hidden; background: rgba(255,255,255,.64);
}
.sub-receipt div { padding: 11px 12px; border-right: 1px dashed rgba(37,99,235,.22); border-bottom: 1px dashed rgba(37,99,235,.22); }
.sub-receipt div:nth-child(2n) { border-right: 0; }
.sub-receipt div:nth-last-child(-n+2) { border-bottom: 0; }
.sub-receipt span { display: block; color: var(--erp-muted, #66748c); font-size: .72rem; font-weight: 850; text-transform: uppercase; }
.sub-receipt strong { display: block; color: var(--erp-ink, #1f2a3d); font-size: .98rem; overflow-wrap: anywhere; }
.sub-pay-steps { margin: 0 0 14px; padding: 0; list-style: none; display: grid; gap: 8px; color: var(--erp-ink, #1f2a3d); }
.sub-pay-steps li { display: grid; grid-template-columns: 96px minmax(0, 1fr); gap: 10px; align-items: start; padding: 8px 0; border-bottom: 1px solid rgba(100,116,139,.14); }
.sub-pay-steps li:last-child { border-bottom: 0; }
.sub-pay-steps strong { color: #0f766e; }
.sub-pay-steps span { color: var(--erp-muted, #66748c); }
.sub-pay-online { margin-bottom: 12px; }
.sub-pay-online-note { margin: 8px 2px 0; font-size: .8rem; color: var(--erp-muted, #66748c); }
.sub-pay-divider { display: flex; align-items: center; gap: 10px; margin: 14px 0; color: var(--erp-muted, #66748c); font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.sub-pay-divider::before, .sub-pay-divider::after { content: ""; flex: 1; height: 1px; background: var(--erp-border, #e4e9f2); }
.sub-pay-actions { display: flex; flex-wrap: wrap; gap: 9px; }
.sub-qr-card {
    display: grid; place-items: center; align-content: center; gap: 10px; padding: 14px; border-radius: 8px;
    background: var(--erp-surface, #fff); border: 1px solid var(--erp-border, #e4e9f2);
}
.sub-qr-top { display: inline-flex; align-items: center; gap: 7px; color: #1d4ed8; font-weight: 900; }
.sub-qr-card img { width: 220px; height: 220px; object-fit: contain; border-radius: 8px; background: #fff; }
.sub-upi-id { width: 100%; text-align: center; padding: 9px; border-radius: 8px; background: color-mix(in srgb, var(--bs-primary) 7%, var(--bs-body-bg)); }
.sub-upi-id span { display: block; color: var(--erp-muted, #66748c); font-size: .72rem; font-weight: 850; text-transform: uppercase; }
.sub-upi-id strong { color: var(--erp-ink, #1f2a3d); overflow-wrap: anywhere; }
.sub-qr-missing { min-height: 240px; display: grid; place-items: center; align-content: center; gap: 8px; text-align: center; color: var(--erp-muted, #66748c); }
.sub-qr-missing i { font-size: 2rem; color: #f59e0b; }
.sub-shot-card {
    width: 100%; display: flex; align-items: center; gap: 9px; padding: 10px; border-radius: 8px;
    color: #92400e; background: rgba(245,158,11,.12); font-size: .85rem; font-weight: 800;
}
.sub-shot-card i { font-size: 1.1rem; }
@media (max-width: 767.98px) {
    .sub-payment { grid-template-columns: 1fr; }
    .sub-receipt { grid-template-columns: 1fr; }
    .sub-receipt div, .sub-receipt div:nth-child(2n), .sub-receipt div:nth-last-child(-n+2) { border-right: 0; border-bottom: 1px dashed rgba(37,99,235,.22); }
    .sub-receipt div:last-child { border-bottom: 0; }
    .sub-pay-steps li { grid-template-columns: 1fr; gap: 2px; }
    .sub-pay-actions .btn { width: 100%; justify-content: center; }
}
@media (max-width: 991.98px) {
    .sub-path { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575.98px) {
    .sub-path { grid-template-columns: 1fr; }
}
</style>

<?php if ($gatewayReady): ?>
<script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
<?php endif; ?>
<script nonce="{csp-script-nonce}">
(function () {
    var panel = document.querySelector('[data-payment-panel]');
    if (!panel) { return; }

    var selectedPlanId = null;
    var selectedAmount = 0;
    var appliedCoupon = null;
    var upiId = panel.getAttribute('data-upi-id') || '';
    var upiName = panel.getAttribute('data-upi-name') || 'HisaabKitaab';
    var waNumber = panel.getAttribute('data-wa-number') || '';
    var userEmail = panel.getAttribute('data-user-email') || '';
    var qr = panel.querySelector('[data-upi-qr]');
    var wa = panel.querySelector('[data-wa-link]');
    var copy = panel.querySelector('[data-copy-upi]');

    function money(amount) {
        var n = parseFloat(amount || 0);
        if (!isFinite(n)) { n = 0; }
        return String.fromCharCode(8377) + n.toLocaleString('en-IN', { maximumFractionDigits: 2 });
        return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 2 });
    }
    function setText(sel, value) {
        var el = panel.querySelector(sel);
        if (el) { el.textContent = value; }
    }
    function upiUrl(plan, amount) {
        var params = new URLSearchParams();
        params.set('pa', upiId);
        params.set('pn', upiName);
        params.set('am', String(parseFloat(amount || 0).toFixed(2)));
        params.set('cu', 'INR');
        params.set('tn', plan + ' subscription');
        return 'upi://pay?' + params.toString();
    }
    function qrUrl(payload) {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' + encodeURIComponent(payload);
    }
    function whatsappUrl(plan, amount, cycle) {
        var msg = 'Hello, I have paid for the ' + plan + ' subscription (' + money(amount) + ', ' + cycle + ').';
        if (userEmail) { msg += '\nRegistered email: ' + userEmail; }
        msg += '\nI am attaching the payment screenshot. Please activate my account.';
        return 'https://wa.me/' + waNumber + '?text=' + encodeURIComponent(msg);
    }

    document.querySelectorAll('[data-select-plan]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var plan = btn.getAttribute('data-plan') || 'Selected plan';
            var amount = btn.getAttribute('data-amount') || '0';
            var cycle = btn.getAttribute('data-cycle') || 'plan cycle';
            selectedPlanId = btn.getAttribute('data-plan-id') || null;
            selectedAmount = parseFloat(amount || 0) || 0;
            resetCoupon();
            document.querySelectorAll('.sub-plan').forEach(function (card) { card.classList.remove('is-selected'); });
            var card = btn.closest('.sub-plan');
            if (card) { card.classList.add('is-selected'); }
            setText('[data-pay-plan]', plan);
            setText('[data-receipt-plan]', plan);
            setText('[data-pay-amount]', money(amount));
            setText('[data-pay-amount-inline]', money(amount));
            setText('[data-pay-cycle]', cycle);
            if (upiId && qr) { qr.src = qrUrl(upiUrl(plan, amount)); }
            if (wa) { wa.href = waNumber ? whatsappUrl(plan, amount, cycle) : '#'; }
            panel.hidden = false;
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    if (copy) {
        copy.addEventListener('click', function () {
            if (!upiId) { return; }
            if (navigator.clipboard) { navigator.clipboard.writeText(upiId); }
            copy.innerHTML = '<i class="bi bi-check2"></i> Copied';
            setTimeout(function () { copy.innerHTML = '<i class="bi bi-clipboard"></i> Copy UPI ID'; }, 1400);
        });
    }

    /* ---------------- Discount coupon preview (read-only GET) ---------------- */
    var couponInput = panel.querySelector('[data-coupon-input]');
    var couponApply = panel.querySelector('[data-coupon-apply]');
    var couponMsg = panel.querySelector('[data-coupon-msg]');

    function showCoupon(msg, ok) {
        if (!couponMsg) { return; }
        couponMsg.textContent = msg;
        couponMsg.hidden = false;
        couponMsg.classList.toggle('is-ok', !!ok);
        couponMsg.classList.toggle('is-err', !ok);
    }
    function resetCoupon() {
        appliedCoupon = null;
        if (couponInput) { couponInput.value = ''; }
        if (couponMsg) { couponMsg.hidden = true; couponMsg.classList.remove('is-ok', 'is-err'); }
        setText('[data-pay-amount]', money(selectedAmount));
        setText('[data-pay-amount-inline]', money(selectedAmount));
    }
    if (couponApply && couponInput) {
        couponApply.addEventListener('click', function () {
            var code = (couponInput.value || '').trim();
            if (!code) { showCoupon('Enter a coupon code.', false); return; }
            if (!selectedPlanId) { showCoupon('Select a plan first.', false); return; }
            couponApply.disabled = true;
            var url = '<?= site_url('subscription/coupon') ?>?plan_id=' + encodeURIComponent(selectedPlanId) + '&coupon=' + encodeURIComponent(code);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    couponApply.disabled = false;
                    if (j && j.ok) {
                        appliedCoupon = j.code || code;
                        setText('[data-pay-amount]', money(j.final));
                        setText('[data-pay-amount-inline]', money(j.final));
                        showCoupon(j.message || 'Coupon applied.', true);
                    } else {
                        resetCoupon();
                        showCoupon((j && j.message) || 'Invalid coupon.', false);
                    }
                }).catch(function () {
                    couponApply.disabled = false;
                    showCoupon('Network error. Try again.', false);
                });
        });
    }

    /* ---------------- Cashfree online payment ---------------- */
    var payBtn = panel.querySelector('[data-cashfree-pay]');
    if (payBtn && typeof Cashfree === 'function') {
        var online = panel.querySelector('.sub-pay-online');
        var mode = (online && online.getAttribute('data-cashfree-mode')) || 'sandbox';
        var errBox = panel.querySelector('[data-pay-error]');
        var meta = document.querySelector('meta[name="csrf-token"]');

        function showErr(msg) {
            if (errBox) { errBox.textContent = msg; errBox.hidden = false; }
        }
        function resetBtn(html) {
            payBtn.disabled = false;
            payBtn.innerHTML = html;
        }

        payBtn.addEventListener('click', function () {
            if (!selectedPlanId) { showErr('Please select a plan first.'); return; }
            if (errBox) { errBox.hidden = true; }
            var original = payBtn.innerHTML;
            payBtn.disabled = true;
            payBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Starting secure checkout…';

            var body = new URLSearchParams();
            if (meta) { body.set(meta.getAttribute('data-name'), meta.getAttribute('content')); }
            if (appliedCoupon) { body.set('coupon', appliedCoupon); }

            fetch('<?= site_url('subscription/pay') ?>/' + encodeURIComponent(selectedPlanId), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
              .then(function (res) {
                // A coupon that clears the full price activates server-side — just reload.
                if (res.ok && res.j && res.j.ok && res.j.activated) {
                    window.location.reload();
                    return;
                }
                if (!res.ok || !res.j || !res.j.ok || !res.j.session) {
                    showErr((res.j && res.j.message) || 'Could not start payment. Please try again.');
                    resetBtn(original);
                    return;
                }
                var cashfree = Cashfree({ mode: res.j.mode || mode });
                cashfree.checkout({ paymentSessionId: res.j.session, redirectTarget: '_self' });
              }).catch(function () {
                showErr('Network error. Please try again.');
                resetBtn(original);
              });
        });
    }

    // Redeem: show a blocking loader while the code is validated + activated
    // (this is a full-page POST, so the loader stays until the page reloads).
    var redeemForm = document.getElementById('redeemForm');
    if (redeemForm) {
        redeemForm.addEventListener('submit', function () {
            var btn = document.getElementById('redeemBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Activating…';
            }
            var ov = document.getElementById('redeemLoading');
            if (ov) { ov.hidden = false; }
        });
    }
})();
</script>
