/*!
 * action_menu.js — CI4 port of the CI3 shared row-action (kebab ⋮) menu JS
 * (from application/views/layout.php). Powers elements/action_menu.php popups
 * on every DataTables listing. The matching CSS is in admin_shell.css.
 */
// Close every open menu and return each popup to its home cell.
function crCloseActions() {
    document.querySelectorAll('.cr-act-pop.cr-open').forEach(function (p) {
        p.classList.remove('cr-open');
        if (p._crHome) { try { p._crHome.appendChild(p); } catch (e) {} }
        p.style.left = ''; p.style.top = '';
    });
    document.querySelectorAll('.cr-act.open').forEach(function (el) { el.classList.remove('open'); });
}
function crToggleActions(btn) {
    var wrap = btn.closest('.cr-act');
    if (!wrap) return;
    var wasOpen = wrap.classList.contains('open');
    crCloseActions();
    if (wasOpen) return;
    // When closed the popup lives inside its cell; find it there.
    var pop = wrap.querySelector('.cr-act-pop');
    if (!pop) return;
    // Reparent to <body> so no ancestor overflow/transform can clip or offset it.
    pop._crHome = wrap;
    document.body.appendChild(pop);
    wrap.classList.add('open');
    pop.classList.add('cr-open');
    var r = btn.getBoundingClientRect();
    var w = pop.offsetWidth || 208;
    var h = pop.offsetHeight || 260;
    var left = r.right - w;                              // right-align to the button
    if (left < 8) left = 8;
    if (left + w > window.innerWidth - 8) left = window.innerWidth - w - 8;
    var top = r.bottom + 6;                              // open downward…
    if (top + h > window.innerHeight - 10) top = r.top - h - 6;  // …flip up if no room
    if (top < 10) top = 10;
    pop.style.left = left + 'px';
    pop.style.top = top + 'px';
}
document.addEventListener('click', function (e) {
    // Clicks on the trigger button are handled by its inline onclick.
    if (e.target.closest('.cr-act-btn')) return;
    // A click inside an OPEN popup (now parented to body): close after the item acts,
    // unless it's a disabled item.
    var pop = e.target.closest('.cr-act-pop');
    if (pop) {
        var it = e.target.closest('.cr-item');
        if (!it || !it.classList.contains('disabled')) { crCloseActions(); }
        return;
    }
    // Any other click = outside → close.
    crCloseActions();
});
window.addEventListener('scroll', crCloseActions, true);
window.addEventListener('resize', crCloseActions);
