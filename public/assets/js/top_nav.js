/**
 * top_nav.js — CI4 port of the CI3 top-navigation behaviours (extracted from
 * application/views/layout.php verbatim): live clock, session meter countdown,
 * global ERP search over the sidebar menu, and the theme picker (preset +
 * custom colours persisted to localStorage). Loaded by app/Views/layouts/admin.php.
 */

/* ===== Theme picker delegating wrappers (called from inline onclick) ===== */
function trackmeApplyCustomTheme() {
    if (window.TrackmeTheme && typeof window.TrackmeTheme.applyFromInputs === 'function') {
        window.TrackmeTheme.applyFromInputs();
    }
}
function trackmeResetTheme() {
    if (window.TrackmeTheme && typeof window.TrackmeTheme.reset === 'function') {
        window.TrackmeTheme.reset();
    }
}
function trackmeSetPreset(primary, font) {
    if (window.TrackmeTheme && typeof window.TrackmeTheme.setPreset === 'function') {
        window.TrackmeTheme.setPreset(primary, font);
    }
}

/* ===== Live top-nav clock ===== */
(function () {
    var dateEl = document.getElementById('topNavDate');
    var timeEl = document.getElementById('topNavTime');
    if (!dateEl || !timeEl) { return; }

    function isCompactClock() {
        return window.matchMedia && window.matchMedia('(max-width: 991px)').matches;
    }
    function updateTopNavClock() {
        var now = new Date();
        var compact = isCompactClock();
        dateEl.textContent = now.toLocaleDateString('en-IN', {
            day: '2-digit', month: 'short', year: compact ? undefined : 'numeric'
        });
        timeEl.textContent = now.toLocaleTimeString('en-IN', {
            hour: '2-digit', minute: '2-digit', second: compact ? undefined : '2-digit', hour12: true
        });
    }
    updateTopNavClock();
    setInterval(updateTopNavClock, 1000);
    window.addEventListener('resize', updateTopNavClock);
})();

