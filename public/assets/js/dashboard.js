/* ------------------------------------------------------------------
 * Dashboard analytics — jQuery AJAX loader + Chart.js renderers.
 * Charts read the live theme colour, animate in, and reload on demand.
 * ------------------------------------------------------------------ */
(function ($) {
    'use strict';

    var charts = {};   // keep instances so we can destroy on reload

    function cssVar(name, fallback) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return v || fallback;
    }
    function primary() { return cssVar('--bs-primary', '#0d6efd'); }
    function palette(n) {
        var base = [primary(), '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc', '#6f42c1', '#fd7e14', '#20c997', '#6610f2', '#d63384'];
        var out = [];
        for (var i = 0; i < n; i++) { out.push(base[i % base.length]); }
        return out;
    }
    function gridColor() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark'
            ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
    }
    function fontColor() { return cssVar('--bs-body-color', '#212529'); }

    function make(id, config) {
        var el = document.getElementById(id);
        if (!el) { return; }
        if (charts[id]) { charts[id].destroy(); }
        Chart.defaults.color = fontColor();
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        charts[id] = new Chart(el, config);
        // Reveal canvas + drop its skeleton.
        var box = el.closest('.chart-box, .chart-box-sm');
        if (box) { var sk = box.querySelector('.chart-skeleton'); if (sk) { sk.remove(); } }
    }

    var baseScale = function () {
        return {
            x: { grid: { color: gridColor() }, ticks: { color: fontColor() } },
            y: { grid: { color: gridColor() }, ticks: { color: fontColor(), precision: 0 }, beginAtZero: true }
        };
    };

    /* ---------- KPI count-up animation ---------- */
    function animateCounts() {
        $('.kpi-value[data-count]').each(function () {
            var $el = $(this), target = parseInt($el.attr('data-count'), 10) || 0, start = null, dur = 900;
            function step(ts) {
                if (!start) { start = ts; }
                var p = Math.min((ts - start) / dur, 1);
                $el.text(Math.floor(p * target).toLocaleString());
                if (p < 1) { requestAnimationFrame(step); }
            }
            requestAnimationFrame(step);
        });
    }

    /* ---------- Renderers ---------- */
    function renderLogins(d) {
        make('chartLogins', {
            type: 'line',
            data: { labels: d.labels, datasets: [
                { label: 'Success', data: d.success, borderColor: primary(), backgroundColor: 'rgba(var(--bs-primary-rgb,13,110,253),.12)', fill: true, tension: .35, pointRadius: 3 },
                { label: 'Failed', data: d.failed, borderColor: '#e74a3b', backgroundColor: 'rgba(231,74,59,.10)', fill: true, tension: .35, pointRadius: 3 }
            ]},
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top' } }, scales: baseScale() }
        });
    }
    function renderDoughnut(id, d) {
        make(id, {
            type: 'doughnut',
            data: { labels: d.labels, datasets: [{ data: d.data, backgroundColor: palette(d.data.length), borderWidth: 2, borderColor: cssVar('--bs-body-bg', '#fff') }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } }
        });
    }
    function renderPie(id, d) {
        make(id, {
            type: 'pie',
            data: { labels: d.labels, datasets: [{ data: d.data, backgroundColor: palette(d.data.length), borderWidth: 2, borderColor: cssVar('--bs-body-bg', '#fff') }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }
    function renderBar(id, d, horizontal) {
        make(id, {
            type: 'bar',
            data: { labels: d.labels, datasets: [{ label: 'Count', data: d.data, backgroundColor: palette(d.data.length), borderRadius: 6 }] },
            options: { responsive: true, maintainAspectRatio: false, indexAxis: horizontal ? 'y' : 'x',
                plugins: { legend: { display: false } }, scales: baseScale() }
        });
    }
    function renderTopUsers(d) {
        var $c = $('#topUsers').empty();
        if (!d.labels.length) { $c.html('<p class="text-secondary mb-0">No activity in the last 30 days.</p>'); return; }
        d.labels.forEach(function (name, i) {
            $c.append(
                '<div class="rank-item"><span class="rank-badge">' + (i + 1) + '</span>' +
                '<span class="flex-grow-1 text-truncate">' + $('<div>').text(name).html() + '</span>' +
                '<span class="badge text-bg-light border">' + d.data[i] + '</span></div>'
            );
        });
    }
    function renderHealth(k) {
        var total = (k.active_users + k.inactive_users) || 1;
        $('#mActive').text(k.active_users);
        $('#mInactive').text(k.inactive_users);
        $('#mModules').text(k.total_modules);
        $('#mRoles').text(k.total_roles);
        $('#barActive').css('width', Math.round(k.active_users / total * 100) + '%');
        $('#barInactive').css('width', Math.round(k.inactive_users / total * 100) + '%');
        $('#barModules').css('width', Math.min(100, k.total_modules * 8) + '%');
        $('#barRoles').css('width', Math.min(100, k.total_roles * 15) + '%');
    }
    function money(n) { return '₹' + Number(n || 0).toLocaleString(); }
    function renderFinance(k, s) {
        $('#fIncome').text(money(k.total_income));
        $('#fExpense').text(money(k.total_expense));
        $('#fBalance').text(money(k.total_balance));
        $('#fDC').text(money(k.debtors) + ' / ' + money(k.creditors));
        make('chartFinance', {
            type: 'bar',
            data: { labels: s.monthly_labels, datasets: [
                { label: 'Income', data: s.monthly_income, backgroundColor: '#1cc88a', borderRadius: 5 },
                { label: 'Expense', data: s.monthly_expense, backgroundColor: '#e74a3b', borderRadius: 5 }
            ]},
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: baseScale() }
        });
        make('chartRvP', {
            type: 'doughnut',
            data: { labels: ['Received', 'Paid'], datasets: [{ data: [s.received_vs_paid.received, s.received_vs_paid.paid], backgroundColor: ['#4e73df', '#f6c23e'], borderWidth: 2, borderColor: cssVar('--bs-body-bg', '#fff') }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom' } } }
        });
    }

    /* ---------- Load everything via AJAX ---------- */
    function loadAnalytics() {
        $('#btnRefresh').prop('disabled', true).find('i').addClass('spin');
        $.getJSON(window.ERP_DASH.analyticsUrl, { block: 'all' })
            .done(function (res) {
                var d = res.data || {};
                if (d.logins)      { renderLogins(d.logins); }
                if (d.usersByType) { renderDoughnut('chartUsersType', d.usersByType); }
                if (d.usersByRole) { renderPie('chartUsersRole', d.usersByRole); }
                if (d.growth)      { renderBar('chartGrowth', d.growth, false); }
                if (d.activity)    { renderBar('chartActivity', d.activity, true); }
                if (d.topUsers)    { renderTopUsers(d.topUsers); }
                if (d.kpis)        { renderHealth(d.kpis); }
                if (d.financeKpis && d.financeSeries) { renderFinance(d.financeKpis, d.financeSeries); }
                $('#lastUpdated').text('Updated ' + new Date().toLocaleTimeString());
            })
            .fail(function () {
                if (window.erpNotify) { window.erpNotify('error', 'Failed to load analytics data.'); }
            })
            .always(function () {
                $('#btnRefresh').prop('disabled', false).find('i').removeClass('spin');
            });
    }

    $(function () {
        animateCounts();
        loadAnalytics();
        $('#btnRefresh').on('click', function () {
            $('.chart-box, .chart-box-sm').each(function () {
                if (!$(this).find('.chart-skeleton').length) {
                    $(this).prepend('<div class="chart-skeleton skeleton skeleton-chart"></div>');
                }
            });
            loadAnalytics();
            if (window.erpNotify) { window.erpNotify('info', 'Refreshing analytics…'); }
        });

        // Re-render charts when the theme colour is saved (keeps charts on-brand).
        $(document).on('click', '#themeSave', function () {
            setTimeout(loadAnalytics, 150);
        });
    });
})(jQuery);
