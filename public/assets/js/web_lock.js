/*!
 * web_lock.js — CI4 port of the CI3 web-panel inactivity lock (layout.php inline).
 * Auto-locks the panel after N minutes of inactivity (global setting), a manual
 * "Lock Web Panel" button, or Ctrl+Q; unlocks by verifying the login password.
 * Config comes from #webLockOverlay data attributes (no PHP templating needed).
 */
(function () {
    var overlay = document.getElementById('webLockOverlay');
    var form = document.getElementById('webLockForm');
    var passwordInput = document.getElementById('webLockPassword');
    if (!overlay || !form || !passwordInput || typeof jQuery === 'undefined') { return; }

    var lockAfterMinutes = parseInt(overlay.getAttribute('data-lock-minutes'), 10) || 0;
    var lockEnabled = lockAfterMinutes > 0;
    var lockAfterSeconds = lockAfterMinutes * 60;
    var unlockUrl = overlay.getAttribute('data-unlock-url');
    var sessionExpiredLoginUrl = overlay.getAttribute('data-logout-url');
    var loginUrl = overlay.getAttribute('data-login-url');

    var errorEl = document.getElementById('webLockError');
    var unlockBtn = document.getElementById('webLockUnlockBtn');
    var lockLogoutBtn = document.getElementById('webLockLogoutBtn');
    var lockNowBtn = document.getElementById('webLockNowBtn');
    var lockTimer = null;
    var isLocked = false;

    function setStoredLock(v) { try { window.localStorage.setItem('trackme_web_panel_locked', v ? '1' : '0'); } catch (e) {} }
    function getStoredLock() { try { return window.localStorage.getItem('trackme_web_panel_locked') === '1'; } catch (e) { return false; } }
    function clearLockTimer() { if (lockTimer) { window.clearTimeout(lockTimer); lockTimer = null; } }
    function startLockTimer() {
        clearLockTimer();
        if (isLocked || !lockEnabled) { return; }
        lockTimer = window.setTimeout(function () { lockPanel(); }, lockAfterSeconds * 1000);
    }
    function lockPanel() {
        isLocked = true; setStoredLock(true); clearLockTimer();
        overlay.classList.add('is-visible');
        if (errorEl) { errorEl.textContent = ''; }
        passwordInput.value = '';
        window.setTimeout(function () { passwordInput.focus(); }, 50);
    }
    function focusUnlockScreen() {
        if (!isLocked) { return; }
        overlay.classList.add('is-visible');
        window.setTimeout(function () { passwordInput.focus(); }, 50);
    }
    function unlockPanel() {
        isLocked = false; setStoredLock(false);
        overlay.classList.remove('is-visible');
        if (errorEl) { errorEl.textContent = ''; }
        passwordInput.value = '';
        startLockTimer();
    }
    function currentPagePath() { return window.location.pathname + window.location.search + window.location.hash; }
    function redirectToExpiredLogin() {
        var targetUrl = sessionExpiredLoginUrl;
        setStoredLock(false);
        try { window.localStorage.setItem('trackme_last_page_before_timeout', currentPagePath()); } catch (e) {}
        if (targetUrl) { targetUrl += '?timeout=1&redirect=' + encodeURIComponent(currentPagePath()); }
        else { targetUrl = (loginUrl || '') + '?timeout=1&redirect=' + encodeURIComponent(currentPagePath()); }
        window.location.href = targetUrl;
    }
    function logoutFromLockPanel() {
        setStoredLock(false);
        try { window.localStorage.removeItem('trackme_last_page_before_timeout'); } catch (e) {}
        window.location.href = sessionExpiredLoginUrl;
    }
    function activityHappened() { if (!isLocked) { startLockTimer(); } }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (n) {
        document.addEventListener(n, activityHappened, true);
    });
    if (lockNowBtn) { lockNowBtn.addEventListener('click', function () { lockPanel(); }); }
    if (lockLogoutBtn) { lockLogoutBtn.addEventListener('click', function (e) { e.preventDefault(); logoutFromLockPanel(); }); }

    document.addEventListener('keydown', function (event) {
        var key = (event.key || '').toLowerCase();
        if (event.ctrlKey && key === 'q') { event.preventDefault(); lockPanel(); return; }
        if (event.ctrlKey && key === 'x') { event.preventDefault(); if (isLocked) { focusUnlockScreen(); } return; }
        if (isLocked && key === 'enter') { event.preventDefault(); form.dispatchEvent(new Event('submit', { cancelable: true })); }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (errorEl) { errorEl.textContent = ''; }
        unlockBtn.disabled = true; unlockBtn.textContent = 'Checking...';
        jQuery.ajax({
            url: unlockUrl, type: 'POST', dataType: 'json', data: { password: passwordInput.value },
            success: function (response) {
                if (response && response.status === 'success') { unlockPanel(); }
                else {
                    if (response && response.message === 'Session expired') { redirectToExpiredLogin(); return; }
                    if (errorEl) { errorEl.textContent = response && response.message ? response.message : 'Unable to unlock panel.'; }
                    passwordInput.select();
                }
            },
            error: function (xhr) {
                if (xhr && xhr.status === 401) { redirectToExpiredLogin(); return; }
                if (errorEl) { errorEl.textContent = 'Unable to verify password. Please try again.'; }
            },
            complete: function () { unlockBtn.disabled = false; unlockBtn.textContent = 'Unlock Panel'; }
        });
    });

    if (!lockEnabled) { setStoredLock(false); }
    else if (getStoredLock()) { lockPanel(); }
    else { startLockTimer(); }
})();
