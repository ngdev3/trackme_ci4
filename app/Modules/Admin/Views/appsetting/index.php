<?php
$sections = isset($sections) && is_array($sections) ? $sections : array();
?>
<style>
   .as-page { padding: 24px; color: #18243c; }
   .as-shell { max-width: 820px; margin: 0 auto; }
   .as-hero { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; padding:18px 20px; border:1px solid #dce6f2; border-radius:10px; background:#fff; box-shadow:0 14px 34px rgba(24,36,60,.07); }
   .as-hero h4 { margin:0; font-size:20px; font-weight:900; }
   .as-hero p { margin:5px 0 0; color:#718096; font-size:13px; font-weight:700; }
   .as-card { border:1px solid #dce6f2; border-radius:10px; background:#fff; box-shadow:0 14px 34px rgba(24,36,60,.06); }
   .as-card-head { padding:14px 18px; border-bottom:1px solid #eef3f9; }
   .as-card-head h5 { margin:0; font-size:15px; font-weight:900; color:#0c315f; }
   .as-card-head p { margin:4px 0 0; color:#718096; font-size:12px; font-weight:700; }
   .as-list { list-style:none; margin:0; padding:10px 14px; }
   .as-item { display:flex; align-items:center; gap:12px; padding:12px 12px; border:1px solid #e6edf6; border-radius:10px; margin-bottom:10px; background:#fbfdff; }
   .as-item.dragging { opacity:.55; }
   .as-item.over { border-color:#1769c2; box-shadow:0 0 0 2px rgba(23,105,194,.15); }
   .as-handle { cursor:grab; width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; background:#0c315f; color:#fff; border-radius:8px; flex:0 0 auto; }
   .as-name { font-weight:800; color:#18243c; flex:1 1 auto; }
   .as-toggle { display:inline-flex; align-items:center; gap:8px; font-size:12px; font-weight:800; color:#516174; }
   .as-actions { padding:14px 18px; border-top:1px solid #eef3f9; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
   .as-btn { border-radius:8px; font-weight:800; padding:10px 18px; border:1px solid #dce6f2; background:#fff; color:#0c315f; cursor:pointer; }
   .as-btn.primary { background:linear-gradient(135deg,#1769c2,#0c4a94); color:#fff; border-color:transparent; }
   .as-btn.ghost { color:#b23; }
   .as-status { font-weight:800; font-size:13px; }
   /* Modern show/hide toggle switch (Left Menu card) */
   .tm-vis { display:inline-flex; align-items:center; gap:10px; flex:0 0 auto; }
   .tm-vis .tm-vis-label { font-size:11px; font-weight:800; letter-spacing:.02em; color:#94a3b8; text-transform:uppercase; min-width:48px; text-align:right; }
   .tm-vis.on .tm-vis-label { color:#1f9d52; }
   .tm-switch { position:relative; display:inline-block; width:46px; height:26px; flex:0 0 auto; }
   .tm-switch input { opacity:0; width:0; height:0; position:absolute; }
   .tm-slider { position:absolute; inset:0; background:#d3dce8; border-radius:999px; cursor:pointer; transition:.25s; }
   .tm-slider:before { content:""; position:absolute; height:20px; width:20px; left:3px; top:3px; background:#fff; border-radius:50%; box-shadow:0 1px 3px rgba(16,32,58,.35); transition:.25s; }
   .tm-switch input:checked + .tm-slider { background:linear-gradient(135deg,#1769c2,#0c4a94); }
   .tm-switch input:checked + .tm-slider:before { transform:translateX(20px); }
   .tm-switch input:focus-visible + .tm-slider { box-shadow:0 0 0 3px rgba(23,105,194,.25); }
   .as-item.tm-hidden-row { background:#f5f7fa; }
   .as-item.tm-hidden-row .as-name { color:#94a3b8; }
   .as-item.tm-locked .tm-switch { opacity:.55; cursor:not-allowed; }
   .as-item.tm-locked .tm-slider { cursor:not-allowed; }
   .as-item.tm-locked .tm-vis-label { color:#94a3b8; }
</style>

<main class="main-content bgc-grey-100 as-page">
   <div id="mainContent">
      <div class="as-shell">
         <div class="as-hero">
            <div>
               <h4><i class="ti-settings"></i> App Settings</h4>
               <p>Personalise your dashboard — reorder the sections by dragging, and show or hide the ones you use. Saved for your account only.</p>
            </div>
            <a class="as-btn" href="<?php echo base_url('admin/dashboard'); ?>"><i class="ti-dashboard"></i> Go to Dashboard</a>
         </div>

         <div class="as-card">
            <div class="as-card-head">
               <h5>Dashboard Layout</h5>
               <p>Drag <i class="ti-move"></i> to reorder. Untick a section to hide it from your dashboard.</p>
            </div>

            <ul class="as-list" id="asList">
               <?php foreach ($sections as $s): ?>
                  <li class="as-item" draggable="true" data-key="<?php echo html_escape($s['key']); ?>">
                     <span class="as-handle" title="Drag to reorder"><i class="ti-move"></i></span>
                     <span class="as-name"><?php echo html_escape($s['label']); ?></span>
                     <label class="as-toggle">
                        <input type="checkbox" class="as-visible" <?php echo empty($s['hidden']) ? 'checked' : ''; ?>>
                        <span>Show</span>
                     </label>
                  </li>
               <?php endforeach; ?>
            </ul>

            <div class="as-actions">
               <button type="button" class="as-btn primary" id="asSave"><i class="ti-check"></i> Save layout</button>
               <button type="button" class="as-btn ghost" id="asReset"><i class="ti-reload"></i> Reset to default</button>
               <span class="as-status" id="asStatus"></span>
            </div>
         </div>

         <div class="as-card" style="margin-top:16px;">
            <div class="as-card-head">
               <h5>Left Menu</h5>
               <p>Drag <i class="ti-move"></i> to reorder your sidebar menu. Untick a menu to hide it from your sidebar. Saved for your account only.</p>
            </div>

            <ul class="as-list" id="menuList">
               <li class="as-item" style="justify-content:center;color:#94a3b8;font-weight:800;">Loading menu&hellip;</li>
            </ul>

            <div class="as-actions">
               <button type="button" class="as-btn primary" id="menuSave"><i class="ti-check"></i> Save menu</button>
               <button type="button" class="as-btn ghost" id="menuReset"><i class="ti-reload"></i> Reset to default</button>
               <span class="as-status" id="menuStatus"></span>
            </div>
         </div>
      </div>
   </div>
</main>

<script>
(function () {
   var list = document.getElementById('asList');
   if (!list) { return; }
   var saveUrl  = '<?php echo base_url('admin/app_setting/save_dashboard_layout'); ?>';
   var resetUrl = '<?php echo base_url('admin/app_setting/reset_dashboard_layout'); ?>';
   var status = document.getElementById('asStatus');

   function items() {
      return Array.prototype.filter.call(list.children, function (el) {
         return el.nodeType === 1 && el.classList.contains('as-item');
      });
   }
   function layout() {
      return items().map(function (el) {
         var cb = el.querySelector('.as-visible');
         return { key: el.getAttribute('data-key'), hidden: (cb && cb.checked) ? 0 : 1 };
      });
   }
   function setStatus(msg, ok) {
      status.textContent = msg;
      status.style.color = ok ? '#1f9d52' : '#e5484d';
      if (msg) { setTimeout(function () { status.textContent = ''; }, 2500); }
   }

   /* Drag reorder */
   var dragEl = null;
   list.addEventListener('dragstart', function (e) {
      dragEl = e.target.closest('.as-item');
      if (dragEl) { dragEl.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', 'x'); } catch (_) {} }
   });
   list.addEventListener('dragend', function () {
      if (dragEl) { dragEl.classList.remove('dragging'); }
      dragEl = null;
      Array.prototype.forEach.call(list.querySelectorAll('.over'), function (x) { x.classList.remove('over'); });
   });
   list.addEventListener('dragover', function (e) {
      if (!dragEl) { return; }
      e.preventDefault();
      var over = e.target.closest('.as-item');
      if (!over || over === dragEl) { return; }
      Array.prototype.forEach.call(list.querySelectorAll('.over'), function (x) { x.classList.remove('over'); });
      over.classList.add('over');
      var rect = over.getBoundingClientRect();
      var after = (e.clientY - rect.top) > rect.height / 2;
      list.insertBefore(dragEl, after ? over.nextSibling : over);
   });

   function post(url, body, okMsg) {
      var opts = { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } };
      if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
      return fetch(url, opts).then(function (r) { return r.json(); }).then(function (res) {
         setStatus((res && res.message) ? res.message : okMsg, !res || res.status !== 'error');
         return res;
      }).catch(function () { setStatus('Request failed. Please try again.', false); });
   }

   document.getElementById('asSave').addEventListener('click', function () {
      post(saveUrl, { layout: layout() }, 'Saved.');
   });
   document.getElementById('asReset').addEventListener('click', function () {
      showConfirm('Reset dashboard', 'Reset your dashboard layout to the default order and show all sections?', function () {
         post(resetUrl, null, 'Reset.').then(function () { setTimeout(function () { window.location.reload(); }, 700); });
      });
   });
})();
</script>

<script>
(function () {
   var list = document.getElementById('menuList');
   if (!list) { return; }
   var saveUrl  = '<?php echo base_url('admin/menu_order/save'); ?>';
   var resetUrl = '<?php echo base_url('admin/menu_order/reset'); ?>';
   var status = document.getElementById('menuStatus');
   var protectedSet = {};
   (window.TM_MENU_PROTECTED || ['Dashboard', 'App Settings', 'Setting']).forEach(function (k) { protectedSet[k] = 1; });

   function setStatus(msg, ok) {
      status.textContent = msg;
      status.style.color = ok ? '#1f9d52' : '#e5484d';
      if (msg) { setTimeout(function () { status.textContent = ''; }, 2500); }
   }
   function items() {
      return Array.prototype.filter.call(list.children, function (el) {
         return el.nodeType === 1 && el.classList.contains('as-item') && el.getAttribute('data-key');
      });
   }
   function orderKeys()  { return items().map(function (el) { return el.getAttribute('data-key'); }); }
   function hiddenKeys() {
      return items().filter(function (el) {
         var cb = el.querySelector('.as-visible'); return cb && !cb.checked;
      }).map(function (el) { return el.getAttribute('data-key'); });
   }

   function render(data) {
      list.innerHTML = '';
      if (!data.length) {
         list.innerHTML = '<li class="as-item" style="justify-content:center;color:#94a3b8;font-weight:800;">No menu items found.</li>';
         return;
      }
      data.forEach(function (it) {
         var li = document.createElement('li');
         li.className = 'as-item';
         li.setAttribute('draggable', 'true');
         li.setAttribute('data-key', it.key);
         li.innerHTML =
            '<span class="as-handle" title="Drag to reorder"><i class="ti-move"></i></span>' +
            '<span class="as-name"></span>' +
            '<span class="tm-vis"><span class="tm-vis-label"></span>' +
            '<label class="tm-switch" title="Show or hide this menu"><input type="checkbox" class="as-visible"><span class="tm-slider"></span></label></span>';
         li.querySelector('.as-name').textContent = it.label;
         var cb = li.querySelector('.as-visible');
         cb.checked = !it.hidden;
         if (protectedSet[it.key]) {
            cb.checked = true;
            cb.disabled = true;
            li.classList.add('tm-locked');
            li.querySelector('.tm-switch').setAttribute('title', 'Always visible (required to reach settings)');
         }
         syncRow(li);
         list.appendChild(li);
      });
   }

   /* Reflect a row's visible/hidden state in its label & styling. */
   function syncRow(li) {
      var cb = li.querySelector('.as-visible');
      var on = !!(cb && cb.checked);
      var locked = li.classList.contains('tm-locked');
      var vis = li.querySelector('.tm-vis');
      var lbl = li.querySelector('.tm-vis-label');
      if (lbl) { lbl.textContent = locked ? 'Required' : (on ? 'Visible' : 'Hidden'); }
      if (vis) { vis.classList.toggle('on', on && !locked); }
      li.classList.toggle('tm-hidden-row', !on);
   }
   list.addEventListener('change', function (e) {
      if (e.target && e.target.classList.contains('as-visible')) {
         syncRow(e.target.closest('.as-item'));
      }
   });

   /* Drag reorder (same behaviour as the dashboard layout list). */
   var dragEl = null;
   list.addEventListener('dragstart', function (e) {
      dragEl = e.target.closest('.as-item');
      if (dragEl) { dragEl.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', 'x'); } catch (_) {} }
   });
   list.addEventListener('dragend', function () {
      if (dragEl) { dragEl.classList.remove('dragging'); }
      dragEl = null;
      Array.prototype.forEach.call(list.querySelectorAll('.over'), function (x) { x.classList.remove('over'); });
   });
   list.addEventListener('dragover', function (e) {
      if (!dragEl) { return; }
      e.preventDefault();
      var over = e.target.closest('.as-item');
      if (!over || over === dragEl) { return; }
      Array.prototype.forEach.call(list.querySelectorAll('.over'), function (x) { x.classList.remove('over'); });
      over.classList.add('over');
      var rect = over.getBoundingClientRect();
      var after = (e.clientY - rect.top) > rect.height / 2;
      list.insertBefore(dragEl, after ? over.nextSibling : over);
   });

   function build(tries) {
      if (!window.TM_MENU) {
         if ((tries || 0) > 40) { setStatus('Sidebar not ready.', false); return; }
         setTimeout(function () { build((tries || 0) + 1); }, 150);
         return;
      }
      render(window.TM_MENU.read());
   }
   build(0);

   document.getElementById('menuSave').addEventListener('click', function () {
      var order = orderKeys(), hidden = hiddenKeys();
      var body = 'order=' + encodeURIComponent(JSON.stringify(order)) + '&hidden=' + encodeURIComponent(JSON.stringify(hidden));
      fetch(saveUrl, {
         method: 'POST', credentials: 'same-origin',
         headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
         body: body
      }).then(function (r) { return r.json(); })
        .then(function (res) {
           setStatus((res && res.status === 'success') ? 'Menu saved.' : 'Could not save.', !res || res.status === 'success');
           if (window.TM_MENU) { window.TM_MENU.apply(order, hidden); }
        }).catch(function () { setStatus('Save failed. Please try again.', false); });
   });

   document.getElementById('menuReset').addEventListener('click', function () {
      showConfirm('Reset menu', 'Reset your sidebar menu to the default order and show all items?', function () {
      fetch(resetUrl, { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function () { setStatus('Reset.', true); setTimeout(function () { window.location.reload(); }, 700); })
        .catch(function () { setStatus('Reset failed.', false); });
      });
   });
})();
</script>
