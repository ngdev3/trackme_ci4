/* ------------------------------------------------------------------
 * ERP Admin — global front-end behaviour.
 * Handles: dark/light appearance, page loader, delete confirmation.
 * Toast/confirm helpers live in notify.js; theming in theme.js.
 * ------------------------------------------------------------------ */
(function () {
    'use strict';

    const THEME_KEY = 'erp-theme';
    const root = document.documentElement;

    /* ---------------- Appearance (dark / light) ---------------- */
    function applyMode(mode) {
        root.setAttribute('data-bs-theme', mode);
        localStorage.setItem(THEME_KEY, mode);
        document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
            el.className = mode === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        });
    }
    applyMode(localStorage.getItem(THEME_KEY) || 'light');

    document.addEventListener('click', function (e) {
        // Toggle button in the navbar.
        const toggle = e.target.closest('[data-theme-toggle]');
        if (toggle) {
            e.preventDefault();
            applyMode(root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
            return;
        }
        // Explicit light/dark buttons in the theme panel.
        const setMode = e.target.closest('[data-set-mode]');
        if (setMode) {
            e.preventDefault();
            applyMode(setMode.getAttribute('data-set-mode'));
        }
    });

    /* ---------------- Dropdown fallback ---------------- */
    (function () {
        if (window.bootstrap && window.bootstrap.Dropdown) { return; }

        function closeDropdowns(except) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function (menu) {
                if (except && menu === except) { return; }
                menu.classList.remove('show');
                const dropdown = menu.closest('.dropdown');
                if (dropdown) { dropdown.classList.remove('show'); }
                const trigger = dropdown ? dropdown.querySelector('[data-bs-toggle="dropdown"]') : null;
                if (trigger) { trigger.setAttribute('aria-expanded', 'false'); }
            });
        }

        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('[data-bs-toggle="dropdown"]');
            if (!trigger) {
                if (!e.target.closest('.dropdown-menu')) { closeDropdowns(); }
                return;
            }

            e.preventDefault();
            const dropdown = trigger.closest('.dropdown');
            const menu = dropdown ? dropdown.querySelector('.dropdown-menu') : null;
            if (!menu) { return; }

            const isOpen = menu.classList.contains('show');
            closeDropdowns(menu);
            menu.classList.toggle('show', !isOpen);
            dropdown.classList.toggle('show', !isOpen);
            trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeDropdowns(); }
        });
    })();

    /* ---------------- Firm switcher ---------------- */
    document.addEventListener('click', function (e) {
        const option = e.target.closest('[data-firm-option]');
        if (!option) { return; }
        e.preventDefault();

        const name = option.getAttribute('data-firm-name') || 'Selected Firm';
        const code = option.getAttribute('data-firm-code') || '';

        document.querySelectorAll('[data-firm-option]').forEach(function (item) {
            item.classList.toggle('active', item === option);
        });
        document.querySelectorAll('[data-current-firm-name]').forEach(function (el) {
            el.textContent = name;
        });
        document.querySelectorAll('[data-current-firm-code]').forEach(function (el) {
            el.textContent = code;
        });

        try {
            localStorage.setItem('erp-current-firm', JSON.stringify({ name: name, code: code }));
        } catch (err) {}

        if (window.erpNotify) {
            window.erpNotify('success', 'Firm changed to ' + name);
        }
    });

    try {
        const savedFirm = JSON.parse(localStorage.getItem('erp-current-firm'));
        if (savedFirm && savedFirm.name) {
            document.querySelectorAll('[data-current-firm-name]').forEach(function (el) { el.textContent = savedFirm.name; });
            document.querySelectorAll('[data-current-firm-code]').forEach(function (el) { el.textContent = savedFirm.code || ''; });
            document.querySelectorAll('[data-firm-option]').forEach(function (item) {
                item.classList.toggle('active', item.getAttribute('data-firm-name') === savedFirm.name);
            });
        }
    } catch (err) {}

    /* ---------------- Profile palette shortcuts ---------------- */
    function hexToRgb(hex) {
        const clean = (hex || '').replace('#', '');
        if (clean.length !== 6) { return '13, 110, 253'; }
        const n = parseInt(clean, 16);
        return ((n >> 16) & 255) + ', ' + ((n >> 8) & 255) + ', ' + (n & 255);
    }

    document.addEventListener('click', function (e) {
        const choice = e.target.closest('[data-palette-choice]');
        if (!choice) { return; }
        e.preventDefault();

        const primary = choice.getAttribute('data-primary') || '#0d6efd';
        const secondary = choice.getAttribute('data-secondary') || '#6c757d';
        root.style.setProperty('--bs-primary', primary);
        root.style.setProperty('--bs-primary-rgb', hexToRgb(primary));
        root.style.setProperty('--bs-secondary', secondary);
        root.style.setProperty('--erp-primary', primary);
        root.style.setProperty('--erp-primary-rgb', hexToRgb(primary));

        try {
            localStorage.setItem('erp-custom-theme', JSON.stringify({ primary: primary, secondary: secondary }));
        } catch (err) {}

        if (window.erpNotify) {
            window.erpNotify('success', 'Color combination applied.');
        }
    });

    /* ---------------- Demo session timer ---------------- */
    (function () {
        const activeTargets = document.querySelectorAll('[data-session-active], [data-session-active-detail]');
        const leftTargets = document.querySelectorAll('[data-session-left], [data-session-left-detail]');
        if (!activeTargets.length && !leftTargets.length) { return; }

        let activeSeconds = 1 * 3600 + 5 * 60 + 56;
        let leftSeconds = 3 * 3600 + 54 * 60 + 4;

        function fmt(total) {
            total = Math.max(0, total);
            const h = Math.floor(total / 3600);
            const m = Math.floor((total % 3600) / 60);
            const s = total % 60;
            return [h, m, s].map(function (n) { return n < 10 ? '0' + n : '' + n; }).join(':');
        }

        function renderSession() {
            const activeText = fmt(activeSeconds);
            const leftText = fmt(leftSeconds);
            activeTargets.forEach(function (el) { el.textContent = activeText; });
            leftTargets.forEach(function (el) { el.textContent = leftText; });
        }

        renderSession();
        window.setInterval(function () {
            activeSeconds += 1;
            leftSeconds = Math.max(0, leftSeconds - 1);
            renderSession();
        }, 1000);
    })();

    /* ---------------- Notification polling ---------------- */
    (function () {
        const badge = document.querySelector('[data-notification-badge]');
        const countLabel = document.querySelector('[data-notification-count]');
        const trigger = document.querySelector('.topbar-notification .topbar-trigger');
        if (!trigger && !countLabel) { return; }

        let lastSeenId = Number(localStorage.getItem('erp-last-notification-id') || 0);

        function setUnread(count) {
            if (countLabel) {
                countLabel.textContent = count + ' unread';
            }
            if (trigger) {
                trigger.classList.toggle('has-alert', count > 0);
            }
            if (badge) {
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            }
        }

        function pollNotifications() {
            fetch((window.APP_BASE_URL || '') + '/api/notifications', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (res) { return res.ok ? res.json() : null; })
                .then(function (payload) {
                    if (!payload) { return; }
                    setUnread(Number(payload.unread || 0));
                    if (payload.items && payload.items.length) {
                        const newest = payload.items[0];
                        const newestId = Number(newest.id || 0);
                        if (lastSeenId && newestId > lastSeenId && window.erpNotify) {
                            window.erpNotify(newest.type === 'error' ? 'error' : (newest.type === 'warning' ? 'warning' : 'info'), newest.title || 'New notification');
                        }
                        if (newestId > lastSeenId) {
                            lastSeenId = newestId;
                            localStorage.setItem('erp-last-notification-id', String(lastSeenId));
                        }
                    }
                })
                .catch(function () {});
        }

        pollNotifications();
        window.setInterval(pollNotifications, 30000);
    })();

    /* ---------------- Page loading bar ---------------- */
    const loader = document.getElementById('pageLoader');
    if (loader) {
        // Hide once the page is ready.
        window.addEventListener('load', function () { loader.classList.remove('active'); });
        // Show again when navigating away via a normal link.
        document.addEventListener('click', function (e) {
            const a = e.target.closest('a[href]');
            if (!a) { return; }
            const href = a.getAttribute('href');
            if (!href || href.startsWith('#') || a.target === '_blank' ||
                a.hasAttribute('data-bs-toggle') || href.startsWith('javascript')) { return; }
            loader.classList.add('active');
        });
    }

    /* ---------------- Sidebar menu interactions ---------------- */
    const sidebar = document.querySelector('.erp-sidebar');
    if (sidebar) {
        const menu = sidebar.querySelector('.sidebar-menu');
        const search = sidebar.querySelector('.sidebar-search input');

        sidebar.addEventListener('click', function (e) {
            const link = e.target.closest('.sidebar-menu .nav-link');
            if (!link) { return; }

            const pulse = document.createElement('span');
            const rect = link.getBoundingClientRect();
            pulse.className = 'menu-pulse';
            pulse.style.left = (e.clientX - rect.left) + 'px';
            pulse.style.top = (e.clientY - rect.top) + 'px';
            link.appendChild(pulse);
            pulse.addEventListener('animationend', function () { pulse.remove(); }, { once: true });
        });

        if (menu && search) {
            search.addEventListener('input', function () {
                const query = search.value.trim().toLowerCase();
                menu.querySelectorAll(':scope > .nav-item').forEach(function (item) {
                    const text = item.textContent.toLowerCase();
                    const matched = !query || text.indexOf(query) !== -1;
                    item.classList.toggle('is-hidden', !matched);

                    if (!query && item.hasAttribute('data-search-open')) {
                        item.classList.remove('menu-open');
                        item.removeAttribute('data-search-open');
                    }

                    if (query && matched && item.querySelector('.nav-treeview') && !item.classList.contains('menu-open')) {
                        item.classList.add('menu-open');
                        item.setAttribute('data-search-open', 'true');
                    }
                });
            });
        }
    }

    /* ---------------- Delete confirmation ---------------- */
    // Buttons with .btn-delete + data-url submit a hidden POST form after confirm.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete');
        if (!btn) { return; }
        e.preventDefault();
        const url = btn.getAttribute('data-url');
        if (!url) { return; }

        if (window.erpConfirm) {
            window.erpConfirm({
                title: 'Delete this record?',
                text: 'It will be moved to trash. You can restore it from the database if needed.',
                icon: 'warning',
                confirmText: 'Yes, delete it',
                confirmColor: '#dc3545',
                onConfirm: function () { submitDelete(url); }
            });
        } else if (confirm('Delete this record?')) {
            submitDelete(url);
        }
    });

    function submitDelete(url) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = meta.getAttribute('data-name');
            input.value = meta.getAttribute('content');
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }
})();