/* ===== Session meter (active / remaining countdown + timeout logout) ===== */
(function () {
    var meter = document.getElementById('sessionMeter');
    if (!meter) { return; }

    var startedAt = parseInt(meter.getAttribute('data-started-at'), 10) || Math.floor(Date.now() / 1000);
    var timeout = parseInt(meter.getAttribute('data-timeout'), 10) || 0;
    var expiresAt = parseInt(meter.getAttribute('data-expires-at'), 10) || (startedAt + timeout);
    var logoutUrl = meter.getAttribute('data-logout-url');
    var renewUrl = meter.getAttribute('data-renew-url');
    var activeEl = document.getElementById('sessionActiveTime');
    var leftEl = document.getElementById('sessionTimeLeft');
    var warningModal = document.getElementById('sessionWarningModal');
    var warningLeftEl = document.getElementById('sessionWarningLeft');
    var renewBtn = document.getElementById('sessionRenewBtn');
    var logoutNowBtn = document.getElementById('sessionLogoutNowBtn');
    var renewErrorEl = document.getElementById('sessionRenewError');
    var warningShown = false;

    function pad(value) { return value < 10 ? '0' + value : '' + value; }
    function formatSeconds(seconds) {
        seconds = Math.max(0, seconds);
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;
        return pad(hours) + ':' + pad(minutes) + ':' + pad(secs);
    }
    function currentPagePath() {
        return window.location.pathname + window.location.search + window.location.hash;
    }
    function timeoutLogout() {
        try { window.localStorage.setItem('trackme_last_page_before_timeout', currentPagePath()); } catch (e) {}
        if (logoutUrl) {
            window.location.href = logoutUrl + '?timeout=1&redirect=' + encodeURIComponent(currentPagePath());
        }
    }
    function setWarningVisible(visible) {
        if (!warningModal) { return; }
        warningModal.classList.toggle('is-visible', visible);
    }
    function showRenewError(message) {
        if (renewErrorEl) { renewErrorEl.textContent = message || 'Unable to renew session. Please try again.'; }
    }
    function clearRenewError() {
        if (renewErrorEl) { renewErrorEl.textContent = ''; }
    }
    function tickSessionMeter() {
        var now = Math.floor(Date.now() / 1000);
        var elapsed = Math.max(0, now - startedAt);
        var remaining = expiresAt > 0 ? Math.max(0, expiresAt - now) : 0;

        if (activeEl) { activeEl.textContent = formatSeconds(elapsed); }
        if (leftEl) { leftEl.textContent = expiresAt > 0 ? formatSeconds(remaining) : '--:--:--'; }
        if (warningLeftEl) { warningLeftEl.textContent = formatSeconds(remaining); }

        meter.classList.toggle('is-ending', expiresAt > 0 && remaining <= 300);

        if (expiresAt > 0 && remaining <= 300 && remaining > 0 && !warningShown) {
            warningShown = true;
            setWarningVisible(true);
        }
        if (expiresAt > 0 && remaining <= 0) { timeoutLogout(); }
    }

    if (renewBtn && window.jQuery) {
        renewBtn.addEventListener('click', function () {
            clearRenewError();
            renewBtn.disabled = true;
            renewBtn.textContent = 'Renewing...';
            jQuery.ajax({
                url: renewUrl, type: 'POST', dataType: 'json',
                success: function (response) {
                    if (response && response.status === 'success') {
                        expiresAt = parseInt(response.expires_at, 10) || (Math.floor(Date.now() / 1000) + 300);
                        meter.setAttribute('data-expires-at', expiresAt);
                        warningShown = false;
                        setWarningVisible(false);
                        tickSessionMeter();
                    } else {
                        showRenewError(response && response.message ? response.message : 'Unable to renew session. Please try again.');
                    }
                },
                error: function () { showRenewError('Unable to renew session. Please check your connection and try again.'); },
                complete: function () { renewBtn.disabled = false; renewBtn.textContent = 'Renew 5 Minutes'; }
            });
        });
    }
    if (logoutNowBtn) { logoutNowBtn.addEventListener('click', timeoutLogout); }

    tickSessionMeter();
    window.setInterval(tickSessionMeter, 1000);
})();

