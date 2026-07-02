/* ------------------------------------------------------------------
 * ERP Notification System
 * Global toasts (Toastr / jQuery) + confirmation popups (SweetAlert2).
 * Reusable across every module.
 *
 * Public API:
 *   erpNotify(type, message, title?)   type: success|error|warning|info
 *   erpConfirm({ title, text, icon, confirmText, onConfirm })
 *   erpToast(message, icon)            (backward-compatible shim)
 * ------------------------------------------------------------------ */
(function () {
    'use strict';

    // ---- Toastr defaults (modern, smooth, auto-close + close button) ----
    if (window.toastr) {
        window.toastr.options = {
            closeButton: true,
            progressBar: true,
            newestOnTop: true,
            positionClass: 'toast-top-right',
            timeOut: 3500,
            extendedTimeOut: 1500,
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut',
            showDuration: 250,
            hideDuration: 300,
            preventDuplicates: true
        };
    }

    var ICONS = { success: 'success', error: 'error', warning: 'warning', info: 'info' };

    window.erpNotify = function (type, message, title) {
        type = ICONS[type] ? type : 'info';
        if (window.toastr) {
            window.toastr[type](message, title || '');
            return;
        }
        // Fallback to SweetAlert2 toast.
        if (window.Swal) {
            Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true })
                .fire({ icon: type, title: message });
        } else {
            alert(message);
        }
    };

    // Backward-compatible shim (older code calls erpToast(msg, icon)).
    window.erpToast = function (message, icon) {
        window.erpNotify(icon || 'success', message);
    };

    // Reusable confirmation popup (SweetAlert2 — nicer for destructive actions).
    window.erpConfirm = function (opts) {
        opts = opts || {};
        if (!window.Swal) {
            if (confirm(opts.text || 'Are you sure?')) { if (opts.onConfirm) { opts.onConfirm(); } }
            return;
        }
        Swal.fire({
            title: opts.title || 'Are you sure?',
            text: opts.text || '',
            icon: opts.icon || 'warning',
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Yes',
            cancelButtonText: opts.cancelText || 'Cancel',
            confirmButtonColor: opts.confirmColor || '#0d6efd',
            reverseButtons: true
        }).then(function (res) {
            if (res.isConfirmed && opts.onConfirm) { opts.onConfirm(); }
        });
    };

    // Required-field warning helper for forms.
    window.erpRequired = function (message) {
        window.erpNotify('warning', message || 'Please fill in all required fields.');
    };
})();
