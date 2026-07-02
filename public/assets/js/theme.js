/* ------------------------------------------------------------------
 * ERP Theme Engine (vanilla JS)
 * Live-customises primary/secondary/font/background colours via CSS
 * variables. Persists to localStorage and applies instantly (no reload).
 *
 * Public API: window.ErpTheme = { apply, save, reset, load, current, DEFAULTS }
 * ------------------------------------------------------------------ */
(function () {
    'use strict';

    var KEY = 'erp-custom-theme';

    var DEFAULTS = {
        primary:   '#0d6efd',
        secondary: '#6c757d',
        font:      '',   // '' = inherit Bootstrap default (respects dark mode)
        bg:        ''    // '' = inherit Bootstrap default
    };

    function hexToRgb(hex) {
        if (!hex) { return null; }
        var m = hex.replace('#', '');
        if (m.length === 3) { m = m[0] + m[0] + m[1] + m[1] + m[2] + m[2]; }
        var n = parseInt(m, 16);
        return (n >> 16 & 255) + ', ' + (n >> 8 & 255) + ', ' + (n & 255);
    }

    // Darken a hex colour by pct (0..1) for hover states.
    function shade(hex, pct) {
        if (!hex) { return hex; }
        var m = hex.replace('#', '');
        if (m.length === 3) { m = m[0] + m[0] + m[1] + m[1] + m[2] + m[2]; }
        var r = parseInt(m.substr(0, 2), 16), g = parseInt(m.substr(2, 2), 16), b = parseInt(m.substr(4, 2), 16);
        r = Math.round(r * (1 - pct)); g = Math.round(g * (1 - pct)); b = Math.round(b * (1 - pct));
        return '#' + [r, g, b].map(function (x) { return ('0' + x.toString(16)).slice(-2); }).join('');
    }

    function load() {
        try {
            return Object.assign({}, DEFAULTS, JSON.parse(localStorage.getItem(KEY)) || {});
        } catch (e) {
            return Object.assign({}, DEFAULTS);
        }
    }

    function current() { return load(); }

    /* Apply a theme object to the document root as CSS variables. */
    function apply(theme) {
        theme = Object.assign({}, DEFAULTS, theme || {});
        var root = document.documentElement;

        if (theme.primary) {
            root.style.setProperty('--bs-primary', theme.primary);
            root.style.setProperty('--bs-primary-rgb', hexToRgb(theme.primary));
            root.style.setProperty('--bs-link-color', theme.primary);
            root.style.setProperty('--bs-link-color-rgb', hexToRgb(theme.primary));
            root.style.setProperty('--bs-link-hover-color', shade(theme.primary, 0.15));
            root.style.setProperty('--erp-primary', theme.primary);
            root.style.setProperty('--erp-primary-dark', shade(theme.primary, 0.12));
        }
        if (theme.secondary) {
            root.style.setProperty('--bs-secondary', theme.secondary);
            root.style.setProperty('--bs-secondary-rgb', hexToRgb(theme.secondary));
            root.style.setProperty('--erp-secondary', theme.secondary);
        }
        if (theme.bg) {
            root.style.setProperty('--bs-body-bg', theme.bg);
            root.style.setProperty('--bs-body-bg-rgb', hexToRgb(theme.bg));
        } else {
            root.style.removeProperty('--bs-body-bg');
            root.style.removeProperty('--bs-body-bg-rgb');
        }
        if (theme.font) {
            root.style.setProperty('--bs-body-color', theme.font);
            root.style.setProperty('--bs-body-color-rgb', hexToRgb(theme.font));
        } else {
            root.style.removeProperty('--bs-body-color');
            root.style.removeProperty('--bs-body-color-rgb');
        }
    }

    function save(theme) {
        localStorage.setItem(KEY, JSON.stringify(theme));
        apply(theme);
    }

    function reset() {
        localStorage.removeItem(KEY);
        var root = document.documentElement;
        ['--bs-primary', '--bs-primary-rgb', '--bs-link-color', '--bs-link-color-rgb',
         '--bs-link-hover-color', '--bs-secondary', '--bs-secondary-rgb',
         '--bs-body-bg', '--bs-body-bg-rgb', '--bs-body-color', '--bs-body-color-rgb',
         '--erp-primary', '--erp-primary-dark', '--erp-secondary'
        ].forEach(function (v) { root.style.removeProperty(v); });
        apply(DEFAULTS);
    }

    // Apply saved theme immediately.
    apply(load());

    window.ErpTheme = { apply: apply, save: save, reset: reset, load: load, current: current, DEFAULTS: DEFAULTS };

    /* ---- Wire up the theme panel controls once the DOM is ready ---- */
    document.addEventListener('DOMContentLoaded', function () {
        var panel = document.getElementById('themePanel');
        if (!panel) { return; }

        var t = load();
        var map = { primary: 'themePrimary', secondary: 'themeSecondary', font: 'themeFont', bg: 'themeBg' };

        // Initialise inputs from stored theme.
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el && t[k]) { el.value = t[k]; }
        });

        function collect() {
            return {
                primary:   val('themePrimary'),
                secondary: val('themeSecondary'),
                font:      (document.getElementById('themeFontEnable') || {}).checked ? val('themeFont') : '',
                bg:        (document.getElementById('themeBgEnable') || {}).checked ? val('themeBg') : ''
            };
        }
        function val(id) { var e = document.getElementById(id); return e ? e.value : ''; }

        // Live preview on input.
        panel.querySelectorAll('input[type="color"], input[type="checkbox"]').forEach(function (el) {
            el.addEventListener('input', function () { apply(collect()); });
            el.addEventListener('change', function () { apply(collect()); });
        });

        var saveBtn = document.getElementById('themeSave');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                save(collect());
                if (window.erpNotify) { window.erpNotify('success', 'Theme saved.'); }
            });
        }

        var resetBtn = document.getElementById('themeReset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                reset();
                var d = DEFAULTS;
                Object.keys(map).forEach(function (k) {
                    var el = document.getElementById(map[k]);
                    if (el) { el.value = d[k] || (k === 'font' ? '#212529' : '#ffffff'); }
                });
                var fe = document.getElementById('themeFontEnable'); if (fe) { fe.checked = false; }
                var be = document.getElementById('themeBgEnable');   if (be) { be.checked = false; }
                if (window.erpNotify) { window.erpNotify('info', 'Theme reset to default.'); }
            });
        }
    });
})();
