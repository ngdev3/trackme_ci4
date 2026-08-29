/* ------------------------------------------------------------------
 * ERP Admin — global front-end behaviour.
 * Handles: page loader, delete confirmation, navigation helpers.
 * Toast/confirm helpers live in notify.js; appearance lives in theme.js.
 * ------------------------------------------------------------------ */
(function () {
    'use strict';

    /* ---------------- CSP-clean delegated handlers ----------------
     * Replaces inline on* attributes (which a strict Content-Security-Policy
     * cannot nonce) with delegated listeners on this external, allow-listed file.
     *   - [data-fresh-submit]  : re-submit the element's form with a fresh CSRF
     *                            token (was onchange="erpFreshSubmit(this.form)")
     *   - [data-window="print"]: window.print()   (was onclick="window.print()")
     *   - [data-window="close"]: window.close()   (was onclick="window.close()")
     */
    document.addEventListener('change', function (e) {
        var t = e.target;
        if (t && t.matches && t.matches('[data-fresh-submit]') && t.form && typeof window.erpFreshSubmit === 'function') {
            window.erpFreshSubmit(t.form);
        }
    });
    document.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('[data-window]') : null;
        if (! t) { return; }
        var action = t.getAttribute('data-window');
        if (action === 'print') { window.print(); }
        else if (action === 'close') { window.close(); }
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

    /* ---------------- In-app confirm (data-confirm) ----------------
     * Any form or link carrying [data-confirm] shows the app's own popup
     * (erpConfirm / SweetAlert2) instead of the browser's default confirm().
     * Attributes:
     *   data-confirm="Body text"          (required — the question)
     *   data-confirm-title="Title"
     *   data-confirm-btn="Yes, do it"
     *   data-confirm-icon="warning|error|info|success"
     */
    function askConfirm(el, onOk) {
        var opts = {
            title: el.getAttribute('data-confirm-title') || 'Are you sure?',
            text: el.getAttribute('data-confirm') || '',
            icon: el.getAttribute('data-confirm-icon') || 'warning',
            confirmText: el.getAttribute('data-confirm-btn') || 'Yes',
            onConfirm: onOk
        };
        if (window.erpConfirm) { window.erpConfirm(opts); }
        else if (confirm(opts.text || 'Are you sure?')) { onOk(); }
    }

    // Forms: intercept submit, confirm in-app, then submit for real.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) { return; }
        e.preventDefault();
        e.stopPropagation();
        askConfirm(form, function () { form.submit(); }); // native submit() skips this listener
    }, true);

    // Links (e.g. logout): intercept click, confirm in-app, then navigate.
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[data-confirm]');
        if (!link) { return; }
        e.preventDefault();
        askConfirm(link, function () {
            var href = link.getAttribute('href');
            if (href && href !== '#') { window.location.href = href; }
        });
    });

    // Buttons carrying data-confirm (incl. those with formaction/formnovalidate):
    // confirm in-app, then submit their form using this button as the submitter.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-confirm]');
        if (!btn) { return; }
        e.preventDefault();
        askConfirm(btn, function () {
            var form = btn.form;
            if (!form) { return; }
            if (typeof form.requestSubmit === 'function') { form.requestSubmit(btn); }
            else { form.submit(); }
        });
    }, true);

    /* ---------------- Form input guard (app-wide) ----------------
     * Enforces each field's own HTML5 rules (required / min / max /
     * maxlength / pattern / type) consistently on every form, and clamps
     * number inputs to their declared range so an oversized value can't be
     * submitted or distort the layout. This is the client half; controllers
     * must still validate server-side. Opt a whole form out with
     * [data-no-validate]; opt a single number field out with [data-no-clamp].
     */
    (function () {
        function clampNumber(input) {
            if (input.disabled || input.readOnly || input.hasAttribute('data-no-clamp')) { return; }
            const raw = String(input.value).trim();
            if (raw === '') { return; }
            const v = parseFloat(raw);
            if (isNaN(v)) { return; }
            let out = v;
            const min = input.getAttribute('min');
            const max = input.getAttribute('max');
            if (min !== null && min !== '' && out < parseFloat(min)) { out = parseFloat(min); }
            if (max !== null && max !== '' && out > parseFloat(max)) { out = parseFloat(max); }
            if (out !== v) {
                input.value = String(out);
                if (window.erpNotify) { window.erpNotify('warning', 'Value adjusted to the allowed range.'); }
            }
        }

        document.addEventListener('change', function (e) {
            const t = e.target;
            if (t && t.matches && t.matches('input[type="number"]')) { clampNumber(t); }
        });

        // Capture phase: stop an invalid submit before any page-specific handler runs.
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-validate')) { return; }
            if (e.submitter && e.submitter.formNoValidate) { return; } // honour a formnovalidate button
            form.querySelectorAll('input[type="number"]').forEach(clampNumber);
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                if (typeof form.reportValidity === 'function') { form.reportValidity(); }
            }
        }, true);
    })();

    /* ---------------- Date pickers (flatpickr, app-wide) ----------------
     * Turns every native date / datetime field into the modern flatpickr
     * calendar that opens on a single click anywhere in the field. Values stay
     * in the server's format (Y-m-d, or "Y-m-d H:i" for datetime). Opt a field
     * out with [data-no-fp]. Also initialises fields injected later (modals/AJAX).
     */
    (function () {
        if (!window.flatpickr) { return; }

        function initIn(root) {
            var scope = root && root.querySelectorAll ? root : document;
            scope.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(function (el) {
                if (el._flatpickr || el.hasAttribute('data-no-fp')) { return; }
                var isDateTime = el.getAttribute('type') === 'datetime-local';
                // Drop the native control so only flatpickr's calendar shows.
                el.type = 'text';
                if (!el.getAttribute('placeholder')) {
                    el.setAttribute('placeholder', isDateTime ? 'YYYY-MM-DD HH:MM' : 'YYYY-MM-DD');
                }
                window.flatpickr(el, {
                    dateFormat: isDateTime ? 'Y-m-d H:i' : 'Y-m-d',
                    enableTime: isDateTime,
                    time_24hr: true,
                    minuteIncrement: 5,
                    allowInput: true,      // still typeable
                    clickOpens: true,      // open on field click
                    disableMobile: true    // use the advanced UI on mobile too
                });
            });
        }

        initIn(document);

        // Re-init fields added after load (Bootstrap modals, AJAX partials).
        if (window.MutationObserver) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    Array.prototype.forEach.call(m.addedNodes || [], function (n) {
                        if (n.nodeType !== 1) { return; }
                        if (n.matches && n.matches('input[type="date"], input[type="datetime-local"]')) { initIn(n.parentNode); }
                        else if (n.querySelector && n.querySelector('input[type="date"], input[type="datetime-local"]')) { initIn(n); }
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        }
    })();
})();
