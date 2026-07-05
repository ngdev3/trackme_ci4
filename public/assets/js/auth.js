/* ==================================================================
 * ERP Admin — Cosmic Auth interactions
 * Loader fade-out · password show/hide · live clock · live weather.
 * All guards are null-safe so a page can include only the parts it uses.
 * ================================================================== */
(function () {
    'use strict';

    /* ---------- loader fade-out ---------- */
    window.addEventListener('load', function () {
        var loader = document.getElementById('authLoader');
        if (loader) setTimeout(function () { loader.classList.add('fadeOut'); }, 300);
    });

    document.addEventListener('DOMContentLoaded', function () {

        /* ---------- password show / hide (supports multiple fields) ---------- */
        document.querySelectorAll('[data-toggle-pass]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-toggle-pass'));
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                var icon = btn.querySelector('i');
                if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });

        /* ---------- live date & time ---------- */
        (function () {
            var days   = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            var de = document.getElementById('wDate');
            var te = document.getElementById('wTime');
            if (!de && !te) return;
            function pad(n) { return n < 10 ? '0' + n : n; }
            function tick() {
                var d = new Date();
                var h = d.getHours(), ap = h >= 12 ? 'PM' : 'AM', h12 = h % 12 || 12;
                if (de) de.textContent = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
                if (te) te.textContent = h12 + ':' + pad(d.getMinutes()) + ' ' + ap;
            }
            tick();
            setInterval(tick, 30000);
        })();

        /* ---------- live weather (free, no API key) ---------- */
        (function () {
            if (!document.getElementById('weather')) return;
            var WMO = {
                0: ['Clear sky', '☀️'], 1: ['Mainly clear', '🌤️'], 2: ['Partly cloudy', '⛅'], 3: ['Overcast', '☁️'],
                45: ['Fog', '🌫️'], 48: ['Rime fog', '🌫️'],
                51: ['Light drizzle', '🌦️'], 53: ['Drizzle', '🌦️'], 55: ['Heavy drizzle', '🌧️'],
                56: ['Freezing drizzle', '🌧️'], 57: ['Freezing drizzle', '🌧️'],
                61: ['Light rain', '🌦️'], 63: ['Rain', '🌧️'], 65: ['Heavy rain', '🌧️'],
                66: ['Freezing rain', '🌧️'], 67: ['Freezing rain', '🌧️'],
                71: ['Light snow', '🌨️'], 73: ['Snow', '🌨️'], 75: ['Heavy snow', '❄️'], 77: ['Snow grains', '🌨️'],
                80: ['Rain showers', '🌦️'], 81: ['Rain showers', '🌧️'], 82: ['Heavy showers', '⛈️'],
                85: ['Snow showers', '🌨️'], 86: ['Snow showers', '❄️'],
                95: ['Thunderstorm', '⛈️'], 96: ['Thunderstorm', '⛈️'], 99: ['Thunderstorm', '⛈️']
            };
            function set(id, txt) { var el = document.getElementById(id); if (el) el.innerHTML = txt; }
            function render(temp, code, city, hi, lo) {
                var info = WMO[code] || ['Weather', '🌍'];
                set('wEmoji', info[1]);
                set('wTemp', Math.round(temp) + '&deg;C');
                set('wCond', info[0]);
                set('wCity', '📍 ' + (city || 'Your location'));
                if (hi != null && lo != null) set('wHL', 'H:<b>' + Math.round(hi) + '&deg;</b> L:<b>' + Math.round(lo) + '&deg;</b>');
            }
            function getWeather(lat, lon, city) {
                fetch('https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lon + '&current=temperature_2m,weather_code&daily=temperature_2m_max,temperature_2m_min&timezone=auto')
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        render(d.current.temperature_2m, d.current.weather_code, city,
                               d.daily.temperature_2m_max[0], d.daily.temperature_2m_min[0]);
                    })
                    .catch(function () { set('wCond', 'Weather unavailable'); set('wEmoji', '🌐'); });
            }
            fetch('https://get.geojs.io/v1/ip/geo.json')
                .then(function (r) { return r.json(); })
                .then(function (loc) {
                    var lat = parseFloat(loc.latitude), lon = parseFloat(loc.longitude);
                    var city = loc.city || loc.region || loc.country || '';
                    if (isNaN(lat) || isNaN(lon)) throw new Error('no geo');
                    getWeather(lat, lon, city);
                })
                .catch(function () { getWeather(28.6139, 77.2090, 'New Delhi'); });
        })();
    });
})();
