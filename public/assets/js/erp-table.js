/* ============================================================================
   ERP shared table hover-card engine.
   Attach a JSON payload to any element as data-tip and give it class .erp-hover;
   on hover a rich card (#erpTip) is rendered from a GENERIC shape so every module
   gets the exact same card without bespoke JS:

     {
       "type":  "Customer",                       // eyebrow label (optional)
       "name":  "Ramesh Traders",                 // card title (required)
       "accent":"blue|green|gray|red",            // header gradient (optional)
       "chips": [ {"t":"Active","ic":"check-circle-fill","ok":true}, ... ],
       "stats": [ {"v":"5","l":"Firms"}, ... ],    // up to ~4 tiles
       "bar":   {"pct":60,"l":"Premium","r":"20 days left","bad":false}, // optional
       "rows":  [ {"ic":"envelope","l":"Email","v":"a@b.com"}, ... ],
       "foot":  "Joined 18 Jul 2026"               // optional
     }

   Delegated + idempotent: safe to load once per page; survives AJAX table swaps.
   ========================================================================== */
(function () {
    if (window.__erpTipInit) { return; }
    window.__erpTipInit = true;

    var tip, curEl = null;

    function el() {
        if (!tip) {
            tip = document.getElementById('erpTip');
            if (!tip) {
                tip = document.createElement('div');
                tip.id = 'erpTip';
                tip.setAttribute('aria-hidden', 'true');
                document.body.appendChild(tip);
            }
        }
        return tip;
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    }
    function icon(name) { return '<i class="bi bi-' + esc(name) + '"></i>'; }

    function build(o) {
        var accent = ['green', 'gray', 'red'].indexOf(o.accent) >= 0 ? ' ' + o.accent : '';
        var h = '<div class="erp-tip-box"><div class="erp-tip-head' + accent + '">';
        if (o.type) { h += '<div class="erp-tip-eyebrow">' + icon(o.icon || 'person-badge') + '<span>' + esc(o.type) + '</span></div>'; }
        h += '<div class="erp-tip-name">' + esc(o.name || '—') + '</div>';
        if (o.chips && o.chips.length) {
            h += '<div class="erp-tip-chips">';
            o.chips.forEach(function (c) {
                if (!c || c.t == null) { return; }
                h += '<span class="erp-tip-chip' + (c.ok ? ' ok' : '') + '">' + (c.ic ? icon(c.ic) : '') + esc(c.t) + '</span>';
            });
            h += '</div>';
        }
        h += '</div>';

        if (o.stats && o.stats.length) {
            h += '<div class="erp-tip-stats">';
            o.stats.forEach(function (s) {
                if (!s) { return; }
                h += '<div class="erp-tip-stat"><div class="v">' + esc(s.v || '—') + '</div><span class="l">' + esc(s.l) + '</span></div>';
            });
            h += '</div>';
        }

        var hasBody = (o.bar && o.bar.pct != null) || (o.rows && o.rows.length);
        if (hasBody) {
            h += '<div class="erp-tip-body">';
            if (o.bar && o.bar.pct != null) {
                var pct = Math.max(0, Math.min(100, o.bar.pct)), bad = o.bar.bad ? ' bad' : '';
                h += '<div class="erp-tip-flow"><div class="erp-tip-flow-top"><span class="l">' + esc(o.bar.l || '') + '</span><span class="r' + bad + '">' + esc(o.bar.r || '') + '</span></div>'
                   + '<div class="erp-tip-bar"><span class="' + (bad ? 'bad' : '') + '" style="width:' + pct + '%"></span></div></div>';
            }
            if (o.rows && o.rows.length) {
                h += '<div class="erp-tip-rows">';
                o.rows.forEach(function (r) {
                    if (!r || r.v == null || r.v === '') { return; }
                    h += '<div class="erp-tip-row"><span class="ic">' + icon(r.ic || 'dot') + '</span>'
                       + '<span class="ct">' + (r.l ? '<span class="rl">' + esc(r.l) + '</span>' : '')
                       + '<div class="rv">' + esc(r.v) + '</div></span></div>';
                });
                h += '</div>';
            }
            h += '</div>';
        }

        if (o.foot) { h += '<div class="erp-tip-foot">' + icon('clock') + '<span>' + esc(o.foot) + '</span></div>'; }
        h += '</div>';
        return h;
    }

    function place(target) {
        var t = el(), r = target.getBoundingClientRect();
        var vw = window.innerWidth, vh = window.innerHeight;
        var w = t.offsetWidth || 360, ht = t.offsetHeight || 240;
        var left = r.left, top = r.bottom + 10;
        if (left + w > vw - 12) { left = vw - w - 12; }
        if (left < 12) { left = 12; }
        if (top + ht > vh - 12) { top = r.top - ht - 10; }        // flip above
        if (top < 12) { top = 12; }
        t.style.left = left + 'px';
        t.style.top = top + 'px';
    }

    function open(target) {
        var raw = target.getAttribute('data-tip');
        if (!raw) { return; }
        var o;
        try { o = JSON.parse(raw); } catch (e) { return; }
        var t = el();
        t.innerHTML = build(o);
        t.style.left = '-9999px'; t.style.top = '0';
        t.classList.add('show');
        curEl = target;
        place(target);
    }

    function close() {
        curEl = null;
        if (tip) { tip.classList.remove('show'); }
    }

    document.addEventListener('mouseover', function (e) {
        var t = e.target.closest && e.target.closest('.erp-hover[data-tip]');
        if (t && t !== curEl) { open(t); }
    });
    document.addEventListener('mouseout', function (e) {
        var t = e.target.closest && e.target.closest('.erp-hover[data-tip]');
        if (t && t === curEl && !t.contains(e.relatedTarget)) { close(); }
    });
    document.addEventListener('focusin', function (e) {
        var t = e.target.closest && e.target.closest('.erp-hover[data-tip]');
        if (t) { open(t); }
    });
    window.addEventListener('scroll', function () { if (curEl) { place(curEl); } }, true);
    window.addEventListener('resize', function () { if (curEl) { place(curEl); } });

    window.ErpTable = { open: open, close: close };
})();
