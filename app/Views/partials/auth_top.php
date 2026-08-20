<?php
/**
 * Shared auth chrome — top half. Matches the public landing site (Hissab-Kitaab
 * brand, teal→blue→violet gradient, Sora/Inter). Renders <head>, the brand hero
 * panel and opens the form card; the including view supplies the form, then
 * includes partials/auth_bottom.
 *
 * Params (all optional):
 *   $pageTitle  string  browser <title>
 *   $heroTitle  string  hero headline (raw HTML — wrap accents in <em>)
 *   $heroLede   string  hero paragraph
 *   $heroBadge  string  small uppercase pill label
 *   $heroPoints list<string>  feature bullets on the hero panel
 */
$pageTitle = $pageTitle ?? 'Sign In';
$heroBadge = $heroBadge ?? 'Plan smarter, grow faster';
$heroTitle = $heroTitle ?? 'Your business books, <em>beautifully simple.</em>';
$heroLede  = $heroLede  ?? 'The simplest way to run your firm\'s cash book, inventory and reports — all in one place.';
$heroPoints = $heroPoints ?? [
    'Record Jama &amp; Naam in seconds',
    'Live reports, statements &amp; exports',
    'Bank-grade security for your data',
];
$appName = brand_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle) ?> &middot; <?= esc($appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= erp_asset('assets/img/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= erp_asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= erp_asset('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= erp_asset('assets/css/i18n.css') ?>">
    <!-- Gate the screen until the chosen UI language has been applied -->
    <?= $this->include('partials/lang_boot') ?>
<style>
:root{
  --ink:#0b1220;--ink2:#1e293b;--muted:#64748b;--line:#e6ebf3;
  --brand:#2563eb;--teal:#0f766e;--violet:#7c3aed;--accent:#f59e0b;
  --bg:#f6f8ff;--surface:#fff;
  --grad:linear-gradient(150deg,#0f766e 0%,#2563eb 52%,#7c3aed 100%);
  --shadow:0 24px 70px rgba(15,23,42,.16);--shadow-sm:0 6px 20px rgba(15,23,42,.08);
}
*{box-sizing:border-box}
body.auth-body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,sans-serif;color:var(--ink2);background:var(--bg);-webkit-font-smoothing:antialiased;min-height:100vh}
h1,h2,.wm{font-family:Sora,Inter,sans-serif;letter-spacing:-.02em;color:var(--ink)}
a{text-decoration:none}
.wm{font-weight:800;font-size:1.5rem}
.wm .a{color:var(--accent)}

/* loader */
#authLoader{position:fixed;inset:0;z-index:9999;display:grid;place-items:center;background:var(--bg);transition:opacity .4s ease,visibility .4s ease}
#authLoader.fadeOut{opacity:0;visibility:hidden}
.auth-spin{width:44px;height:44px;border-radius:50%;border:4px solid #dbe4f5;border-top-color:var(--brand);animation:aspin .7s linear infinite}
@keyframes aspin{to{transform:rotate(360deg)}}

/* shell */
.login-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.login-shell{width:100%;max-width:1080px;display:grid;grid-template-columns:1.05fr .95fr;background:var(--surface);border:1px solid var(--line);border-radius:26px;overflow:hidden;box-shadow:var(--shadow)}

/* hero (brand) panel */
.login-hero{position:relative;overflow:hidden;padding:44px 42px;color:#fff;background:var(--grad);display:flex;flex-direction:column}
.login-hero .blob{position:absolute;border-radius:50%;filter:blur(60px);opacity:.5}
.login-hero .blob.b1{width:320px;height:320px;background:rgba(255,255,255,.22);top:-120px;right:-90px}
.login-hero .blob.b2{width:260px;height:260px;background:rgba(15,118,110,.5);bottom:-110px;left:-80px}
.hero-brand{display:inline-flex;align-items:center;gap:11px;position:relative;z-index:1}
.hero-brand .mark{width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.16);display:grid;place-items:center;font-size:1.3rem}
.hero-brand .wm{color:#fff}
.hero-brand small{display:block;color:rgba(255,255,255,.75);font-size:.72rem;font-weight:600;letter-spacing:.02em}
.hero-badge{display:inline-flex;align-items:center;gap:7px;margin-top:34px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.28);border-radius:20px;padding:6px 13px;font-size:.74rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;position:relative;z-index:1;width:max-content}
.login-hero h1{position:relative;z-index:1;margin:16px 0 12px;color:#fff;font-size:clamp(1.7rem,3vw,2.35rem);font-weight:800;line-height:1.1}
.login-hero h1 em{font-style:normal;color:#ffe08a}
.login-hero>p{position:relative;z-index:1;color:rgba(255,255,255,.9);margin:0 0 26px;font-size:1.02rem;max-width:420px}
.hero-points{list-style:none;padding:0;margin:0;display:grid;gap:13px;position:relative;z-index:1}
.hero-points li{display:flex;align-items:center;gap:11px;font-weight:600;color:#fff}
.hero-points .ic{width:30px;height:30px;border-radius:9px;background:rgba(255,255,255,.16);display:grid;place-items:center;flex:0 0 auto}
.hero-foot{margin-top:auto;padding-top:30px;display:flex;gap:24px;position:relative;z-index:1}
.hero-foot div b{display:block;font-family:Sora,sans-serif;font-size:1.3rem;font-weight:800}
.hero-foot div span{color:rgba(255,255,255,.78);font-size:.8rem;font-weight:600}

/* form panel */
.login-panel{position:relative;padding:44px 46px;display:flex;flex-direction:column;justify-content:center}
.login-card{width:100%;max-width:400px;margin:0 auto}
.mobile-logo{display:none}
.login-card h2{font-size:1.6rem;font-weight:800;margin:0 0 4px}
.login-card .subtitle{color:var(--muted);margin:0 0 22px;font-size:.98rem}

/* fields */
.auth-field{margin-bottom:16px}
.auth-label{display:block;font-weight:700;font-size:.88rem;color:var(--ink);margin-bottom:6px}
.input-wrap{position:relative;display:flex;align-items:center}
.input-icon{position:absolute;left:13px;color:var(--muted);font-size:1.05rem;pointer-events:none;display:flex}
.auth-control{width:100%;padding:12px 14px 12px 42px;border:1.5px solid var(--line);border-radius:12px;font:inherit;color:var(--ink);background:#fff;outline:none;transition:.15s border,.15s box-shadow}
.auth-control:focus{border-color:var(--brand);box-shadow:0 0 0 4px rgba(37,99,235,.12)}
.toggle-pass{position:absolute;right:8px;background:none;border:0;color:var(--muted);width:34px;height:34px;border-radius:8px;cursor:pointer;display:grid;place-items:center}
.toggle-pass:hover{color:var(--brand);background:var(--bg)}

.form-meta{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:4px 0 18px}
.remember-option{display:inline-flex;align-items:center;gap:8px;font-size:.9rem;color:var(--ink2);cursor:pointer;margin:0}
.remember-option input{width:17px;height:17px;accent-color:var(--brand)}
.forgot-link{font-size:.9rem;font-weight:700;color:var(--brand)}
.forgot-link:hover{text-decoration:underline}

.login-button{width:100%;display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:13px 20px;border:0;border-radius:12px;background:var(--grad);color:#fff;font-weight:700;font-size:1.02rem;cursor:pointer;box-shadow:0 12px 26px rgba(37,99,235,.32);transition:.18s transform,.18s box-shadow}
.login-button:hover{transform:translateY(-2px);box-shadow:0 16px 32px rgba(37,99,235,.4)}
.login-button:active{transform:translateY(0)}

/* divider + social */
.auth-divider{display:flex;align-items:center;gap:12px;margin:22px 0;color:var(--muted);font-size:.82rem;font-weight:600}
.auth-divider::before,.auth-divider::after{content:"";flex:1;height:1px;background:var(--line)}
.social-login{display:grid;gap:10px}
.social-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:11px 16px;border:1.5px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font-weight:700;font-size:.95rem;transition:.15s}
.social-btn:hover{border-color:#cdd8ec;background:var(--bg);transform:translateY(-1px)}
.social-btn i{font-size:1.15rem}
.social-google i{color:#ea4335}

/* alerts / links */
.auth-alert{padding:12px 14px;border-radius:12px;margin-bottom:16px;font-size:.9rem;font-weight:600}
.auth-alert.info{background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;word-break:break-all}
.back-link{display:inline-flex;align-items:center;gap:7px;margin-top:18px;color:var(--muted);font-weight:700;font-size:.92rem}
.back-link:hover{color:var(--brand)}
.text-center{text-align:center}
.login-copyright{margin-top:24px;text-align:center;color:var(--muted);font-size:.8rem}
.login-copyright a{color:var(--brand);font-weight:700}
.auth-policy-sep{margin:0 6px;color:var(--line)}

/* language switcher */
.auth-lang{position:fixed;top:18px;right:20px;z-index:40}
.auth-lang-btn{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--line);border-radius:11px;padding:8px 12px;font-weight:700;font-size:.86rem;color:var(--ink);cursor:pointer;box-shadow:var(--shadow-sm)}
.auth-lang-caret{font-size:.7rem;color:var(--muted)}
.auth-lang-menu{position:absolute;top:calc(100% + 8px);right:0;min-width:220px;max-height:340px;overflow:auto;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow);padding:6px;display:none}
.auth-lang.open .auth-lang-menu{display:block}
.auth-lang-item{width:100%;display:flex;align-items:center;gap:10px;background:none;border:0;padding:9px 10px;border-radius:10px;cursor:pointer;text-align:left}
.auth-lang-item:hover{background:var(--bg)}
.auth-lang-item .lang-flag{font-size:1.15rem}
.auth-lang-text strong{display:block;font-size:.9rem;color:var(--ink)}
.auth-lang-text small{color:var(--muted);font-size:.76rem}
.auth-lang-item .lang-check{margin-left:auto;color:var(--brand);opacity:0}
.auth-lang-item.active .lang-check{opacity:1}

@media(max-width:860px){
  .login-shell{grid-template-columns:1fr;max-width:460px}
  .login-hero{display:none}
  .login-panel{padding:34px 26px}
  .mobile-logo{display:flex;align-items:center;gap:10px;justify-content:center;margin-bottom:18px}
  .mobile-logo .wm{font-size:1.5rem}
  .mobile-logo .mark{width:40px;height:40px;border-radius:11px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:1.15rem}
}
/* Sign in ↔ Create account toggle (self-service signup) */
.auth-view[hidden]{display:none}
.auth-switch{margin:18px 0 0;text-align:center;font-size:.92rem;color:var(--muted)}
.auth-switch a{font-weight:800;color:var(--brand);cursor:pointer;margin-left:4px;text-decoration:none}
.auth-switch a:hover{text-decoration:underline}
</style>
</head>
<body class="auth-body">
    <div id="authLoader"><span class="auth-spin"></span></div>

    <!-- Language loader + first-visit chooser -->
    <?= $this->include('partials/lang_widgets') ?>

    <!-- Language switcher -->
    <div class="auth-lang" id="authLang">
        <button type="button" class="auth-lang-btn" data-lang-toggle translate="no">
            <i class="bi bi-translate"></i>
            <span data-lang-current>English</span>
            <i class="bi bi-chevron-down auth-lang-caret"></i>
        </button>
        <div class="auth-lang-menu">
            <?php foreach (erp_languages() as $code => $lang): ?>
                <button type="button" class="lang-option auth-lang-item" data-lang="<?= esc($code, 'attr') ?>" data-lang-label="<?= esc($lang['native'], 'attr') ?>" translate="no">
                    <span class="lang-flag"><?= $lang['flag'] ?></span>
                    <span class="auth-lang-text">
                        <strong><?= esc($lang['native']) ?></strong>
                        <small><?= esc($lang['name']) ?></small>
                    </span>
                    <i class="bi bi-check2 lang-check"></i>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <main class="login-page">
        <section class="login-shell" aria-label="<?= esc($appName) ?> authentication">
            <!-- BRAND HERO -->
            <aside class="login-hero">
                <span class="blob b1"></span><span class="blob b2"></span>
                <div class="hero-brand">
                    <span class="mark"><i class="bi bi-journal-text"></i></span>
                    <div>
                        <span class="wm">Hissab-<span class="a">Kitaab</span></span>
                        <small><?= esc(brand_tagline()) ?></small>
                    </div>
                </div>

                <span class="hero-badge"><i class="bi bi-stars"></i> <?= esc($heroBadge) ?></span>
                <h1><?= $heroTitle ?></h1>
                <p><?= esc($heroLede) ?></p>

                <ul class="hero-points">
                    <?php foreach ($heroPoints as $pt): ?>
                        <li><span class="ic"><i class="bi bi-check-lg"></i></span><span><?= $pt ?></span></li>
                    <?php endforeach; ?>
                </ul>

                <div class="hero-foot">
                    <div><b>1&nbsp;app</b><span>Your whole firm</span></div>
                    <div><b>256-bit</b><span>Encryption</span></div>
                    <div><b>24/7</b><span>Access anywhere</span></div>
                </div>
            </aside>

            <!-- FORM PANEL -->
            <div class="login-panel">
                <div class="login-card">
                    <div class="mobile-logo">
                        <span class="mark"><i class="bi bi-journal-text"></i></span>
                        <span class="wm">Hissab-<span class="a">Kitaab</span></span>
                    </div>
