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
        $('[data-count]').each(function () {
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

    /* ---------- Weather card ---------- */
    function weatherLabel(code) {
        var labels = {
            0: 'Clear sky', 1: 'Mainly clear', 2: 'Partly cloudy', 3: 'Overcast',
            45: 'Foggy', 48: 'Rime fog', 51: 'Light drizzle', 53: 'Drizzle',
            55: 'Dense drizzle', 61: 'Light rain', 63: 'Rain', 65: 'Heavy rain',
            71: 'Light snow', 73: 'Snow', 75: 'Heavy snow', 80: 'Rain showers',
            81: 'Rain showers', 82: 'Heavy showers', 95: 'Thunderstorm'
        };
        return labels[code] || 'Mixed conditions';
    }
    function weatherTheme(code) {
        code = Number(code);
        if (code === 0 || code === 1) { return { cls: 'weather-sunny', icon: 'bi-sun' }; }
        if (code === 2 || code === 3) { return { cls: 'weather-cloudy', icon: 'bi-clouds' }; }
        if (code === 45 || code === 48) { return { cls: 'weather-foggy', icon: 'bi-cloud-fog2' }; }
        if (code >= 71 && code <= 86) { return { cls: 'weather-snowy', icon: 'bi-cloud-snow' }; }
        if (code >= 95) { return { cls: 'weather-stormy', icon: 'bi-cloud-lightning-rain' }; }
        if (code >= 51 && code <= 82) { return { cls: 'weather-rainy', icon: 'bi-cloud-rain-heavy' }; }
        return { cls: 'weather-cloudy', icon: 'bi-cloud-sun' };
    }
    function weatherPalette(themeClass) {
        var palettes = {
            'weather-sunny': {
                primary: '#e85d24',
                rgb: '232, 93, 36',
                bg: '#fff1e7',
                surface: '#fffaf4',
                surface2: '#fff3e5',
                border: '#f5c7a7',
                ink: '#3b2417',
                muted: '#8a5b3f',
                hero1: 'rgba(232, 93, 36, .96)',
                hero2: 'rgba(162, 70, 25, .96)'
            },
            'weather-cloudy': {
                primary: '#526f8b',
                rgb: '82, 111, 139',
                bg: '#eef4f8',
                surface: '#fbfdff',
                surface2: '#eef4f8',
                border: '#cad8e4',
                ink: '#223142',
                muted: '#64778b',
                hero1: 'rgba(82, 111, 139, .96)',
                hero2: 'rgba(43, 62, 82, .96)'
            },
            'weather-rainy': {
                primary: '#1f7aa8',
                rgb: '31, 122, 168',
                bg: '#e9f5fb',
                surface: '#f8fcff',
                surface2: '#e7f3f9',
                border: '#bfdceb',
                ink: '#173244',
                muted: '#557083',
                hero1: 'rgba(31, 122, 168, .96)',
                hero2: 'rgba(24, 74, 105, .96)'
            },
            'weather-stormy': {
                primary: '#475569',
                rgb: '71, 85, 105',
                bg: '#e9edf3',
                surface: '#f8fafc',
                surface2: '#edf1f6',
                border: '#c5ccd8',
                ink: '#172033',
                muted: '#596579',
                hero1: 'rgba(71, 85, 105, .98)',
                hero2: 'rgba(30, 41, 59, .98)'
            },
            'weather-snowy': {
                primary: '#4f9dcc',
                rgb: '79, 157, 204',
                bg: '#edf9ff',
                surface: '#ffffff',
                surface2: '#eef8fd',
                border: '#c9e6f4',
                ink: '#1d3445',
                muted: '#607d91',
                hero1: 'rgba(79, 157, 204, .95)',
                hero2: 'rgba(76, 116, 150, .95)'
            },
            'weather-foggy': {
                primary: '#78909c',
                rgb: '120, 144, 156',
                bg: '#f0f3f4',
                surface: '#ffffff',
                surface2: '#f0f3f5',
                border: '#d3dcdf',
                ink: '#26343a',
                muted: '#6c7d84',
                hero1: 'rgba(120, 144, 156, .95)',
                hero2: 'rgba(82, 101, 112, .95)'
            }
        };
        return palettes[themeClass] || palettes['weather-cloudy'];
    }
    function applyWeatherPalette(themeClass) {
        var p = weatherPalette(themeClass);
        var rootStyle = document.documentElement.style;
        document.documentElement.setAttribute('data-weather-theme', themeClass.replace('weather-', ''));
        rootStyle.setProperty('--bs-primary', p.primary);
        rootStyle.setProperty('--bs-primary-rgb', p.rgb);
        rootStyle.setProperty('--erp-primary', p.primary);
        rootStyle.setProperty('--erp-primary-rgb', p.rgb);
        rootStyle.setProperty('--erp-app-bg', p.bg);
        rootStyle.setProperty('--erp-surface', p.surface);
        rootStyle.setProperty('--erp-surface-2', p.surface2);
        rootStyle.setProperty('--erp-border', p.border);
        rootStyle.setProperty('--erp-ink', p.ink);
        rootStyle.setProperty('--erp-muted', p.muted);
        rootStyle.setProperty('--weather-hero-1', p.hero1);
        rootStyle.setProperty('--weather-hero-2', p.hero2);
    }
    function weatherIcon(code) {
        return weatherTheme(code).icon;
    }
    function applyWeatherTheme(code) {
        var theme = weatherTheme(code);
        var $card = $('#weatherCard');
        if (!$card.length) { return; }
        $card.removeClass('weather-loading weather-sunny weather-cloudy weather-rainy weather-stormy weather-snowy weather-foggy')
            .addClass(theme.cls);
        $card.find('.weather-orb i').attr('class', 'bi ' + theme.icon);
        $('#weatherThemeLabel').text('Theme: ' + weatherLabel(code));
        applyWeatherPalette(theme.cls);
    }
    function setWeatherFallback() {
        $('#weatherPlace').text('Workspace forecast');
        $('#weatherTemp').text('28');
        $('#weatherHumidity').text('62%');
        $('#weatherWind').text('10 km/h');
        $('#weatherRainChance').text('35%');
        $('#weatherSummary').text('Weather preview is ready. Allow location access for live local conditions.');
        applyWeatherTheme(2);
        renderWeatherWeek({
            time: ['Today', '+1', '+2', '+3', '+4', '+5', '+6'],
            temperature_2m_max: [28, 29, 30, 31, 30, 29, 28],
            temperature_2m_min: [22, 22, 23, 24, 23, 22, 21],
            weather_code: [2, 1, 0, 3, 61, 2, 1],
            precipitation_probability_max: [35, 15, 5, 20, 65, 35, 15]
        });
    }
    function dayName(value, index) {
        if (index === 0) { return 'Today'; }
        var d = new Date(value + 'T00:00:00');
        if (isNaN(d.getTime())) { return value; }
        return d.toLocaleDateString(undefined, { weekday: 'short' });
    }
    function renderWeatherWeek(daily) {
        var $week = $('#weatherWeek');
        if (!$week.length || !daily || !daily.time) { return; }
        var html = daily.time.slice(0, 7).map(function (day, i) {
            var hi = Math.round((daily.temperature_2m_max || [])[i] || 0);
            var lo = Math.round((daily.temperature_2m_min || [])[i] || 0);
            var code = (daily.weather_code || [])[i];
            var rain = Math.round((daily.precipitation_probability_max || [])[i] || 0);
            return '<span class="weather-day">' +
                '<b>' + dayName(day, i) + '</b>' +
                '<i class="bi ' + weatherIcon(code) + ' weather-day-icon"></i>' +
                '<strong>' + hi + '&deg; / ' + lo + '&deg;</strong>' +
                '<span class="weather-rain-prob"><i class="bi bi-droplet-fill"></i>' + rain + '%</span>' +
            '</span>';
        }).join('');
        $week.html(html);
    }
    function loadWeatherAt(lat, lon, label) {
        $('#weatherSummary').text('Updating weather...');
        $.getJSON('https://api.open-meteo.com/v1/forecast', {
            latitude: lat,
            longitude: lon,
            current: 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code',
            daily: 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
            forecast_days: 7,
            timezone: 'auto'
        }).done(function (res) {
            var c = res.current || {};
            var rainToday = Math.round(((res.daily || {}).precipitation_probability_max || [])[0] || 0);
            $('#weatherPlace').text(label || 'Current location');
            $('#weatherTemp').text(Math.round(c.temperature_2m || 0));
            $('#weatherHumidity').text(Math.round(c.relative_humidity_2m || 0) + '%');
            $('#weatherWind').text(Math.round(c.wind_speed_10m || 0) + ' km/h');
            $('#weatherRainChance').text(rainToday + '%');
            $('#weatherSummary').text(weatherLabel(c.weather_code));
            applyWeatherTheme(c.weather_code);
            renderWeatherWeek(res.daily);
        }).fail(setWeatherFallback);
    }
    function loadCityWeather(city) {
        var q = $.trim(city || '');
        if (!q) { return; }
        $('#weatherSummary').text('Searching ' + q + '...');
        $.getJSON('https://geocoding-api.open-meteo.com/v1/search', {
            name: q,
            count: 1,
            language: 'en',
            format: 'json'
        }).done(function (res) {
            var place = res.results && res.results[0];
            if (!place) {
                $('#weatherSummary').text('City not found. Try another city name.');
                return;
            }
            var label = place.name + (place.admin1 ? ', ' + place.admin1 : '');
            try { localStorage.setItem('erp-weather-city', q); } catch (err) {}
            loadWeatherAt(place.latitude, place.longitude, label);
        }).fail(function () {
            $('#weatherSummary').text('Could not search city right now.');
        });
    }
    function loadWeather(useSavedCity) {
        if (useSavedCity) {
            try {
                var savedCity = localStorage.getItem('erp-weather-city');
                if (savedCity) {
                    loadCityWeather(savedCity);
                    return;
                }
            } catch (err) {}
        }
        if (!navigator.geolocation) {
            loadWeatherAt(28.6139, 77.2090, 'New Delhi');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (pos) {
            try { localStorage.removeItem('erp-weather-city'); } catch (err) {}
            loadWeatherAt(pos.coords.latitude, pos.coords.longitude, 'Current location');
        }, function () {
            loadWeatherAt(28.6139, 77.2090, 'New Delhi');
        }, { timeout: 3500, maximumAge: 600000 });
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
        loadWeather(true);
        $('#weatherChangeBtn').on('click', function () {
            $('#weatherCityForm').toggleClass('is-open');
            if ($('#weatherCityForm').hasClass('is-open')) {
                $('#weatherCityInput').trigger('focus');
            }
        });
        $('#weatherLocateBtn').on('click', function () {
            loadWeather(false);
        });
        $('#weatherCityForm').on('submit', function (e) {
            e.preventDefault();
            loadCityWeather($('#weatherCityInput').val());
        });
        $('#btnRefresh').on('click', function () {
            $('.chart-box, .chart-box-sm').each(function () {
                if (!$(this).find('.chart-skeleton').length) {
                    $(this).prepend('<div class="chart-skeleton skeleton skeleton-chart"></div>');
                }
            });
            loadAnalytics();
            loadWeather(true);
            if (window.erpNotify) { window.erpNotify('info', 'Refreshing analytics…'); }
        });

        // Re-render charts when the theme colour is saved (keeps charts on-brand).
        $(document).on('click', '#themeSave', function () {
            setTimeout(loadAnalytics, 150);
        });

        // TrackmeNew-style per-widget refresh icons: spin then reload the page
        // (server-rendered tiles + AJAX charts both refresh).
        $(document).on('click', '[data-tm-refresh]', function () {
            $(this).addClass('spinning');
            if (window.erpNotify) { window.erpNotify('info', 'Refreshing…'); }
            setTimeout(function () { window.location.reload(); }, 250);
        });
    });
})(jQuery);
