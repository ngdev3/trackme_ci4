<?php
/**
 * Shared cosmic auth chrome — top half.
 * Renders <head>, animated space backdrop, and the hero panel, then
 * opens the right-hand form panel. The including view supplies the
 * form, then includes partials/auth_bottom.
 *
 * Params (all optional):
 *   $pageTitle  string  browser <title>
 *   $heroTitle  string  hero headline (raw HTML — wrap accents in <em>)
 *   $heroLede   string  hero paragraph
 *   $heroBadge  string  small uppercase pill label
 */
$pageTitle = $pageTitle ?? 'Sign In';
$heroBadge = $heroBadge ?? 'Mission Control';
$heroTitle = $heroTitle ?? 'Your whole business, <em>in one orbit.</em>';
$heroLede  = $heroLede  ?? 'Users, roles, permissions and daily operations — all revolving around a single secure command center.';

/* ---------- random colour palette (changes every page load) ---------- */
$palettes = [
    ['brand' => '#5b8cff', 'brand2' => '#8a6dff', 'brand3' => '#ff8fc6', 'accent' => '#ffb648', 'hero1' => '#0b1030', 'hero2' => '#120a26', 'orbit' => '170,190,255'],
    ['brand' => '#21d39b', 'brand2' => '#12b3c4', 'brand3' => '#7af0c0', 'accent' => '#ffd166', 'hero1' => '#04231f', 'hero2' => '#07182a', 'orbit' => '150,255,220'],
    ['brand' => '#ff6a88', 'brand2' => '#ff99ac', 'brand3' => '#ffd36b', 'accent' => '#ff8e53', 'hero1' => '#2a0b22', 'hero2' => '#160a26', 'orbit' => '255,180,210'],
    ['brand' => '#00c2ff', 'brand2' => '#18e0c8', 'brand3' => '#7afcff', 'accent' => '#ffd166', 'hero1' => '#04203a', 'hero2' => '#07182e', 'orbit' => '150,235,255'],
    ['brand' => '#8a6dff', 'brand2' => '#b06dff', 'brand3' => '#ffb0e0', 'accent' => '#ffd166', 'hero1' => '#1a0b33', 'hero2' => '#0c0a26', 'orbit' => '200,180,255'],
    ['brand' => '#ff5a5f', 'brand2' => '#ff8a5b', 'brand3' => '#ffd36b', 'accent' => '#ffb648', 'hero1' => '#2a0b16', 'hero2' => '#1a0a18', 'orbit' => '255,190,170'],
    ['brand' => '#3ddc97', 'brand2' => '#37b6ff', 'brand3' => '#b8ff9e', 'accent' => '#ffd166', 'hero1' => '#06231d', 'hero2' => '#071a2b', 'orbit' => '170,255,210'],
];
$pal = $palettes[array_rand($palettes)];

$positions = [
    ['46%', '58%'], ['32%', '34%'], ['60%', '40%'], ['38%', '68%'],
    ['55%', '62%'], ['34%', '52%'], ['64%', '30%'], ['44%', '46%'],
];
$pos = $positions[array_rand($positions)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle) ?> &middot; ERP Admin</title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
    <style>
        :root {
            --auth-brand:   <?= $pal['brand'] ?>;
            --auth-brand-2: <?= $pal['brand2'] ?>;
            --auth-brand-3: <?= $pal['brand3'] ?>;
            --auth-accent:  <?= $pal['accent'] ?>;
            --auth-hero-1:  <?= $pal['hero1'] ?>;
            --auth-hero-2:  <?= $pal['hero2'] ?>;
            --auth-orbit:   <?= $pal['orbit'] ?>;
            --auth-solar-top:  <?= $pos[0] ?>;
            --auth-solar-left: <?= $pos[1] ?>;
        }
    </style>
</head>
<body class="auth-body">
    <div id="authLoader"><div class="auth-spinner"></div></div>

    <div class="space" aria-hidden="true">
        <span class="nebula n1"></span><span class="nebula n2"></span>
        <span class="nebula n3"></span><span class="nebula n4"></span>
        <span class="galaxy"></span>
        <span class="dust d1"></span><span class="dust d2"></span><span class="dust d3"></span>
        <span class="dust d4"></span><span class="dust d5"></span>
        <span class="shooting"></span><span class="shooting s2"></span><span class="shooting s3"></span>
    </div>

    <main class="login-page">
        <section class="login-shell" aria-label="ERP admin authentication">
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
                        <div class="hero-brand-mark"><i class="bi bi-boxes"></i></div>
                        <div>
                            <b>ERP Admin</b>
                            <small>Operations Control Suite</small>
                        </div>
                    </div>

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
                    <span class="hero-badge"><i></i> <?= esc($heroBadge) ?></span>
                    <h1><?= $heroTitle ?></h1>
                    <p><?= esc($heroLede) ?></p>
                </div>

                <div class="hero-foot">
                    <div><b>24/7</b><span>Live console</span></div>
                    <div><b>360&deg;</b><span>Ops visibility</span></div>
                    <div><b>&infin;</b><span>Scale ready</span></div>
                </div>
            </div>

            <!-- FORM PANEL -->
            <div class="login-panel">
                <div class="login-card">
</content>
