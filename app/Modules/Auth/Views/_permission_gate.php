<?php
/**
 * Pre-authentication permission gate.
 *
 * Included at the bottom of the Login and Forgot Password views. It renders a
 * FULL-SCREEN overlay (visible by default = fail-closed) that blocks the page
 * until the user has granted every required permission. The overlay is only
 * removed once all checks pass, and it re-validates on every page load / refresh.
 *
 * Add a new mandatory permission by appending one object to REQUIRED below —
 * nothing else needs to change (modular by design).
 *
 * NOTE: browser geolocation needs a secure context (HTTPS or http://localhost).
 * A real hardware MAC is not exposed to web pages, so the "Device Identifier"
 * uses the closest web-platform equivalent — a persistent device UUID.
 */
?>
<style>
    #cr-perm-gate { position: fixed; inset: 0; z-index: 2147483000; display: flex; align-items: center; justify-content: center; overflow: auto;
        padding: 22px; font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        background: radial-gradient(circle at 18% 12%, #e7f0ff, transparent 46%), radial-gradient(circle at 88% 92%, #fdeef5, transparent 46%), #eef2f9; color: #1f2a44; }
    #cr-perm-gate * { box-sizing: border-box; }
    /* The host login theme forces -webkit-text-fill-color: transparent on some
       text (gradient headings), which made our bold words invisible. Force every
       element inside the gate to paint text with its own colour. */
    #cr-perm-gate, #cr-perm-gate * { -webkit-text-fill-color: currentColor !important; -webkit-background-clip: border-box !important; background-clip: border-box !important; }
    #cr-perm-gate b, #cr-perm-gate strong { color: inherit !important; font-weight: 700; }
    /* Landscape: two columns — info on the left, how-to guide on the right. */
    .crpg-card { width: 100%; max-width: 900px; background: #ffffff; border: 1px solid #e4e9f2; border-radius: 20px; padding: 26px; box-shadow: 0 30px 70px rgba(30,45,90,.16);
        display: grid; grid-template-columns: 1.05fr 1fr; gap: 26px; align-items: start; }
    .crpg-left, .crpg-right { min-width: 0; }
    .crpg-right { border-left: 1px solid #eceff5; padding-left: 26px; }
    .crpg-right-h { font-size: 13px; font-weight: 800; color: #c98a1e; text-align: center; margin-bottom: 13px; }
    @media (max-width: 760px) { .crpg-card { grid-template-columns: 1fr; gap: 18px; } .crpg-right { border-left: 0; border-top: 1px solid #eceff5; padding-left: 0; padding-top: 18px; } }
    .crpg-ic { width: 66px; height: 66px; border-radius: 18px; margin: 0 auto 14px; display: grid; place-items: center; font-size: 30px;
        background: linear-gradient(135deg, #ffb648, #ff6a88); box-shadow: 0 12px 28px rgba(255,120,120,.32); }
    .crpg-title { text-align: center; font-size: 21px; font-weight: 800; color: #1a2740; margin: 0 0 6px; }
    .crpg-sub { text-align: center; font-size: 13px; line-height: 1.55; color: #66748c; margin: 0 0 18px; }
    .crpg-list { list-style: none; margin: 0 0 18px; padding: 0; }
    .crpg-item { display: flex; align-items: center; gap: 12px; padding: 11px 13px; border: 1px solid #e6ecf5; border-radius: 12px; margin-bottom: 9px; background: #f8fafc; }
    .crpg-item-ic { font-size: 19px; width: 26px; text-align: center; flex: none; }
    .crpg-item-t { flex: 1; }
    .crpg-item-t b { display: block; font-size: 14px; font-weight: 700; color: #1f2a44; }
    .crpg-item-t span { display: block; font-size: 11.5px; color: #7a8aa0; margin-top: 1px; }
    .crpg-status { font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
    .crpg-status.ok { background: #e7f7ee; color: #127a34; }
    .crpg-status.no { background: #fdecec; color: #c0392b; }
    .crpg-status.wait { background: #fef3e0; color: #b26a00; }
    .crpg-btn { display: block; width: 100%; border: 0; border-radius: 12px; padding: 14px; font-size: 15px; font-weight: 800; cursor: pointer;
        color: #4a1500; background: linear-gradient(135deg, #ffb648, #ff6a88); box-shadow: 0 12px 26px rgba(255,120,120,.3); }
    .crpg-btn:hover { filter: brightness(1.04); }
    .crpg-btn:disabled { opacity: .6; cursor: default; }
    .crpg-note { text-align: center; font-size: 11px; color: #8a97ab; margin-top: 12px; line-height: 1.5; }
    .crpg-err { text-align: center; font-size: 12px; color: #c0392b; font-weight: 600; margin-top: 10px; min-height: 16px; }

    /* ---- animated "how to enable" guide (bright theme) ---- */
    .crpg-helplink { text-align: center; margin-top: 12px; }
    .crpg-helplink a { color: #c98a1e; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: underline; }
    .crpg-help { display: none; margin-top: 14px; border-top: 1px solid #eceff5; padding-top: 14px; }
    .crpg-help.show { display: block; }
    .crpg-demo { position: relative; height: 234px; background: #f2f5fb; border: 1px solid #e0e6f0; border-radius: 12px; padding: 12px; margin-bottom: 13px; overflow: hidden; }
    .crpg-bar { display: flex; align-items: center; gap: 8px; background: #ffffff; border: 1px solid #dde5f0; border-radius: 20px; padding: 7px 12px; font-size: 12px; color: #334155; box-shadow: 0 2px 6px rgba(30,45,90,.05); }
    .crpg-lock { display: inline-grid; place-items: center; width: 24px; height: 24px; border-radius: 7px; background: rgba(20,40,90,.05); color: #1d4ed8; animation: crlock 5s infinite; }
    .crpg-url { opacity: .9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    /* pointing arrow that draws the eye to the info/lock icon (stays up, keeps bouncing) */
    .crpg-arrow { position: absolute; left: 17px; top: 41px; font-size: 26px; line-height: 1; color: #f08a12; text-shadow: 0 2px 5px rgba(30,45,90,.25); opacity: 0; animation: crarrow 5s infinite; }
    /* Chrome-style site-permissions panel */
    .crpg-panel { position: absolute; left: 12px; top: 60px; width: 236px; background: #ffffff; border: 1px solid #dde5f0; border-radius: 12px; padding: 9px 12px; box-shadow: 0 18px 40px rgba(30,45,90,.22); opacity: 0; transform: translateY(-8px) scale(.97); transform-origin: top left; animation: crpop 5s infinite; }
    .crpg-caret { position: absolute; top: -6px; left: 17px; width: 12px; height: 12px; background: #ffffff; border-left: 1px solid #dde5f0; border-top: 1px solid #dde5f0; transform: rotate(45deg); }
    .crpg-phead { font-size: 10.5px; font-weight: 700; color: #127a34; display: flex; align-items: center; gap: 5px; padding-bottom: 7px; margin-bottom: 5px; border-bottom: 1px solid #eef2f7; }
    .crpg-prow { display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: #334155; padding: 5px 0; }
    .crpg-prow.muted { color: #8a97ab; font-size: 11.5px; }
    .crpg-sel { position: relative; width: 84px; height: 26px; flex: none; }
    .crpg-sel span { position: absolute; inset: 0; display: grid; place-items: center; border-radius: 7px; font-weight: 800; font-size: 12px; }
    .crpg-sel .allow { color: #127a34; background: #e7f7ee; }
    .crpg-sel .block { color: #c0392b; background: #fdecec; animation: crswap 5s infinite; }
    .crpg-cur { position: absolute; font-size: 16px; filter: drop-shadow(0 2px 3px rgba(30,45,90,.3)); animation: crcur 5s infinite; pointer-events: none; }
    @keyframes crcur { 0% { left: 78%; top: 60%; transform: scale(1);} 26% { left: 8%; top: 16%; transform: scale(1);} 31% { transform: scale(.72);} 36% { transform: scale(1);} 58% { left: 8%; top: 16%; } 74% { left: 64%; top: 50%; } 100% { left: 64%; top: 50%; } }
    @keyframes crlock { 0%,23% { box-shadow: none;} 31% { box-shadow: 0 0 0 3px rgba(29,78,216,.35);} 46%,100% { box-shadow: none; } }
    /* Arrow appears as the cursor reaches the lock and keeps bouncing to the end. */
    @keyframes crarrow { 0%,22% { opacity: 0; transform: translateY(7px);} 28% { opacity: 1; transform: translateY(0);} 40% { transform: translateY(6px);} 52% { transform: translateY(0);} 64% { transform: translateY(6px);} 76% { transform: translateY(0);} 88% { transform: translateY(6px);} 100% { opacity: 1; transform: translateY(0);} }
    /* Permission panel pops open and stays open for the rest of the loop. */
    @keyframes crpop { 0%,34% { opacity: 0; transform: translateY(-8px) scale(.96);} 44% { opacity: 1; transform: translateY(0) scale(1.02);} 48%,100% { opacity: 1; transform: translateY(0) scale(1); } }
    @keyframes crswap { 0%,56% { opacity: 1;} 66%,100% { opacity: 0; } }
    .crpg-steps { margin: 0 0 12px; padding-left: 20px; }
    .crpg-steps li { font-size: 12.5px; color: #4a5670; line-height: 1.6; margin-bottom: 4px; }
    .crpg-steps b { color: #1a2740; }
    .crpg-reload { display: block; width: 100%; border: 1px solid #dbe4f0; background: #f1f5fb; color: #334155; border-radius: 10px; padding: 11px; font-size: 13px; font-weight: 800; cursor: pointer; }
    .crpg-reload:hover { background: #e6edf6; }

    /* "Open Site Settings" button (appears when a permission stays blocked) */
    .crpg-settings { display: block; width: 100%; margin-top: 10px; border: 1px solid #cfd9ea; background: #ffffff; color: #1a2740; border-radius: 12px; padding: 12px; font-size: 14px; font-weight: 800; cursor: pointer; }
    .crpg-settings:hover { background: #f3f7fc; }
    /* Per-browser quick tips */
    .crpg-browsers { margin: 2px 0 12px; }
    .crpg-br { font-size: 11.5px; color: #4a5670; line-height: 1.5; padding: 6px 0; border-top: 1px dashed #e6ecf5; }
    .crpg-br b { color: #1a2740; }
    .crpg-br i { color: #1d4ed8; font-style: normal; font-weight: 600; }
    .crpg-br.here { background: #eaf3ff; border: 1px solid #cfe0f5; border-radius: 9px; padding: 8px 11px; margin-top: 6px; }
    .crpg-br.here::before { content: "✓ Your browser — "; color: #0a7d33; font-weight: 800; }

    /* Fixed pointer aimed UP at the browser's site-info (ⓘ / 🔒) icon, which sits
       at the start of the address bar (~190px from the left on desktop Chrome). */
    .crpg-corner { position: fixed; top: 2px; left: 176px; z-index: 2147483001; display: flex; align-items: flex-start; gap: 10px; max-width: 380px; pointer-events: none; }
    .crpg-corner-arrow { font-size: 34px; line-height: 1; color: #f0640b; text-shadow: 0 3px 8px rgba(0,0,0,.35); animation: crcorner 1.2s ease-in-out infinite; }
    .crpg-corner-txt { margin-top: 12px; background: #1a2740; color: #fff; font-size: 12px; font-weight: 700; padding: 8px 12px; border-radius: 10px; box-shadow: 0 12px 28px rgba(30,45,90,.35); line-height: 1.45; }
    @keyframes crcorner { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    @media (max-width: 620px) { .crpg-corner { left: 12px; } }

    /* "Allow, then come back & reload" popup */
    .crpg-modal { position: fixed; inset: 0; z-index: 2147483002; display: none; align-items: center; justify-content: center; padding: 20px; background: rgba(16,26,48,.55); }
    .crpg-modal.show { display: flex; }
    .crpg-modal-box { width: 100%; max-width: 420px; background: #fff; border-radius: 18px; padding: 24px; text-align: center; box-shadow: 0 30px 70px rgba(16,26,48,.4); }
    .crpg-modal-ic { width: 56px; height: 56px; border-radius: 16px; margin: 0 auto 12px; display: grid; place-items: center; font-size: 26px; background: linear-gradient(135deg, #ffb648, #ff6a88); }
    .crpg-modal-h { font-size: 18px; font-weight: 800; color: #1a2740; margin: 0 0 8px; }
    .crpg-modal-p { font-size: 13px; color: #4a5670; line-height: 1.6; margin: 0 0 10px; }
    .crpg-modal-p b { color: #1a2740; }
    .crpg-modal-sub { font-size: 11.5px; color: #66748c; line-height: 1.55; margin: 0 0 16px; }
    .crpg-modal-url { display: block; margin-top: 4px; font-family: ui-monospace, Menlo, monospace; font-size: 11px; color: #1d4ed8; word-break: break-all; background: #f2f6fd; border: 1px solid #dbe6f5; border-radius: 8px; padding: 6px 8px; }
    .crpg-modal-x { display: block; width: 100%; margin-top: 8px; border: 0; background: none; color: #8a97ab; font-size: 12.5px; font-weight: 700; cursor: pointer; }

    /* Keep the page locked/hidden behind the gate until it is dismissed. */
    html.cr-perm-locked, body.cr-perm-locked { overflow: hidden !important; }
</style>

<div id="cr-perm-gate" role="dialog" aria-modal="true" aria-label="Permissions required">
    <div class="crpg-corner" aria-hidden="true">
        <span class="crpg-corner-arrow">↑</span>
        <span class="crpg-corner-txt">Click the <b>ⓘ</b> / <b>🔒</b> icon up here in the address bar, then set the permissions to <b>Allow</b>.</span>
    </div>
    <div class="crpg-card">
        <div class="crpg-left">
            <div class="crpg-ic">🔒</div>
            <h2 class="crpg-title">Permissions Required</h2>
            <p class="crpg-sub">For security reasons, this application requires access to the items below. The Login and Forgot Password pages remain inaccessible until <b>all</b> permissions are approved.</p>
            <ul class="crpg-list" id="crpg-list"></ul>
            <button type="button" class="crpg-btn" id="crpg-grant">Grant Permissions</button>
            <button type="button" class="crpg-settings" id="crpg-settings" style="display:none;">⚙️ Open <span id="crpg-bname">Browser</span> Site Settings</button>
            <div class="crpg-err" id="crpg-err"></div>
            <p class="crpg-note">Location and Notifications need a secure connection (HTTPS or localhost). All three permissions must be set to <b>Allow</b> to continue.</p>
        </div>

        <div class="crpg-right" id="crpg-help">
            <div class="crpg-right-h">📍 How to enable these permissions</div>
            <div class="crpg-demo" aria-hidden="true">
                <div class="crpg-bar">
                    <span class="crpg-lock">ⓘ</span>
                    <span class="crpg-url">localhost:8077/trackme_ci4/public/admin/auth/login</span>
                </div>
                <span class="crpg-arrow">⬆</span>
                <div class="crpg-panel">
                    <span class="crpg-caret"></span>
                    <div class="crpg-phead">ⓘ Site information</div>
                    <div class="crpg-prow"><span>📍 Location</span><span class="crpg-sel"><span class="allow">Allow ▾</span><span class="block">Block ▾</span></span></div>
                    <div class="crpg-prow muted"><span>🔔 Notifications</span><span>Allow ▾</span></div>
                    <div class="crpg-prow muted"><span>🪟 Pop-ups</span><span>Allow ▾</span></div>
                </div>
                <span class="crpg-cur">👆</span>
            </div>
            <ol class="crpg-steps">
                <li>Click the <b>ⓘ info</b> (or <b>🔒 lock</b>) icon at the left of the address bar.</li>
                <li>Set <b>Location</b>, <b>Notifications</b> and <b>Pop-ups</b> to <b>Allow</b>.</li>
                <li><b>Reload</b> the page — you'll be let straight in.</li>
            </ol>
            <div class="crpg-browsers">
                <div class="crpg-br" data-b="chrome"><b>Chrome / Edge:</b> click the <i>ⓘ / 🔒</i> icon → <i>Site settings</i> → set Location, Notifications &amp; Pop-ups to <i>Allow</i>.</div>
                <div class="crpg-br" data-b="firefox"><b>Firefox:</b> click the <i>permissions</i> icon in the address bar → clear the <i>Blocked</i> state, or <i>Settings → Privacy &amp; Security → Permissions</i>.</div>
                <div class="crpg-br" data-b="safari"><b>Safari:</b> <i>Safari → Settings → Websites</i> → Location / Notifications → <i>Allow</i>.</div>
            </div>
            <button type="button" class="crpg-reload" id="crpg-reload">↻ Reload page</button>
        </div>
    </div>

    <!-- Popup shown after we send the user to the browser's Site Settings -->
    <div class="crpg-modal" id="crpg-modal" aria-hidden="true">
        <div class="crpg-modal-box">
            <div class="crpg-modal-ic">⚙️</div>
            <h3 class="crpg-modal-h">Site Settings opened in a new tab</h3>
            <p class="crpg-modal-p">In the new tab, set <b>Location</b>, <b>Notifications</b> and <b>Pop-ups</b> to <b>Allow</b> for this site.<br>Then <b>come back to this tab</b> and reload.</p>
            <p class="crpg-modal-sub" id="crpg-modal-sub"></p>
            <button type="button" class="crpg-btn" id="crpg-modal-reload">✓ I've allowed — Reload now</button>
            <button type="button" class="crpg-modal-x" id="crpg-modal-close">Close</button>
        </div>
    </div>
</div>

<script>
(function () {
    "use strict";

    function uuid() {
        if (window.crypto && crypto.randomUUID) { try { return crypto.randomUUID(); } catch (e) {} }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0, v = c === 'x' ? r : ((r & 0x3) | 0x8); return v.toString(16);
        });
    }
    function setCookie(k, v) {
        var sec = (location.protocol === 'https:') ? '; Secure' : '';
        document.cookie = k + '=' + encodeURIComponent(v) + '; Path=/; SameSite=Lax' + sec;
    }

    /* ---- MODULAR list of required permissions ----
       check():  Promise<bool> — is it already granted? (must NOT prompt)
       request(): Promise<bool> — actively request it (may prompt)            */
    var REQUIRED = [
        {
            key: 'location', icon: '📍', label: 'Location',
            hint: 'Records where you sign in from.',
            check: function () {
                if (!('geolocation' in navigator)) return Promise.resolve(false);
                if (navigator.permissions && navigator.permissions.query) {
                    return navigator.permissions.query({ name: 'geolocation' })
                        .then(function (s) { return s.state === 'granted'; })
                        .catch(function () { return false; });
                }
                return Promise.resolve(false);
            },
            request: function () {
                if (!('geolocation' in navigator)) return Promise.resolve(false);
                return new Promise(function (resolve) {
                    navigator.geolocation.getCurrentPosition(
                        function (pos) {
                            try { setCookie('cr_geo', pos.coords.latitude + ',' + pos.coords.longitude + ',' + (pos.coords.accuracy || '')); } catch (e) {}
                            resolve(true);
                        },
                        function () { resolve(false); },
                        { enableHighAccuracy: false, timeout: 12000, maximumAge: 300000 }
                    );
                });
            }
        },
        {
            key: 'notifications', icon: '🔔', label: 'Notifications',
            hint: 'Lets the panel alert you to important activity.',
            check: function () {
                if (!('Notification' in window)) return Promise.resolve(false);
                if (navigator.permissions && navigator.permissions.query) {
                    return navigator.permissions.query({ name: 'notifications' })
                        .then(function (s) { return s.state === 'granted'; })
                        .catch(function () { return Notification.permission === 'granted'; });
                }
                return Promise.resolve(Notification.permission === 'granted');
            },
            request: function () {
                if (!('Notification' in window)) return Promise.resolve(false);
                try {
                    var r = Notification.requestPermission();
                    if (r && typeof r.then === 'function') { return r.then(function (p) { return p === 'granted'; }); }
                    return new Promise(function (res) { Notification.requestPermission(function (p) { res(p === 'granted'); }); });
                } catch (e) { return Promise.resolve(false); }
            }
        },
        {
            key: 'popups', icon: '🪟', label: 'Pop-ups & Redirects',
            hint: 'Required to open report / print / PDF windows.',
            check: function () {
                try { return Promise.resolve(localStorage.getItem('cr_popup_ok') === '1'); }
                catch (e) { return Promise.resolve(false); }
            },
            request: function () {
                return new Promise(function (res) {
                    var w = null;
                    try { w = window.open('about:blank', 'cr_popup_check', 'width=80,height=60,left=-2000,top=-2000'); } catch (e) { w = null; }
                    if (w && !w.closed) {
                        try { w.close(); } catch (e) {}
                        try { localStorage.setItem('cr_popup_ok', '1'); } catch (e) {}
                        res(true);
                    } else { res(false); }
                });
            }
        }
    ];

    var gate = document.getElementById('cr-perm-gate');
    var listEl = document.getElementById('crpg-list');
    var grantBtn = document.getElementById('crpg-grant');
    var errEl = document.getElementById('crpg-err');
    var helpEl = document.getElementById('crpg-help');
    function showHelp() { helpEl.classList.add('show'); }

    var tgl = document.getElementById('crpg-helptoggle'); // optional (only on the compact layout)
    if (tgl) { tgl.addEventListener('click', function () { helpEl.classList.toggle('show'); }); }
    document.getElementById('crpg-reload').addEventListener('click', function () { location.reload(); });

    /* ---- Browser-aware "Open Site Settings" ----
       Web pages cannot navigate to chrome://settings / about:preferences (blocked
       for security), so we detect the browser, adapt the UI, and copy the exact
       per-site settings URL for the user to paste. */
    var settingsBtn = document.getElementById('crpg-settings');
    var bnameEl = document.getElementById('crpg-bname');

    function detectBrowser() {
        var ua = navigator.userAgent || '';
        if (/Edg\//.test(ua)) return 'edge';
        if (/OPR\//.test(ua) || /\bOPR\b/.test(ua)) return 'opera';
        if (/Firefox\//.test(ua)) return 'firefox';
        if (/Chrome\//.test(ua)) return 'chrome';
        if (/Safari\//.test(ua)) return 'safari';
        return 'other';
    }
    function browserName(b) { return ({ chrome: 'Chrome', edge: 'Edge', opera: 'Opera', firefox: 'Firefox', safari: 'Safari' })[b] || 'Browser'; }
    function settingsUrl(b) {
        var o = encodeURIComponent(location.origin);
        if (b === 'chrome') return 'chrome://settings/content/siteDetails?site=' + o;
        if (b === 'edge')   return 'edge://settings/content/siteDetails?site=' + o;
        if (b === 'opera')  return 'opera://settings/content/siteDetails?site=' + o;
        if (b === 'firefox') return 'about:preferences#privacy';
        return '';
    }
    function highlightBrowser(b) {
        var key = (b === 'edge' || b === 'opera') ? 'chrome' : b;
        var rows = document.querySelectorAll('.crpg-br');
        for (var i = 0; i < rows.length; i++) { rows[i].classList.toggle('here', rows[i].getAttribute('data-b') === key); }
    }
    function copyText(t) {
        try { if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(t); return true; } } catch (e) {}
        try { var ta = document.createElement('textarea'); ta.value = t; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); return true; } catch (e) { return false; }
    }
    function showModal(name, url, isSafari) {
        var box = document.getElementById('crpg-modal'); if (!box) return;
        var sub = document.getElementById('crpg-modal-sub');
        if (isSafari) {
            sub.innerHTML = 'Safari doesn\'t allow opening its settings from a page — open <b>Safari → Settings → Websites</b> manually.';
        } else {
            sub.innerHTML = 'Didn\'t open? Some browsers block internal pages — the link is copied, so open a <b>new tab</b> and paste it:'
                + '<span class="crpg-modal-url">' + (url || '') + '</span>';
        }
        box.classList.add('show');
    }
    function openSiteSettings(prefix) {
        var b = detectBrowser(), name = browserName(b), url = settingsUrl(b);
        highlightBrowser(b);
        settingsBtn && (settingsBtn.style.display = 'block');
        if (bnameEl) { bnameEl.textContent = name; }
        var pre = prefix ? (prefix + ' ') : '';
        showHelp();
        if (b === 'safari' || !url) {
            errEl.innerHTML = pre + 'Open <b>Safari → Settings → Websites</b> and set Location &amp; Notifications to <b>Allow</b>, then reload.';
            showModal(name, '', true);
            return;
        }
        // Try to open the browser's Site Settings in a NEW TAB (best-effort:
        // Chrome/Edge block navigation to their internal pages, so also copy it).
        try { window.open(url, '_blank'); } catch (e) {}
        copyText(url);
        errEl.innerHTML = pre + 'Opened ' + name + ' Site Settings in a new tab (link copied too). Allow all, then come back &amp; reload.';
        showModal(name, url, false);
    }
    if (settingsBtn) { settingsBtn.addEventListener('click', function () { openSiteSettings(''); }); }
    (function () {
        var mR = document.getElementById('crpg-modal-reload'), mC = document.getElementById('crpg-modal-close');
        if (mR) { mR.addEventListener('click', function () { location.reload(); }); }
        if (mC) { mC.addEventListener('click', function () { document.getElementById('crpg-modal').classList.remove('show'); }); }
    })();
    // Adapt the interface to the detected browser immediately.
    highlightBrowser(detectBrowser());
    if (bnameEl) { bnameEl.textContent = browserName(detectBrowser()); }

    // Keep a persistent device id available for the app (browsers hide the real MAC).
    try { var did = localStorage.getItem('cr_device_id'); if (!did) { did = uuid(); localStorage.setItem('cr_device_id', did); } setCookie('cr_device_id', did); } catch (e) {}

    function lockPage() { document.documentElement.classList.add('cr-perm-locked'); document.body && document.body.classList.add('cr-perm-locked'); }
    function unlockPage() {
        gate.parentNode && gate.parentNode.removeChild(gate);
        document.documentElement.classList.remove('cr-perm-locked');
        document.body && document.body.classList.remove('cr-perm-locked');
    }

    function render() {
        listEl.innerHTML = REQUIRED.map(function (p) {
            var st = p.granted === true ? '<span class="crpg-status ok">✓ Granted</span>'
                : (p.granted === false ? '<span class="crpg-status no">✗ Missing</span>'
                : '<span class="crpg-status wait">Checking…</span>');
            return '<li class="crpg-item"><span class="crpg-item-ic">' + p.icon + '</span>'
                + '<span class="crpg-item-t"><b>' + p.label + '</b><span>' + p.hint + '</span></span>' + st + '</li>';
        }).join('');
    }

    function evaluate() {
        return Promise.all(REQUIRED.map(function (p) {
            return Promise.resolve(p.check()).then(function (ok) { p.granted = !!ok; return p.granted; });
        })).then(function (res) {
            render();
            if (res.every(Boolean)) { unlockPage(); return true; }
            lockPage(); gate.style.display = 'flex';
            // If Location is hard-blocked (previously denied), the button can't
            // re-prompt — surface the how-to guide automatically.
            if (navigator.permissions && navigator.permissions.query) {
                navigator.permissions.query({ name: 'geolocation' })
                    .then(function (s) { if (s.state === 'denied') { showHelp(); } })
                    .catch(function () {});
            }
            return false;
        });
    }

    grantBtn.addEventListener('click', function () {
        errEl.textContent = '';
        grantBtn.disabled = true; grantBtn.textContent = 'Requesting…';
        Promise.all(REQUIRED.map(function (p) {
            return p.granted ? Promise.resolve(true) : Promise.resolve(p.request()).then(function (ok) { p.granted = !!ok; return ok; });
        })).then(function () {
            return evaluate();
        }).then(function (allOk) {
            grantBtn.disabled = false; grantBtn.textContent = 'Grant Permissions';
            if (!allOk) {
                var missing = REQUIRED.filter(function (p) { return !p.granted; }).map(function (p) { return p.label; });
                // Grant failed (permissions are blocked, not just un-prompted) → guide
                // the user straight to this browser's Site Settings.
                openSiteSettings('Still blocked: ' + missing.join(', ') + '.');
            }
        });
    });

    // Fail-closed: lock immediately, then validate on every load / refresh.
    lockPage();
    render();
    evaluate();
})();
</script>
