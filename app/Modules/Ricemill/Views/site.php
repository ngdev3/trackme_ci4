<?php
helper('url');
$base    = base_url();
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
function rm_old($k) { return htmlspecialchars((string) service('request')->getPost($k), ENT_QUOTES); }

/* ---- Real mill profile pulled from DB (firm_name), with safe fallbacks ---- */
$m = isset($mill) ? $mill : null;
$rawName  = ($m && !empty($m->name)) ? trim($m->name) : 'CR Industries';
$millName = implode(' ', array_map(function ($w) {
    return (strlen($w) <= 2) ? strtoupper($w) : ucfirst(strtolower($w));
}, preg_split('/\s+/', $rawName)));                          // "CR Industries"
$initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $rawName), 0, 2)); // "CR"
$email    = ($m && !empty($m->company_email)) ? trim($m->company_email) : '';
$address  = ($m && !empty($m->address)) ? trim($m->address) : '';
$gst      = ($m && !empty($m->gst_no)) ? trim($m->gst_no) : '';
$fssai    = ($m && !empty($m->fssai_no) && strtoupper($m->fssai_no) !== 'N/A') ? trim($m->fssai_no) : '';
$licMandi = ($m && !empty($m->mandi_license_mandi) && strtoupper($m->mandi_license_mandi) !== 'N/A') ? trim($m->mandi_license_mandi) : '';
$licMill  = ($m && !empty($m->mandi_license_mill) && strtoupper($m->mandi_license_mill) !== 'N/A') ? trim($m->mandi_license_mill) : '';
$phone    = '';   // No phone stored in DB — set here (e.g. '+91 98765 43210') to show a "Call us" button.
$mapAddr  = $address !== '' ? $address : 'Shahabad, Hardoi, Uttar Pradesh';
$heroVid  = $base . 'assets/ricemill/hero.mp4';
$heroPos  = $base . 'assets/ricemill/hero-poster.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (function_exists('seo_head')):
        echo seo_head(array(
            'title'       => isset($title) ? $title : $millName,
            'description' => $millName . ' — premium quality rice mill. Modern milling, GST & FSSAI certified, trusted bulk supply of basmati & non-basmati rice.',
            'type'        => 'website',
            'canonical'   => base_url('ricemill'),
        ));
        echo seo_jsonld(array(
            'breadcrumb'   => array('Home' => base_url('ricemill')),
            'faq'          => true,
            'webpage_name' => isset($title) ? $title : $millName,
        ));
        echo seo_analytics_head();
    else: ?>
      <title><?= isset($title) ? htmlspecialchars($title) : htmlspecialchars($millName) ?></title>
      <meta name="description" content="<?= htmlspecialchars($millName) ?> — premium quality rice mill. Modern milling, GST &amp; FSSAI certified, trusted bulk supply of basmati &amp; non-basmati rice.">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --green:#2f9e52; --green-dk:#1f7a3d; --green-deep:#155c2c; --gold:#e0a92e; --gold-dk:#b9851a;
            --ink:#243029; --muted:#6c7a70; --bg:#ffffff; --cream:#fbfaf4; --soft:#f4f9f4; --line:#eaf0e8;
            --shadow:0 22px 50px rgba(31,122,61,.10); --shadow-sm:0 10px 26px rgba(31,122,61,.08);
        }
        *{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth}
        body{font-family:'Plus Jakarta Sans',system-ui,Arial,sans-serif;color:var(--ink);background:var(--bg);line-height:1.65;overflow-x:hidden}
        a{text-decoration:none;color:inherit}
        h1,h2,h3,.disp{font-family:'Fraunces',Georgia,serif;font-weight:700;letter-spacing:-.01em}
        .wrap{max-width:1160px;margin:0 auto;padding:0 22px}

        /* Reusable flex helpers */
        .flex{display:flex}
        .flex-wrap{display:flex;flex-wrap:wrap}
        .ai-c{align-items:center}
        .jc-sb{justify-content:space-between}
        .jc-c{justify-content:center}
        .gap-s{gap:12px}.gap-m{gap:20px}.gap-l{gap:34px}

        .btn{display:inline-flex;align-items:center;gap:9px;border:0;border-radius:50px;font-weight:700;cursor:pointer;padding:13px 26px;font-size:15px;transition:transform .2s,box-shadow .2s,background .2s;font-family:inherit}
        .btn:hover{transform:translateY(-2px)}
        .btn-gold{background:var(--gold);color:#3a2c00;box-shadow:0 10px 22px rgba(224,169,46,.32)}
        .btn-green{background:var(--green);color:#fff;box-shadow:0 10px 22px rgba(31,122,61,.28)}
        .btn-outline{background:#fff;color:var(--green-dk);border:1.6px solid var(--green)}
        .btn-outline:hover{background:var(--soft)}

        .rv{opacity:0;transform:translateY(26px);transition:opacity .7s ease,transform .7s ease}
        .rv.in{opacity:1;transform:none}

        /* Header (flex) */
        header{position:sticky;top:0;z-index:60;background:rgba(255,255,255,.9);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
        .nav{display:flex;align-items:center;justify-content:space-between;height:72px}
        .brand{display:flex;align-items:center;gap:12px}
        .brand .logo{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,var(--green),var(--gold));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-family:'Fraunces',serif;font-size:18px;box-shadow:0 8px 18px rgba(31,122,61,.28)}
        .brand b{font-family:'Fraunces',serif;font-size:21px;color:var(--green-dk);display:block;line-height:1.1}
        .brand small{font-size:11px;color:var(--muted);font-weight:600;letter-spacing:1.5px;text-transform:uppercase}
        .menu{display:flex;align-items:center;gap:28px}
        .menu a.lnk{font-weight:600;color:var(--ink);font-size:15px;position:relative}
        .menu a.lnk:after{content:"";position:absolute;left:0;bottom:-6px;width:0;height:2px;background:var(--gold);transition:.25s}
        .menu a.lnk:hover:after{width:100%}
        .admin-login{background:var(--green-dk);color:#fff;padding:10px 18px;border-radius:50px;font-weight:700;font-size:14px;transition:.2s}
        .admin-login:hover{background:var(--green)}
        .hamb{display:none;font-size:26px;background:none;border:0;color:var(--green-dk);cursor:pointer}

        /* HERO with background video + light overlay */
        .hero{position:relative;min-height:88vh;display:flex;align-items:center;overflow:hidden;background:var(--cream)}
        .hero-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0}
        .hero-overlay{position:absolute;inset:0;z-index:1;
            background:linear-gradient(105deg,rgba(255,255,255,.92) 0%,rgba(255,255,255,.78) 42%,rgba(244,249,244,.45) 100%);}
        .hero-overlay:after{content:"";position:absolute;inset:0;background-image:radial-gradient(rgba(31,122,61,.08) 1.3px,transparent 1.3px);background-size:26px 26px}
        .hero-inner{position:relative;z-index:2;display:flex;flex-wrap:wrap;align-items:center;gap:40px;padding:70px 22px}
        .hero-copy{flex:1 1 460px}
        .hero-side{flex:0 1 360px;display:flex;justify-content:center}
        .tagpill{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--line);border-radius:50px;padding:8px 16px;font-size:13px;font-weight:700;color:var(--green-dk);margin-bottom:22px;box-shadow:var(--shadow-sm)}
        .hero h1{font-size:clamp(36px,5.2vw,60px);line-height:1.04;margin-bottom:20px;color:var(--green-deep)}
        .hero h1 em{font-style:normal;color:var(--gold-dk)}
        .hero p.lead{font-size:18px;color:var(--muted);margin-bottom:30px;max-width:520px}
        .hero-cta{display:flex;flex-wrap:wrap;gap:14px}
        .glass-card{flex:1;background:rgba(255,255,255,.72);backdrop-filter:blur(8px);border:1px solid #fff;border-radius:22px;padding:26px;box-shadow:var(--shadow)}
        .glass-card h3{font-family:'Fraunces',serif;color:var(--green-dk);font-size:20px;margin-bottom:16px}
        .mini-stats{display:flex;flex-wrap:wrap;gap:14px}
        .mini{flex:1 1 calc(50% - 7px);background:var(--soft);border-radius:14px;padding:16px}
        .mini b{font-family:'Fraunces',serif;font-size:26px;color:var(--green);display:block}
        .mini span{font-size:12.5px;color:var(--muted);font-weight:600}

        /* Trust strip (flex) */
        .trust{background:#fff;border-bottom:1px solid var(--line)}
        .trust .wrap{display:flex;flex-wrap:wrap;gap:14px;justify-content:center;padding:20px 22px}
        .badge{display:flex;align-items:center;gap:10px;background:var(--cream);border:1px solid var(--line);border-radius:12px;padding:11px 16px;font-size:13.5px}
        .badge i{font-style:normal;font-size:18px}
        .badge b{display:block;font-weight:800;font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
        .badge span{font-weight:700;color:var(--ink)}

        section.block{padding:84px 0}
        section.block.alt{background:var(--cream)}
        .eyebrow{display:inline-flex;align-items:center;gap:8px;color:var(--gold-dk);font-weight:800;letter-spacing:1.5px;text-transform:uppercase;font-size:12.5px;margin-bottom:12px}
        .eyebrow:before{content:"";width:26px;height:2px;background:var(--gold)}
        .sec-title{font-size:clamp(28px,3.4vw,40px);margin-bottom:16px;color:var(--green-deep)}
        .sec-sub{color:var(--muted);max-width:660px;margin-bottom:40px;font-size:16.5px}

        /* Cards (flex-wrap) */
        .cards{display:flex;flex-wrap:wrap;gap:24px}
        .card{flex:1 1 300px;background:#fff;border:1px solid var(--line);border-radius:20px;padding:30px;transition:.25s}
        .card:hover{transform:translateY(-6px);box-shadow:var(--shadow);border-color:#fff}
        .card .ic{width:60px;height:60px;border-radius:16px;background:var(--soft);display:flex;align-items:center;justify-content:center;font-size:30px;margin-bottom:18px}
        .card h4{font-family:'Fraunces',serif;font-size:21px;margin-bottom:9px;color:var(--green-dk)}
        .card p{color:var(--muted);font-size:15px}

        /* About (flex) */
        .about{display:flex;flex-wrap:wrap;gap:50px;align-items:center}
        .about-copy{flex:1 1 380px}
        .about-art{flex:1 1 360px;position:relative;border-radius:24px;min-height:340px;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:var(--shadow);background:var(--soft)}
        .about-art video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.92}
        .about-art .veil{position:absolute;inset:0;background:linear-gradient(160deg,rgba(31,122,61,.12),rgba(224,169,46,.18))}
        .about-art .float-card{position:relative;z-index:2;background:#fff;border-radius:16px;padding:16px 20px;box-shadow:var(--shadow);min-width:190px}
        .about-art .float-card b{font-family:'Fraunces',serif;font-size:26px;color:var(--green);display:block}
        .about-art .float-card span{font-size:12.5px;color:var(--muted)}
        .ticks{list-style:none;margin-top:22px}
        .ticks li{display:flex;gap:12px;margin-bottom:14px;font-weight:500}
        .ticks li b{display:inline-flex;width:26px;height:26px;border-radius:50%;background:var(--soft);color:var(--green);align-items:center;justify-content:center;font-size:13px;flex:0 0 26px}

        /* Stats (flex) */
        .stats{display:flex;flex-wrap:wrap;gap:20px;margin-top:50px}
        .stat{flex:1 1 200px;background:#fff;border:1px solid var(--line);border-radius:18px;padding:26px;text-align:center;box-shadow:var(--shadow-sm)}
        .stat b{font-family:'Fraunces',serif;font-size:38px;color:var(--green);display:block}
        .stat span{color:var(--muted);font-size:14px;font-weight:600}

        /* Steps (flex) */
        .steps{display:flex;flex-wrap:wrap;gap:16px}
        .step{flex:1 1 180px;background:#fff;border:1px solid var(--line);border-radius:18px;padding:26px 18px;text-align:center;transition:.25s}
        .step:hover{transform:translateY(-5px);box-shadow:var(--shadow)}
        .step .n{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dk));color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-weight:700;font-size:19px;margin:0 auto 14px;box-shadow:0 8px 18px rgba(31,122,61,.28)}
        .step h5{font-family:'Fraunces',serif;font-size:17px;margin-bottom:6px;color:var(--green-dk)}
        .step p{font-size:13px;color:var(--muted)}

        /* Certs (flex) */
        .certs{display:flex;flex-wrap:wrap;gap:18px}
        .cert{flex:1 1 calc(50% - 9px);display:flex;gap:16px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px}
        .cert .ic{width:50px;height:50px;border-radius:12px;background:var(--soft);display:flex;align-items:center;justify-content:center;font-size:24px;flex:0 0 50px}
        .cert small{font-weight:800;font-size:11px;letter-spacing:.6px;text-transform:uppercase;color:var(--muted)}
        .cert b{display:block;font-size:17px;color:var(--ink);word-break:break-word;font-weight:700;margin-top:3px}

        /* Contact (flex) */
        .contact{display:flex;flex-wrap:wrap;gap:40px;align-items:flex-start}
        .contact-box{flex:1 1 320px;background:#fff;border:1px solid var(--line);border-radius:20px;padding:30px;box-shadow:var(--shadow-sm)}
        .contact-item{display:flex;gap:16px;margin-bottom:22px}
        .contact-item .ic{width:48px;height:48px;border-radius:13px;background:var(--soft);display:flex;align-items:center;justify-content:center;font-size:21px;flex:0 0 48px}
        .contact-item b{display:block;font-weight:700}
        .contact-item span{color:var(--muted);font-size:14.5px;word-break:break-word}
        .map{margin-top:6px;border-radius:14px;overflow:hidden;border:1px solid var(--line);line-height:0}
        .map iframe{width:100%;height:180px;border:0}
        .form-card{flex:1 1 420px;background:#fff;border:1px solid var(--line);border-radius:20px;padding:32px;box-shadow:var(--shadow)}
        .form-card h3{font-size:24px;color:var(--green-dk);margin-bottom:4px}
        .form-row{display:flex;flex-wrap:wrap;gap:16px}
        .form-row .field{flex:1 1 200px;margin-bottom:0}
        .field{margin-bottom:16px}
        .field label{display:block;font-weight:700;font-size:13.5px;margin-bottom:7px}
        .field label .req{color:#d33}
        .field input,.field select,.field textarea{width:100%;border:1.5px solid var(--line);border-radius:12px;padding:13px 15px;font-family:inherit;font-size:15px;background:#fbfdfb;transition:.18s}
        .field input:focus,.field select:focus,.field textarea:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 4px rgba(31,122,61,.1)}
        .field textarea{min-height:100px;resize:vertical}
        .flash{border-radius:14px;padding:15px 18px;margin-bottom:22px;font-weight:600;display:flex;gap:10px;align-items:flex-start}
        .flash.ok{background:#e7f6ec;color:#155c2c;border:1px solid #b7e2c4}
        .flash.err{background:#fdecec;color:#a32020;border:1px solid #f3c4c4}

        /* Footer (flex) */
        footer{background:var(--green-deep);color:#d6e8da;padding:56px 0 30px}
        .foot{display:flex;flex-wrap:wrap;gap:34px}
        .foot > div{flex:1 1 240px}
        footer h4{color:#fff;margin-bottom:16px;font-family:'Fraunces',serif;font-size:20px}
        footer p,footer a{color:#d6e8da;font-size:14.5px}
        footer a{display:block;margin-bottom:9px}
        footer a:hover{color:var(--gold)}
        .copy{border-top:1px solid rgba(255,255,255,.14);margin-top:36px;padding-top:20px;text-align:center;font-size:13px;color:#a7c6b1}

        @media(max-width:760px){
            .menu{position:absolute;top:72px;left:0;right:0;background:#fff;flex-direction:column;align-items:stretch;gap:0;border-bottom:1px solid var(--line);display:none;padding:8px 0}
            .menu.show{display:flex}
            .menu a.lnk{padding:13px 22px}.menu a.lnk:after{display:none}
            .menu .admin-login{margin:10px 22px;text-align:center}
            .hamb{display:block}
            .cert{flex:1 1 100%}
        }
    </style>
</head>
<body>
<?php if (function_exists('seo_gtm_body')) echo seo_gtm_body(); ?>

<header>
    <div class="wrap nav">
        <a href="#home" class="brand">
            <span class="logo"><?= htmlspecialchars($initials) ?></span>
            <span><b><?= htmlspecialchars($millName) ?></b><small>Rice Mill</small></span>
        </a>
        <button class="hamb" onclick="document.getElementById('menu').classList.toggle('show')">&#9776;</button>
        <nav class="menu" id="menu">
            <a class="lnk" href="#about">About</a>
            <a class="lnk" href="#products">Products</a>
            <a class="lnk" href="#quality">Quality</a>
            <a class="lnk" href="#certs">Certifications</a>
            <a class="lnk" href="#contact">Contact</a>
            <a class="lnk" href="<?= $base ?>privacy-policy">Privacy Policy</a>
            <a class="admin-login" href="<?= $base ?>admin/auth/login">🔒 Admin Login</a>
        </nav>
    </div>
</header>

<!-- HERO with background video -->
<section class="hero" id="home">
    <video class="hero-video" autoplay muted loop playsinline preload="metadata" poster="<?= $heroPos ?>">
        <source src="<?= $heroVid ?>" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>
    <div class="wrap hero-inner">
        <div class="hero-copy">
            <span class="tagpill">🌾 Trusted Quality • Shahabad, Hardoi (U.P.)</span>
            <h1>Pure Grain,<br><em><?= htmlspecialchars($millName) ?></em><br>Milled to Perfection</h1>
            <p class="lead">From the farmer's paddy to your plate — modern, hygienic milling of basmati &amp; non-basmati rice. GST &amp; FSSAI certified, with dependable bulk supply for traders, retailers and exporters.</p>
            <div class="hero-cta">
                <a href="#inquiry" class="btn btn-gold">Send an Inquiry →</a>
                <a href="#products" class="btn btn-outline">Explore Products</a>
            </div>
        </div>
        <div class="hero-side">
            <div class="glass-card">
                <h3>Why buyers choose us</h3>
                <div class="mini-stats">
                    <div class="mini"><b>50+</b><span>Tonnes / day</span></div>
                    <div class="mini"><b>15+</b><span>Rice varieties</span></div>
                    <div class="mini"><b>100%</b><span>Quality checked</span></div>
                    <div class="mini"><b>24×7</b><span>Order support</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST STRIP (real credentials from DB) -->
<div class="trust">
    <div class="wrap">
        <?php if ($gst): ?><div class="badge"><i>🧾</i><div><b>GST No.</b><span><?= htmlspecialchars($gst) ?></span></div></div><?php endif; ?>
        <?php if ($fssai): ?><div class="badge"><i>✅</i><div><b>FSSAI</b><span><?= htmlspecialchars($fssai) ?></span></div></div><?php endif; ?>
        <?php if ($licMill): ?><div class="badge"><i>🏭</i><div><b>Mill License</b><span><?= htmlspecialchars($licMill) ?></span></div></div><?php endif; ?>
        <?php if ($licMandi): ?><div class="badge"><i>📜</i><div><b>Mandi License</b><span><?= htmlspecialchars($licMandi) ?></span></div></div><?php endif; ?>
    </div>
</div>

<!-- ABOUT -->
<section class="block" id="about">
    <div class="wrap">
        <div class="about">
            <div class="about-copy rv">
                <div class="eyebrow">About the Mill</div>
                <h2 class="sec-title">Honest milling, dependable supply</h2>
                <p class="sec-sub"><?= htmlspecialchars($millName) ?> is a modern paddy processing unit in Shahabad, Hardoi (Uttar Pradesh), committed to delivering hygienically milled rice. We pair trusted farmer relationships with the latest milling, de-stoning, polishing and color-sorting technology.</p>
                <ul class="ticks">
                    <li><b>✓</b> Direct paddy procurement from trusted farmers</li>
                    <li><b>✓</b> Automated cleaning, de-stoning &amp; grading</li>
                    <li><b>✓</b> Moisture-controlled storage &amp; packaging</li>
                    <li><b>✓</b> GST &amp; FSSAI compliant, bulk-supply ready</li>
                </ul>
            </div>
            <div class="about-art rv">
                <video autoplay muted loop playsinline poster="<?= $heroPos ?>"><source src="<?= $heroVid ?>" type="video/mp4"></video>
                <div class="veil"></div>
                <div class="float-card"><b>100%</b><span>Quality checked grain</span></div>
            </div>
        </div>

        <div class="stats">
            <div class="stat rv"><b data-count="50">0</b><span>Tonnes / day capacity</span></div>
            <div class="stat rv"><b data-count="15">0</b><span>Rice varieties</span></div>
            <div class="stat rv"><b data-count="100">0</b><span>% Quality checked</span></div>
            <div class="stat rv"><b data-count="500">0</b><span>+ Happy buyers</span></div>
        </div>
    </div>
</section>

<!-- PRODUCTS -->
<section class="block alt" id="products">
    <div class="wrap">
        <div class="eyebrow">Products &amp; Services</div>
        <h2 class="sec-title">What we offer</h2>
        <p class="sec-sub">A complete range of milled rice and value-added services tailored to your volume and quality needs.</p>
        <div class="cards">
            <div class="card rv"><div class="ic">🌾</div><h4>Basmati Rice</h4><p>Long-grain aromatic basmati — raw, steam &amp; sella grades for premium markets.</p></div>
            <div class="card rv"><div class="ic">🍚</div><h4>Non-Basmati Rice</h4><p>Sona Masoori, IR-64, parboiled &amp; everyday table rice in bulk packaging.</p></div>
            <div class="card rv"><div class="ic">🥡</div><h4>Broken &amp; By-products</h4><p>Rice bran, broken rice &amp; husk supplied for industrial and feed use.</p></div>
            <div class="card rv"><div class="ic">⚙️</div><h4>Custom Milling</h4><p>Job-work milling, polishing &amp; sorting of your paddy on modern machinery.</p></div>
            <div class="card rv"><div class="ic">📦</div><h4>Private Packing</h4><p>Custom branding &amp; pack sizes from 5 kg retail to 50 kg wholesale bags.</p></div>
            <div class="card rv"><div class="ic">🚚</div><h4>Bulk Supply</h4><p>Reliable, on-time dispatch with transport support across the region.</p></div>
        </div>
    </div>
</section>

<!-- QUALITY -->
<section class="block" id="quality">
    <div class="wrap">
        <div class="eyebrow">Quality Process</div>
        <h2 class="sec-title">Five steps to perfect grain</h2>
        <p class="sec-sub">Every batch passes through a controlled, food-safe process for clean and uniform rice.</p>
        <div class="steps">
            <div class="step rv"><div class="n">1</div><h5>Procurement</h5><p>Quality paddy sourced &amp; moisture tested.</p></div>
            <div class="step rv"><div class="n">2</div><h5>Cleaning</h5><p>De-stoning &amp; impurity removal.</p></div>
            <div class="step rv"><div class="n">3</div><h5>Milling</h5><p>Precision hulling &amp; polishing.</p></div>
            <div class="step rv"><div class="n">4</div><h5>Sorting</h5><p>Color &amp; size grading for uniformity.</p></div>
            <div class="step rv"><div class="n">5</div><h5>Packing</h5><p>Hygienic, weight-checked packaging.</p></div>
        </div>
    </div>
</section>

<!-- CERTIFICATIONS (real, from DB) -->
<section class="block alt" id="certs">
    <div class="wrap">
        <div class="eyebrow">Trust &amp; Compliance</div>
        <h2 class="sec-title">Certified &amp; registered</h2>
        <p class="sec-sub">We operate with full statutory compliance — here are our verifiable credentials.</p>
        <div class="certs">
            <?php if ($gst): ?><div class="cert rv"><div class="ic">🧾</div><div><small>GST Registration</small><b><?= htmlspecialchars($gst) ?></b></div></div><?php endif; ?>
            <?php if ($fssai): ?><div class="cert rv"><div class="ic">✅</div><div><small>FSSAI License</small><b><?= htmlspecialchars($fssai) ?></b></div></div><?php endif; ?>
            <?php if ($licMill): ?><div class="cert rv"><div class="ic">🏭</div><div><small>Mill License</small><b><?= htmlspecialchars($licMill) ?></b></div></div><?php endif; ?>
            <?php if ($licMandi): ?><div class="cert rv"><div class="ic">📜</div><div><small>Mandi License</small><b><?= htmlspecialchars($licMandi) ?></b></div></div><?php endif; ?>
        </div>
    </div>
</section>

<?php if (!empty($apk_latest)): ?>
<!-- MOBILE APP / APK DOWNLOAD (APK Manager) -->
<section class="block alt" id="app">
    <div class="wrap">
        <div style="align-items:center;background:#fff;border:1px solid #e6ddc9;border-radius:16px;box-shadow:0 20px 50px rgba(60,45,20,.1);display:flex;flex-wrap:wrap;gap:28px;justify-content:space-between;padding:34px 36px">
            <div style="align-items:center;display:flex;gap:20px;min-width:260px">
                <div style="align-items:center;background:linear-gradient(135deg,#1f9d55,#12823f);border-radius:20px;color:#fff;display:flex;font-size:40px;height:84px;justify-content:center;width:84px"><span style="font-family:sans-serif">&#129302;</span></div>
                <div>
                    <div style="color:#8a6d2f;font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase">Mobile App</div>
                    <h3 style="margin:4px 0 4px;font-size:24px"><?= html_escape($apk_app_name) ?></h3>
                    <div style="color:#6b5d42;font-weight:700">Version <?= html_escape($apk_latest->version_name) ?> · <?= apk_human_size((int) $apk_latest->file_size) ?> · <?= $apk_latest->created_at ? date('d M Y', strtotime($apk_latest->created_at)) : '' ?></div>
                    <?php if (trim((string) $apk_latest->release_notes) !== ''): ?>
                        <div style="color:#7a6b4e;font-size:13px;font-weight:600;margin-top:8px;max-width:460px"><?= nl2br(html_escape($apk_latest->release_notes)) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:12px;min-width:220px">
                <?php if (!empty($apk_public)): ?>
                    <a href="<?= site_url('app_download/apk/' . (int) $apk_latest->id . '?src=website') ?>" style="align-items:center;background:#1f9d55;border-radius:12px;color:#fff;display:flex;font-weight:800;gap:10px;justify-content:center;padding:14px 22px;text-decoration:none"><span style="font-size:18px">&#11015;</span> Download APK</a>
                <?php else: ?>
                    <a href="<?= base_url('admin/app_update/portal') ?>" style="align-items:center;background:#1f9d55;border-radius:12px;color:#fff;display:flex;font-weight:800;gap:10px;justify-content:center;padding:14px 22px;text-decoration:none"><span style="font-size:18px">&#128274;</span> Employee Login to Download</a>
                <?php endif; ?>
                <a href="<?= html_escape($apk_play_url) ?>" target="_blank" rel="noopener" style="align-items:center;background:#111827;border-radius:12px;color:#fff;display:flex;font-weight:800;gap:10px;justify-content:center;padding:14px 22px;text-decoration:none"><span style="font-size:16px">&#9654;</span> Get it on Google Play</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CONTACT + INQUIRY -->
<section class="block" id="contact">
    <div class="wrap">
        <div class="eyebrow">Get in touch</div>
        <h2 class="sec-title">Contact &amp; Inquiry</h2>
        <p class="sec-sub">Tell us your requirement and our team will get back with the best rates.</p>
        <div class="contact">
            <div class="contact-box rv">
                <?php if ($address): ?><div class="contact-item"><div class="ic">📍</div><div><b>Address</b><span><?= htmlspecialchars($address) ?></span></div></div><?php endif; ?>
                <?php if ($email): ?><div class="contact-item"><div class="ic">✉️</div><div><b>Email</b><span><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></span></div></div><?php endif; ?>
                <?php if ($phone): ?><div class="contact-item"><div class="ic">📞</div><div><b>Phone</b><span><a href="tel:<?= htmlspecialchars($phone) ?>"><?= htmlspecialchars($phone) ?></a></span></div></div><?php endif; ?>
                <div class="contact-item"><div class="ic">🕒</div><div><b>Working Hours</b><span>Mon – Sat, 9:00 AM – 7:00 PM</span></div></div>
                <div class="map"><iframe loading="lazy" src="https://maps.google.com/maps?q=<?= urlencode($mapAddr) ?>&z=13&output=embed"></iframe></div>
            </div>

            <div class="form-card rv" id="inquiry">
                <h3>Send an Inquiry</h3>
                <p style="color:var(--muted);font-size:14px;margin-bottom:20px;">Fields marked <span style="color:#d33">*</span> are required.</p>

                <?php if ($success): ?>
                    <div class="flash ok"><span>✅</span><span><?= htmlspecialchars($success) ?></span></div>
                <?php elseif ($error): ?>
                    <div class="flash err"><span>⚠️</span><span><?= htmlspecialchars($error) ?></span></div>
                <?php endif; ?>

                <form method="post" action="<?= $base ?>ricemill/inquiry">
                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="field">
                            <label>Name <span class="req">*</span></label>
                            <input type="text" name="name" value="<?= rm_old('name') ?>" placeholder="Your full name" required>
                        </div>
                        <div class="field">
                            <label>Mobile No. <span class="req">*</span></label>
                            <input type="tel" name="mobile_no" value="<?= rm_old('mobile_no') ?>" placeholder="10-digit mobile" pattern="[0-9+ ]{7,20}" required>
                        </div>
                    </div>
                    <div class="field">
                        <label>Address</label>
                        <input type="text" name="address" value="<?= rm_old('address') ?>" placeholder="City / Area">
                    </div>
                    <div class="form-row" style="margin-bottom:16px;">
                        <div class="field">
                            <label>Product / Requirement <span class="req">*</span></label>
                            <select name="product" required>
                                <option value="">— Select —</option>
                                <?php foreach (array('Basmati Rice','Non-Basmati Rice','Parboiled Rice','Broken Rice / By-products','Custom Milling','Bulk Supply','Other') as $opt): ?>
                                    <option value="<?= $opt ?>" <?= service('request')->getPost('product') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Quantity</label>
                            <input type="text" name="quantity" value="<?= rm_old('quantity') ?>" placeholder="e.g. 10 tonnes / 50 bags">
                        </div>
                    </div>
                    <div class="field">
                        <label>Message / Remark</label>
                        <textarea name="message" placeholder="Tell us more about your requirement..."><?= rm_old('message') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-green" style="width:100%;justify-content:center;">Submit Inquiry →</button>
                </form>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="wrap">
        <div class="foot">
            <div>
                <h4><?= htmlspecialchars($millName) ?></h4>
                <p style="max-width:340px;">Premium milled rice and dependable bulk supply — backed by modern processing, honest quality and full statutory compliance.</p>
                <?php if ($gst): ?><p style="margin-top:12px;font-size:13px;opacity:.85;">GST: <?= htmlspecialchars($gst) ?></p><?php endif; ?>
            </div>
            <div>
                <h4>Quick Links</h4>
                <a href="#about">About</a>
                <a href="#products">Products</a>
                <a href="#quality">Quality Process</a>
                <a href="#certs">Certifications</a>
                <a href="#inquiry">Inquiry</a>
                <a href="<?= $base ?>privacy-policy">Privacy Policy</a>
            </div>
            <div>
                <h4>Reach Us</h4>
                <?php if ($address): ?><p style="margin-bottom:10px;"><?= htmlspecialchars($address) ?></p><?php endif; ?>
                <?php if ($email): ?><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a><?php endif; ?>
                <?php if ($phone): ?><a href="tel:<?= htmlspecialchars($phone) ?>"><?= htmlspecialchars($phone) ?></a><?php endif; ?>
                <a href="<?= $base ?>admin/auth/login">Admin Login</a>
            </div>
        </div>
        <div class="copy">© <?= date('Y') ?> <?= htmlspecialchars($millName) ?>. All rights reserved. &nbsp;•&nbsp; Shahabad, Hardoi, Uttar Pradesh.</div>
    </div>
</footer>

<script>
    (function(){
        var io = new IntersectionObserver(function(es){
            es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); } });
        }, {threshold:.14});
        document.querySelectorAll('.rv').forEach(function(el){ io.observe(el); });

        var counted = false;
        function runCounts(){
            if(counted) return; counted = true;
            document.querySelectorAll('[data-count]').forEach(function(el){
                var target = +el.getAttribute('data-count'), cur = 0, step = Math.max(1, Math.round(target/45));
                var t = setInterval(function(){ cur += step; if(cur >= target){ cur = target; clearInterval(t); } el.textContent = cur; }, 22);
            });
        }
        var statsEl = document.querySelector('.stats');
        if(statsEl){
            var io2 = new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ runCounts(); io2.disconnect(); } }); }, {threshold:.3});
            io2.observe(statsEl);
        }
    })();
</script>

<?php if ($success): ?>
<script>if (window.location.hash !== '#inquiry') { window.location.hash = '#inquiry'; }</script>
<?php endif; ?>
</body>
</html>
