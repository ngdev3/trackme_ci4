/* ------------------------------------------------------------------
 * ERP Appearance Engine
 * Server-backed, per-user appearance settings with live preview.
 * The only editable UI is Settings -> Appearance.
 * ------------------------------------------------------------------ */
(function () {
    'use strict';

    var root = document.documentElement;
    var DEFAULTS = {
        theme_mode: 'system',
        font_color: '#1f2a3d',
        background_color: '#eef2f8',
        primary_color: '#0d6efd',
        secondary_color: '#6610f2',
        sidebar_color: '#0e1626',
        header_color: '#ffffff'
    };

    function cleanHex(hex, fallback) {
        return /^#[0-9a-f]{6}$/i.test(hex || '') ? hex : fallback;
    }

    function hexToRgb(hex) {
        var m = cleanHex(hex, '#0d6efd').replace('#', '');
        var n = parseInt(m, 16);
        return (n >> 16 & 255) + ', ' + (n >> 8 & 255) + ', ' + (n & 255);
    }

    function shade(hex, pct) {
        var m = cleanHex(hex, '#0d6efd').replace('#', '');
        var r = parseInt(m.substr(0, 2), 16);
        var g = parseInt(m.substr(2, 2), 16);
        var b = parseInt(m.substr(4, 2), 16);
        r = Math.max(0, Math.min(255, Math.round(r * (1 - pct))));
        g = Math.max(0, Math.min(255, Math.round(g * (1 - pct))));
        b = Math.max(0, Math.min(255, Math.round(b * (1 - pct))));
        return '#' + [r, g, b].map(function (x) { return ('0' + x.toString(16)).slice(-2); }).join('');
    }

    function effectiveMode(mode) {
        if (mode === 'system') {
            return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        return mode === 'dark' ? 'dark' : 'light';
    }

    function currentFromServer() {
        return Object.assign({}, DEFAULTS, window.ERP_APPEARANCE || {});
    }

    function apply(appearance) {
        appearance = Object.assign({}, DEFAULTS, appearance || {});
        var primary = cleanHex(appearance.primary_color, DEFAULTS.primary_color);
        var secondary = cleanHex(appearance.secondary_color, DEFAULTS.secondary_color);
        var font = cleanHex(appearance.font_color, DEFAULTS.font_color);
        var bg = cleanHex(appearance.background_color, DEFAULTS.background_color);
        var sidebar = cleanHex(appearance.sidebar_color, DEFAULTS.sidebar_color);
        var header = cleanHex(appearance.header_color, DEFAULTS.header_color);

        root.setAttribute('data-bs-theme', effectiveMode(appearance.theme_mode));
        root.setAttribute('data-erp-appearance-mode', appearance.theme_mode || 'system');

        root.style.setProperty('--bs-primary', primary);
        root.style.setProperty('--bs-primary-rgb', hexToRgb(primary));
        root.style.setProperty('--bs-link-color', primary);
        root.style.setProperty('--bs-link-color-rgb', hexToRgb(primary));
        root.style.setProperty('--bs-link-hover-color', shade(primary, 0.15));
        root.style.setProperty('--bs-secondary', secondary);
        root.style.setProperty('--bs-secondary-rgb', hexToRgb(secondary));

        root.style.setProperty('--erp-primary', primary);
        root.style.setProperty('--erp-primary-rgb', hexToRgb(primary));
        root.style.setProperty('--erp-secondary', secondary);
        root.style.setProperty('--erp-accent', secondary);
        root.style.setProperty('--erp-app-bg', bg);
        root.style.setProperty('--erp-ink', font);
        root.style.setProperty('--erp-sidebar-custom', sidebar);
        root.style.setProperty('--erp-sidebar-1', shade(sidebar, -0.08));
        root.style.setProperty('--erp-sidebar-2', shade(sidebar, 0.2));
        root.style.setProperty('--erp-header-bg', header);

        root.style.setProperty('--bs-body-bg', bg);
        root.style.setProperty('--bs-body-bg-rgb', hexToRgb(bg));
        root.style.setProperty('--bs-body-color', font);
        root.style.setProperty('--bs-body-color-rgb', hexToRgb(font));

        window.ERP_APPEARANCE = Object.assign({}, appearance);
    }

    function collect(container) {
        var out = {};
        container.querySelectorAll('[data-appearance-field]').forEach(function (field) {
            if (field.type === 'radio') {
                if (field.checked) { out[field.name] = field.value; }
            } else {
                out[field.name] = field.value;
            }
        });
        return Object.assign({}, DEFAULTS, out);
    }

    function setControls(container, appearance) {
        appearance = Object.assign({}, DEFAULTS, appearance || {});
        container.querySelectorAll('[data-appearance-field]').forEach(function (field) {
            if (field.type === 'radio') {
                field.checked = field.value === appearance.theme_mode;
                var option = field.closest('.appearance-mode-option');
                if (option) { option.classList.toggle('active', field.checked); }
            } else if (appearance[field.name]) {
                field.value = appearance[field.name];
            }
        });
        updateColorLabels(container);
    }

    function updateColorLabels(container) {
        container.querySelectorAll('[data-color-value]').forEach(function (label) {
            var field = container.querySelector('[name="' + label.getAttribute('data-color-value') + '"]');
            if (field) { label.textContent = field.value; }
        });
    }

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? { name: meta.getAttribute('data-name'), token: meta.getAttribute('content') } : null;
    }

    function updateCsrf(payload) {
        if (!payload || !payload.csrf) { return; }
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('data-name', payload.csrf.name);
            meta.setAttribute('content', payload.csrf.token);
        }
    }

    function post(url, data) {
        var body = new FormData();
        Object.keys(data || {}).forEach(function (key) { body.append(key, data[key]); });
        var token = csrf();
        if (token) { body.append(token.name, token.token); }
        return fetch(url, {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok) { throw json; }
                return json;
            });
        });
    }

    apply(currentFromServer());

    if (window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        var onSystemChange = function () {
            if ((window.ERP_APPEARANCE || {}).theme_mode === 'system') {
                apply(window.ERP_APPEARANCE);
            }
        };
        if (mq.addEventListener) { mq.addEventListener('change', onSystemChange); }
        else if (mq.addListener) { mq.addListener(onSystemChange); }
    }

    window.ErpAppearance = {
        DEFAULTS: DEFAULTS,
        apply: apply,
        current: function () { return Object.assign({}, DEFAULTS, window.ERP_APPEARANCE || {}); }
    };

    document.addEventListener('DOMContentLoaded', function () {
        var studio = document.getElementById('appearanceStudio');
        if (!studio) { return; }

        setControls(studio, currentFromServer());

        studio.addEventListener('input', function (e) {
            if (!e.target.matches('[data-appearance-field]')) { return; }
            if (e.target.type === 'radio') { setControls(studio, collect(studio)); }
            updateColorLabels(studio);
            apply(collect(studio));
        });
        studio.addEventListener('change', function (e) {
            if (!e.target.matches('[data-appearance-field]')) { return; }
            if (e.target.type === 'radio') { setControls(studio, collect(studio)); }
            updateColorLabels(studio);
            apply(collect(studio));
        });

        studio.addEventListener('click', function (e) {
            var preset = e.target.closest('[data-appearance-preset]');
            if (preset) {
                e.preventDefault();
                try {
                    var data = JSON.parse(preset.getAttribute('data-appearance-preset'));
                    setControls(studio, data);
                    apply(collect(studio));
                } catch (err) {}
                return;
            }

            if (e.target.closest('#appearanceSave')) {
                e.preventDefault();
                post(studio.getAttribute('data-save-url'), collect(studio))
                    .then(function (payload) {
                        updateCsrf(payload);
                        apply(payload.appearance || collect(studio));
                        document.dispatchEvent(new CustomEvent('erp:appearance-saved'));
                        if (window.erpNotify) { window.erpNotify('success', payload.message || 'Appearance saved.'); }
                    })
                    .catch(function (payload) {
                        if (window.erpNotify) { window.erpNotify('error', payload.message || 'Could not save appearance.'); }
                    });
                return;
            }

            if (e.target.closest('#appearanceReset')) {
                e.preventDefault();
                post(studio.getAttribute('data-reset-url'), {})
                    .then(function (payload) {
                        updateCsrf(payload);
                        setControls(studio, payload.appearance || DEFAULTS);
                        apply(payload.appearance || DEFAULTS);
                        if (window.erpNotify) { window.erpNotify('info', payload.message || 'Appearance reset.'); }
                    })
                    .catch(function (payload) {
                        if (window.erpNotify) { window.erpNotify('error', payload.message || 'Could not reset appearance.'); }
                    });
            }
        });
    });
})();
