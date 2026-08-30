/* ============================================================================
 * Shared Party/account picker enhancer.
 *
 * Upgrades every  input#myInput[name="account_name"]  (and any
 * input[data-acc-picker]) in the admin app into a rich dropdown: search box,
 * All / Jama / Naam filter tabs, avatar rows with each party's live balance,
 * entry count and last activity, and a colour-coded pill (Jama=Cr green,
 * Naam=Dr red). "Type a new one" + Enter creates a party via Quick Add.
 *
 * The original input is kept (hidden) as the submit value holder in the same
 * "Name_<id>" format the server already parses, and native input/change events
 * are dispatched on it so existing per-page logic keeps working. Skips inputs
 * that are already type=hidden, or marked data-no-acc-picker.
 *
 * Per-field registration filter:  data-acc-filter="unregistered" | "registered"
 * restricts the list to parties without / with a valid GSTIN (?reg=... on the
 * feed). Default = every account.
 *
 * Config (set in the layout before this file):
 *   window.ACC_PICKER = { optionsUrl: '.../admin/billing/account_options',
 *                         quickAddUrl: '.../admin/account_name/quick_add' }
 * ==========================================================================*/
(function () {
	var cfg = window.ACC_PICKER || {};
	var CACHE = {}; // url -> { data, loading, waiters:[] }

	function urlFor(filter) {
		var u = cfg.optionsUrl || '';
		if (filter === 'registered' || filter === 'unregistered') {
			u += (u.indexOf('?') > -1 ? '&' : '?') + 'reg=' + filter;
		}
		return u;
	}
	function loadOptions(url, cb) {
		var c = CACHE[url] || (CACHE[url] = { data: null, loading: false, waiters: [] });
		if (c.data) { cb(c.data); return; }
		c.waiters.push(cb);
		if (c.loading) return;
		c.loading = true;
		var done = function (data) {
			c.data = data || [];
			if (url === urlFor('all')) { window.ACCOUNT_OPTIONS = c.data; } // back-compat
			c.loading = false;
			c.waiters.forEach(function (f) { f(c.data); });
			c.waiters = [];
		};
		if (!url) { done([]); return; }
		if (window.jQuery) {
			jQuery.ajax({ url: url, type: 'GET', dataType: 'json', success: done, error: function () { done([]); } });
		} else if (window.fetch) {
			fetch(url).then(function (r) { return r.json(); }).then(done).catch(function () { done([]); });
		} else { done([]); }
	}

	/* ---- stateless formatting helpers ---- */
	function inr(n) { return '₹' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
	function signed(n) { return (n < 0 ? '-' : '') + inr(n); }
	function sideClass(s) { return s === 'Cr' ? 'acc-jama' : (s === 'Dr' ? 'acc-naam' : 'acc-nil'); }
	function avColor(s) { return s === 'Cr' ? '#1f9d70' : (s === 'Dr' ? '#d0483f' : '#8190a5'); }
	function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
	function initial(nm) { nm = (nm || '?').trim(); return (nm.charAt(0) || '?').toUpperCase(); }
	function findById(list, id) { list = list || []; for (var i = 0; i < list.length; i++) if (list[i].account_id === id) return list[i]; return null; }
	function findByName(list, nm) { list = list || []; nm = String(nm).toUpperCase(); for (var i = 0; i < list.length; i++) if (list[i].name.toUpperCase() === nm) return list[i]; return null; }
	function fire(el, type) {
		var ev;
		try { ev = new Event(type, { bubbles: true }); } catch (e) { ev = document.createEvent('Event'); ev.initEvent(type, true, true); }
		el.dispatchEvent(ev);
	}

	function enhance(input) {
		if (!input || input.__accEnhanced) return;
		if (input.type === 'hidden') return;                  // page has its own picker
		if (input.hasAttribute('data-no-acc-picker')) return; // page drives this field itself
		input.__accEnhanced = true;

		var filter = input.getAttribute('data-acc-filter') || 'all';
		var noCreate = input.hasAttribute('data-acc-nocreate'); // search-only fields
		var opts = [];                                        // this instance's account list
		var ph = input.getAttribute('placeholder') || 'Search an account or type a new one...';
		input.style.display = 'none';
		input.setAttribute('autocomplete', 'off');

		var wrap = document.createElement('div');
		wrap.className = 'acc-picker';
		wrap.innerHTML =
			'<div class="acc-picker-box"><span class="acc-picker-sel" style="display:none;"></span>' +
			'<span class="acc-picker-ph">' + esc(ph) + '</span><i class="acc-picker-caret">▾</i></div>' +
			'<div class="acc-dd"><div class="acc-dd-searchwrap"><input type="text" class="acc-dd-search" placeholder="' + esc(ph) + '" autocomplete="off"></div>' +
			'<div class="acc-tabs"><button type="button" class="acc-tab active" data-f="all">All</button>' +
			'<button type="button" class="acc-tab" data-f="Cr">Jama</button>' +
			'<button type="button" class="acc-tab" data-f="Dr">Naam</button></div>' +
			'<div class="acc-list"></div></div>';
		input.parentNode.insertBefore(wrap, input.nextSibling);

		var box = wrap.querySelector('.acc-picker-box'),
			dd = wrap.querySelector('.acc-dd'),
			search = wrap.querySelector('.acc-dd-search'),
			list = wrap.querySelector('.acc-list'),
			selEl = wrap.querySelector('.acc-picker-sel'),
			phEl = wrap.querySelector('.acc-picker-ph'),
			tabs = wrap.querySelectorAll('.acc-tab'),
			curFilter = 'all';

		function isOpen() { return dd.classList.contains('show'); }
		function open() { dd.classList.add('show'); box.classList.add('open'); renderList(); setTimeout(function () { search.focus(); }, 0); }
		function close() { dd.classList.remove('show'); box.classList.remove('open'); }

		function renderList() {
			var q = (search.value || '').trim().toUpperCase(), html = '', shown = 0, i;
			for (i = 0; i < opts.length && shown < 100; i++) {
				var a = opts[i];
				if (curFilter !== 'all' && a.side !== curFilter) continue;
				if (q && a.name.toUpperCase().indexOf(q) === -1) continue;
				shown++;
				var meta = 'ID #' + a.account_id + ' · Bal ' + signed(a.net) + ' · ' + a.entries + ' ' + (a.entries === 1 ? 'entry' : 'entries') + (a.last_txt ? ' · last ' + a.last_txt : '');
				html += '<div class="acc-row" data-id="' + a.account_id + '">' +
					'<span class="acc-av" style="background:' + avColor(a.side) + '">' + esc(initial(a.name)) + '</span>' +
					'<span class="acc-row-main"><span class="acc-row-nm">' + esc(a.name) + ' <span class="acc-id">#' + a.account_id + '</span></span><span class="acc-row-meta">' + meta + '</span></span>' +
					'<span class="acc-pill ' + sideClass(a.side) + '">' + signed(a.net) + '</span></div>';
			}
			if (!shown) {
				var typed = (search.value || '').trim();
				if (typed) {
					html = noCreate
						? '<div class="acc-empty">No matching account.</div>'
						: '<div class="acc-empty">No match. Press <b>Enter</b> to create “<b>' + esc(typed) + '</b>” as a new party.</div>';
				} else {
					html = '<div class="acc-empty">' + (opts.length ? 'No accounts.' : 'Loading accounts…') + '</div>';
				}
			}
			list.innerHTML = html;
		}

		function setValue(v) { input.value = v; fire(input, 'input'); fire(input, 'change'); }

		function selectAccount(a) {
			setValue(a.name + '_' + a.account_id);
			selEl.innerHTML = '<span class="acc-av sm" style="background:' + avColor(a.side) + '">' + esc(initial(a.name)) + '</span>' +
				'<b>' + esc(a.name) + '</b> <span class="acc-id">#' + a.account_id + '</span> <span class="acc-pill ' + sideClass(a.side) + '">' + signed(a.net) + '</span>';
			selEl.style.display = ''; phEl.style.display = 'none';
		}
		function selectFree(nm) {
			setValue(nm);
			selEl.innerHTML = '<b>' + esc(nm) + '</b> <span class="acc-pill acc-new">NEW</span>';
			selEl.style.display = ''; phEl.style.display = 'none';
		}

		function createNew(nm) {
			var run = function () {
				var ok = function (r) {
					if (r && (r.status === 'success' || r.status === 'exists')) {
						var id = parseInt(r.id, 10), a = findById(opts, id);
						if (!a) { a = { account_id: id, name: r.name || nm, side: 'Nil', label: 'Nil', abs: 0, net: 0, amount_txt: '0.00', entries: 0, last_txt: '' }; opts.push(a); }
						selectAccount(a); close();
						if (window.showToast) showToast('success', (r.status === 'exists' ? 'Existing' : 'New') + ' party selected: ' + a.name, 'Party');
					} else if (window.showToast) { showToast('error', (r && r.message) || 'Could not create the party.', 'Party'); }
					else { alert((r && r.message) || 'Could not create the party.'); }
				};
				var fail = function () { if (window.showToast) { showToast('error', 'Could not create the party.', 'Party'); } else { alert('Could not create the party.'); } };
				if (!cfg.quickAddUrl) { fail(); return; }
				if (window.jQuery) { jQuery.ajax({ url: cfg.quickAddUrl, type: 'POST', dataType: 'json', data: { account_name: nm, status: 'Active' }, success: ok, error: fail }); }
				else { fail(); }
			};
			if (window.showConfirm) { showConfirm('Create new party', 'Add “' + nm + '” as a new party / account?', run); }
			else if (confirm('Create new party “' + nm + '”?')) { run(); }
		}

		box.addEventListener('click', function () { isOpen() ? close() : open(); });
		Array.prototype.forEach.call(tabs, function (t) {
			t.addEventListener('click', function (e) {
				e.stopPropagation();
				Array.prototype.forEach.call(tabs, function (x) { x.classList.remove('active'); });
				t.classList.add('active'); curFilter = t.getAttribute('data-f'); renderList();
			});
		});
		search.addEventListener('input', renderList);
		search.addEventListener('click', function (e) { e.stopPropagation(); });
		search.addEventListener('keydown', function (e) {
			if (e.keyCode === 13) {
				e.preventDefault();
				var typed = (search.value || '').trim();
				if (!typed) return;
				var hit = findByName(opts, typed);
				if (hit) { selectAccount(hit); close(); }
				else if (!noCreate) { createNew(typed); }
			}
		});
		list.addEventListener('click', function (e) {
			var row = e.target.closest ? e.target.closest('.acc-row') : null;
			if (!row) return;
			var a = findById(opts, parseInt(row.getAttribute('data-id'), 10));
			if (a) selectAccount(a);
			close();
		});
		document.addEventListener('click', function (e) { if (isOpen() && !wrap.contains(e.target)) close(); });

		function prefill() {
			var v = (input.value || '').trim();
			if (!v) { selEl.style.display = 'none'; phEl.style.display = ''; return; }
			var a = findById(opts, parseInt((v.match(/_(\d+)$/) || [0, 0])[1], 10)) || findByName(opts, v.replace(/_\d+$/, ''));
			a ? selectAccount(a) : selectFree(v.replace(/_\d+$/, ''));
		}

		// Re-sync the chip when a page sets the input value programmatically
		// (jQuery .val() fires no event) — such pages dispatch 'acc:resync'.
		wrap.__accResync = function () { prefill(); if (isOpen()) renderList(); };
		input.addEventListener('acc:resync', function () { prefill(); if (isOpen()) renderList(); });
		prefill();
		loadOptions(urlFor(filter), function (data) { opts = data || []; prefill(); if (isOpen()) renderList(); });
	}

	function boot() {
		var nodes = document.querySelectorAll('input#myInput[name="account_name"], input[data-acc-picker]');
		Array.prototype.forEach.call(nodes, enhance);
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
	window.AccPicker = { enhance: enhance, boot: boot, load: loadOptions };
})();
