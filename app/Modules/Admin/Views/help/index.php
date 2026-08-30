<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
    .hlp-scope { color: #18243c; }
    .cx-wrap { width: 100%; max-width: 1320px; margin: 0 auto; height: calc(100vh - 150px); min-height: 600px; display: flex; }
    .cx-app { flex: 1; display: flex; flex-direction: column; background: #fff; border: 1px solid #e3e9f2; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 50px rgba(16,32,72,.12); }

    /* Header */
    .cx-head { display: flex; align-items: center; gap: 12px; padding: 13px 18px; color: #fff;
        background: linear-gradient(120deg, #12325b, #1a3f6b 55%, #3b1e6e); }
    .cx-avatar { width: 42px; height: 42px; border-radius: 50%; display: grid; place-items: center; font-size: 20px; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.3); flex: none; }
    .cx-head-t b { display: block; font-size: 15px; font-weight: 800; }
    .cx-head-t span { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #cfe0f5; margin-top: 1px; }
    .cx-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.25); }
    .cx-head-act { margin-left: auto; display: flex; gap: 8px; }
    .cx-hbtn { width: 32px; height: 32px; border-radius: 9px; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1); color: #fff; cursor: pointer; font-size: 13px; }
    .cx-hbtn:hover { background: rgba(255,255,255,.22); }

    /* Messages */
    .cx-msgs { flex: 1; overflow-y: auto; padding: 18px; background:
        radial-gradient(circle at 20% 10%, #f3f7fc, transparent 40%),
        radial-gradient(circle at 90% 90%, #f2f6fb, transparent 40%), #f7f9fc; }
    .cx-row { display: flex; align-items: flex-end; gap: 9px; margin-bottom: 14px; }
    .cx-row.me { flex-direction: row-reverse; }
    .cx-ava-sm { width: 30px; height: 30px; border-radius: 50%; flex: none; display: grid; place-items: center; font-size: 14px; color: #fff; background: linear-gradient(135deg, #1a3f6b, #3b1e6e); }
    .cx-me-ava { background: linear-gradient(135deg, #0f9d58, #0b7a44); }
    .cx-col { max-width: 860px; }
    .cx-row.me .cx-col { max-width: 620px; }
    .cx-bubble { padding: 10px 14px; border-radius: 15px; font-size: 14px; line-height: 1.5; box-shadow: 0 3px 10px rgba(24,36,60,.05); }
    .cx-row.bot .cx-bubble { background: #fff; border: 1px solid #e7edf5; border-bottom-left-radius: 5px; color: #223; }
    .cx-row.me .cx-bubble { background: #1a3f6b; color: #fff; border-bottom-right-radius: 5px; font-weight: 500; }
    .cx-time { font-size: 10px; color: #9aa8bd; margin-top: 4px; padding: 0 4px; }
    .cx-row.me .cx-time { text-align: right; }

    /* Link cards in bot replies */
    .cx-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 9px; margin-top: 9px; }
    .cx-card { display: flex; gap: 10px; align-items: flex-start; border: 1px solid #e6ecf5; border-radius: 12px; padding: 10px 12px; text-decoration: none; color: inherit; background: #fbfdff; transition: transform .14s ease, box-shadow .14s ease, border-color .14s; }
    .cx-card:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(24,36,60,.12); border-color: #cdd9ec; }
    .cx-card-ic { width: 36px; height: 36px; border-radius: 10px; flex: none; display: grid; place-items: center; font-size: 16px; color: #fff; }
    .cx-card-b b { display: block; font-size: 13px; font-weight: 800; color: #12233d; }
    .cx-card-b .d { display: block; font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.35; }
    .cx-card-b .o { display: inline-flex; align-items: center; gap: 4px; margin-top: 5px; font-size: 10.5px; font-weight: 800; color: #1d4ed8; }
    .cx-tag { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; color: #94a3b8; }

    /* Typing indicator */
    .cx-typing { display: inline-flex; gap: 4px; padding: 13px 15px; }
    .cx-typing i { width: 7px; height: 7px; border-radius: 50%; background: #9aa8bd; animation: cxb 1.2s infinite; }
    .cx-typing i:nth-child(2) { animation-delay: .2s; } .cx-typing i:nth-child(3) { animation-delay: .4s; }
    @keyframes cxb { 0%,60%,100% { transform: translateY(0); opacity: .5; } 30% { transform: translateY(-4px); opacity: 1; } }

    /* Quick chips + input */
    .cx-chips { display: flex; gap: 7px; padding: 10px 14px 0; overflow-x: auto; flex-wrap: nowrap; }
    .cx-chips::-webkit-scrollbar { height: 0; }
    .cx-chip { white-space: nowrap; background: #eef3fb; border: 1px solid #dce6f2; color: #1a3f6b; font-size: 12px; font-weight: 700; padding: 6px 13px; border-radius: 999px; cursor: pointer; flex: none; }
    .cx-chip:hover { background: #e0eaf7; }
    .cx-chip.hot { background: linear-gradient(135deg,#1769c2,#0c315f); color: #fff; border-color: transparent; }
    .cx-new { display: inline-block; margin-left: 6px; padding: 0 6px; border-radius: 999px; background: #16a34a; color: #fff; font-size: 9px; font-weight: 900; letter-spacing: .04em; vertical-align: 1px; }
    .cx-input { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-top: 1px solid #eef2f7; background: #fff; }
    .cx-input input { flex: 1; min-height: 46px; border: 1px solid #dce6f2; border-radius: 24px; padding: 0 18px; font-size: 14px; font-weight: 500; background: #f8fafc; }
    .cx-input input:focus { outline: none; border-color: #1a3f6b; background: #fff; box-shadow: 0 0 0 3px rgba(26,63,107,.1); }
    .cx-send { width: 46px; height: 46px; border-radius: 50%; border: 0; background: #1a3f6b; color: #fff; font-size: 17px; cursor: pointer; flex: none; }
    .cx-send:hover { background: #143257; }

    @media (max-width: 900px) { .cx-col, .cx-row.me .cx-col { max-width: 84%; } }
    @media (max-width: 640px) { .cx-wrap { height: calc(100vh - 165px); } }
</style>

<div class="main-content hlp-scope">
    <div class="cx-wrap">
        <div class="cx-app">
            <div class="cx-head">
                <div class="cx-avatar"><i class="ti-headphone-alt"></i></div>
                <div class="cx-head-t">
                    <b>CR Help Assistant</b>
                    <span><span class="cx-dot"></span> Online &middot; replies instantly</span>
                </div>
                <div class="cx-head-act">
                    <button type="button" class="cx-hbtn" id="cxClear" title="Clear chat"><i class="ti-trash"></i></button>
                </div>
            </div>

            <div class="cx-msgs" id="cxMsgs"></div>

            <div class="cx-chips" id="cxChips">
                <span class="cx-chip hot">Item Master</span>
                <span class="cx-chip hot">Change firm log</span>
                <span class="cx-chip hot">Attachment size</span>
                <span class="cx-chip">Rokad attachments</span>
                <span class="cx-chip">Who edited an entry</span>
                <span class="cx-chip">Restore deleted entry</span>
                <span class="cx-chip">Give user access</span>
                <span class="cx-chip">Tax invoice</span>
                <span class="cx-chip">IP &amp; location</span>
                <span class="cx-chip">Salary</span>
                <span class="cx-chip">Kisan Vahi</span>
            </div>

            <form class="cx-input" id="cxForm" onsubmit="return false;">
                <input type="text" id="cxInput" placeholder="Type your question…  (e.g. rokad attachments)" autocomplete="off">
                <button type="submit" class="cx-send" id="cxSend"><i class="ti-arrow-right"></i></button>
            </form>
        </div>
    </div>
</div>

<script>
    var HELP_KB  = <?= json_encode($kb, JSON_UNESCAPED_UNICODE); ?>;
    var HELP_FAQ = <?= json_encode($faqs, JSON_UNESCAPED_UNICODE); ?>;

    (function () {
        var groupIcon = { 'Cash Book': ['ti-wallet', '#2563eb'], 'Reports': ['ti-bar-chart', '#0891b2'], 'Invoicing': ['ti-receipt', '#7c3aed'], 'Purchase': ['ti-shopping-cart', '#ea580c'], 'Cold Storage': ['ti-package', '#0d9488'], 'Rice Mill': ['ti-blackboard', '#ca8a04'], 'People': ['ti-user', '#db2777'], 'Admin': ['ti-settings', '#1a3f6b'], 'Masters': ['ti-layers', '#4f46e5'] };
        function icoFor(g) { return groupIcon[g] || ['ti-help', '#64748b']; }
        function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
        function now() { var d = new Date(); var h = d.getHours(), m = d.getMinutes(); var ap = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12; return h + ':' + (m < 10 ? '0' + m : m) + ' ' + ap; }

        var msgs = document.getElementById('cxMsgs');
        function scrollDown() { msgs.scrollTop = msgs.scrollHeight; }

        function bubbleRow(who, inner, withTime) {
            var row = document.createElement('div');
            row.className = 'cx-row ' + (who === 'me' ? 'me' : 'bot');
            var ava = who === 'me'
                ? '<div class="cx-ava-sm cx-me-ava"><i class="ti-user"></i></div>'
                : '<div class="cx-ava-sm"><i class="ti-headphone-alt"></i></div>';
            row.innerHTML = ava + '<div class="cx-col"><div class="cx-bubble">' + inner + '</div>'
                + (withTime ? '<div class="cx-time">' + now() + '</div>' : '') + '</div>';
            msgs.appendChild(row); scrollDown();
            return row;
        }

        function search(q) {
            q = (q || '').toLowerCase().trim(); if (!q) return [];
            var toks = q.split(/\s+/).filter(Boolean);
            return HELP_KB.map(function (it) {
                var title = (it.t || '').toLowerCase(), keys = (it.k || '').toLowerCase(), desc = (it.d || '').toLowerCase(), grp = (it.g || '').toLowerCase(), s = 0;
                toks.forEach(function (tk) {
                    if (title.indexOf(tk) !== -1) s += 4;
                    if (keys.indexOf(tk) !== -1) s += 3;
                    if (desc.indexOf(tk) !== -1) s += 1;
                    if (grp.indexOf(tk) !== -1) s += 1;
                });
                if (title.indexOf(q) !== -1 || keys.indexOf(q) !== -1) s += 3;
                return { it: it, s: s };
            }).filter(function (x) { return x.s > 0; }).sort(function (a, b) { return b.s - a.s; }).slice(0, 6).map(function (x) { return x.it; });
        }
        function cardHtml(it) {
            var ic = icoFor(it.g);
            var badge = it.new ? '<span class="cx-new">NEW</span>' : '';
            return '<a class="cx-card" href="' + it.u + '" target="_blank" rel="noopener">'
                + '<span class="cx-card-ic" style="background:' + ic[1] + '"><i class="' + ic[0] + '"></i></span>'
                + '<span class="cx-card-b"><span class="cx-tag">' + esc(it.g) + badge + '</span><b>' + esc(it.t) + '</b><span class="d">' + esc(it.d) + '</span>'
                + '<span class="o">Open in new tab <i class="ti-new-window"></i></span></span></a>';
        }

        function botReply(query) {
            // typing indicator
            var t = bubbleRow('bot', '<div class="cx-typing"><i></i><i></i><i></i></div>', false);
            setTimeout(function () {
                var res = search(query), html;
                if (res.length) {
                    html = 'Here ' + (res.length === 1 ? 'is the page' : 'are the pages') + ' for <b>' + esc(query) + '</b> — tap to open in a new tab:'
                        + '<div class="cx-cards">' + res.map(cardHtml).join('') + '</div>';
                } else {
                    html = "I couldn't find a page for <b>" + esc(query) + "</b>. Try simpler words like <i>attachment</i>, <i>invoice</i>, <i>salary</i>, <i>permission</i> — or tap a suggestion below.";
                }
                t.querySelector('.cx-bubble').innerHTML = html;
                var col = t.querySelector('.cx-col');
                if (!col.querySelector('.cx-time')) { var tm = document.createElement('div'); tm.className = 'cx-time'; tm.textContent = now(); col.appendChild(tm); }
                scrollDown();
            }, 480);
        }

        function ask(q) {
            q = (q || '').trim(); if (!q) return;
            bubbleRow('me', esc(q), true);
            document.getElementById('cxInput').value = '';
            botReply(q);
        }

        document.getElementById('cxForm').addEventListener('submit', function () { ask(document.getElementById('cxInput').value); });
        document.getElementById('cxChips').addEventListener('click', function (e) { if (e.target.classList.contains('cx-chip')) ask(e.target.textContent); });
        document.getElementById('cxClear').addEventListener('click', function () { msgs.innerHTML = ''; greet(); });

        function greet() {
            bubbleRow('bot', 'Hi! 👋 I\'m your CR ERP help assistant. Tell me what you\'re trying to do — for example <b>“rokad attachments”</b> or <b>“who edited an entry”</b> — and I\'ll give you a direct link.', true);
            // Offer FAQ as tappable starters inside chat.
            var faqCards = HELP_FAQ.slice(0, 4).map(function (f) {
                var ic = icoFor('Admin');
                return '<a class="cx-card" href="' + (f.u || '#') + '" target="_blank" rel="noopener">'
                    + '<span class="cx-card-ic" style="background:#1a3f6b"><i class="ti-comment-alt"></i></span>'
                    + '<span class="cx-card-b"><span class="cx-tag">Popular question</span><b>' + esc(f.q) + '</b>'
                    + '<span class="o">' + esc(f.label || 'Open') + ' <i class="ti-new-window"></i></span></span></a>';
            }).join('');
            bubbleRow('bot', 'Or pick a common question:<div class="cx-cards">' + faqCards + '</div>', true);

            // What's new this month — features flagged as new in the KB.
            var fresh = HELP_KB.filter(function (it) { return it.new; }).slice(0, 6);
            if (fresh.length) {
                bubbleRow('bot', '🆕 <b>What\'s new this month</b> — recently added or improved:<div class="cx-cards">' + fresh.map(cardHtml).join('') + '</div>', true);
            }
        }

        greet();
    })();
</script>
