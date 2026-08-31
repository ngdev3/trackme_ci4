<?php
helper('url');
/* ---------- random colour palette (changes every page load) ---------- */
$palettes = array(
    array('brand'=>'#5b8cff','brand2'=>'#8a6dff','brand3'=>'#ff8fc6','accent'=>'#ffb648','hero1'=>'#0b1030','hero2'=>'#120a26','orbit'=>'170,190,255'),
    array('brand'=>'#21d39b','brand2'=>'#12b3c4','brand3'=>'#7af0c0','accent'=>'#ffd166','hero1'=>'#04231f','hero2'=>'#07182a','orbit'=>'150,255,220'),
    array('brand'=>'#ff6a88','brand2'=>'#ff99ac','brand3'=>'#ffd36b','accent'=>'#ff8e53','hero1'=>'#2a0b22','hero2'=>'#160a26','orbit'=>'255,180,210'),
    array('brand'=>'#00c2ff','brand2'=>'#18e0c8','brand3'=>'#7afcff','accent'=>'#ffd166','hero1'=>'#04203a','hero2'=>'#07182e','orbit'=>'150,235,255'),
    array('brand'=>'#8a6dff','brand2'=>'#b06dff','brand3'=>'#ffb0e0','accent'=>'#ffd166','hero1'=>'#1a0b33','hero2'=>'#0c0a26','orbit'=>'200,180,255'),
    array('brand'=>'#ff5a5f','brand2'=>'#ff8a5b','brand3'=>'#ffd36b','accent'=>'#ffb648','hero1'=>'#2a0b16','hero2'=>'#1a0a18','orbit'=>'255,190,170'),
    array('brand'=>'#3ddc97','brand2'=>'#37b6ff','brand3'=>'#b8ff9e','accent'=>'#ffd166','hero1'=>'#06231d','hero2'=>'#071a2b','orbit'=>'170,255,210'),
);
$pal = $palettes[array_rand($palettes)];

/* ---------- random solar-system position (changes every page load) ---------- */
$positions = array(
    array('46%','58%'), array('32%','34%'), array('60%','40%'),
    array('38%','68%'), array('55%','62%'), array('34%','52%'),
    array('64%','30%'), array('44%','46%'),
);
$pos = $positions[array_rand($positions)];

