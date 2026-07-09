/* Users page — charts, animated counters, table/grid toggle, and AJAX
 * sort + pagination (no full page reload). */
function erpUsersInit() {
    'use strict';

    // ---- animated stat counters (run once on full load) ----
    document.querySelectorAll('.stat-num[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (target === 0) { el.textContent = '0'; return; }
        var start = null, dur = 900;
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            el.textContent = Math.round((1 - Math.pow(1 - p, 3)) * target).toLocaleString();
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });

    // ---- table / grid view (delegated, survives fragment swaps) ----
    function setView(view) {
        document.querySelectorAll('.view-toggle [data-view]').forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-view') === view);
        });
        document.querySelectorAll('[data-view-panel]').forEach(function (p) {
            p.classList.toggle('d-none', p.getAttribute('data-view-panel') !== view);
        });
        try { localStorage.setItem('erp-users-view', view); } catch (e) {}
    }
    function applyView() {
        var v = 'table';
        try { v = localStorage.getItem('erp-users-view') || 'table'; } catch (e) {}
        setView(v);
    }

    // ---- AJAX sort + search + paginate + page-size (no full reload) ----
    var list = document.getElementById('usersList');
    if (list) {
        var listUrl     = list.getAttribute('data-list-url');          // …/users/list
        var prettyUrl   = listUrl.replace(/\/list$/, '');              // …/users
        var searchForm  = document.getElementById('usersSearchForm');
        var searchInput = document.getElementById('usersSearch');
        var searchTimer = null;
        var reqSeq = 0;   // only the newest request may apply (search race guard)

        function loadList(search, push) {
            var myReq = ++reqSeq;
            list.classList.add('tm-loading');
            if (searchForm) { searchForm.classList.add('is-searching'); }
            fetch(listUrl + (search || ''), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.text(); })
                .then(function (html) {
                    if (myReq !== reqSeq) { return; }   // superseded by a newer request
                    list.innerHTML = html;
                    if (push) { history.pushState({ s: search }, '', prettyUrl + (search || '')); }
                    applyView();
                    list.classList.remove('tm-loading');
                    if (searchForm) { searchForm.classList.remove('is-searching'); }
                })
                .catch(function () {
                    if (myReq !== reqSeq) { return; }
                    list.classList.remove('tm-loading');
                    if (searchForm) { searchForm.classList.remove('is-searching'); }
                });
        }

        // Merge overrides into the current query, reset to page 1, then reload.
        function go(overrides) {
            var p = new URLSearchParams(window.location.search);
            Object.keys(overrides).forEach(function (k) {
                if (overrides[k] === null || overrides[k] === '') { p.delete(k); } else { p.set(k, overrides[k]); }
            });
            p.delete('page');
            var qs = p.toString();
            loadList(qs ? '?' + qs : '', true);
        }

        // Sort links, pager links, view toggle.
        list.addEventListener('click', function (e) {
            var vt = e.target.closest('.view-toggle [data-view]');
            if (vt) { setView(vt.getAttribute('data-view')); return; }
            var a = e.target.closest('a.sort-link, .pagination a');
            if (a && list.contains(a) && a.getAttribute('href')) {
                e.preventDefault();
                loadList(new URL(a.href, window.location.origin).search, true);
            }
        });

        // Page-size selector (lives inside the swapped fragment; change bubbles).
        list.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'perSelect') { go({ per: e.target.value }); }
        });

        // Live search (debounced) + Enter.
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () { go({ q: searchInput.value.trim() }); }, 350);
            });
        }
        if (searchForm) {
            searchForm.addEventListener('submit', function (e) {
                e.preventDefault();
                clearTimeout(searchTimer);
                go({ q: searchInput ? searchInput.value.trim() : '' });
            });
        }

        window.addEventListener('popstate', function () {
            if (! document.getElementById('usersList')) { return; }
            loadList(window.location.search, false);
            if (searchInput) { searchInput.value = new URLSearchParams(window.location.search).get('q') || ''; }
        });
    }

    applyView();

    // ---- charts (run once on full load) ----
    if (typeof Chart === 'undefined' || !window.USERS_CHARTS) { return; }
    var C = window.USERS_CHARTS;
    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    Chart.defaults.color = dark ? '#aab2cc' : '#55607f';
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    var grid = dark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
    var palette = ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#8b5cf6', '#14b8a6', '#ec4899'];
    var legendBottom = { legend: { position: 'bottom', labels: { boxWidth: 11, padding: 10, font: { size: 11 } } } };
    var ctx = function (id) { var e = document.getElementById(id); return e ? e.getContext('2d') : null; };

    if (C.byRole && ctx('roleChart')) {
        new Chart(ctx('roleChart'), { type: 'doughnut',
            data: { labels: C.byRole.labels, datasets: [{ data: C.byRole.data, backgroundColor: palette, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', animation: { animateScale: true }, plugins: { legend: legendBottom.legend } } });
    }
    if (C.status && ctx('statusChart')) {
        new Chart(ctx('statusChart'), { type: 'doughnut',
            data: { labels: C.status.labels, datasets: [{ data: C.status.data, backgroundColor: ['#22c55e', '#ef4444'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', animation: { animateScale: true }, plugins: { legend: legendBottom.legend } } });
    }
    if (C.byType && ctx('typeChart')) {
        new Chart(ctx('typeChart'), { type: 'bar',
            data: { labels: C.byType.labels, datasets: [{ label: 'Users', data: C.byType.data, backgroundColor: '#6366f1', borderRadius: 6 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { precision: 0 } } } } });
    }
    if (C.growth && ctx('growthChart')) {
        new Chart(ctx('growthChart'), { type: 'line',
            data: { labels: C.growth.labels, datasets: [{ label: 'New users', data: C.growth.data, borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,.14)', fill: true, tension: .38, pointRadius: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { precision: 0 } } } } });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', erpUsersInit);
} else {
    erpUsersInit();
}
