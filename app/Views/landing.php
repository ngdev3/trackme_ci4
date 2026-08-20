<?php
/**
 * Hissab-Kitaab public marketing landing page (standalone, self-contained).
 * Rendered by LandingController for guests. Pricing is live from the DB.
 */
$appName       = $appName       ?? brand_name();
$tagline       = $tagline       ?? brand_tagline();
$supportWa     = $supportWa     ?? '916393505070';
$supportWaShown= $supportWaShown?? '+91 63935 05070';
$supportEmail  = $supportEmail  ?? brand_support_email();
$plans         = $plans         ?? [];
$planFeatures  = $planFeatures  ?? [];
$trialDays     = (int) ($trialDays ?? 180);

// Legal / contact identity (kept in sync with the Privacy Policy page).
$company      = 'CR Industries';
$contactEmail = 'support@hissabkitaab.com';
$website      = 'https://hissabkitaab.com';
$playUrl      = 'https://play.google.com/store/apps/details?id=com.crind.hissabkitaab';
$websiteShown = preg_replace('~^https?://~', '', rtrim($website, '/'));

$cycleWord = static function ($c) {
    $c = (string) $c;
    return $c === 'monthly' ? '/mo' : ($c === 'lifetime' ? ' once' : '/yr');
};
// Mark the second plan (or the last) as the "popular" highlight.
$popularId = null;
if (! empty($plans)) {
    $popularId = (int) $plans[min(1, count($plans) - 1)]['id'];
}
$loginUrl  = site_url('login');
$googleUrl = site_url('auth/google');
$waUrl     = 'https://wa.me/' . $supportWa . '?text=' . rawurlencode('Hi, I would like to know more about ' . $appName . '.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($appName) ?> — <?= esc($tagline) ?></title>
    <meta name="description" content="<?= esc($appName) ?> is the simplest way to run your firm's cash book, inventory, reports and reminders. <?= esc(ucfirst($tagline)) ?>.">
    <link rel="icon" type="image/svg+xml" href="<?= erp_asset('assets/img/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= erp_asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --ink:#0b1220; --ink2:#1e293b; --muted:#64748b; --line:#e6ebf3;
  --brand:#2563eb; --teal:#0f766e; --violet:#7c3aed; --accent:#f59e0b;
  --bg:#f6f8ff; --surface:#ffffff; --radius:18px;
  --grad:linear-gradient(120deg,#0f766e 0%,#2563eb 52%,#7c3aed 100%);
  --shadow:0 22px 60px rgba(15,23,42,.14); --shadow-sm:0 6px 20px rgba(15,23,42,.08);
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,sans-serif;color:var(--ink2);background:var(--bg);line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:hidden}
h1,h2,h3,.wm{font-family:Sora,Inter,sans-serif;color:var(--ink);letter-spacing:-.02em;line-height:1.08;margin:0}
a{color:inherit;text-decoration:none}
img{max-width:100%}
.container{width:100%;max-width:1160px;margin:0 auto;padding:0 20px}
.section{padding:96px 0}
.eyebrow{display:inline-flex;align-items:center;gap:8px;font-weight:800;font-size:.76rem;letter-spacing:.12em;text-transform:uppercase;color:var(--brand)}
.eyebrow.on-dark{color:#c7d2fe}
.h-sec{font-size:clamp(1.8rem,3.6vw,2.7rem);font-weight:800;margin:12px 0 10px}
.lede{color:var(--muted);font-size:1.06rem;max-width:640px}
.center{text-align:center}
.center .lede{margin-left:auto;margin-right:auto}

/* buttons */
.btn{display:inline-flex;align-items:center;gap:9px;font-weight:700;font-size:.98rem;padding:13px 22px;border-radius:12px;border:1px solid transparent;cursor:pointer;transition:.18s transform,.18s box-shadow,.18s background;white-space:nowrap}
.btn:hover{transform:translateY(-2px)}
.btn-primary{background:var(--grad);color:#fff;box-shadow:0 10px 26px rgba(37,99,235,.35)}
.btn-ghost{background:#fff;color:var(--ink);border-color:var(--line);box-shadow:var(--shadow-sm)}
.btn-light{background:rgba(255,255,255,.14);color:#fff;border-color:rgba(255,255,255,.28)}
.btn-outline-light{background:transparent;color:#fff;border-color:rgba(255,255,255,.45)}
.btn-lg{padding:16px 28px;font-size:1.05rem}

/* wordmark */
.wm{font-weight:800;font-size:1.32rem}
.wm .a{color:var(--accent)}
.wm.on-dark{color:#fff}

/* ===== NAV ===== */
header.nav{position:fixed;top:0;left:0;right:0;z-index:50;transition:.25s}
header.nav .bar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:14px;padding:12px 20px;background:rgba(255,255,255,.72);backdrop-filter:blur(14px);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow-sm)}
header.nav.scrolled .bar{box-shadow:0 12px 30px rgba(15,23,42,.12)}
.nav-links{display:flex;align-items:center;gap:28px}
.nav-links a{font-weight:600;color:var(--ink2);font-size:.96rem}
.nav-links a:hover{color:var(--brand)}
.nav-cta{display:flex;align-items:center;gap:10px}
.nav-toggle{display:none;background:none;border:0;font-size:1.5rem;color:var(--ink)}
@media(max-width:900px){
  .nav-links,.nav-cta .btn-ghost{display:none}
  .nav-toggle{display:block}
  header.nav.open .nav-links{display:flex;position:absolute;top:78px;left:20px;right:20px;flex-direction:column;align-items:flex-start;gap:6px;background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px;box-shadow:var(--shadow)}
  header.nav.open .nav-links a{padding:10px 8px;width:100%;border-radius:10px}
  header.nav.open .nav-links a:hover{background:var(--bg)}
}

/* ===== HERO ===== */
.hero{position:relative;padding:150px 0 90px;overflow:hidden}
.hero .blob{position:absolute;border-radius:50%;filter:blur(70px);opacity:.5;z-index:0}
.hero .b1{width:520px;height:520px;background:#7c3aed44;top:-160px;right:-120px}
.hero .b2{width:460px;height:460px;background:#0f766e44;bottom:-180px;left:-140px}
.hero .b3{width:300px;height:300px;background:#2563eb33;top:120px;left:40%}
.hero-grid{position:relative;z-index:1;display:grid;grid-template-columns:1.05fr .95fr;gap:48px;align-items:center}
.hero h1{font-size:clamp(2.3rem,5.2vw,3.85rem);font-weight:800}
.hero h1 .grad{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.hero p.sub{margin:20px 0 28px;font-size:1.14rem;color:var(--muted);max-width:560px}
.hero-cta{display:flex;flex-wrap:wrap;gap:12px}
.hero-trust{display:flex;flex-wrap:wrap;gap:20px;margin-top:26px;color:var(--muted);font-size:.9rem;font-weight:600}
.hero-trust span{display:inline-flex;align-items:center;gap:7px}
.hero-trust i{color:var(--teal)}
@media(max-width:860px){.hero-grid{grid-template-columns:1fr;gap:36px}.hero{padding:130px 0 60px}}

/* product mockup */
.mock{position:relative}
.mock-card{position:relative;z-index:2;background:var(--surface);border:1px solid var(--line);border-radius:22px;box-shadow:var(--shadow);padding:16px;transform:perspective(1600px) rotateY(-9deg) rotateX(4deg);transition:.4s}
.mock:hover .mock-card{transform:perspective(1600px) rotateY(-3deg) rotateX(1deg)}
.mock-top{display:flex;align-items:center;gap:6px;padding:4px 6px 12px}
.mock-top .dot{width:10px;height:10px;border-radius:50%}
.mock-top .dot:nth-child(1){background:#ff5f57}.mock-top .dot:nth-child(2){background:#febc2e}.mock-top .dot:nth-child(3){background:#28c840}
.mock-top b{margin-left:8px;font-size:.82rem;color:var(--muted);font-weight:700}
.mock-tiles{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px}
.mtile{border:1px solid var(--line);border-radius:14px;padding:12px}
.mtile-lbl{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)}
.mtile-val{font-size:1.15rem;font-weight:800;color:var(--ink);font-family:Sora,sans-serif}
.mtile .up{font-size:.72rem;font-weight:700;color:#16a34a}
.mock-panels{display:grid;grid-template-columns:1.5fr 1fr;gap:10px}
.mpanel{border:1px solid var(--line);border-radius:14px;padding:12px}
.mpanel h6{margin:0 0 6px;font-size:.74rem;font-weight:800;color:var(--ink2)}
.badge-float{position:absolute;z-index:3;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow);padding:10px 13px;display:flex;align-items:center;gap:10px;font-size:.82rem;font-weight:700}
.badge-float .ic{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;color:#fff}
.bf1{top:8px;left:-26px;animation:floaty 5s ease-in-out infinite}
.bf2{bottom:26px;right:-22px;animation:floaty 6s ease-in-out infinite .6s}
.bf1 .ic{background:#16a34a}.bf2 .ic{background:var(--violet)}
.badge-float small{display:block;font-weight:600;color:var(--muted);font-size:.72rem}
@keyframes floaty{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
@media(max-width:520px){.badge-float{display:none}}

/* ===== marquee / trust ===== */
.stats{background:var(--surface);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.stats .container{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;padding:34px 20px}
.stat{ text-align:center}
.stat b{display:block;font-family:Sora,sans-serif;font-size:2rem;font-weight:800;color:var(--ink);background:var(--grad);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.stat span{color:var(--muted);font-weight:600;font-size:.9rem}
@media(max-width:700px){.stats .container{grid-template-columns:1fr 1fr;gap:26px}}

/* ===== features ===== */
.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:44px}
.feat{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:26px;box-shadow:var(--shadow-sm);transition:.2s}
.feat:hover{transform:translateY(-6px);box-shadow:var(--shadow);border-color:#dbe4f5}
.feat .fic{width:50px;height:50px;border-radius:14px;display:grid;place-items:center;font-size:1.4rem;color:#fff;margin-bottom:16px}
.feat h3{font-size:1.16rem;font-weight:700;margin-bottom:8px}
.feat p{color:var(--muted);font-size:.96rem;margin:0}
@media(max-width:860px){.feat-grid{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.feat-grid{grid-template-columns:1fr}}

/* ===== showcase ===== */
.show{display:grid;grid-template-columns:1fr 1fr;gap:52px;align-items:center;margin-top:56px}
.show.rev .show-media{order:2}
.show-media{background:var(--grad);border-radius:24px;padding:26px;box-shadow:var(--shadow)}
.show-media .inner{background:#fff;border-radius:16px;padding:16px;min-height:260px}
.show ul{list-style:none;padding:0;margin:18px 0 0;display:grid;gap:12px}
.show ul li{display:flex;gap:11px;align-items:flex-start;font-weight:600;color:var(--ink2)}
.show ul li i{color:var(--teal);margin-top:3px}
@media(max-width:860px){.show{grid-template-columns:1fr;gap:26px}.show.rev .show-media{order:0}}

/* ledger mock */
.lrow{display:flex;align-items:center;justify-content:space-between;padding:9px 4px;border-bottom:1px dashed var(--line);font-size:.86rem}
.lrow:last-child{border-bottom:0}
.lrow .l{display:flex;align-items:center;gap:9px}
.lrow .ic{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;font-size:.9rem}
.pos{color:#16a34a;font-weight:800}.neg{color:#dc2626;font-weight:800}
.chip{font-size:.66rem;font-weight:800;padding:2px 8px;border-radius:20px;background:#eef2ff;color:#4338ca}

/* ===== pricing ===== */
.price-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:18px;margin-top:44px;align-items:stretch}
.pcard{position:relative;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:26px;display:flex;flex-direction:column;box-shadow:var(--shadow-sm);transition:.2s}
.pcard:hover{transform:translateY(-6px);box-shadow:var(--shadow)}
.pcard.pop{border-color:transparent;box-shadow:0 26px 60px rgba(37,99,235,.22);background:linear-gradient(180deg,#fff, #fbfdff)}
.pcard.pop:before{content:"Most popular";position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:var(--grad);color:#fff;font-size:.72rem;font-weight:800;padding:5px 14px;border-radius:20px;letter-spacing:.03em;box-shadow:0 8px 18px rgba(124,58,237,.3)}
.pcard h3{font-size:1.2rem;font-weight:700}
.pcard .price{margin:14px 0 4px;font-family:Sora,sans-serif}
.pcard .price b{font-size:2.4rem;font-weight:800;color:var(--ink)}
.pcard .price span{color:var(--muted);font-weight:600}
.pcard ul{list-style:none;padding:0;margin:18px 0 22px;display:grid;gap:10px;flex:1}
.pcard ul li{display:flex;gap:9px;align-items:flex-start;font-size:.92rem;color:var(--ink2)}
.pcard ul li i{color:#16a34a;margin-top:3px}
.price-note{text-align:center;margin-top:26px;color:var(--muted);font-weight:600}
.price-note b{color:var(--teal)}

/* ===== testimonials ===== */
.tgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:44px}
.tcard{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:24px;box-shadow:var(--shadow-sm)}
.tstars{color:var(--accent);margin-bottom:10px}
.tcard p{font-size:.98rem;color:var(--ink2)}
.twho{display:flex;align-items:center;gap:12px;margin-top:16px}
.tav{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:800;font-family:Sora,sans-serif}
.twho b{display:block;font-weight:700;color:var(--ink);font-size:.95rem}
.twho small{color:var(--muted)}
@media(max-width:860px){.tgrid{grid-template-columns:1fr}}

/* ===== faq ===== */
.faq{max-width:820px;margin:44px auto 0}
.faq details{background:var(--surface);border:1px solid var(--line);border-radius:14px;padding:4px 20px;margin-bottom:12px;box-shadow:var(--shadow-sm)}
.faq summary{list-style:none;cursor:pointer;padding:16px 0;font-weight:700;color:var(--ink);display:flex;justify-content:space-between;align-items:center;gap:10px}
.faq summary::-webkit-details-marker{display:none}
.faq summary i{transition:.2s;color:var(--brand)}
.faq details[open] summary i{transform:rotate(45deg)}
.faq p{margin:0 0 16px;color:var(--muted)}

/* ===== CTA band ===== */
.cta-band{position:relative;overflow:hidden;background:var(--grad);border-radius:28px;padding:56px;text-align:center;color:#fff;box-shadow:var(--shadow)}
.cta-band h2{color:#fff;font-size:clamp(1.7rem,3.4vw,2.5rem)}
.cta-band p{color:rgba(255,255,255,.9);max-width:560px;margin:12px auto 26px;font-size:1.06rem}
.cta-band .glow{position:absolute;width:420px;height:420px;border-radius:50%;background:rgba(255,255,255,.16);filter:blur(50px)}
.cta-band .g1{top:-160px;left:-80px}.cta-band .g2{bottom:-180px;right:-60px}

/* ===== footer (light, multi-column) ===== */
footer{background:#eef1f5;color:#334155;padding:66px 0 0;border-top:1px solid var(--line)}
.foot-grid{display:grid;grid-template-columns:1.7fr 1fr 1.15fr 1.5fr;gap:36px}
footer h5{color:#0b1220;font-size:1.06rem;margin:0 0 18px;font-weight:800;font-family:Sora,sans-serif}
footer a{color:#475569;font-size:.96rem}
footer a:hover{color:var(--brand)}
.foot-col a{display:block;padding:7px 0}
.foot-brand .wm{font-size:1.55rem}
.foot-tag{color:#475569;max-width:360px;margin:16px 0 18px;font-size:1rem}
.trust-badge{display:inline-flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:9px 14px;box-shadow:var(--shadow-sm);font-weight:700;color:#0b1220;font-size:.86rem}
.trust-badge i{color:var(--teal);font-size:1.1rem}
.foot-legal-note{color:#64748b;font-size:.86rem;margin:16px 0 0;line-height:1.55}
.contact-list{display:grid;gap:15px;margin-top:2px}
.contact-item{display:flex;align-items:flex-start;gap:12px;color:#64748b;font-size:.86rem}
.contact-item .ci{flex:0 0 auto;width:36px;height:36px;border-radius:50%;background:#fff;border:1px solid var(--line);display:grid;place-items:center;color:var(--brand);box-shadow:var(--shadow-sm)}
.contact-item a{display:inline-block;padding:0;font-weight:700;color:#0b1220;font-size:.98rem}
.contact-item a:hover{color:var(--brand)}
.foot-soc{display:flex;gap:10px;margin-top:20px}
.foot-soc a{width:40px;height:40px;border-radius:50%;background:#0b1220;display:grid;place-items:center;padding:0;color:#fff;font-size:1.05rem;transition:.18s transform,.18s box-shadow}
.foot-soc a:hover{transform:translateY(-3px);box-shadow:0 10px 20px rgba(15,23,42,.22)}
.foot-bottom{border-top:1px solid var(--line);margin-top:50px;padding:22px 0;display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;color:#64748b;font-size:.9rem}
@media(max-width:900px){.foot-grid{grid-template-columns:1fr 1fr}.foot-brand{grid-column:1/-1}}
@media(max-width:560px){.foot-grid{grid-template-columns:1fr}}

/* ===== inquiry form ===== */
.inq{display:grid;grid-template-columns:.92fr 1.08fr;border-radius:24px;overflow:hidden;box-shadow:var(--shadow);border:1px solid var(--line);background:var(--surface)}
.inq-info{position:relative;overflow:hidden;background:var(--grad);color:#fff;padding:42px}
.inq-info .glow{position:absolute;width:340px;height:340px;border-radius:50%;background:rgba(255,255,255,.15);filter:blur(55px);top:-130px;right:-90px}
.inq-info h2{color:#fff;font-size:2rem;margin-top:10px}
.inq-info>p{color:rgba(255,255,255,.9);margin:12px 0 26px;position:relative;z-index:1}
.inq-contact{display:grid;gap:16px;position:relative;z-index:1}
.inq-contact a,.inq-contact .row2{display:flex;align-items:center;gap:13px;color:#fff;font-weight:700}
.inq-contact a:hover{color:#fff;opacity:.9}
.inq-ic{width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.16);display:grid;place-items:center;font-size:1.15rem;flex:0 0 auto}
.inq-contact small{display:block;color:rgba(255,255,255,.78);font-weight:500;font-size:.82rem}
.inq-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:28px;position:relative;z-index:1}
.inq-badges span{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:20px;padding:6px 12px;font-size:.8rem;font-weight:700;display:inline-flex;align-items:center;gap:6px}
.inq-form{padding:42px;position:relative}
.inq-form h3{font-size:1.4rem;margin-bottom:4px}
.inq-form .sub{color:var(--muted);margin-bottom:20px;font-size:.95rem}
.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:15px}
.field.full{grid-column:1/-1}
.field label{font-weight:700;font-size:.9rem;color:var(--ink)}
.field label .req{color:#dc2626}
.control{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;font:inherit;color:var(--ink);background:#fff;transition:.15s border,.15s box-shadow;outline:none}
.control:focus{border-color:var(--brand);box-shadow:0 0 0 4px rgba(37,99,235,.12)}
select.control{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center}
textarea.control{resize:vertical;min-height:118px}
.field.invalid .control{border-color:#dc2626;box-shadow:0 0 0 4px rgba(220,38,38,.1)}
.field.valid .control{border-color:#16a34a}
.field .err{color:#dc2626;font-size:.82rem;font-weight:600;display:none}
.field.invalid .err{display:block}
.field .hint{color:var(--muted);font-size:.78rem}
.consent{display:flex;gap:10px;align-items:flex-start;font-size:.9rem;color:var(--ink2);cursor:pointer}
.consent input{margin-top:3px;width:18px;height:18px;flex:0 0 auto;accent-color:var(--brand)}
.consent a{color:var(--brand);font-weight:700}
.hp{position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden}
.inq-submit{margin-top:6px;width:100%;justify-content:center}
.inq-submit[disabled]{opacity:.75;cursor:not-allowed;transform:none}
.inq-alert{display:none;padding:12px 14px;border-radius:12px;font-weight:600;margin-bottom:16px;font-size:.92rem}
.inq-alert.err{display:block;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
.inq-success{display:none;text-align:center;padding:26px 10px}
.inq-success .tick{width:76px;height:76px;border-radius:50%;background:#dcfce7;color:#16a34a;display:grid;place-items:center;font-size:2.3rem;margin:0 auto 14px;animation:pop .4s ease}
@keyframes pop{0%{transform:scale(.4);opacity:0}100%{transform:scale(1);opacity:1}}
.inq-success h3{font-size:1.5rem}
.inq-success p{color:var(--muted);max-width:430px;margin:8px auto 18px}
.spin{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.5);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:860px){.inq{grid-template-columns:1fr}.fgrid{grid-template-columns:1fr}.inq-info,.inq-form{padding:30px}}

/* reveal */
.reveal{opacity:0;transform:translateY(24px);transition:.7s cubic-bezier(.2,.7,.2,1)}
.reveal.in{opacity:1;transform:none}

/* whatsapp fab */
.wa-fab{position:fixed;bottom:22px;right:22px;z-index:60;width:56px;height:56px;border-radius:50%;background:#25d366;color:#fff;display:grid;place-items:center;font-size:1.7rem;box-shadow:0 12px 30px rgba(37,211,102,.45);transition:.2s}
.wa-fab:hover{transform:scale(1.08)}
</style>
</head>
<body>

<!-- NAV -->
<header class="nav" id="nav">
  <div class="container">
    <div class="bar">
      <a href="#top" class="wm">Hissab-<span class="a">Kitaab</span></a>
      <nav class="nav-links">
        <a href="#features">Features</a>
        <a href="#how">How it works</a>
        <a href="#pricing">Pricing</a>
        <a href="#faq">FAQ</a>
        <a href="#inquiry">Contact</a>
      </nav>
      <div class="nav-cta">
        <a href="<?= esc($loginUrl) ?>" class="btn btn-ghost">Sign in</a>
        <a href="<?= esc($loginUrl) ?>" class="btn btn-primary">Get started <i class="bi bi-arrow-right"></i></a>
        <button class="nav-toggle" id="navToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
      </div>
    </div>
  </div>
</header>

<!-- HERO -->
<section class="hero" id="top">
  <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
  <div class="container hero-grid">
    <div class="reveal">
      <span class="eyebrow"><i class="bi bi-stars"></i> <?= esc(ucwords($tagline)) ?></span>
      <h1>Your firm's <span class="grad">cash book, inventory &amp; reports</span> — beautifully simple.</h1>
      <p class="sub"><?= esc($appName) ?> turns your daily Jama &amp; Naam entries into clean reports, live balances and smart reminders — so you always know where your money is.</p>
      <div class="hero-cta">
        <a href="<?= esc($loginUrl) ?>" class="btn btn-primary btn-lg">Start your <?= $trialDays ?>-day free trial <i class="bi bi-arrow-right"></i></a>
        <a href="#features" class="btn btn-ghost btn-lg"><i class="bi bi-play-circle"></i> See features</a>
      </div>
      <div class="hero-trust">
        <span><i class="bi bi-check-circle-fill"></i> No credit card to start</span>
        <span><i class="bi bi-shield-lock-fill"></i> Bank-grade security</span>
        <span><i class="bi bi-phone-fill"></i> Works on any device</span>
      </div>
    </div>

    <!-- product mockup -->
    <div class="mock reveal">
      <div class="badge-float bf1"><span class="ic"><i class="bi bi-arrow-down-left"></i></span><div>Payment received<small>+₹45,000 today</small></div></div>
      <div class="badge-float bf2"><span class="ic"><i class="bi bi-gem"></i></span><div>Premium active<small>All features unlocked</small></div></div>
      <div class="mock-card">
        <div class="mock-top"><span class="dot"></span><span class="dot"></span><span class="dot"></span><b><?= esc($appName) ?> · Dashboard</b></div>
        <div class="mock-tiles">
          <div class="mtile"><div class="mtile-lbl">Cash in hand</div><div class="mtile-val">₹5,90,036</div><div class="up"><i class="bi bi-arrow-up"></i> 12.5% this week</div></div>
          <div class="mtile"><div class="mtile-lbl">Net profit</div><div class="mtile-val">₹7,20,000</div><div class="up"><i class="bi bi-arrow-up"></i> 15.8% MoM</div></div>
        </div>
        <div class="mock-panels">
          <div class="mpanel">
            <h6>Cash flow · last 6 months</h6>
            <svg viewBox="0 0 240 96" width="100%" height="96" preserveAspectRatio="none">
              <defs><linearGradient id="ar" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#2563eb" stop-opacity=".35"/><stop offset="1" stop-color="#2563eb" stop-opacity="0"/></linearGradient></defs>
              <path d="M0,74 L40,70 L80,72 L120,58 L160,40 L200,26 L240,10" fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M0,74 L40,70 L80,72 L120,58 L160,40 L200,26 L240,10 L240,96 L0,96 Z" fill="url(#ar)"/>
              <circle cx="200" cy="26" r="4" fill="#2563eb"/><circle cx="240" cy="10" r="4" fill="#7c3aed"/>
            </svg>
          </div>
          <div class="mpanel">
            <h6>Expenses</h6>
            <svg viewBox="0 0 42 42" width="86" height="86" style="display:block;margin:2px auto">
              <circle cx="21" cy="21" r="15.9" fill="none" stroke="#eef2ff" stroke-width="6"/>
              <circle cx="21" cy="21" r="15.9" fill="none" stroke="#2563eb" stroke-width="6" stroke-dasharray="52 100" stroke-dashoffset="25"/>
              <circle cx="21" cy="21" r="15.9" fill="none" stroke="#0f766e" stroke-width="6" stroke-dasharray="28 100" stroke-dashoffset="-27"/>
              <circle cx="21" cy="21" r="15.9" fill="none" stroke="#f59e0b" stroke-width="6" stroke-dasharray="20 100" stroke-dashoffset="-55"/>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats">
  <div class="container">
    <div class="stat"><b data-count="12">0</b><span>Cr+ tracked yearly</span></div>
    <div class="stat"><b data-count="3200">0</b><span>Firms onboarded</span></div>
    <div class="stat"><b data-count="99" data-suffix="%">0</b><span>Uptime</span></div>
    <div class="stat"><b data-count="4.8" data-decimal="1">0</b><span>Avg. rating</span></div>
  </div>
</div>

<!-- FEATURES -->
<section class="section" id="features">
  <div class="container center">
    <span class="eyebrow"><i class="bi bi-grid-1x2"></i> Everything in one place</span>
    <h2 class="h-sec">One app to run the whole firm</h2>
    <p class="lede">From the daily cash register to stock, reports and reminders — <?= esc($appName) ?> replaces the messy notebooks and scattered sheets.</p>
  </div>
  <div class="container">
    <div class="feat-grid">
      <?php
      $features = [
        ['bi-journal-text','#2563eb',$appName . ' Vahi','Record Jama &amp; Naam in seconds. Live Rokad balance, daily Rokadh Parcha and a searchable ledger.'],
        ['bi-box-seam','#0f766e','Inventory','Track items, stock levels and value with low-stock and out-of-stock alerts built in.'],
        ['bi-bar-chart-line','#7c3aed','Reports &amp; Exports','Beautiful breakdowns, account statements and one-tap PDF / Excel / print exports.'],
        ['bi-alarm','#f59e0b','Smart Reminders','Never miss a payment or follow-up with reminders and browser push notifications.'],
        ['bi-shield-lock','#e11d48','Password Vault','Keep firm logins and secrets in an encrypted, company-scoped vault.'],
        ['bi-buildings','#0891b2','Multi-firm & Users','Run multiple firms, invite your team and control who sees what with roles.'],
      ];
      foreach ($features as [$ic,$col,$t,$d]): ?>
        <div class="feat reveal">
          <div class="fic" style="background:<?= $col ?>"><i class="bi <?= $ic ?>"></i></div>
          <h3><?= $t ?></h3>
          <p><?= $d ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- HOW IT WORKS / SHOWCASE -->
<section class="section" id="how" style="background:var(--surface);border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
  <div class="container center">
    <span class="eyebrow"><i class="bi bi-signpost-2"></i> How it works</span>
    <h2 class="h-sec">Live in three simple steps</h2>
    <p class="lede">No accounting degree needed. If you can write in a notebook, you can run <?= esc($appName) ?>.</p>
  </div>

  <div class="container">
    <div class="show reveal">
      <div class="show-media">
        <div class="inner">
          <div class="lrow"><div class="l"><span class="ic" style="background:#dcfce7;color:#16a34a"><i class="bi bi-arrow-down-left"></i></span><div><b>testing01</b><br><small style="color:var(--muted)">Farmer · Jama</small></div></div><span class="pos">+₹11,11,111</span></div>
          <div class="lrow"><div class="l"><span class="ic" style="background:#fee2e2;color:#dc2626"><i class="bi bi-arrow-up-right"></i></span><div><b>ulskjf</b><br><small style="color:var(--muted)">Cash · Naam</small></div></div><span class="neg">−₹1,31,231</span></div>
          <div class="lrow"><div class="l"><span class="ic" style="background:#e0e7ff;color:#4338ca"><i class="bi bi-cash-coin"></i></span><div><b>Opening balance</b><br><small style="color:var(--muted)">Today</small></div></div><span class="chip">Auto</span></div>
        </div>
      </div>
      <div>
        <span class="eyebrow"><i class="bi bi-1-circle"></i> Step 1</span>
        <h2 class="h-sec" style="font-size:1.7rem">Record every rupee in &amp; out</h2>
        <p class="lede">Add a deposit (Jama) or expense (Naam) with the payment mode, party and an optional attachment. Your Rokad balance updates instantly.</p>
        <ul>
          <li><i class="bi bi-check-circle-fill"></i> Cash, UPI, bank &amp; more payment modes</li>
          <li><i class="bi bi-check-circle-fill"></i> Attach bills &amp; receipts to any entry</li>
          <li><i class="bi bi-check-circle-fill"></i> Daily Rokadh Parcha, always balanced</li>
        </ul>
      </div>
    </div>

    <div class="show rev reveal">
      <div class="show-media">
        <div class="inner">
          <div style="display:flex;gap:12px;align-items:center;justify-content:center;padding:6px 0 12px">
            <svg viewBox="0 0 42 42" width="120" height="120">
              <circle cx="21" cy="21" r="15.9" fill="none" stroke="#eef2ff" stroke-width="5"/>
              <circle cx="21" cy="21" r="15.9" fill="none" stroke="#2563eb" stroke-width="5" stroke-dasharray="46 100" stroke-dashoffset="25"/>
              <circle cx="21" cy="21" r="15.9" fill="none" stroke="#0f766e" stroke-width="5" stroke-dasharray="30 100" stroke-dashoffset="-21"/>
              <circle cx="21" cy="21" r="15.9" fill="none" stroke="#f59e0b" stroke-width="5" stroke-dasharray="24 100" stroke-dashoffset="-51"/>
              <text x="21" y="20" text-anchor="middle" font-size="4.4" font-weight="700" fill="#0b1220" font-family="Sora">₹1.25L</text>
              <text x="21" y="25" text-anchor="middle" font-size="2.4" fill="#64748b">expenses</text>
            </svg>
            <div style="font-size:.82rem">
              <div style="margin:5px 0"><span style="color:#2563eb">●</span> Purchase 52%</div>
              <div style="margin:5px 0"><span style="color:#0f766e">●</span> Transport 16%</div>
              <div style="margin:5px 0"><span style="color:#f59e0b">●</span> Salary 12%</div>
            </div>
          </div>
        </div>
      </div>
      <div>
        <span class="eyebrow"><i class="bi bi-2-circle"></i> Step 2</span>
        <h2 class="h-sec" style="font-size:1.7rem">Watch reports build themselves</h2>
        <p class="lede">Every entry rolls up into live dashboards, category breakdowns and account statements — ready to export or share, no spreadsheets required.</p>
        <ul>
          <li><i class="bi bi-check-circle-fill"></i> Real-time firm dashboard</li>
          <li><i class="bi bi-check-circle-fill"></i> Statement &amp; report exports (PDF / XLSX)</li>
          <li><i class="bi bi-check-circle-fill"></i> Money-in vs money-out at a glance</li>
        </ul>
      </div>
    </div>

    <div class="show reveal">
      <div class="show-media">
        <div class="inner">
          <div class="lrow"><div class="l"><span class="ic" style="background:#eff6ff;color:#2563eb"><i class="bi bi-box"></i></span><div><b>Cement (50kg)</b><br><small style="color:var(--muted)">350 bags</small></div></div><span class="chip" style="background:#dcfce7;color:#16a34a">In stock</span></div>
          <div class="lrow"><div class="l"><span class="ic" style="background:#fff7ed;color:#d97706"><i class="bi bi-box"></i></span><div><b>Steel Rod (12mm)</b><br><small style="color:var(--muted)">28 pieces</small></div></div><span class="chip" style="background:#fef9c3;color:#a16207">Low stock</span></div>
          <div class="lrow"><div class="l"><span class="ic" style="background:#fef2f2;color:#dc2626"><i class="bi bi-box"></i></span><div><b>Floor Tiles (2x2)</b><br><small style="color:var(--muted)">0 box</small></div></div><span class="chip" style="background:#fee2e2;color:#dc2626">Out</span></div>
        </div>
      </div>
      <div>
        <span class="eyebrow"><i class="bi bi-3-circle"></i> Step 3</span>
        <h2 class="h-sec" style="font-size:1.7rem">Stay ahead with alerts</h2>
        <p class="lede">Low-stock warnings, payment reminders and browser push keep you a step ahead — the app nudges you before things slip.</p>
        <ul>
          <li><i class="bi bi-check-circle-fill"></i> Low &amp; out-of-stock alerts</li>
          <li><i class="bi bi-check-circle-fill"></i> Reminders with browser push</li>
          <li><i class="bi bi-check-circle-fill"></i> One tap to the exact screen that needs you</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section class="section" id="pricing">
  <div class="container center">
    <span class="eyebrow"><i class="bi bi-tags"></i> Simple pricing</span>
    <h2 class="h-sec">Start free, upgrade when you grow</h2>
    <p class="lede">Every plan starts with a <b><?= $trialDays ?>-day free trial</b> — full access, no card needed. Pick the package that fits your firm.</p>
  </div>
  <div class="container">
    <?php if (empty($plans)): ?>
      <div class="center" style="margin-top:30px;color:var(--muted)">Plans are being set up. <a href="<?= esc($loginUrl) ?>" style="color:var(--brand);font-weight:700">Sign in</a> to get started.</div>
    <?php else: ?>
      <div class="price-grid">
        <?php foreach ($plans as $p):
          $isPop = (int) $p['id'] === $popularId;
          $price = (float) $p['price'];
          $feats = $planFeatures[(int) $p['id']] ?? [];
        ?>
          <div class="pcard reveal <?= $isPop ? 'pop' : '' ?>">
            <h3><?= esc($p['name']) ?></h3>
            <div class="price"><b>₹<?= esc(number_format($price, 0)) ?></b><span><?= esc($cycleWord($p['billing_cycle'] ?? 'yearly')) ?></span></div>
            <div style="color:var(--muted);font-size:.86rem;font-weight:600">Billed <?= esc($p['billing_cycle'] ?? 'yearly') ?></div>
            <ul>
              <?php foreach ($feats as $f): ?>
                <li><i class="bi bi-check-circle-fill"></i> <?= esc($f) ?></li>
              <?php endforeach; ?>
            </ul>
            <a href="<?= esc($loginUrl) ?>" class="btn <?= $isPop ? 'btn-primary' : 'btn-ghost' ?>" style="justify-content:center">Choose <?= esc($p['name']) ?></a>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="price-note"><i class="bi bi-shield-check"></i> Secure online payment via Cashfree · <b>instant activation</b> · cancel anytime.</div>
    <?php endif; ?>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section" style="background:var(--surface);border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
  <div class="container center">
    <span class="eyebrow"><i class="bi bi-chat-quote"></i> Loved by firms</span>
    <h2 class="h-sec">Trusted by growing businesses</h2>
  </div>
  <div class="container">
    <div class="tgrid">
      <?php
      $tst = [
        ['R','#2563eb','Ramesh Traders','Wholesaler, Bhadohi','Replaced three notebooks and an Excel sheet. My daily cash never mismatches now — the Rokadh Parcha is a lifesaver.'],
        ['S','#0f766e','Sharma Enterprises','Retailer, Varanasi','Reports and statements that used to take hours are ready in one tap. Sharing with my CA is effortless.'],
        ['A','#7c3aed','Amit Distributors','Distributor, Kanpur','Low-stock alerts alone paid for the subscription. The whole team is on the same page for the first time.'],
      ];
      foreach ($tst as [$in,$col,$n,$r,$q]): ?>
        <div class="tcard reveal">
          <div class="tstars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
          <p>“<?= esc($q) ?>”</p>
          <div class="twho">
            <span class="tav" style="background:<?= $col ?>"><?= $in ?></span>
            <div><b><?= esc($n) ?></b><small><?= esc($r) ?></small></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section" id="faq">
  <div class="container center">
    <span class="eyebrow"><i class="bi bi-question-circle"></i> FAQ</span>
    <h2 class="h-sec">Questions, answered</h2>
  </div>
  <div class="container">
    <div class="faq">
      <?php
      $faqs = [
        ['Is there really a free trial?', 'Yes — every new account gets a ' . $trialDays . '-day free trial with full access to premium features. No credit card is required to start.'],
        ['What happens when my trial ends?', 'Your data is always kept safe. When the trial ends, premium features are locked until you subscribe to a plan — you can upgrade anytime from inside the app.'],
        ['How do I pay for a plan?', 'Securely online via Cashfree (UPI, cards, netbanking). Your plan activates instantly and you get a GST tax receipt for every payment.'],
        ['Can I run more than one firm?', 'Yes. Higher plans let you run multiple firms and invite your team, with roles that control who can see and do what.'],
        ['Is my data secure?', 'Your firm data is isolated per company, sensitive vault entries are encrypted, and access is protected with role-based permissions.'],
      ];
      foreach ($faqs as $i => [$q,$a]): ?>
        <details <?= $i === 0 ? 'open' : '' ?>>
          <summary><?= esc($q) ?> <i class="bi bi-plus-lg"></i></summary>
          <p><?= esc($a) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- INQUIRY FORM -->
<section class="section" id="inquiry" style="padding-top:40px">
  <div class="container center" style="margin-bottom:8px">
    <span class="eyebrow"><i class="bi bi-chat-dots"></i> Get in touch</span>
    <h2 class="h-sec">Have a question? Let's talk.</h2>
    <p class="lede">Tell us what you need — pricing, a demo, or a hand getting started. We usually reply within one business day.</p>
  </div>
  <div class="container">
    <div class="inq reveal">
      <div class="inq-info">
        <span class="glow"></span>
        <span class="eyebrow on-dark"><i class="bi bi-headset"></i> We're here to help</span>
        <h2>Talk to the team</h2>
        <p>Reach us the way that suits you — fill in the form, or contact us directly.</p>
        <div class="inq-contact">
          <a href="<?= esc($waUrl) ?>" target="_blank" rel="noopener"><span class="inq-ic"><i class="bi bi-whatsapp"></i></span><div>WhatsApp<small><?= esc($supportWaShown) ?></small></div></a>
          <a href="mailto:<?= esc($contactEmail) ?>"><span class="inq-ic"><i class="bi bi-envelope-fill"></i></span><div>Email us<small><?= esc($contactEmail) ?></small></div></a>
          <div class="row2"><span class="inq-ic"><i class="bi bi-clock-fill"></i></span><div>Support hours<small>Mon–Sat · 10 AM – 7 PM IST</small></div></div>
        </div>
        <div class="inq-badges">
          <span><i class="bi bi-shield-check"></i> Your details stay private</span>
          <span><i class="bi bi-lightning-charge-fill"></i> Fast replies</span>
        </div>
      </div>

      <div class="inq-form">
        <div class="inq-success" id="inqSuccess">
          <div class="tick"><i class="bi bi-check-lg"></i></div>
          <h3>Message sent!</h3>
          <p id="inqSuccessMsg">Thank you! Our team will get back to you shortly.</p>
          <button type="button" class="btn btn-ghost" id="inqAnother"><i class="bi bi-arrow-left"></i> Send another message</button>
        </div>

        <form id="inqForm" novalidate>
          <h3>Send us an inquiry</h3>
          <p class="sub">Fields marked <span style="color:#dc2626">*</span> are required.</p>
          <div class="inq-alert" id="inqAlert" role="alert"></div>
          <?= csrf_field() ?>
          <div class="hp" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

          <div class="fgrid">
            <div class="field" data-field="name">
              <label>Full name <span class="req">*</span></label>
              <input class="control" type="text" name="name" maxlength="120" autocomplete="name" placeholder="Your name">
              <span class="err">Please enter your name.</span>
            </div>
            <div class="field" data-field="email">
              <label>Email <span class="req">*</span></label>
              <input class="control" type="email" name="email" maxlength="190" autocomplete="email" placeholder="you@business.com">
              <span class="err">Please enter a valid email address.</span>
            </div>
            <div class="field" data-field="phone">
              <label>Phone</label>
              <input class="control" type="tel" name="phone" maxlength="20" autocomplete="tel" placeholder="+91 9XXXXXXXXX">
              <span class="err">Please enter a valid phone number.</span>
            </div>
            <div class="field" data-field="company">
              <label>Business name</label>
              <input class="control" type="text" name="company" maxlength="150" placeholder="Your firm (optional)">
              <span class="err">Business name is too long.</span>
            </div>
            <div class="field full" data-field="subject">
              <label>I'm interested in</label>
              <select class="control" name="subject">
                <option value="general">General enquiry</option>
                <option value="pricing">Pricing &amp; plans</option>
                <option value="demo">A product demo</option>
                <option value="support">Support</option>
                <option value="partnership">Partnership</option>
              </select>
              <span class="err">Please choose a valid option.</span>
            </div>
            <div class="field full" data-field="message">
              <label>Message <span class="req">*</span></label>
              <textarea class="control" name="message" maxlength="2000" placeholder="How can we help?"></textarea>
              <div style="display:flex"><span class="err">Please add a little more detail (min 10 characters).</span><span class="hint" id="inqCount" style="margin-left:auto">0 / 2000</span></div>
            </div>
          </div>

          <div class="field full" data-field="consent">
            <label class="consent"><input type="checkbox" name="consent" value="1"><span>I agree to be contacted by <?= esc($appName) ?> about my enquiry and accept the <a href="<?= site_url('privacy') ?>" target="_blank" rel="noopener">Privacy Policy</a>.</span></label>
            <span class="err">Please agree to be contacted.</span>
          </div>

          <button type="submit" class="btn btn-primary btn-lg inq-submit" id="inqSubmit">
            <span class="lbl">Send message</span> <i class="bi bi-send"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- CTA BAND -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="cta-band reveal">
      <span class="glow g1"></span><span class="glow g2"></span>
      <div style="position:relative;z-index:1">
        <h2>Ready to take control of your Hisaab?</h2>
        <p>Join thousands of firms who run their entire cash book, stock and reports on <?= esc($appName) ?>. Start free today.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
          <a href="<?= esc($loginUrl) ?>" class="btn btn-light btn-lg">Start free trial <i class="bi bi-arrow-right"></i></a>
          <a href="<?= esc($googleUrl) ?>" class="btn btn-outline-light btn-lg"><i class="bi bi-google"></i> Continue with Google</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="foot-grid">
      <div class="foot-brand">
        <a href="#top" class="wm">Hissab-<span class="a">Kitaab</span></a>
        <p class="foot-tag"><?= esc($appName) ?> is India's simplest cash book, inventory &amp; reports platform for growing businesses.</p>
        <span class="trust-badge"><i class="bi bi-shield-lock-fill"></i> Bank-grade security · 256-bit encryption</span>
        <p class="foot-legal-note">
          Operated by <?= esc($company) ?>.<br>
          Online payments securely processed by Cashfree Payments.
        </p>
      </div>

      <div class="foot-col">
        <h5>Company</h5>
        <a href="<?= site_url('about') ?>">About Us</a>
        <a href="<?= site_url('careers') ?>">Careers</a>
        <a href="#inquiry">Contact Us</a>
        <a href="#pricing">Pricing</a>
        <a href="#faq">FAQ</a>
      </div>

      <div class="foot-col">
        <h5>Legal</h5>
        <a href="<?= site_url('terms') ?>">Terms &amp; Conditions</a>
        <a href="<?= site_url('privacy') ?>">Privacy Policy</a>
        <a href="<?= site_url('refunds') ?>">Refund &amp; Cancellation</a>
        <a href="<?= site_url('contact') ?>">Contact &amp; Support</a>
      </div>

      <div class="foot-col foot-contact">
        <h5>We'd love to hear from you!</h5>
        <div class="contact-list">
          <div class="contact-item"><span class="ci"><i class="bi bi-whatsapp"></i></span><div>Call / WhatsApp<br><a href="<?= esc($waUrl) ?>" target="_blank" rel="noopener"><?= esc($supportWaShown) ?></a></div></div>
          <div class="contact-item"><span class="ci"><i class="bi bi-envelope-fill"></i></span><div>Email us at<br><a href="mailto:<?= esc($contactEmail) ?>"><?= esc($contactEmail) ?></a></div></div>
          <div class="contact-item"><span class="ci"><i class="bi bi-globe2"></i></span><div>Visit us<br><a href="<?= esc($website) ?>" target="_blank" rel="noopener"><?= esc($websiteShown) ?></a></div></div>
        </div>
        <div class="foot-soc">
          <a href="<?= esc($waUrl) ?>" target="_blank" rel="noopener" aria-label="WhatsApp" style="background:#25d366"><i class="bi bi-whatsapp"></i></a>
          <a href="mailto:<?= esc($contactEmail) ?>" aria-label="Email" style="background:#ea4335"><i class="bi bi-envelope-fill"></i></a>
          <a href="<?= esc($playUrl) ?>" target="_blank" rel="noopener" aria-label="Get it on Google Play" style="background:#0f9d58"><i class="bi bi-google-play"></i></a>
          <a href="<?= esc($website) ?>" target="_blank" rel="noopener" aria-label="Website" style="background:#2563eb"><i class="bi bi-globe2"></i></a>
        </div>
      </div>
    </div>

    <div class="foot-bottom">
      <span>&copy; <?= date('Y') ?> <?= esc($company) ?>. All rights reserved.</span>
      <span><?= esc($appName) ?> · <?= esc(ucfirst($tagline)) ?></span>
    </div>
  </div>
</footer>

<a href="<?= esc($waUrl) ?>" target="_blank" rel="noopener" class="wa-fab" aria-label="Chat on WhatsApp"><i class="bi bi-whatsapp"></i></a>

<script>
(function(){
  // sticky nav shadow
  var nav=document.getElementById('nav');
  var onScroll=function(){ nav.classList.toggle('scrolled', window.scrollY>10); };
  onScroll(); window.addEventListener('scroll',onScroll,{passive:true});

  // mobile menu
  var t=document.getElementById('navToggle');
  if(t){ t.addEventListener('click',function(){ nav.classList.toggle('open'); }); }
  document.querySelectorAll('.nav-links a').forEach(function(a){ a.addEventListener('click',function(){ nav.classList.remove('open'); }); });

  // reveal on scroll
  var io=new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); } }); },{threshold:.12});
  document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });

  // count-up stats
  var seen=false;
  var stats=document.querySelector('.stats');
  var run=function(){
    document.querySelectorAll('[data-count]').forEach(function(el){
      var target=parseFloat(el.getAttribute('data-count'));
      var dec=parseInt(el.getAttribute('data-decimal')||'0',10);
      var suf=el.getAttribute('data-suffix')||'';
      var start=null, dur=1400;
      var step=function(ts){ if(!start)start=ts; var p=Math.min((ts-start)/dur,1);
        var val=target*(0.5-Math.cos(Math.PI*p)/2);
        el.textContent=(dec?val.toFixed(dec):Math.round(val).toLocaleString('en-IN'))+suf;
        if(p<1)requestAnimationFrame(step); };
      requestAnimationFrame(step);
    });
  };
  var so=new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting&&!seen){ seen=true; run(); so.disconnect(); } }); },{threshold:.4});
  if(stats)so.observe(stats);
})();

/* ---------------- Inquiry form: client validation + AJAX ---------------- */
(function(){
  var form=document.getElementById('inqForm');
  if(!form) return;
  var el=function(n){ return form.elements[n]; };
  var submitBtn=document.getElementById('inqSubmit');
  var alertBox=document.getElementById('inqAlert');
  var successBox=document.getElementById('inqSuccess');
  var countEl=document.getElementById('inqCount');
  var msg=el('message');

  function fieldEl(name){ return form.querySelector('.field[data-field="'+name+'"]'); }
  function setState(name, ok, errText){
    var f=fieldEl(name); if(!f) return;
    f.classList.toggle('invalid', !ok);
    var inp=el(name), filled = inp && inp.type!=='checkbox' && (inp.value||'').trim()!=='';
    f.classList.toggle('valid', ok && filled);
    if(errText){ var e=f.querySelector('.err'); if(e) e.textContent=errText; }
  }
  var emailRe=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var phoneRe=/^[0-9+()\-\s]{7,20}$/;
  var validators={
    name:function(v){ v=v.trim(); if(v.length<2) return 'Please enter your name (min 2 characters).'; if(v.length>120) return 'Name is too long.'; return ''; },
    email:function(v){ v=v.trim(); if(!v) return 'Please enter your email.'; if(!emailRe.test(v)||v.length>190) return 'Please enter a valid email address.'; return ''; },
    phone:function(v){ v=v.trim(); if(!v) return ''; if(!phoneRe.test(v)) return 'Please enter a valid phone number.'; return ''; },
    company:function(v){ if(v.trim().length>150) return 'Business name is too long.'; return ''; },
    message:function(v){ v=v.trim(); if(v.length<10) return 'Please add a little more detail (min 10 characters).'; if(v.length>2000) return 'Message is too long.'; return ''; },
    consent:function(){ return el('consent').checked ? '' : 'Please agree to be contacted.'; }
  };
  function validateField(name){
    var inp=el(name); var v = (inp && inp.type!=='checkbox') ? inp.value : '';
    var err=validators[name] ? validators[name](v) : '';
    setState(name, err==='', err);
    return err==='';
  }
  function validateAll(){
    var ok=true, first=null;
    Object.keys(validators).forEach(function(n){ if(!validateField(n)){ ok=false; if(!first) first=n; } });
    if(first && el(first) && el(first).focus){ el(first).focus(); }
    return ok;
  }
  ['name','email','phone','company','message'].forEach(function(n){
    var inp=el(n); if(!inp) return;
    inp.addEventListener('blur', function(){ validateField(n); });
    inp.addEventListener('input', function(){ if(fieldEl(n).classList.contains('invalid')) validateField(n); });
  });
  el('consent').addEventListener('change', function(){ validateField('consent'); });
  if(msg&&countEl){ var upd=function(){ countEl.textContent=msg.value.length+' / 2000'; }; msg.addEventListener('input',upd); upd(); }

  function refreshToken(csrf){
    if(!csrf||!csrf.name) return;
    var t=form.querySelector('input[name="'+csrf.name+'"]') || form.querySelector('input[type=hidden][name^="csrf"]');
    if(t){ t.name=csrf.name; t.value=csrf.hash; }
  }

  form.addEventListener('submit', function(e){
    e.preventDefault();
    alertBox.classList.remove('err'); alertBox.textContent='';
    if(!validateAll()){ alertBox.textContent='Please fix the highlighted fields and try again.'; alertBox.classList.add('err'); return; }
    var orig=submitBtn.innerHTML;
    submitBtn.disabled=true; submitBtn.innerHTML='<span class="spin"></span> Sending…';
    fetch('<?= site_url('inquiry') ?>',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new FormData(form)})
      .then(function(r){ return r.json().then(function(j){ return {status:r.status,j:j}; }).catch(function(){ return {status:r.status,j:{}}; }); })
      .then(function(res){
        var j=res.j||{};
        refreshToken(j.csrf);
        if(res.status===200 && j.ok){
          if(j.message) document.getElementById('inqSuccessMsg').textContent=String(j.message).replace(/<[^>]*>/g,'');
          form.style.display='none';
          successBox.style.display='block';
          successBox.scrollIntoView({behavior:'smooth',block:'center'});
          return;
        }
        submitBtn.disabled=false; submitBtn.innerHTML=orig;
        if(res.status===422 && j.errors){ Object.keys(j.errors).forEach(function(n){ setState(n,false,j.errors[n]); }); }
        alertBox.textContent=j.message||'Something went wrong. Please try again.'; alertBox.classList.add('err');
      })
      .catch(function(){ submitBtn.disabled=false; submitBtn.innerHTML=orig; alertBox.textContent='Network error. Please check your connection and try again.'; alertBox.classList.add('err'); });
  });

  var another=document.getElementById('inqAnother');
  if(another) another.addEventListener('click', function(){
    successBox.style.display='none'; form.reset(); form.style.display='block';
    ['name','email','phone','company','message','consent'].forEach(function(n){ var f=fieldEl(n); if(f) f.classList.remove('valid','invalid'); });
    if(countEl) countEl.textContent='0 / 2000';
    if(el('name')) el('name').focus();
  });
})();
</script>
</body>
</html>
