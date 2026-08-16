<?php
/**
 * Public privacy policy for Google Play and the Hissab-Kitaab website/app.
 */
$company    = 'CR Industries';
$appName    = brand_name();
$package    = 'com.crind.hissabkitaab';
$contact    = 'support@hissabkitaab.com';
$website    = 'https://hissabkitaab.com/';
$privacyUrl = 'http://hissabkitaab.com/privacy';
$updated    = '7 August 2026';

$dataCards = [
    ['title' => 'Account Identity', 'body' => 'Name, username, email, mobile number, profile photo, role, company or firm membership, permissions, sign-in status, and password-reset records.'],
    ['title' => 'Business Books', 'body' => 'Companies, accounting groups, ledgers, vouchers, rokad/cash entries, transactions, parties, notes, reminders, reports, receipts, and exports.'],
    ['title' => 'Inventory Workflows', 'body' => 'Products, warehouses, lots, stock movements, corrections, daily closings, inward/outward entries, attachments, and inventory reports.'],
    ['title' => 'Billing Records', 'body' => 'Subscription plan, invoice, order id, payment status, billing gateway, expiry, renewal state, and entitlement history.'],
    ['title' => 'Google Play Data', 'body' => 'Product id, base plan id, purchase token, linked purchase token, Google Play order id, acknowledgement status, expiry, and latest verification response.'],
    ['title' => 'Security Signals', 'body' => 'IP address, browser, operating system, device type, failed login attempts, suspicious-login flags, activity logs, API tokens, and diagnostics.'],
    ['title' => 'Location', 'body' => 'An approximate location (city / region) derived from your IP address at sign-in, and — only if you grant location access on your device — a precise (GPS) location attached to that sign-in. Location is used to help you and your administrators recognise and secure account activity. You can decline precise location and still use the app; you can revoke it anytime in your device settings.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="index, follow">
<title>Privacy Policy - <?= esc($appName) ?></title>
<style>
    :root {
        color-scheme: light;
        --ink: #152033;
        --muted: #5d687c;
        --line: #dbe4ef;
        --paper: #ffffff;
        --soft: #f5f8fc;
        --brand: #0f766e;
        --brand-2: #2563eb;
        --gold: #f59e0b;
        --danger: #dc2626;
        --shadow: 0 24px 70px rgba(21, 32, 51, .12);
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
        margin: 0;
        color: var(--ink);
        background:
            radial-gradient(circle at 8% 4%, rgba(37, 99, 235, .16), transparent 28%),
            radial-gradient(circle at 92% 8%, rgba(15, 118, 110, .18), transparent 30%),
            linear-gradient(180deg, #edf5f7 0%, #f8fafc 42%, #ffffff 100%);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        line-height: 1.65;
    }
    a { color: var(--brand); font-weight: 700; text-decoration: none; }
    a:hover, a:focus { text-decoration: underline; }
    .page { max-width: 1120px; margin: 0 auto; padding: 28px 18px 64px; }
    .topbar {
        display: flex; align-items: center; justify-content: space-between; gap: 16px;
        margin-bottom: 22px; color: var(--muted); font-size: 13px;
    }
    .brand { display: inline-flex; align-items: center; gap: 10px; color: var(--ink); font-weight: 800; }
    .mark {
        width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center;
        color: #fff; background: linear-gradient(135deg, var(--brand), var(--brand-2));
        box-shadow: 0 12px 28px rgba(15, 118, 110, .22);
    }
    .hero {
        overflow: hidden; position: relative; border-radius: 26px; padding: 34px;
        color: #fff; background:
            radial-gradient(circle at 78% 22%, rgba(245, 158, 11, .42), transparent 22%),
            radial-gradient(circle at 12% 90%, rgba(20, 184, 166, .34), transparent 28%),
            linear-gradient(135deg, #083344 0%, #0f766e 48%, #1d4ed8 100%);
        box-shadow: var(--shadow);
    }
    .hero:after {
        content: ""; position: absolute; inset: auto -90px -140px auto; width: 340px; height: 340px;
        border: 34px solid rgba(255,255,255,.12); border-radius: 50%;
    }
    .hero-grid { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr); gap: 28px; align-items: end; }
    .eyebrow { margin: 0 0 12px; color: rgba(255,255,255,.78); font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    h1 { margin: 0; max-width: 660px; font-size: clamp(34px, 6vw, 64px); line-height: .98; letter-spacing: 0; }
    .lede { max-width: 640px; margin: 18px 0 0; color: rgba(255,255,255,.86); font-size: 17px; }
    .identity {
        display: grid; gap: 10px; padding: 18px; border: 1px solid rgba(255,255,255,.2);
        border-radius: 18px; background: rgba(255,255,255,.13); backdrop-filter: blur(14px);
    }
    .identity div { display: grid; gap: 2px; }
    .identity span { color: rgba(255,255,255,.68); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .identity strong, .identity a { color: #fff; font-size: 14px; word-break: break-word; }
    .quick {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin: 16px 0 24px;
    }
    .quick a {
        display: block; padding: 14px 15px; border: 1px solid var(--line); border-radius: 14px;
        background: rgba(255,255,255,.86); color: var(--ink); box-shadow: 0 10px 24px rgba(21,32,51,.06);
    }
    .quick small { display: block; color: var(--muted); font-weight: 600; }
    .section {
        margin-top: 18px; border: 1px solid var(--line); border-radius: 22px;
        background: var(--paper); box-shadow: 0 12px 32px rgba(21,32,51,.06);
    }
    .section-head { padding: 24px 26px 0; }
    .section h2 { margin: 0; font-size: 24px; letter-spacing: 0; }
    .section-head p { margin: 8px 0 0; color: var(--muted); }
    .content { padding: 22px 26px 26px; }
    .data-map { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .data-card { min-height: 172px; padding: 18px; border: 1px solid #e4ebf5; border-radius: 16px; background: linear-gradient(180deg, #fff 0%, #f8fbff 100%); }
    .data-card b { display: block; margin-bottom: 8px; color: var(--brand); font-size: 16px; }
    .data-card p { margin: 0; color: var(--muted); font-size: 14px; }
    .steps { counter-reset: step; display: grid; gap: 12px; }
    .step { display: grid; grid-template-columns: 46px 1fr; gap: 14px; align-items: start; padding: 16px; border-radius: 16px; background: var(--soft); }
    .step:before {
        counter-increment: step; content: counter(step); width: 46px; height: 46px; border-radius: 14px;
        display: grid; place-items: center; color: #fff; background: linear-gradient(135deg, var(--brand), var(--brand-2)); font-weight: 900;
    }
    .step b { display: block; margin-bottom: 4px; }
    .step p { margin: 0; color: var(--muted); }
    .play-panel {
        display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;
    }
    .callout {
        padding: 18px; border-radius: 18px; border: 1px solid rgba(15,118,110,.22);
        background: linear-gradient(135deg, rgba(15,118,110,.1), rgba(37,99,235,.08));
    }
    .callout.warning { border-color: rgba(245,158,11,.28); background: linear-gradient(135deg, rgba(245,158,11,.16), rgba(255,255,255,.88)); }
    .callout b { display: block; margin-bottom: 6px; }
    .callout p { margin: 0; color: var(--muted); }
    .rights-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .right-box { padding: 16px; border-left: 4px solid var(--brand); border-radius: 14px; background: var(--soft); }
    .right-box b { display: block; margin-bottom: 4px; }
    .right-box p { margin: 0; color: var(--muted); }
    .contact {
        display: grid; grid-template-columns: 1.2fr .8fr; gap: 18px; align-items: center;
        margin-top: 18px; padding: 26px; border-radius: 22px;
        background: #102033; color: #fff;
    }
    .contact h2 { margin: 0 0 8px; font-size: 24px; }
    .contact p { margin: 0; color: rgba(255,255,255,.72); }
    .contact-card { display: grid; gap: 10px; padding: 16px; border-radius: 16px; background: rgba(255,255,255,.1); }
    .contact-card a, .contact-card strong { color: #fff; word-break: break-word; }
    .foot { margin-top: 18px; color: var(--muted); font-size: 13px; text-align: center; }
    @media (max-width: 860px) {
        .hero-grid, .contact, .play-panel { grid-template-columns: 1fr; }
        .quick, .data-map, .rights-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 560px) {
        .page { padding: 18px 12px 42px; }
        .topbar { align-items: flex-start; flex-direction: column; }
        .hero { padding: 24px; border-radius: 20px; }
        .quick, .data-map, .rights-grid { grid-template-columns: 1fr; }
        .section-head, .content, .contact { padding: 20px; }
    }
    @media (prefers-color-scheme: dark) {
        :root { color-scheme: dark; --ink: #e7edf7; --muted: #9ba8bb; --line: #273449; --paper: #111827; --soft: #172236; }
        body { background: linear-gradient(180deg, #07111f 0%, #0f172a 100%); }
        .quick a, .data-card { background: #111827; }
        .callout.warning { background: rgba(245,158,11,.08); }
        .contact { background: #050b14; }
    }
</style>
</head>
<body>
<main class="page">
    <div class="topbar">
        <div class="brand"><span class="mark">HK</span><span><?= esc($appName) ?> Trust Center</span></div>
        <div>Last updated <?= esc($updated) ?></div>
    </div>

    <section class="hero">
        <div class="hero-grid">
            <div>
                <p class="eyebrow">Privacy Policy for Google Play and Web</p>
                <h1>Your business data stays your business.</h1>
                <p class="lede">This page explains what <?= esc($appName) ?> collects, why it is needed,
                how Google Play Billing is handled, and how users can contact us for data access,
                correction, export, or deletion.</p>
            </div>
            <div class="identity" aria-label="App identity">
                <div><span>App name</span><strong><?= esc($appName) ?></strong></div>
                <div><span>Package name</span><strong><?= esc($package) ?></strong></div>
                <div><span>Developer</span><strong><?= esc($company) ?></strong></div>
                <div><span>Website</span><a href="<?= esc($website, 'attr') ?>"><?= esc($website) ?></a></div>
                <div><span>Privacy URL</span><a href="<?= esc($privacyUrl, 'attr') ?>"><?= esc($privacyUrl) ?></a></div>
            </div>
        </div>
    </section>

    <nav class="quick" aria-label="Privacy summary">
        <a href="#collect">Data We Collect<small>Account, ERP, billing, logs</small></a>
        <a href="#use">How We Use It<small>Operate and protect the service</small></a>
        <a href="#play">Google Play<small>Billing and subscription verification</small></a>
        <a href="#rights">Your Choices<small>Access, correction, export, deletion</small></a>
    </nav>

    <section class="section" id="collect">
        <div class="section-head">
            <h2>1. Information We Collect</h2>
            <p>We collect only the information needed to run the ERP, accounting, inventory, billing,
            authentication, and notification features that users choose to use.</p>
        </div>
        <div class="content">
            <div class="data-map">
                <?php foreach ($dataCards as $card): ?>
                    <article class="data-card">
                        <b><?= esc($card['title']) ?></b>
                        <p><?= esc($card['body']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section" id="use">
        <div class="section-head">
            <h2>2. How We Use Information</h2>
            <p>Each use is tied to a service function, security requirement, or legal/accounting need.</p>
        </div>
        <div class="content">
            <div class="steps">
                <div class="step"><div><b>Run the product</b><p>Authenticate users, load company data, apply roles and permissions, maintain subscriptions, and provide dashboards, reports, exports, and receipts.</p></div></div>
                <div class="step"><div><b>Protect accounts</b><p>Detect failed or suspicious login activity, secure API access with bearer tokens, keep audit logs, and prevent unauthorized use.</p></div></div>
                <div class="step"><div><b>Deliver useful alerts</b><p>Send reminders, service messages, subscription updates, payment confirmations, security alerts, and browser push notifications when enabled.</p></div></div>
                <div class="step"><div><b>Meet obligations</b><p>Maintain invoices, tax/payment records, support records, fraud-prevention evidence, backups, and operational logs where required.</p></div></div>
            </div>
        </div>
    </section>

    <section class="section" id="play">
        <div class="section-head">
            <h2>3. Google Play Billing and Third Parties</h2>
            <p>For Android subscriptions, Google Play is the payment processor and our server verifies
            purchases before access is activated.</p>
        </div>
        <div class="content">
            <div class="play-panel">
                <div class="callout">
                    <b>What Google Play sends us</b>
                    <p>Purchase token, product/base-plan identifiers, Google Play order id, linked token,
                    renewal/cancellation/refund status, acknowledgement status, and expiry time.</p>
                </div>
                <div class="callout warning">
                    <b>What we do not receive</b>
                    <p>We do not receive or store full card numbers, UPI IDs, bank account details,
                    Google account passwords, or Google Play payment credentials.</p>
                </div>
            </div>
            <p>We also use Google Sign-In for optional authentication, Google Play Developer API for
            subscription verification, Google Translate on authentication screens, and payment gateway
            services for web subscription payments where available. We do not sell personal information
            and we do not share information for third-party advertising.</p>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>4. Sharing, Security, and Retention</h2>
            <p>Business data is controlled by firm/company access rules and retained only as needed.</p>
        </div>
        <div class="content">
            <p>We may share information with service providers that process data for us, with company or
            firm owners and administrators for accounts under that firm, when required by law, to protect
            rights and security, or during a business transfer. Access inside the Service is controlled
            by account roles, permissions, company membership, and subscription status.</p>
            <p>We use reasonable safeguards such as HTTPS in production, password hashing, token-based
            API access, role/permission checks, audit logs, and server-side Google Play verification. We
            retain account, business, audit, billing, and tax records as long as needed to provide the
            Service, meet legal or accounting requirements, resolve disputes, prevent abuse, and maintain
            backups.</p>
        </div>
    </section>

    <section class="section" id="rights">
        <div class="section-head">
            <h2>5. Your Choices and Rights</h2>
            <p>Contact us for privacy requests. Some business records may require firm owner approval.</p>
        </div>
        <div class="content">
            <div class="rights-grid">
                <div class="right-box"><b>Access or correction</b><p>Ask us to provide or correct personal information linked to your account.</p></div>
                <div class="right-box"><b>Export or deletion</b><p>Request export or deletion where permitted by law and business-record obligations.</p></div>
                <div class="right-box"><b>Notifications</b><p>Disable optional web-push notifications in your browser or device settings.</p></div>
                <div class="right-box"><b>Subscriptions</b><p>Cancel Android subscriptions through Google Play according to Google Play policies.</p></div>
            </div>
            <p>The Service is designed for business and accounting use. It is not directed to children
            under 13 years old or the minimum age in your jurisdiction. Information may be processed and
            stored where we or our service providers operate.</p>
        </div>
    </section>

    <section class="contact">
        <div>
            <h2>Contact Us</h2>
            <p>For privacy questions, data requests, account deletion, or Google Play policy support,
            contact the <?= esc($appName) ?> team.</p>
        </div>
        <div class="contact-card">
            <div><strong>Email</strong><br><a href="mailto:<?= esc($contact, 'attr') ?>"><?= esc($contact) ?></a></div>
            <div><strong>Website</strong><br><a href="<?= esc($website, 'attr') ?>"><?= esc($website) ?></a></div>
            <div><strong>Privacy URL</strong><br><a href="<?= esc($privacyUrl, 'attr') ?>"><?= esc($privacyUrl) ?></a></div>
        </div>
    </section>

    <p class="foot">&copy; <?= date('Y') ?> <?= esc($company) ?>. <?= esc($appName) ?> package <?= esc($package) ?>. All rights reserved.</p>
</main>
</body>
</html>
