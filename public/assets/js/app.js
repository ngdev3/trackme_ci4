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
