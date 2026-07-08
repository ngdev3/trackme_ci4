/* Firm business dashboard — charts (Chart.js) + real-time polling.
 * Chart data comes from window.FIRM_CHARTS; live feed config from window.DL_LIVE.
 * All pieces are defensive no-ops if their data / elements are absent. */
function erpFirmCharts() {
    'use strict';
    if (typeof Chart === 'undefined' || !window.FIRM_CHARTS) { return; }

    var C = window.FIRM_CHARTS;
    var green = '#22c55e', red = '#ef4444', blue = '#3b82f6', amber = '#f59e0b',
        slate = '#94a3b8', violet = '#8b5cf6', sky = '#0ea5e9';

    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    Chart.defaults.color = dark ? '#aab2cc' : '#55607f';
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    var grid = dark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';

    var money = function (v) { return '₹ ' + Number(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var legendBottom = { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } };
    function get(id) { var el = document.getElementById(id); return el ? el.getContext('2d') : null; }

    // --- Redesigned: real ledger daily trend (Jama vs Naam) -----------------
    if (C.txnTrend && get('dlTrendChart')) {
        var g = get('dlTrendChart');
        var gr1 = g.createLinearGradient(0, 0, 0, 300); gr1.addColorStop(0, 'rgba(34,197,94,.35)'); gr1.addColorStop(1, 'rgba(34,197,94,0)');
        var gr2 = g.createLinearGradient(0, 0, 0, 300); gr2.addColorStop(0, 'rgba(239,68,68,.30)'); gr2.addColorStop(1, 'rgba(239,68,68,0)');
        new Chart(g, {
            type: 'line',
            data: {
                labels: C.txnTrend.labels,
                datasets: [
                    { label: 'Jama (In)', data: C.txnTrend.jama, borderColor: green, backgroundColor: gr1, fill: true, tension: .4, pointRadius: 0, pointHoverRadius: 4, borderWidth: 2 },
                    { label: 'Naam (Out)', data: C.txnTrend.naam, borderColor: red, backgroundColor: gr2, fill: true, tension: .4, pointRadius: 0, pointHoverRadius: 4, borderWidth: 2 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: legendBottom.legend, tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + money(c.parsed.y); } } } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: grid } } }
            }
        });
    }

    // --- Redesigned: payment-mode split -------------------------------------
    if (C.txnByMode && get('dlModeChart')) {
        var hasMode = (C.txnByMode.data || []).some(function (v) { return v > 0; });
        new Chart(get('dlModeChart'), {
            type: 'doughnut',
            data: {
                labels: hasMode ? C.txnByMode.labels : ['No data'],
                datasets: [{ data: hasMode ? C.txnByMode.data : [1], backgroundColor: hasMode ? [green, blue, violet, amber, sky, slate] : ['#e5e7eb'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: legendBottom.legend, tooltip: { enabled: hasMode, callbacks: { label: function (c) { return c.label + ': ' + money(c.parsed); } } } } }
        });
    }

    // --- Retained accounting charts (no-op if their canvas is absent) --------
    if (C.erp && C.erp.salesPurchase && get('salesPurchaseChart')) {
        new Chart(get('salesPurchaseChart'), {
            type: 'bar',
            data: {
                labels: C.erp.salesPurchase.labels,
                datasets: [
                    { label: 'Sales', data: C.erp.salesPurchase.sales || [], backgroundColor: green, borderRadius: 4 },
                    { label: 'Purchase', data: C.erp.salesPurchase.purchase || [], backgroundColor: blue, borderRadius: 4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: legendBottom.legend, tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + money(c.parsed.y); } } } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: grid } } } }
        });
    }
    if (C.erp && C.erp.cashBank && get('cashBankChart')) {
        var hasCB = (C.erp.cashBank.data || []).some(function (v) { return v !== 0; });
        new Chart(get('cashBankChart'), {
            type: 'doughnut',
            data: { labels: hasCB ? C.erp.cashBank.labels : ['No data'], datasets: [{ data: hasCB ? C.erp.cashBank.data : [1], backgroundColor: hasCB ? [amber, blue] : ['#e5e7eb'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: legendBottom.legend, tooltip: { enabled: hasCB, callbacks: { label: function (c) { return c.label + ': ' + money(c.parsed); } } } } }
        });
    }
}

/* ------------------------------------------------------------------------- */
/* Real-time layer: live clock + periodic KPI / counter / feed refresh.       */
/* ------------------------------------------------------------------------- */
function erpDashLive() {
    'use strict';
    var CFG = window.DL_LIVE;
    var inr = function (v) { return Number(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var money = function (v) { return '₹ ' + inr(v); };
    var intf = function (v) { return Number(v).toLocaleString('en-IN'); };

    // Ticking clock + "updated N ago".
    var lastUpdate = Date.now();
    function two(n) { return (n < 10 ? '0' : '') + n; }
    setInterval(function () {
        var d = new Date();
        var clock = document.querySelector('[data-live-clock]');
        if (clock) { clock.textContent = two(d.getHours()) + ':' + two(d.getMinutes()) + ':' + two(d.getSeconds()); }
        var up = document.querySelector('[data-live-updated]');
        if (up) {
            var s = Math.round((Date.now() - lastUpdate) / 1000);
            up.textContent = s < 3 ? 'updated just now' : 'updated ' + (s < 60 ? s + 's' : Math.floor(s / 60) + 'm') + ' ago';
        }
    }, 1000);

    if (!CFG || !CFG.url) { return; }

    function setVal(el, text) {
        if (!el || el.textContent === text) { return; }
        el.textContent = text;
        el.classList.remove('dl-flash'); void el.offsetWidth; el.classList.add('dl-flash');
    }

    function esc(s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : String(s)); return d.innerHTML; }
    function cap(s) { s = String(s || ''); return s.charAt(0).toUpperCase() + s.slice(1); }

    function apply(d) {
        if (!d) { return; }
        var k = d.kpis || {};
        document.querySelectorAll('[data-live]').forEach(function (el) {
            var key = el.getAttribute('data-live');
            if (!(key in k)) { return; }
            setVal(el, el.hasAttribute('data-money') ? money(k[key]) : intf(k[key]));
        });
        // KPI sub-lines.
        var subJama = document.querySelector('[data-live-sub="jama"]');
        if (subJama) { subJama.innerHTML = '<span class="up">+' + inr(k.today_jama || 0) + '</span> today'; }
        var subNaam = document.querySelector('[data-live-sub="naam"]');
        if (subNaam) { subNaam.innerHTML = '<span class="down">-' + inr(k.today_naam || 0) + '</span> today'; }
        var subNet = document.querySelector('[data-live-sub="net"]');
        if (subNet) { subNet.textContent = (k.net >= 0 ? 'Surplus this period' : 'Deficit this period'); }
        var subCnt = document.querySelector('[data-live-sub="count"]');
        if (subCnt) { subCnt.textContent = (k.pending || 0) + ' pending / overdue'; }

        // Counter chips.
        var c = d.counts || {};
        document.querySelectorAll('[data-live-count]').forEach(function (el) {
            var key = el.getAttribute('data-live-count');
            if (key in c) { setVal(el, intf(c[key])); }
        });

        // Activity feed.
        var feed = document.querySelector('[data-live-feed]');
        if (feed && Array.isArray(d.recent)) {
            if (d.recent.length === 0) {
                feed.innerHTML = '<li class="dl-empty"><i class="bi bi-inbox"></i>No transactions yet.</li>';
            } else {
                feed.innerHTML = d.recent.map(function (t, i) {
                    var jama = t.type === 'jama';
                    return '<li class="' + (i === 0 ? 'dl-new' : '') + '">'
                        + '<span class="dl-feed-ic ' + (jama ? 'jama' : 'naam') + '"><i class="bi ' + (jama ? 'bi-arrow-down' : 'bi-arrow-up') + '"></i></span>'
                        + '<span class="dl-feed-main"><span class="dl-feed-name">' + esc(t.name) + '</span>'
                        + '<span class="dl-feed-meta">' + esc(t.txn_no) + ' · ' + esc(cap(t.mode)) + ' · ' + esc(t.ago || '') + '</span></span>'
                        + '<span class="dl-feed-amt ' + (jama ? 'jama' : 'naam') + '">' + (jama ? '+' : '-') + inr(t.amount) + '</span></li>';
                }).join('');
            }
        }
        lastUpdate = Date.now();
    }

    function poll() {
        var q = '?period=' + encodeURIComponent(CFG.period || 'month')
            + (CFG.from ? '&from=' + encodeURIComponent(CFG.from) : '')
            + (CFG.to ? '&to=' + encodeURIComponent(CFG.to) : '');
        fetch(CFG.url + q, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(apply)
            .catch(function () { /* keep last good state */ });
    }

    // Pause polling when the tab is hidden; refresh immediately on return.
    setInterval(function () { if (!document.hidden) { poll(); } }, CFG.every || 20000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); } });
    // First refresh shortly after load so charts paint first.
    setTimeout(poll, 4000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { erpFirmCharts(); erpDashLive(); });
} else {
    erpFirmCharts(); erpDashLive();
}
