/*!
 * cr_notify.js — Global Advanced Popup & Toast system for TrackmeNew / C R Industries ERP
 * -------------------------------------------------------------------------------------
 * One reusable, dependency-free helper that replaces the native browser alert /
 * confirm / prompt with a modern, animated, mobile-responsive UI.
 *
 * Public API (all attached to window):
 *   showToast(type, message, title)                       // success|error|warning|info
 *   showAlert(title, message, type)              -> Promise resolved on OK
 *   showConfirm(title, message, onConfirm, onCancel)  -> Promise<bool>
 *   showPrompt(title, message, defaultValue, onConfirm, onCancel) -> Promise<string|null>
 *   crConfirmNav(el, message, title)             // inline-anchor helper: returns false,
 *                                                // navigates to el.href on confirm
 *   window.alert is transparently upgraded to a toast (non-blocking, auto-typed).
 *
 * Also namespaced as window.CRNotify.{toast,alert,confirm,prompt}.
 * Injects its own CSS once, so a single <script> include is all that's needed.
 */
(function (w, d) {
    'use strict';
    if (w.CRNotify && w.CRNotify.__ready) { return; } // guard double-include

    var ICONS = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle',
        question: 'fa-question-circle'
    };

    /* ---------------------------------------------------------------- styles */
    function injectCss() {
        if (d.getElementById('crn-styles')) { return; }
        var css = ''
        + '.crn-toast-wrap{position:fixed;right:18px;bottom:18px;z-index:2147483000;display:flex;flex-direction:column;gap:12px;max-width:390px;width:calc(100% - 36px);pointer-events:none}'
        + '.crn-toast{--c1:#2563eb;--c2:#60a5fa;--tint:#eff6ff;pointer-events:auto;position:relative;overflow:hidden;display:flex;align-items:flex-start;gap:13px;background:linear-gradient(180deg,#fff,var(--tint));border-radius:15px;padding:14px 16px 16px;box-shadow:0 18px 48px rgba(16,24,40,.26),0 3px 10px rgba(16,24,40,.08);border:1px solid rgba(16,24,40,.06);opacity:0;transform:translateX(115%) scale(.9);transition:opacity .4s cubic-bezier(.2,.9,.3,1.35),transform .4s cubic-bezier(.2,.9,.3,1.35);font-family:inherit}'
        + '.crn-toast.crn-in{opacity:1;transform:translateX(0) scale(1)}'
        + '.crn-toast.crn-out{opacity:0;transform:translateX(115%) scale(.9)}'
        + '.crn-toast:before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;background:linear-gradient(180deg,var(--c1),var(--c2))}'
        + '.crn-toast .crn-i{flex:0 0 auto;width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:19px;background:linear-gradient(135deg,var(--c1),var(--c2));box-shadow:0 8px 18px -5px var(--c1);animation:crn-pop .45s cubic-bezier(.2,.9,.3,1.6) both}'
        + '@keyframes crn-pop{0%{transform:scale(.4) rotate(-12deg);opacity:0}100%{transform:scale(1) rotate(0);opacity:1}}'
        + '.crn-toast .crn-b{min-width:0;flex:1;padding-top:1px}'
        + '.crn-toast .crn-t{font-size:14px;font-weight:800;color:#111827;margin-bottom:2px;letter-spacing:-.01em}'
        + '.crn-toast .crn-m{font-size:12.5px;font-weight:600;color:#4b5563;line-height:1.46;word-break:break-word}'
        + '.crn-toast .crn-x{margin:-3px -5px 0 4px;cursor:pointer;border:0;background:none;color:#9aa4b2;font-size:19px;line-height:1;flex:0 0 auto;padding:3px;border-radius:7px;transition:.15s}'
        + '.crn-toast .crn-x:hover{background:rgba(0,0,0,.06);color:#374151}'
        + '.crn-progress{position:absolute;left:0;bottom:0;height:4px;width:100%;transform-origin:left;background:linear-gradient(90deg,var(--c1),var(--c2));box-shadow:0 0 8px var(--c1);animation:crn-shrink linear forwards}'
        + '.crn-toast:hover .crn-progress{animation-play-state:paused}'
        + '@keyframes crn-shrink{from{transform:scaleX(1)}to{transform:scaleX(0)}}'
        + '.crn-toast.t-success{--c1:#059669;--c2:#34d399;--tint:#ecfdf5}'
        + '.crn-toast.t-error{--c1:#e11d48;--c2:#fb7185;--tint:#fff1f2}'
        + '.crn-toast.t-warning{--c1:#ea580c;--c2:#fbbf24;--tint:#fff7ed}'
        + '.crn-toast.t-info{--c1:#2563eb;--c2:#60a5fa;--tint:#eff6ff}'
        + '@media(prefers-reduced-motion:reduce){.crn-toast,.crn-toast .crn-i{transition:opacity .2s ease;animation:none}}'
        /* modal */
        + '.crn-back{position:fixed;inset:0;z-index:2147483001;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(15,23,42,.55);opacity:0;transition:opacity .2s ease;backdrop-filter:blur(2px)}'
        + '.crn-back.crn-in{opacity:1}'
        + '.crn-modal{background:#fff;border-radius:16px;box-shadow:0 30px 70px rgba(12,22,40,.35);width:100%;max-width:440px;overflow:hidden;transform:translateY(10px) scale(.96);opacity:0;transition:transform .22s cubic-bezier(.2,.9,.3,1.2),opacity .22s ease}'
        + '.crn-back.crn-in .crn-modal{transform:translateY(0) scale(1);opacity:1}'
        + '.crn-head{display:flex;align-items:center;gap:13px;padding:22px 24px 6px}'
        + '.crn-badge{flex:0 0 auto;width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px}'
        + '.crn-badge.b-success{background:#e7f7ee;color:#16a34a}.crn-badge.b-error{background:#fdeaea;color:#dc2626}'
        + '.crn-badge.b-warning{background:#fdf2e3;color:#d97706}.crn-badge.b-info{background:#e8f0fb;color:#2557a7}.crn-badge.b-question{background:#eef1f6;color:#334155}'
        + '.crn-title{font-size:18px;font-weight:900;color:#18243c;margin:0}'
        + '.crn-body{padding:6px 24px 4px;color:#4a586d;font-size:14px;font-weight:600;line-height:1.55;word-break:break-word}'
        + '.crn-inwrap{padding:12px 24px 4px}'
        + '.crn-input{width:100%;border:1px solid #d7e0ec;border-radius:10px;padding:11px 13px;font-size:14px;font-weight:600;color:#18243c;background:#fbfdff;outline:none;transition:border-color .15s,box-shadow .15s}'
        + '.crn-input:focus{border-color:#1769c2;box-shadow:0 0 0 3px rgba(23,105,194,.14)}'
        + '.crn-input.crn-err{border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.12)}'
        + '.crn-errmsg{color:#dc2626;font-size:12px;font-weight:700;margin-top:6px;display:none}'
        + '.crn-foot{display:flex;justify-content:flex-end;gap:10px;padding:18px 24px 22px;margin-top:6px}'
        + '.crn-btn{border:0;border-radius:10px;cursor:pointer;font-size:14px;font-weight:800;padding:11px 20px;min-height:44px;transition:transform .08s,filter .15s;font-family:inherit}'
        + '.crn-btn:active{transform:translateY(1px)}'
        + '.crn-btn-cancel{background:#eef2f7;color:#51617a}.crn-btn-cancel:hover{filter:brightness(.97)}'
        + '.crn-btn-ok{background:#1769c2;color:#fff}.crn-btn-ok:hover{filter:brightness(1.06)}'
        + '.crn-btn-ok.k-success{background:#16a34a}.crn-btn-ok.k-error{background:#dc2626}.crn-btn-ok.k-warning{background:#d97706}'
        + '@media(max-width:520px){.crn-foot{flex-direction:column-reverse}.crn-btn{width:100%}.crn-toast-wrap{right:12px;bottom:12px}}';
        var s = d.createElement('style');
        s.id = 'crn-styles';
        s.appendChild(d.createTextNode(css));
        (d.head || d.documentElement).appendChild(s);
    }

    function ready(fn) {
        if (d.body) { fn(); }
        else { d.addEventListener('DOMContentLoaded', fn); }
    }

    function esc(str) {
        return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /* ------------------------------------------------------------- toast */
    function toastWrap() {
        var wrap = d.querySelector('.crn-toast-wrap');
        if (!wrap) {
            wrap = d.createElement('div');
            wrap.className = 'crn-toast-wrap';
            d.body.appendChild(wrap);
        }
        return wrap;
    }

    function showToast(type, message, title, opts) {
        opts = opts || {};
        type = ICONS[type] ? type : 'info';
        var icon = opts.icon || ICONS[type];
        injectCss();
        ready(function () {
            var wrap = toastWrap();
            var life = opts.duration || 4600;
            var el = d.createElement('div');
            el.className = 'crn-toast t-' + type;
            el.innerHTML = '<span class="crn-i"><i class="fa ' + icon + '"></i></span>'
                + '<div class="crn-b">'
                + (title ? '<div class="crn-t">' + esc(title) + '</div>' : '')
                + '<div class="crn-m"></div></div>'
                + '<button type="button" class="crn-x" aria-label="Close">&times;</button>'
                + '<div class="crn-progress" style="animation-duration:' + life + 'ms"></div>';
            el.querySelector('.crn-m').textContent = message == null ? '' : String(message);
            wrap.appendChild(el);

            var closed = false;
            function close() {
                if (closed) { return; }
                closed = true;
                el.classList.remove('crn-in'); el.classList.add('crn-out');
                setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 400);
            }
            requestAnimationFrame(function () { el.classList.add('crn-in'); });

            // Dismissal is driven by the countdown bar finishing (hover pauses the
            // CSS animation, so hovering pauses auto-close for free). Fallback
            // timer covers browsers where the animation never fires.
            var prog = el.querySelector('.crn-progress');
            var animated = false;
            if (prog) {
                prog.addEventListener('animationstart', function () { animated = true; });
                prog.addEventListener('animationend', close);
            }
            setTimeout(function () { if (!animated) { close(); } }, life + 400);

            el.querySelector('.crn-x').addEventListener('click', function (e) { e.stopPropagation(); close(); });
            if (opts.url) {
                el.style.cursor = 'pointer';
                el.addEventListener('click', function () { w.location.href = opts.url; });
            }
            if (typeof opts.onClick === 'function') {
                el.style.cursor = 'pointer';
                el.addEventListener('click', opts.onClick);
            }
        });
        return true;
    }

    /* ------------------------------------------------------------- modal core */
    function buildModal(o) {
        // o: {kind:'alert'|'confirm'|'prompt', type, title, message, okText, cancelText,
        //     defaultValue, required, placeholder, onOk, onCancel, resolve}
        injectCss();
        var type = ICONS[o.type] ? o.type : (o.kind === 'confirm' ? 'question' : 'info');
        var back = d.createElement('div');
        back.className = 'crn-back';

        var showInput = o.kind === 'prompt';
        var okKind = (type === 'error' || type === 'warning' || type === 'success') ? (' k-' + type) : '';

        back.innerHTML =
            '<div class="crn-modal" role="dialog" aria-modal="true">'
          + '<div class="crn-head"><span class="crn-badge b-' + type + '"><i class="fa ' + ICONS[type] + '"></i></span>'
          + '<h4 class="crn-title"></h4></div>'
          + (o.message ? '<div class="crn-body"></div>' : '')
          + (showInput ? '<div class="crn-inwrap"><input type="text" class="crn-input"><div class="crn-errmsg"></div></div>' : '')
          + '<div class="crn-foot">'
          + (o.kind === 'alert' ? '' : '<button type="button" class="crn-btn crn-btn-cancel"></button>')
          + '<button type="button" class="crn-btn crn-btn-ok' + okKind + '"></button>'
          + '</div></div>';

        back.querySelector('.crn-title').textContent = o.title || '';
        if (o.message) { back.querySelector('.crn-body').textContent = o.message; }
        var okBtn = back.querySelector('.crn-btn-ok');
        var cancelBtn = back.querySelector('.crn-btn-cancel');
        okBtn.textContent = o.okText || (o.kind === 'confirm' ? 'Confirm' : 'OK');
        if (cancelBtn) { cancelBtn.textContent = o.cancelText || 'Cancel'; }

        var input = back.querySelector('.crn-input');
        var errEl = back.querySelector('.crn-errmsg');
        if (input) {
            input.value = o.defaultValue != null ? o.defaultValue : '';
            if (o.placeholder) { input.placeholder = o.placeholder; }
        }

        var closed = false;
        function destroy() {
            back.classList.remove('crn-in');
            setTimeout(function () { if (back.parentNode) { back.parentNode.removeChild(back); } d.removeEventListener('keydown', onKey); }, 220);
        }
        function done(result, isOk) {
            if (closed) { return; }
            closed = true;
            destroy();
            if (isOk) { if (typeof o.onOk === 'function') { o.onOk(result); } if (o.resolve) { o.resolve(result); } }
            else { if (typeof o.onCancel === 'function') { o.onCancel(); } if (o.resolve) { o.resolve(o.kind === 'prompt' ? null : false); } }
        }
        function submit() {
            if (showInput) {
                var val = input.value.trim();
                if (o.required && val === '') {
                    input.classList.add('crn-err');
                    errEl.textContent = o.requiredMsg || 'This field is required.';
                    errEl.style.display = 'block';
                    input.focus();
                    return;
                }
                if (typeof o.validate === 'function') {
                    var vr = o.validate(val);
                    if (vr && vr !== true) {
                        input.classList.add('crn-err');
                        errEl.textContent = vr;
                        errEl.style.display = 'block';
                        input.focus();
                        return;
                    }
                }
                done(val, true);
            } else {
                done(true, true);
            }
        }

        okBtn.addEventListener('click', submit);
        if (cancelBtn) { cancelBtn.addEventListener('click', function () { done(null, false); }); }
        back.addEventListener('mousedown', function (e) { if (e.target === back && o.kind !== 'prompt') { done(null, false); } });
        if (input) {
            input.addEventListener('input', function () { input.classList.remove('crn-err'); errEl.style.display = 'none'; });
            input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); submit(); } });
        }
        function onKey(e) {
            if (e.key === 'Escape') { done(null, false); }
            else if (e.key === 'Enter' && !showInput) { submit(); }
        }
        d.addEventListener('keydown', onKey);

        ready(function () {
            d.body.appendChild(back);
            requestAnimationFrame(function () { back.classList.add('crn-in'); });
            setTimeout(function () { (input || okBtn).focus(); }, 60);
        });
    }

    function showAlert(title, message, type, onOk) {
        return new Promise(function (resolve) {
            buildModal({ kind: 'alert', type: type || 'info', title: title || 'Notice', message: message || '', onOk: onOk, resolve: resolve });
        });
    }
    function showConfirm(title, message, onConfirm, onCancel, opts) {
        opts = opts || {};
        return new Promise(function (resolve) {
            buildModal({
                kind: 'confirm', type: opts.type || 'question',
                title: title || 'Please confirm', message: message || '',
                okText: opts.okText || 'Confirm', cancelText: opts.cancelText || 'Cancel',
                onOk: function () { if (typeof onConfirm === 'function') { onConfirm(); } },
                onCancel: onCancel, resolve: resolve
            });
        });
    }
    function showPrompt(title, message, defaultValue, onConfirm, onCancel, opts) {
        opts = opts || {};
        return new Promise(function (resolve) {
            buildModal({
                kind: 'prompt', type: opts.type || 'info',
                title: title || 'Enter value', message: message || '',
                defaultValue: defaultValue, placeholder: opts.placeholder || '',
                required: opts.required !== false, requiredMsg: opts.requiredMsg,
                validate: opts.validate,
                okText: opts.okText || 'OK', cancelText: opts.cancelText || 'Cancel',
                onOk: function (v) { if (typeof onConfirm === 'function') { onConfirm(v); } },
                onCancel: onCancel, resolve: resolve
            });
        });
    }

    /* --------- inline-anchor confirm helper: `onclick="return crConfirmNav(this,'msg')"` */
    function crConfirmNav(el, message, title) {
        showConfirm(title || 'Please confirm', message || 'Are you sure?', function () {
            if (!el) { return; }
            // Plain link -> navigate (respecting target="_blank" / named targets).
            if (el.tagName === 'A' && el.href) {
                if (el.target && el.target !== '_self') { w.open(el.href, el.target); }
                else { w.location.href = el.href; }
                return;
            }
            // Submit button / form control -> submit its form, preserving the
            // submitter's name/value (the server may read $_POST[button name]).
            var form = el.form || (el.closest ? el.closest('form') : null);
            if (form) {
                if (el.name && (el.type === 'submit' || el.type === 'image' || el.tagName === 'BUTTON')) {
                    if (form.requestSubmit) { try { form.requestSubmit(el); return; } catch (e) {} }
                    var h = d.createElement('input');
                    h.type = 'hidden'; h.name = el.name; h.value = el.value == null ? '' : el.value;
                    form.appendChild(h);
                }
                form.submit();
                return;
            }
            if (el.href) { w.location.href = el.href; }
            else if (typeof el.submit === 'function') { el.submit(); }
        });
        return false; // stop the default action; the callback drives it on confirm
    }

    /* ---------- auto-type detection for the transparent window.alert upgrade */
    function detectType(msg) {
        var s = String(msg || '').toLowerCase();
        if (/success|successfully|saved|added|updated|created|sent|done|complete/.test(s)) { return 'success'; }
        if (/error|failed|failure|wrong|invalid|unable|cannot|can't|denied|not found|no record|already exist/.test(s)) { return 'error'; }
        if (/please|select|required|must|warning|enter |choose|at least|not allowed|empty/.test(s)) { return 'warning'; }
        return 'info';
    }

    injectCss();

    w.showToast = showToast;
    w.showAlert = showAlert;
    w.showConfirm = showConfirm;
    w.showPrompt = showPrompt;
    w.crConfirmNav = crConfirmNav;
    w.CRNotify = {
        __ready: true,
        toast: showToast, alert: showAlert, confirm: showConfirm, prompt: showPrompt,
        confirmNav: crConfirmNav, detectType: detectType
    };

    // Transparent upgrade: native alert() -> modern toast (non-blocking, auto-typed).
    // Safe because alert()'s return value is never used. Kept as _nativeAlert for escape.
    w._nativeAlert = w.alert;
    w.alert = function (message) { showToast(detectType(message), message); return undefined; };
})(window, document);
