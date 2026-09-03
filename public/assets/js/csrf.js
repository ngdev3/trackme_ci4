/*!
 * csrf.js — app-wide CSRF token wiring for the CI4 CSRF filter.
 * Reads the token from <meta name="X-CSRF-TOKEN"> (emitted by csrf_meta()) and:
 *   1) attaches it as the X-CSRF-TOKEN header to every non-GET jQuery AJAX/
 *      DataTables request (covers all $.ajax/$.post/$.get + DataTables ajax),
 *   2) injects/updates a hidden csrf field on every POST <form> at submit time
 *      (covers normal, non-AJAX form posts) — so no per-form/per-call edits.
 * The token is stable per session (Security::$regenerate = false), so a value
 * read once stays valid for the page's lifetime.
 */
(function () {
    function meta(name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') : '';
    }
    // csrf_meta() emits: <meta name="{headerName}" content="{hash}"> with the
    // header name as the meta name. Fall back to the default header/field names.
    var HEADER = 'X-CSRF-TOKEN';
    var FIELD = 'csrf_test_name';
    function token() { return meta(HEADER) || meta('csrf-token') || ''; }

    // 1) jQuery AJAX (incl. DataTables, which uses jQuery under the hood).
    if (window.jQuery) {
        jQuery.ajaxSetup({
            beforeSend: function (xhr, settings) {
                var m = (settings.type || settings.method || 'GET').toUpperCase();
                if (m !== 'GET' && m !== 'HEAD' && m !== 'OPTIONS') {
                    var t = token();
                    if (t) { xhr.setRequestHeader(HEADER, t); }
                }
            }
        });
    }

    // 2) Non-AJAX POST forms — inject/refresh the hidden token field on submit.
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f || (f.tagName !== 'FORM')) { return; }
        var method = (f.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') { return; }
        var t = token();
        if (!t) { return; }
        var inp = f.querySelector('input[name="' + FIELD + '"]');
        if (!inp) {
            inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = FIELD;
            f.appendChild(inp);
        }
        inp.value = t;
    }, true);
})();