$flash_error   = session()->getFlashdata('error');
$flash_success = session()->getFlashdata('success');
?>
<!DOCTYPE html>
<html lang="en">
<!-- BEGIN HEAD -->
<head>
<meta charset="utf-8"/>
<title><?= esc($title ?? 'Track (The Rest Accounting Key)') ?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<meta content="" name="description"/>
<meta content="" name="author"/>
<!-- BEGIN GLOBAL MANDATORY STYLES -->
<link href="<?=base_url()?>assets/css/bootstrap.css" rel="stylesheet">
<link href="<?=base_url()?>assets/css/chart.css" rel="stylesheet">
<link href="<?=base_url()?>assets/css/jqstooltip.css" rel="stylesheet">
<link href="<?=base_url()?>assets/css/layout.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --brand: <?php echo $pal['brand']; ?>;
        --brand-2: <?php echo $pal['brand2']; ?>;
        --brand-3: <?php echo $pal['brand3']; ?>;
        --accent: <?php echo $pal['accent']; ?>;
        --hero-1: <?php echo $pal['hero1']; ?>;
        --hero-2: <?php echo $pal['hero2']; ?>;
        --orbit-rgb: <?php echo $pal['orbit']; ?>;
        --solar-top: <?php echo $pos[0]; ?>;
        --solar-left: <?php echo $pos[1]; ?>;
        --ink: #0c1024;
        --muted: #8089a8;
        --line: #e4e7f2;
        --ease: cubic-bezier(.22, 1, .36, 1);
    }

    * { box-sizing: border-box; }
    html, body { min-height: 100%; margin: 0; }

    body.app {
        color: var(--ink);
        font-family: "Inter", "Segoe UI", Arial, sans-serif;
        background: #05060f;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
    }

    /* ===================== DEEP SPACE BACKDROP ===================== */
    .space {
        position: fixed; inset: 0; z-index: 0; overflow: hidden;
        background: radial-gradient(120% 90% at 80% -10%, var(--hero-1) 0%, #0a0c25 45%, #05060f 100%);
    }
    .space:before, .space:after {
        content: ""; position: absolute; inset: -50%;
        background-image:
            radial-gradient(1.4px 1.4px at 20% 30%, #fff, transparent),
            radial-gradient(1.2px 1.2px at 70% 60%, #cfe0ff, transparent),
            radial-gradient(1px 1px at 40% 80%, #fff, transparent),
            radial-gradient(1.6px 1.6px at 85% 20%, #fff, transparent),
            radial-gradient(1px 1px at 55% 45%, #bcd2ff, transparent),
            radial-gradient(1.2px 1.2px at 10% 70%, #fff, transparent),
            radial-gradient(1px 1px at 90% 75%, #fff, transparent);
        background-repeat: repeat; background-size: 420px 420px;
        animation: twinkle 5s ease-in-out infinite alternate;
    }
    .space:after { background-size: 620px 620px; opacity: .6; animation-duration: 8s; animation-delay: -2s; }
    @keyframes twinkle { 0% { opacity: .35; } 100% { opacity: .95; } }

    .shooting {
        position: absolute; top: 12%; left: 60%; width: 140px; height: 1.5px;
        background: linear-gradient(90deg, rgba(255,255,255,0), #fff);
        border-radius: 50%; filter: drop-shadow(0 0 6px #cfe0ff);
        transform: rotate(-32deg); opacity: 0; animation: shoot 7s ease-in infinite;
    }
    @keyframes shoot {
        0% { transform: translate(0,0) rotate(-32deg); opacity: 0; }
        4% { opacity: 1; }
        14% { transform: translate(-460px, 290px) rotate(-32deg); opacity: 0; }
        100% { opacity: 0; }
    }
    .shooting.s2 { top: 4%; left: 38%; width: 110px; animation-duration: 11s; animation-delay: 3.5s; }
    .shooting.s3 { top: 22%; left: 80%; width: 170px; animation-duration: 9s; animation-delay: 6s; }

    /* ---------- drifting nebula clouds (palette tinted) ---------- */
    .nebula { position: absolute; border-radius: 50%; filter: blur(90px); opacity: .42; mix-blend-mode: screen; animation: nebula-drift 26s var(--ease) infinite alternate; }
    .nebula.n1 { width: 620px; height: 620px; top: -140px; left: -120px; background: var(--brand); animation-delay: 0s; }
    .nebula.n2 { width: 560px; height: 560px; bottom: -160px; right: -120px; background: var(--brand-2); animation-delay: -7s; }
    .nebula.n3 { width: 460px; height: 460px; top: 36%; left: 44%; background: var(--accent); opacity: .26; animation-delay: -13s; }
    .nebula.n4 { width: 420px; height: 420px; bottom: 12%; left: 16%; background: var(--brand-3); opacity: .3; animation-delay: -19s; }
    @keyframes nebula-drift {
        0%   { transform: translate(0,0) scale(1); }
        50%  { transform: translate(50px,-36px) scale(1.18); }
        100% { transform: translate(-40px,46px) scale(.92); }
    }

    /* ---------- distant rotating galaxy spiral ---------- */
    .galaxy {
        position: absolute; top: -160px; right: -160px; width: 560px; height: 560px; border-radius: 50%;
        background:
            radial-gradient(circle at 50% 50%, rgba(255,255,255,.9) 0 1.4%, rgba(255,240,210,.5) 2% 4%, transparent 5%),
            conic-gradient(from 0deg, transparent 0 6%, rgba(var(--orbit-rgb), .26) 14%, transparent 26%, rgba(var(--orbit-rgb), .18) 40%, transparent 54%, rgba(var(--orbit-rgb), .24) 68%, transparent 82%, rgba(var(--orbit-rgb), .16) 94%, transparent 100%);
        filter: blur(1px); opacity: .5; mix-blend-mode: screen;
        transform: rotate(0) scaleX(.66) rotate(-28deg);
        animation: galaxy-rot 80s linear infinite;
    }
    @keyframes galaxy-rot { to { transform: rotate(360deg) scaleX(.66) rotate(-28deg); } }

    /* ---------- floating glowing dust ---------- */
    .dust { position: absolute; width: 4px; height: 4px; border-radius: 50%; background: #fff; box-shadow: 0 0 8px 2px rgba(255,255,255,.7); opacity: .65; animation: float-up linear infinite; }
    .dust.d1 { left: 14%; bottom: -10px; animation-duration: 16s; }
    .dust.d2 { left: 32%; bottom: -10px; width: 3px; height: 3px; animation-duration: 22s; animation-delay: -6s; background: var(--accent); box-shadow: 0 0 8px 2px var(--accent); }
    .dust.d3 { left: 58%; bottom: -10px; animation-duration: 19s; animation-delay: -3s; }
    .dust.d4 { left: 76%; bottom: -10px; width: 3px; height: 3px; animation-duration: 25s; animation-delay: -11s; background: var(--brand-3); box-shadow: 0 0 8px 2px var(--brand-3); }
    .dust.d5 { left: 90%; bottom: -10px; animation-duration: 21s; animation-delay: -8s; }
    @keyframes float-up {
        0%   { transform: translateY(0) translateX(0); opacity: 0; }
        10%  { opacity: .8; }
        90%  { opacity: .8; }
        100% { transform: translateY(-105vh) translateX(28px); opacity: 0; }
    }

    /* ===================== LOADER ===================== */
    #loader { transition: all .35s ease-in-out; opacity: 1; visibility: visible; position: fixed; inset: 0; background: #05060f; z-index: 90000; }
    #loader.fadeOut { opacity: 0; visibility: hidden; }
    .spinner {
        width: 48px; height: 48px; position: absolute; top: calc(50% - 24px); left: calc(50% - 24px);
        border: 3px solid rgba(255,255,255,.12); border-top-color: var(--accent);
        border-radius: 50%; box-shadow: 0 0 28px rgba(255,182,72,.4); animation: login-spin .8s linear infinite;
    }
    @keyframes login-spin { to { transform: rotate(360deg); } }

    /* ===================== LAYOUT ===================== */
    .login-page { position: relative; z-index: 2; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 30px 18px; }
    .login-shell {
        width: min(1120px, 100%); min-height: 660px;
        display: grid; grid-template-columns: 1.15fr .85fr; overflow: hidden; border-radius: 26px;
        background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.12);
        box-shadow: 0 40px 120px rgba(0,0,0,.6), inset 0 1px 0 rgba(255,255,255,.1);
        backdrop-filter: blur(22px); -webkit-backdrop-filter: blur(22px); animation: rise .9s var(--ease) both;
    }
    @keyframes rise { from { opacity: 0; transform: translateY(28px); } to { opacity: 1; transform: translateY(0); } }

    /* ===================== HERO / SPACE SCENE ===================== */
    .login-hero {
        position: relative; overflow: hidden; color: #fff; padding: 40px 44px;
        display: flex; flex-direction: column; justify-content: space-between;
        background:
            radial-gradient(140% 120% at 65% 60%, rgba(255,255,255,.04), rgba(8,9,28,.92) 60%),
            linear-gradient(150deg, var(--hero-1), var(--hero-2));
    }

    .hero-top { position: relative; z-index: 5; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
    .brand-row { display: flex; align-items: center; gap: 13px; }
    .brand-mark { width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; border-radius: 14px; background: rgba(255,255,255,.95); box-shadow: 0 12px 28px rgba(0,0,0,.35); }
    .brand-mark img { max-width: 38px; max-height: 38px; }
    .brand-row b { font-family: "Space Grotesk", sans-serif; font-size: 17px; font-weight: 700; letter-spacing: .3px; }
    .brand-row small { display: block; color: rgba(255,255,255,.62); font-size: 11.5px; }

    /* ---------- WEATHER CARD ---------- */
    .weather-card {
        min-width: 188px; padding: 13px 15px; border-radius: 15px;
        background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.16);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.12); backdrop-filter: blur(10px);
    }
    .w-top { display: flex; align-items: center; gap: 11px; }
    .w-emoji { font-size: 30px; line-height: 1; }
    .w-temp { font-family: "Space Grotesk", sans-serif; font-size: 26px; font-weight: 700; line-height: 1; }
    .w-cond { font-size: 11.5px; color: rgba(235,240,255,.7); margin-top: 3px; }
    .w-meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 11px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,.12); font-size: 11.5px; }
    .w-meta #wCity { font-weight: 600; color: rgba(255,255,255,.86); display: inline-flex; align-items: center; gap: 5px; }
    .w-hl { color: rgba(235,240,255,.6); }
    .w-hl b { color: var(--accent); font-weight: 700; }
    .w-date { margin-top: 9px; font-size: 11px; letter-spacing: .02em; color: rgba(235,240,255,.62); }
    .w-date #wTime { color: var(--accent); font-weight: 700; }

    .hero-text { position: relative; z-index: 5; margin-top: auto; }
    .hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; margin-bottom: 18px; font-size: 11.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #fff; border-radius: 999px; background: color-mix(in srgb, var(--accent) 16%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 45%, transparent); }
    .hero-badge i { width: 7px; height: 7px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 12px var(--accent); }
    .login-hero h1 { font-family: "Space Grotesk", sans-serif; margin: 0 0 14px; font-size: 40px; line-height: 1.06; font-weight: 700; letter-spacing: -.5px; max-width: 440px; }
    .login-hero h1 em { font-style: normal; background: linear-gradient(110deg, var(--accent), var(--brand-3) 55%, var(--brand-2)); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .login-hero p { margin: 0; max-width: 400px; color: rgba(226,231,255,.74); font-size: 15px; line-height: 1.7; }

    .hero-foot { position: relative; z-index: 5; margin-top: 28px; display: flex; gap: 26px; flex-wrap: wrap; }
    .hero-foot div b { display: block; font-family: "Space Grotesk", sans-serif; font-size: 20px; font-weight: 700; }
    .hero-foot div span { color: rgba(226,231,255,.55); font-size: 11.5px; letter-spacing: .04em; }

    /* ===================== SOLAR SYSTEM ===================== */
    .solar { position: absolute; z-index: 1; pointer-events: none; top: var(--solar-top); left: var(--solar-left); width: 0; height: 0; transform: translate(-50%, -50%); }
    .orbit { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 1px solid rgba(var(--orbit-rgb), .13); border-radius: 50%; animation: orbit-spin linear infinite; }
    @keyframes orbit-spin { to { transform: translate(-50%, -50%) rotate(360deg); } }
    .planet { position: absolute; top: 0; left: 50%; transform: translate(-50%, -50%); border-radius: 50%; animation: planet-spin linear infinite; }
    @keyframes planet-spin { to { transform: translate(-50%, -50%) rotate(-360deg); } }

    .sun {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 116px; height: 116px; border-radius: 50%;
        background: radial-gradient(circle at 50% 50%, #fff6d8 0%, #ffd166 28%, #ff9a3c 55%, #ff6a2c 78%, #e8451f 100%);
        box-shadow: 0 0 60px 12px rgba(255,150,60,.55), 0 0 140px 40px rgba(255,110,44,.35), inset -10px -10px 36px rgba(180,40,10,.4);
        animation: sun-pulse 5s ease-in-out infinite;
    }
    .sun:before { content: ""; position: absolute; inset: -22px; border-radius: 50%; background: radial-gradient(circle, rgba(255,190,90,.28), transparent 65%); animation: corona 6s ease-in-out infinite; }
    @keyframes sun-pulse {
        0%,100% { box-shadow: 0 0 60px 12px rgba(255,150,60,.55), 0 0 140px 40px rgba(255,110,44,.32), inset -10px -10px 36px rgba(180,40,10,.4); }
        50%     { box-shadow: 0 0 80px 18px rgba(255,170,70,.7), 0 0 180px 56px rgba(255,120,50,.45), inset -10px -10px 36px rgba(180,40,10,.4); }
    }
    @keyframes corona { 0%,100% { transform: scale(1); opacity: .85; } 50% { transform: scale(1.12); opacity: 1; } }

    .o-mercury { width: 168px; height: 168px; animation-duration: 8s; }
    .o-venus   { width: 244px; height: 244px; animation-duration: 14s; }
    .o-earth   { width: 330px; height: 330px; animation-duration: 20s; }
    .o-mars    { width: 420px; height: 420px; animation-duration: 28s; }
    .o-jupiter { width: 560px; height: 560px; animation-duration: 42s; }
    .o-saturn  { width: 720px; height: 720px; animation-duration: 64s; }
    .o-mercury .planet { animation-duration: 8s; }
    .o-venus   .planet { animation-duration: 14s; }
    .o-earth   .planet { animation-duration: 20s; }
    .o-mars    .planet { animation-duration: 28s; }
    .o-jupiter .planet { animation-duration: 42s; }
    .o-saturn  .planet { animation-duration: 64s; }

    .mercury { width: 11px; height: 11px; background: radial-gradient(circle at 34% 30%, #cdbca6, #8a7c6b 55%, #4f463c 100%); box-shadow: inset -2px -2px 4px rgba(0,0,0,.55); }
    .venus   { width: 17px; height: 17px; background: radial-gradient(circle at 34% 30%, #ffe9b0, #e7b25e 55%, #9c6a25 100%); box-shadow: inset -3px -3px 6px rgba(80,40,0,.5), 0 0 10px rgba(255,210,130,.35); }
    .earth   { width: 21px; height: 21px; background: radial-gradient(circle at 33% 28%, #bfe8ff 0%, #4aa3e6 30%, #1f6fc4 55%, #0b3f86 100%); box-shadow: inset -4px -4px 7px rgba(0,10,40,.6), 0 0 12px rgba(90,160,255,.45); }
    .earth-moon { position: absolute; top: 50%; left: 50%; width: 30px; height: 30px; transform: translate(-50%, -50%); animation: orbit-spin 4s linear infinite; }
    .earth-moon i { position: absolute; top: 0; left: 50%; transform: translate(-50%, -50%); width: 6px; height: 6px; border-radius: 50%; background: radial-gradient(circle at 35% 30%, #f3f3f3, #b9bcc4 60%, #6f7480); }
    .mars    { width: 15px; height: 15px; background: radial-gradient(circle at 33% 28%, #ff9d6e, #d2552e 55%, #7e2a16 100%); box-shadow: inset -3px -3px 6px rgba(40,5,0,.55), 0 0 10px rgba(255,110,70,.3); }
    .jupiter {
        width: 40px; height: 40px; overflow: hidden; position: absolute;
        background:
            radial-gradient(circle at 32% 26%, rgba(255,255,255,.35), transparent 40%),
            repeating-linear-gradient(90deg, #e9c79b 0 5px, #cf9d6a 5px 9px, #d8b486 9px 14px, #b67d4f 14px 18px);
        box-shadow: inset -6px -6px 12px rgba(60,30,0,.55), 0 0 16px rgba(220,170,110,.35);
    }
    .jupiter:after { content: ""; position: absolute; top: 58%; left: 30%; width: 11px; height: 8px; border-radius: 50%; background: radial-gradient(circle, #d8693f, #a23c1e); opacity: .85; }
    .saturn  { width: 34px; height: 34px; background: radial-gradient(circle at 33% 27%, #ffeec2, #e3c179 55%, #9c7732 100%); box-shadow: inset -5px -5px 10px rgba(60,40,0,.5); }
    .saturn-ring { position: absolute; top: 0; left: 50%; width: 70px; height: 70px; transform: translate(-50%, -50%); animation: planet-spin linear infinite; animation-duration: inherit; }
    .saturn-ring:before { content: ""; position: absolute; top: 50%; left: 50%; width: 70px; height: 24px; transform: translate(-50%, -50%) rotate(-22deg); border-radius: 50%; border: 5px solid rgba(226,193,121,.6); border-left-color: rgba(226,193,121,.25); border-right-color: rgba(226,193,121,.25); box-shadow: 0 0 8px rgba(226,193,121,.3); }

    /* ===================== FORM PANEL ===================== */
    .login-panel { display: flex; align-items: center; justify-content: center; padding: 50px 46px; background: rgba(255,255,255,.97); }
    .login-card { width: 100%; max-width: 372px; }
    .mobile-logo { display: none; width: 58px; margin: 0 auto 20px; }
    .login-card h2 { font-family: "Space Grotesk", sans-serif; margin: 0 0 8px; font-size: 29px; font-weight: 700; letter-spacing: -.3px; }
    .login-card .subtitle { margin: 0 0 26px; color: var(--muted); font-size: 14px; line-height: 1.55; }

    .form-group { margin-bottom: 17px; }
    .form-label { display: block; margin-bottom: 8px; font-size: 12.5px; font-weight: 700; color: #34406b; }
    .input-wrap { position: relative; }
    .input-icon { position: absolute; top: 50%; left: 15px; transform: translateY(-50%); width: 19px; height: 19px; color: #9aa3c4; pointer-events: none; transition: color .2s ease; }
    .login-card .form-control { width: 100%; height: 51px; padding: 12px 46px 12px 44px; border: 1.5px solid var(--line); border-radius: 13px; color: var(--ink); background: #f7f8fc; font-size: 14.5px; font-family: inherit; box-shadow: none; transition: border-color .2s ease, box-shadow .2s ease, background .2s ease; }
    .login-card .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand) 18%, transparent); outline: 0; }
    .input-wrap:focus-within .input-icon { color: var(--brand); }
    .login-card .form-control::placeholder { color: #aab2cc; }

    .toggle-pass { position: absolute; top: 50%; right: 8px; transform: translateY(-50%); width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border: 0; background: transparent; cursor: pointer; color: #9aa3c4; border-radius: 9px; transition: color .2s ease, background .2s ease; }
    .toggle-pass:hover { color: var(--brand); background: color-mix(in srgb, var(--brand) 10%, transparent); }
    .toggle-pass svg { width: 19px; height: 19px; }

    .form-meta { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin: 6px 0 24px; font-size: 13px; }
    .remember-option { display: inline-flex; align-items: center; gap: 9px; margin: 0; color: #4f597c; font-weight: 600; cursor: pointer; }
    .remember-option input { width: 17px; height: 17px; margin: 0; accent-color: var(--brand); }
    .forgot-link { color: var(--brand); font-weight: 700; text-decoration: none; }
    .forgot-link:hover, .forgot-link:focus { color: color-mix(in srgb, var(--brand) 75%, #000); text-decoration: none; }

    .login-button { position: relative; overflow: hidden; width: 100%; height: 53px; display: inline-flex; align-items: center; justify-content: center; gap: 10px; border: 0; border-radius: 13px; color: #fff; background: linear-gradient(120deg, var(--brand), var(--brand-2) 60%, var(--brand-3)); background-size: 180% 100%; font-size: 15px; font-weight: 700; font-family: inherit; cursor: pointer; box-shadow: 0 16px 34px color-mix(in srgb, var(--brand) 34%, transparent); transition: transform .18s var(--ease), box-shadow .18s var(--ease), background-position .5s var(--ease); }
    .login-button:hover, .login-button:focus { color: #fff; transform: translateY(-2px); background-position: 100% 0; box-shadow: 0 22px 44px color-mix(in srgb, var(--brand-2) 40%, transparent); }
    .login-button:active { transform: translateY(0); }
    .login-button svg { width: 18px; height: 18px; transition: transform .25s var(--ease); }
    .login-button:hover svg { transform: translateX(4px); }

    .help-block, label.error { display: block; margin-top: 7px; color: #e0413f !important; font-size: 12px; font-weight: 600; }
    .alert, #errorMsg { border-radius: 12px; border: 1px solid rgba(224,65,63,.25); background: rgba(224,65,63,.08); color: #c5322f; padding: 11px 14px; font-size: 13px; box-shadow: none; margin-bottom: 16px; }
    .alert-success { color: #1c7a4e; background: rgba(33,211,155,.12); border-color: rgba(33,211,155,.4); }
    .alert-warning { color: #9a6a16; background: rgba(244,185,94,.14); border-color: rgba(244,185,94,.4); }
    .alert .close { float: right; border: 0; background: transparent; color: inherit; opacity: .6; cursor: pointer; }
    .login-copyright { margin-top: 26px; text-align: center; color: #9aa3c4; font-size: 12px; }

    /* ===================== RESPONSIVE ===================== */
    @media (max-width: 940px) {
        .login-shell { grid-template-columns: 1fr; width: min(440px, 100%); min-height: auto; }
        .login-hero { min-height: 320px; padding: 28px; }
        .solar { top: 62%; left: 72%; }
        .hero-top { flex-direction: column; }
        .hero-foot { display: none; }
        .login-hero h1 { font-size: 31px; }
        .login-panel { padding: 38px 26px; }
        .mobile-logo { display: block; }
    }
    @media (max-width: 460px) {
        .login-page { padding: 14px; }
        .weather-card { min-width: 0; width: 100%; }
        .login-panel { padding: 30px 20px; }
        .login-card h2 { font-size: 25px; }
        .form-meta { flex-direction: column; align-items: flex-start; gap: 12px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .orbit, .planet, .earth-moon, .saturn-ring, .sun, .sun:before, .shooting, .space:before, .space:after { animation: none !important; }
    }
</style>
</head>
<!-- END HEAD -->
<!-- BEGIN BODY -->
<?php
if (($_SERVER['SERVER_NAME'] ?? '') == "localhost") {
    $fs_email = 'admin@yopmail.com';
    $fs_password = 'Sunrise@5853';
    $fs_remember = '';
} else {
    $fs_email = '';
    $fs_password = '';
    $fs_remember = '';
}
?>
<body class="app is-collapsed">
    <div id="loader">
        <div class="spinner"></div>
    </div>

    <div class="space" aria-hidden="true">
        <span class="nebula n1"></span>
        <span class="nebula n2"></span>
        <span class="nebula n3"></span>
        <span class="nebula n4"></span>
        <span class="galaxy"></span>
        <span class="dust d1"></span>
        <span class="dust d2"></span>
        <span class="dust d3"></span>
        <span class="dust d4"></span>
        <span class="dust d5"></span>
        <span class="shooting"></span>
        <span class="shooting s2"></span>
        <span class="shooting s3"></span>
    </div>

    <main class="login-page">
        <section class="login-shell" aria-label="Admin login">
            <!-- HERO : SOLAR SYSTEM -->
            <div class="login-hero">
                <div class="solar" aria-hidden="true">
                    <div class="sun"></div>
                    <div class="orbit o-mercury"><span class="planet mercury"></span></div>
                    <div class="orbit o-venus"><span class="planet venus"></span></div>
                    <div class="orbit o-earth"><span class="planet earth"><span class="earth-moon"><i></i></span></span></div>
                    <div class="orbit o-mars"><span class="planet mars"></span></div>
                    <div class="orbit o-jupiter"><span class="planet jupiter"></span></div>
                    <div class="orbit o-saturn"><span class="planet saturn"></span><span class="saturn-ring"></span></div>
                </div>

                <div class="hero-top">
                    <div class="brand-row">
                        <div class="brand-mark"><img src="<?=base_url()?>assets/images/logo.png" alt="C R Industries"></div>
                        <div>
                            <b>C R Industries</b>
                            <small>Operations Control Suite</small>
                        </div>
                    </div>

                    <!-- LIVE WEATHER / DATE -->
                    <div class="weather-card" id="weather">
                        <div class="w-top">
                            <span class="w-emoji" id="wEmoji">🛰️</span>
                            <div>
                                <div class="w-temp" id="wTemp">--&deg;</div>
                                <div class="w-cond" id="wCond">Fetching weather…</div>
                            </div>
                        </div>
                        <div class="w-meta">
                            <span id="wCity">📍 Locating…</span>
                            <span class="w-hl" id="wHL"></span>
                        </div>
                        <div class="w-date"><span id="wDate"></span> &middot; <span id="wTime"></span></div>
                    </div>
                </div>

                <div class="hero-text">
                    <span class="hero-badge"><i></i> Mission Control</span>
                    <h1>Your whole business, <em>in one orbit.</em></h1>
                    <p>Accounting, billing, purchase, stock and daily operations — all revolving around a single secure command center.</p>
                </div>

                <div class="hero-foot">
                    <div><b>24/7</b><span>Live console</span></div>
                    <div><b>360&deg;</b><span>Ops visibility</span></div>
                    <div><b>&infin;</b><span>Scale ready</span></div>
                </div>
            </div>

            <!-- FORM -->
            <div class="login-panel">
                <form id="login-form1" class="login-card" action="<?= base_url('admin/auth/login') ?>" method="post">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <img class="mobile-logo" src="<?=base_url()?>assets/images/logo.png" alt="C R Industries">
                    <h2>Welcome back 🚀</h2>
                    <p class="subtitle">Sign in to launch your admin dashboard.</p>

                    <?php if (!empty($flash_error)): ?>
                        <div class="alert" id="errorMsg"><?= esc($flash_error) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($flash_success)): ?>
                        <div class="alert alert-success"><?= esc($flash_success) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($timeout)): ?>
                        <div class="alert alert-warning">Your session expired. Please login again and we will take you back to your last page.</div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 6.5h16v11H4v-11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="m5 7.5 7 5.2 7-5.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input id="email" class="form-control" type="text" autocomplete="off" placeholder="Enter email address" name="email" value="<?php if(!$fs_remember){echo esc($fs_email);}?>"/>
                        </div>
                        <span class="help-block"></span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M6 10h12v9H6v-9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M12 14.2v1.9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                            <input id="password" class="form-control" type="password" autocomplete="off" placeholder="Enter password" name="password" value="<?php if(!$fs_remember){echo esc($fs_password);}?>"/>
                            <button type="button" class="toggle-pass" id="togglePass" aria-label="Show password">
                                <svg id="eyeOpen" viewBox="0 0 24 24" fill="none"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
                                <svg id="eyeOff" viewBox="0 0 24 24" fill="none" style="display:none"><path d="M4 4l16 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.6 5.9A9.6 9.6 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-2.5 3.2M6.3 7.8A17 17 0 0 0 2.5 12S6 18.5 12 18.5c1 0 2-.2 2.9-.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                        <span class="help-block"></span>
                    </div>

                    <div class="form-meta">
                        <label class="remember-option" for="inputCall1">
                            <input type="checkbox" id="inputCall1" name="remember" <?php if($fs_remember){echo "checked";}?>>
                            <span>Remember me</span>
                        </label>
                        <a href="<?=base_url()?>admin/auth/forgot" id="forget-password" class="forgot-link">Forgot password?</a>
                    </div>

                    <button class="login-button" type="submit">
                        <span>Sign in to Dashboard</span>
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="m13 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div class="login-copyright">Copyright &copy; <?php echo date("Y"); ?> C.R. Industries, All Rights Reserved</div>
                </form>
            </div>
        </section>
    </main>

<!-- END LOGIN -->

<script type="text/javascript" src="<?= base_url()?>assets/js/vendor.js"></script>
<script type="text/javascript" src="<?= base_url()?>assets/js/bundle.js"></script>
<script src="<?= base_url()?>assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="<?= base_url()?>assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<!-- END CORE PLUGINS -->
<!-- BEGIN PAGE LEVEL PLUGINS -->
<script src="<?= base_url()?>assets/global/plugins/jquery-validation/js/jquery.validate.min.js" type="text/javascript"></script>
<script>
try {
    window.localStorage.removeItem('trackme_web_panel_locked');
} catch (e) {}

jQuery(document).ready(function() {
    setTimeout(function(){ $("#errorMsg").hide();}, 4000);
});

/* ---------- password show / hide ---------- */
(function(){
    var btn = document.getElementById('togglePass');
    if(!btn) return;
    btn.addEventListener('click', function(){
        var pw = document.getElementById('password');
        var open = document.getElementById('eyeOpen');
        var off = document.getElementById('eyeOff');
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        open.style.display = show ? 'none' : 'block';
        off.style.display  = show ? 'block' : 'none';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
})();

/* ---------- live date & time ---------- */
(function(){
    var days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    function pad(n){ return n < 10 ? '0'+n : n; }
    function tick(){
        var d = new Date();
        var h = d.getHours(), ap = h >= 12 ? 'PM' : 'AM', h12 = h % 12 || 12;
        var de = document.getElementById('wDate'), te = document.getElementById('wTime');
        if(de) de.textContent = days[d.getDay()]+', '+d.getDate()+' '+months[d.getMonth()]+' '+d.getFullYear();
        if(te) te.textContent = h12+':'+pad(d.getMinutes())+' '+ap;
    }
    tick(); setInterval(tick, 30000);
})();

/* ---------- live weather (free, no API key) ---------- */
(function(){
    var WMO = {
        0:['Clear sky','☀️'], 1:['Mainly clear','🌤️'], 2:['Partly cloudy','⛅'], 3:['Overcast','☁️'],
        45:['Fog','🌫️'], 48:['Rime fog','🌫️'],
        51:['Light drizzle','🌦️'], 53:['Drizzle','🌦️'], 55:['Heavy drizzle','🌧️'],
        56:['Freezing drizzle','🌧️'], 57:['Freezing drizzle','🌧️'],
        61:['Light rain','🌦️'], 63:['Rain','🌧️'], 65:['Heavy rain','🌧️'],
        66:['Freezing rain','🌧️'], 67:['Freezing rain','🌧️'],
        71:['Light snow','🌨️'], 73:['Snow','🌨️'], 75:['Heavy snow','❄️'], 77:['Snow grains','🌨️'],
        80:['Rain showers','🌦️'], 81:['Rain showers','🌧️'], 82:['Heavy showers','⛈️'],
        85:['Snow showers','🌨️'], 86:['Snow showers','❄️'],
        95:['Thunderstorm','⛈️'], 96:['Thunderstorm','⛈️'], 99:['Thunderstorm','⛈️']
    };
    function set(id, txt){ var el = document.getElementById(id); if(el) el.innerHTML = txt; }
    function render(temp, code, city, hi, lo){
        var info = WMO[code] || ['Weather','🌍'];
        set('wEmoji', info[1]);
        set('wTemp', Math.round(temp) + '&deg;C');
        set('wCond', info[0]);
        set('wCity', '📍 ' + (city || 'Your location'));
        if(hi != null && lo != null) set('wHL', 'H:<b>'+Math.round(hi)+'&deg;</b> L:<b>'+Math.round(lo)+'&deg;</b>');
    }
    function getWeather(lat, lon, city){
        fetch('https://api.open-meteo.com/v1/forecast?latitude='+lat+'&longitude='+lon+'&current=temperature_2m,weather_code&daily=temperature_2m_max,temperature_2m_min&timezone=auto')
            .then(function(r){ return r.json(); })
            .then(function(d){
                render(d.current.temperature_2m, d.current.weather_code, city,
                       d.daily.temperature_2m_max[0], d.daily.temperature_2m_min[0]);
            })
            .catch(function(){ set('wCond', 'Weather unavailable'); set('wEmoji','🌐'); });
    }
    // locate by IP first (no permission prompt); fall back to a default city
    fetch('https://get.geojs.io/v1/ip/geo.json')
        .then(function(r){ return r.json(); })
        .then(function(loc){
            var lat = parseFloat(loc.latitude), lon = parseFloat(loc.longitude);
            var city = loc.city || loc.region || loc.country || '';
            if(isNaN(lat) || isNaN(lon)) throw new Error('no geo');
            getWeather(lat, lon, city);
        })
        .catch(function(){ getWeather(28.6139, 77.2090, 'New Delhi'); });
})();

$("#login-form1").validate({
    rules: {
        email:{
            required:true,
            email: true
        },
        password: {
            required: true
        },
    },
    messages: {
        email: {
            required:'<span class="help-block" style="color:red">Email is required!</span>',
            email:'<span class="help-block" style="color:red">Invalid email format!</span>'
        },
        password: {
            required:'<span class="help-block" style="color:red">Password is required!</span>'
        },
    }
});
</script>
<script type="text/javascript">
    window.addEventListener('load', () => {
        const loader = document.getElementById('loader');
        setTimeout(() => {
            loader.classList.add('fadeOut');
        }, 300);
    });
</script>
<!-- END JAVASCRIPTS -->
<?php include __DIR__ . '/_permission_gate.php'; ?>
</body>
<!-- END BODY -->
</html>