/* ===== Global ERP search (indexes the sidebar menu) ===== */
(function () {
    var globalSearch = document.getElementById('erpGlobalSearch');
    var globalInput = document.getElementById('erpGlobalSearchInput');
    var globalClear = document.getElementById('erpGlobalSearchClear');
    var suggestions = document.getElementById('erpGlobalSearchSuggestions');
    var sidebarInput = document.getElementById('sidebarMenuSearchInput');
    var sidebarEmpty = document.getElementById('sidebarMenuSearchEmpty');
    var sidebarMenus = Array.prototype.slice.call(document.querySelectorAll('.sidebar-menu'));
    var searchItems = [];
    var activeIndex = -1;

    function normalizeSearchText(text) { return (text || '').replace(/\s+/g, ' ').trim().toLowerCase(); }
    function escapeHtml(text) {
        return (text || '').replace(/[&<>"']/g, function (char) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
        });
    }
    function isNavigableHref(href) {
        if (!href) { return false; }
        var cleanHref = href.trim().toLowerCase();
        return cleanHref !== '#' && cleanHref.indexOf('javascript:') !== 0;
    }
    function menuTitleForLink(link) {
        var dropdown = link.closest('li.dropdown');
        if (!dropdown) { return ''; }
        var titleEl = dropdown.querySelector(':scope > a .title');
        return titleEl ? titleEl.textContent : '';
    }
    function buildSearchIndex() {
        var seen = {};
        var links = Array.prototype.slice.call(document.querySelectorAll('.sidebar-menu a[href]'));
        links.forEach(function (link) {
            var href = link.getAttribute('href');
            if (!isNavigableHref(href)) { return; }
            var rawLabel = (link.textContent || '').replace(/\s+/g, ' ').trim();
            var rawSection = (menuTitleForLink(link) || '').replace(/\s+/g, ' ').trim();
            var label = normalizeSearchText(rawLabel);
            var section = normalizeSearchText(rawSection);
            var displaySection = rawSection || 'ERP Option';
            var key = href + '|' + label + '|' + section;
            if (!label || seen[key]) { return; }
            seen[key] = true;
            searchItems.push({
                label: rawLabel, section: displaySection, href: href,
                text: normalizeSearchText(displaySection + ' ' + rawLabel), icon: 'ti-arrow-circle-right'
            });
        });
    }
    function matchesForQuery(query, limit) {
        var normalized = normalizeSearchText(query);
        var maxItems = limit || 8;
        if (!normalized) { return searchItems.slice(0, maxItems); }
        return searchItems.filter(function (item) { return item.text.indexOf(normalized) !== -1; }).slice(0, maxItems);
    }
    function navigateToItem(item) { if (item && item.href) { window.location.href = item.href; } }
    function renderSuggestions() {
        if (!suggestions || !globalInput) { return; }
        var query = globalInput.value;
        var matches = matchesForQuery(query, 10);
        activeIndex = matches.length ? 0 : -1;
        if (!query && document.activeElement !== globalInput) {
            suggestions.classList.remove('is-visible');
            return;
        }
        if (!matches.length) {
            suggestions.innerHTML = '<div class="erp-search-empty">No ERP option found.</div>';
            suggestions.classList.add('is-visible');
            return;
        }
        suggestions.innerHTML = matches.map(function (item, index) {
            return '<button type="button" class="erp-search-suggestion ' + (index === activeIndex ? 'is-active' : '') + '" data-index="' + index + '">' +
                '<i class="' + item.icon + '"></i>' +
                '<span><strong>' + escapeHtml(item.label) + '</strong><span>' + escapeHtml(item.section) + '</span></span>' +
                '</button>';
        }).join('');
        suggestions.classList.add('is-visible');
        Array.prototype.slice.call(suggestions.querySelectorAll('.erp-search-suggestion')).forEach(function (button) {
            button.addEventListener('mousedown', function (event) {
                event.preventDefault();
                navigateToItem(matches[parseInt(button.getAttribute('data-index'), 10)]);
            });
        });
    }
    function updateActiveSuggestion(direction) {
        var buttons = suggestions ? Array.prototype.slice.call(suggestions.querySelectorAll('.erp-search-suggestion')) : [];
        if (!buttons.length) { return; }
        activeIndex += direction;
        if (activeIndex < 0) { activeIndex = buttons.length - 1; }
        if (activeIndex >= buttons.length) { activeIndex = 0; }
        buttons.forEach(function (button, index) { button.classList.toggle('is-active', index === activeIndex); });
    }
    function clearGlobalSearch() {
        if (!globalInput || !suggestions) { return; }
        globalInput.value = '';
        suggestions.classList.remove('is-visible');
        if (globalClear) { globalClear.classList.remove('is-visible'); }
        globalInput.focus();
    }
    function filterSidebarMenu() {
        if (!sidebarInput) { return; }
        var query = normalizeSearchText(sidebarInput.value);
        var visibleCount = 0;
        sidebarMenus.forEach(function (menu) {
            Array.prototype.slice.call(menu.children).forEach(function (topItem) {
                if (!topItem.matches('li')) { return; }
                if (!topItem.hasAttribute('data-was-open')) {
                    topItem.setAttribute('data-was-open', topItem.classList.contains('open') ? '1' : '0');
                }
                var topLink = topItem.querySelector(':scope > a');
                var topText = normalizeSearchText(topLink ? topLink.textContent : '');
                var childItems = Array.prototype.slice.call(topItem.querySelectorAll('.dropdown-menu li'));
                var topMatch = !!query && topText.indexOf(query) !== -1;
                var childMatched = false;
                childItems.forEach(function (childItem) {
                    var childText = normalizeSearchText(childItem.textContent);
                    var match = !query || topMatch || childText.indexOf(query) !== -1;
                    childItem.classList.toggle('is-search-child-hidden', !match);
                    childMatched = childMatched || (!!query && match);
                });
                var showTop = !query || topMatch || childMatched;
                topItem.classList.toggle('is-search-hidden', !showTop);
                if (query) {
                    topItem.classList.toggle('open', showTop && (topMatch || childMatched));
                    var dropdown = topItem.querySelector(':scope > .dropdown-menu');
                    if (dropdown) { dropdown.style.display = showTop && (topMatch || childMatched) ? 'block' : ''; }
                } else {
                    topItem.classList.toggle('open', topItem.getAttribute('data-was-open') === '1');
                    childItems.forEach(function (childItem) { childItem.classList.remove('is-search-child-hidden'); });
                    var menuDropdown = topItem.querySelector(':scope > .dropdown-menu');
                    if (menuDropdown) { menuDropdown.style.display = ''; }
                }
                if (showTop) { visibleCount++; }
            });
        });
        if (sidebarEmpty) { sidebarEmpty.classList.toggle('is-visible', !!query && visibleCount === 0); }
    }

    buildSearchIndex();

    if (globalInput) {
        globalInput.addEventListener('focus', renderSuggestions);
        globalInput.addEventListener('input', function () {
            if (globalClear) { globalClear.classList.toggle('is-visible', !!globalInput.value); }
            renderSuggestions();
        });
        globalInput.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') { event.preventDefault(); updateActiveSuggestion(1); }
            else if (event.key === 'ArrowUp') { event.preventDefault(); updateActiveSuggestion(-1); }
            else if (event.key === 'Enter') {
                event.preventDefault();
                var matches = matchesForQuery(globalInput.value, 10);
                navigateToItem(matches[activeIndex >= 0 ? activeIndex : 0]);
            } else if (event.key === 'Escape' && suggestions) { suggestions.classList.remove('is-visible'); }
        });
    }
    if (globalClear) { globalClear.addEventListener('click', clearGlobalSearch); }
    if (sidebarInput) { sidebarInput.addEventListener('input', filterSidebarMenu); }
    document.addEventListener('click', function (event) {
        if (globalSearch && suggestions && !globalSearch.contains(event.target)) {
            suggestions.classList.remove('is-visible');
        }
    });
})();

