/* ==================================================================
 * Reminder pop-up — shows an alert while the app/web is open when a
 * reminder becomes due, with quick actions (Snooze / Done / Open).
 * Polls /reminders/due; degrades silently if the user lacks access.
 * ================================================================== */
(function () {
    'use strict';

    var POLL_MS = 45000;          // check every 45s
    var base    = (window.ERP_BASE || (location.origin + '/ERP/'));
    var shown   = new Set();       // reminder ids already surfaced this session
    var token   = null;            // current CSRF hash
    var stopped = false;
    var container;

    function metaToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : null;
    }

    function ensureContainer() {
        if (container) return container;
        container = document.createElement('div');
        container.className = 'rem-pop-wrap';
        document.body.appendChild(container);
        return container;
    }

    function esc(s) {
        return (s == null ? '' : String(s)).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token || metaToken() || '',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: body || ''
        }).then(function (r) {
            return r.json().catch(function () { return {}; });
        }).then(function (j) {
            if (j && j.csrf) { token = j.csrf; }   // keep the rotating token fresh
            return j;
        });
    }

    function prioClass(p) {
        return p === 'high' ? 'rem-pop-high' : (p === 'low' ? 'rem-pop-low' : 'rem-pop-med');
    }

    function render(item) {
        if (shown.has(item.id)) return;
        shown.add(item.id);

        var card = document.createElement('div');
        card.className = 'rem-pop ' + prioClass(item.priority);
        card.setAttribute('data-id', item.id);
        card.innerHTML =
            '<div class="rem-pop-ic"><i class="bi bi-alarm-fill"></i></div>' +
            '<div class="rem-pop-body">' +
                '<div class="rem-pop-head">' +
                    '<strong class="rem-pop-title">' + esc(item.title) + '</strong>' +
                    '<button class="rem-pop-x" title="Dismiss" aria-label="Dismiss">&times;</button>' +
                '</div>' +
                (item.message ? '<div class="rem-pop-msg">' + esc(item.message) + '</div>' : '') +
                '<div class="rem-pop-meta"><i class="bi bi-clock"></i> Due: ' + esc(item.due_at) + '</div>' +
                '<div class="rem-pop-actions">' +
                    '<button class="rem-pop-btn rem-pop-done" data-act="done"><i class="bi bi-check2"></i> Done</button>' +
                    '<button class="rem-pop-btn rem-pop-snooze" data-act="snooze" data-min="10">Snooze 10m</button>' +
                    '<button class="rem-pop-btn rem-pop-snooze" data-act="snooze" data-min="60">1h</button>' +
                    '<a class="rem-pop-btn rem-pop-open" href="' + esc(item.url) + '"><i class="bi bi-box-arrow-up-right"></i> Open</a>' +
                '</div>' +
            '</div>';

        ensureContainer().appendChild(card);

        function remove() {
            card.classList.add('rem-pop-out');
            setTimeout(function () { card.remove(); }, 200);
        }

        card.querySelector('.rem-pop-x').addEventListener('click', remove);
        card.querySelectorAll('[data-act]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var act = btn.getAttribute('data-act');
                btn.disabled = true;
                if (act === 'done') {
                    post(base + 'reminders/complete/' + item.id).then(remove);
                } else if (act === 'snooze') {
                    post(base + 'reminders/snooze/' + item.id, 'minutes=' + btn.getAttribute('data-min'))
                        .then(function () { shown.delete(item.id); remove(); });
                }
            });
        });

        // gentle chime (best-effort; ignored if blocked)
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var o = ctx.createOscillator(), g = ctx.createGain();
            o.frequency.value = 880; g.gain.value = 0.05;
            o.connect(g); g.connect(ctx.destination); o.start();
            setTimeout(function () { o.stop(); ctx.close(); }, 160);
        } catch (e) {}
    }

    function poll() {
        if (stopped) return;
        fetch(base + 'reminders/due', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                if (r.status === 401 || r.status === 403) { stopped = true; return null; }
                if (!r.ok) return null;
                return r.json();
            })
            .then(function (j) {
                if (!j) return;
                if (j.csrf) { token = j.csrf; }
                (j.items || []).forEach(render);
            })
            .catch(function () {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        token = metaToken();
        setTimeout(poll, 4000);            // first check shortly after load
        setInterval(poll, POLL_MS);
    });
})();
