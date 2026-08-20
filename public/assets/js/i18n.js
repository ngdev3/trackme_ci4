/* ==================================================================
 * ERP Admin — In-app language switcher (whole-UI translation)
 * ------------------------------------------------------------------
 * Drives Google's translate engine through the `googtrans` cookie so
 * the ENTIRE interface is translated client-side into any of the 12
 * supported languages, with the choice persisted across pages and
 * sessions. No per-string translation files needed.
 * ================================================================== */
(function () {
    'use strict';

    var RTL = ['ar', 'ur', 'fa', 'he'];

    function domainVariants() {
        var host = location.hostname;
        var list = ['']; // no-domain (host-only) cookie
        list.push(host);
        var parts = host.split('.');
        if (parts.length > 1) {
            list.push('.' + parts.slice(-2).join('.')); // registrable domain
        }
        return list;
    }

    function writeCookie(name, value) {
        domainVariants().forEach(function (d) {
            document.cookie = name + '=' + value + ';path=/' + (d ? ';domain=' + d : '');
        });
    }

    function clearCookie(name) {
        var past = ';expires=Thu, 01 Jan 1970 00:00:00 GMT';
        domainVariants().forEach(function (d) {
            document.cookie = name + '=' + past + ';path=/' + (d ? ';domain=' + d : '');
        });
    }

    function currentLang() {
        var m = document.cookie.match(/googtrans=\/[^/]*\/([^;]+)/);
        return m ? decodeURIComponent(m[1]) : 'en';
    }

    // Public: switch the whole UI to a language code (e.g. 'hi', 'ar', 'en').
    window.erpSetLanguage = function (code) {
        if (!code || code === 'en') {
            clearCookie('googtrans');
            try { localStorage.setItem('erp-lang', 'en'); } catch (e) {}
        } else {
            writeCookie('googtrans', '/en/' + code);
            try { localStorage.setItem('erp-lang', code); } catch (e) {}
        }
        location.reload();
    };

    document.addEventListener('DOMContentLoaded', function () {
        var cur = currentLang();

        // Reflect current language in the dropdown + wire up clicks.
        document.querySelectorAll('.lang-option').forEach(function (btn) {
            var isActive = btn.getAttribute('data-lang') === cur;
            btn.classList.toggle('active', isActive);
            if (isActive) {
                var label = document.querySelector('[data-lang-current]');
                if (label) label.textContent = btn.getAttribute('data-lang-label');
            }
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                window.erpSetLanguage(btn.getAttribute('data-lang'));
            });
        });

        // Text direction for RTL languages (keeps the fixed shell intact,
        // just flips text flow/alignment).
        document.documentElement.setAttribute('dir', RTL.indexOf(cur) >= 0 ? 'rtl' : 'ltr');

        // Self-contained dropdown toggle for the login screen (no Bootstrap JS there).
        var authLang = document.getElementById('authLang');
        if (authLang) {
            var toggle = authLang.querySelector('[data-lang-toggle]');
            if (toggle) {
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    authLang.classList.toggle('open');
                });
                document.addEventListener('click', function (e) {
                    if (!authLang.contains(e.target)) authLang.classList.remove('open');
                });
            }
        }

        // ---- Reveal content only after the language module has applied ----
        // The <head> sets html.lang-loading when a non-English cookie exists,
        // so the page stays hidden (spinner shown) until Google finishes
        // translating — no flash of untranslated English.
        //
        // CRITICAL: the reveal must never gate page visibility on the (external,
        // often slow/blocked) Google Translate engine for long — otherwise every
        // navigation, including a firm switch, hangs behind the loader. We reveal
        // the instant translation applies, and otherwise on a SHORT safety
        // timeout (was 3.5s → felt like a "loading hang" after every switch). If
        // translate is slow we accept a brief flash of English rather than a hang.
        if (document.documentElement.classList.contains('lang-loading')) {
            var revealed = false;
            var reveal = function () {
                if (revealed) return;
                revealed = true;
                document.documentElement.classList.remove('lang-loading');
            };
            if (/translated-(ltr|rtl)/.test(document.documentElement.className)) {
                reveal();
            } else if (window.MutationObserver) {
                var obs = new MutationObserver(function () {
                    if (/translated-(ltr|rtl)/.test(document.documentElement.className)) {
                        reveal();
                        obs.disconnect();
                    }
                });
                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            }
            // Reveal as soon as the page is interactive too, so a stalled translate
            // engine can never keep the page hidden beyond a moment.
            if (document.readyState === 'complete') {
                setTimeout(reveal, 300);
            } else {
                window.addEventListener('load', function () { setTimeout(reveal, 300); });
            }
            setTimeout(reveal, 1000); // hard cap — page is never hidden more than ~1s
        }

        // ---- First-visit language chooser popup ----
        var modal = document.getElementById('erpLangModal');
        var chosen = false;
        try { chosen = !!localStorage.getItem('erp-lang-chosen'); } catch (e) {}
        if (modal && !chosen && cur === 'en') {
            var closeModal = function () {
                modal.classList.remove('show');
                try { localStorage.setItem('erp-lang-chosen', '1'); } catch (e) {}
            };
            modal.classList.add('show');
            modal.querySelectorAll('.erp-lang-pick').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var code = btn.getAttribute('data-lang');
                    try { localStorage.setItem('erp-lang-chosen', '1'); } catch (e) {}
                    if (!code || code === 'en') {
                        closeModal(); // already English — just dismiss
                    } else {
                        window.erpSetLanguage(code); // sets cookie + reloads translated
                    }
                });
            });
            // Dismiss (keep English) via the close button or backdrop click.
            var skip = modal.querySelector('[data-lang-skip]');
            if (skip) skip.addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });
        }
    });
})();