/* ===== Theme picker (preset swatches + custom colours, persisted) ===== */
(function () {
    var storageKey = 'trackme-admin-custom-theme-v2';
    var oldStorageKey = 'trackme-admin-custom-theme';
    var defaults = { primary: '#1769c2', font: '#18243c' };

    function normalizeHex(color, fallback) {
        color = (color || '').toString().trim();
        if (/^#[0-9a-fA-F]{6}$/.test(color)) { return color.toLowerCase(); }
        return fallback;
    }
    function hexToRgb(hex) {
        hex = normalizeHex(hex, defaults.primary).replace('#', '');
        return { r: parseInt(hex.substring(0, 2), 16), g: parseInt(hex.substring(2, 4), 16), b: parseInt(hex.substring(4, 6), 16) };
    }
    function rgbToHex(rgb) {
        function clamp(value) { return Math.max(0, Math.min(255, Math.round(value))); }
        return '#' + [clamp(rgb.r), clamp(rgb.g), clamp(rgb.b)].map(function (value) {
            value = value.toString(16);
            return value.length === 1 ? '0' + value : value;
        }).join('');
    }
    function mix(hex, target, amount) {
        var rgb = hexToRgb(hex);
        var targetRgb = hexToRgb(target);
        return rgbToHex({
            r: rgb.r + (targetRgb.r - rgb.r) * amount,
            g: rgb.g + (targetRgb.g - rgb.g) * amount,
            b: rgb.b + (targetRgb.b - rgb.b) * amount
        });
    }
    function applyTheme(settings) {
        var primary = normalizeHex(settings.primary, defaults.primary);
        var font = normalizeHex(settings.font, defaults.font);
        var rgb = hexToRgb(primary);
        var dark = mix(primary, '#000000', .42);
        var soft = mix(primary, '#ffffff', .88);
        var accent = mix(primary, '#f0a020', .38);
        var muted = mix(font, '#ffffff', .46);
        var line = mix(primary, '#ffffff', .78);
        var bg = mix(primary, '#ffffff', .94);

        document.body.classList.remove('tm-theme-emerald', 'tm-theme-violet', 'tm-theme-rose');
        document.body.style.setProperty('--tm-brand', primary);
        document.body.style.setProperty('--tm-brand-dark', dark);
        document.body.style.setProperty('--tm-brand-soft', soft);
        document.body.style.setProperty('--tm-accent', accent);
        document.body.style.setProperty('--tm-brand-rgb', rgb.r + ', ' + rgb.g + ', ' + rgb.b);
        document.body.style.setProperty('--tm-sidebar-start', dark);
        document.body.style.setProperty('--tm-sidebar-end', mix(primary, '#000000', .68));
        document.body.style.setProperty('--tm-ink', font);
        document.body.style.setProperty('--tm-muted', muted);
        document.body.style.setProperty('--tm-line', line);
        document.body.style.setProperty('--tm-bg', bg);

        var primaryInput = document.getElementById('themePrimaryColor');
        var fontInput = document.getElementById('themeFontColor');
        if (primaryInput) { primaryInput.value = primary; }
        if (fontInput) { fontInput.value = font; }

        var swatches = document.querySelectorAll('.theme-swatch');
        for (var i = 0; i < swatches.length; i++) {
            var sw = (swatches[i].style.getPropertyValue('--sw') || '').trim().toLowerCase();
            swatches[i].classList.toggle('is-active', sw === primary);
        }
    }
    function readTheme() {
        try {
            localStorage.removeItem(oldStorageKey);
            return JSON.parse(localStorage.getItem(storageKey)) || defaults;
        } catch (e) { return defaults; }
    }
    function saveTheme(settings) {
        try { localStorage.setItem(storageKey, JSON.stringify(settings)); } catch (e) {}
        applyTheme(settings);
    }

    window.TrackmeTheme = window.TrackmeTheme || {};
    window.TrackmeTheme.applyFromInputs = function () {
        var primaryInput = document.getElementById('themePrimaryColor');
        var fontInput = document.getElementById('themeFontColor');
        saveTheme({
            primary: normalizeHex(primaryInput ? primaryInput.value : defaults.primary, defaults.primary),
            font: normalizeHex(fontInput ? fontInput.value : defaults.font, defaults.font)
        });
    };
    window.TrackmeTheme.reset = function () {
        try { localStorage.removeItem(storageKey); } catch (e) {}
        applyTheme(defaults);
    };
    window.TrackmeTheme.setPreset = function (primary, font) {
        saveTheme({ primary: normalizeHex(primary, defaults.primary), font: normalizeHex(font, defaults.font) });
    };

    function initThemePicker() {
        applyTheme(readTheme());
        var themeBlock = document.querySelector('.theme-block');
        var themeToggle = document.querySelector('.theme-block > a');
        var primaryInput = document.getElementById('themePrimaryColor');
        var fontInput = document.getElementById('themeFontColor');
        var resetButton = document.getElementById('themeResetBtn');

        function saveCurrentTheme() {
            saveTheme({
                primary: normalizeHex(primaryInput ? primaryInput.value : defaults.primary, defaults.primary),
                font: normalizeHex(fontInput ? fontInput.value : defaults.font, defaults.font)
            });
        }
        if (primaryInput) {
            primaryInput.addEventListener('input', saveCurrentTheme);
            primaryInput.addEventListener('change', saveCurrentTheme);
        }
        if (fontInput) {
            fontInput.addEventListener('input', saveCurrentTheme);
            fontInput.addEventListener('change', saveCurrentTheme);
        }
        if (resetButton) { resetButton.addEventListener('click', function () { trackmeResetTheme(); }); }
        if (themeToggle && themeBlock) {
            themeToggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                themeBlock.classList.toggle('is-open');
            });
            document.addEventListener('click', function (event) {
                if (!themeBlock.contains(event.target)) { themeBlock.classList.remove('is-open'); }
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemePicker);
    } else {
        initThemePicker();
    }
})();
