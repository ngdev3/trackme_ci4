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
            positionClass: 'toast-bottom-right',
            timeOut: 3500,
            extendedTimeOut: 1500,
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut',
            showDuration: 250,
            hideDuration: 300,
            preventDuplicates: true
        };
    }

    var TYPES = {
        success: { icon: 'success', color: '#198754' },
        warning: { icon: 'warning', color: '#f59f00' },
        error: { icon: 'error', color: '#dc3545' },
        danger: { icon: 'error', color: '#dc3545' },
        info: { icon: 'info', color: '#0d6efd' }
    };

    window.erpNotify = function (type, message, title) {
        type = TYPES[type] ? type : 'info';
        if (type === 'danger') { type = 'error'; }
        if (window.toastr) {
            window.toastr.options.positionClass = 'toast-bottom-right';
            var container = document.getElementById('toast-container');
            if (container) {
                container.classList.remove(
                    'toast-top-right',
                    'toast-top-left',
                    'toast-top-center',
                    'toast-top-full-width',
                    'toast-bottom-left',
                    'toast-bottom-center',
                    'toast-bottom-full-width'
                );
                container.classList.add('toast-bottom-right');
            }
            window.toastr[type](message, title || '');
            return;
        }
        // Fallback to SweetAlert2 toast.
        if (window.Swal) {
            Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                showClass: { popup: 'swal2-show' },
                hideClass: { popup: 'swal2-hide' }
            })
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
        var icon = TYPES[opts.icon] ? TYPES[opts.icon].icon : 'warning';
        var confirmColor = opts.confirmColor || (icon === 'error' ? TYPES.error.color : (icon === 'warning' ? TYPES.warning.color : TYPES.info.color));
        Swal.fire({
            title: opts.title || 'Are you sure?',
            text: opts.text || '',
            icon: icon,
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Yes',
            cancelButtonText: opts.cancelText || 'Cancel',
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#6c757d',
            buttonsStyling: true,
            customClass: { popup: 'erp-swal', confirmButton: 'erp-swal-confirm', cancelButton: 'erp-swal-cancel' },
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
