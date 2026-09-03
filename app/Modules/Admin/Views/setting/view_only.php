<?php
/** View-Only Users manager — global + per-firm scope. CI4, Super-Admin-only. */
helper(['url']);
$users          = $users ?? [];
$templates      = $templates ?? [];
$user_templates = $user_templates ?? [];
$onCount = 0;
foreach ($users as $u) {
    if ((int) ($u->is_view_only ?? 0) === 1 || ! empty($user_templates[(int) $u->id])) { $onCount++; }
}
?>
<link rel="stylesheet" href="<?= base_url('assets/global/plugins/select2/select2.css') ?>">
<style>
  .vo{color:var(--tm-ink,#18243c);padding:22px}.vo-shell{margin:0 auto;max-width:1180px}
  .vo-hero{align-items:center;background:linear-gradient(135deg,var(--tm-brand,#1769c2),var(--tm-brand-dark,#0f4e97));border-radius:14px;box-shadow:0 16px 40px rgba(23,105,194,.22);color:#fff;display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;margin-bottom:16px;padding:20px 24px}
  .vo-hero-l{align-items:center;display:flex;gap:14px}
  .vo-hero-ic{align-items:center;background:rgba(255,255,255,.16);border-radius:12px;display:flex;font-size:22px;height:50px;justify-content:center;width:50px}
  .vo-hero h4{font-size:21px;font-weight:900;margin:0}.vo-hero p{font-size:12.5px;font-weight:600;margin:3px 0 0;opacity:.92}
  .vo-back{align-items:center;background:rgba(255,255,255,.18);border-radius:10px;color:#fff;display:inline-flex;font-weight:800;gap:7px;padding:9px 15px;text-decoration:none}.vo-back:hover{background:rgba(255,255,255,.28);color:#fff}
  .vo-note{background:#eef6ff;border:1px solid #cfe3fb;border-radius:12px;color:#25507e;font-size:12.5px;font-weight:600;line-height:1.55;margin-bottom:16px;padding:13px 16px}
  .vo-note b{color:#173a5e}
  .vo-card{background:#fff;border:1px solid #e5ecf5;border-radius:14px;box-shadow:0 16px 38px rgba(24,36,60,.07);overflow:hidden}
  .vo-bar{align-items:center;border-bottom:1px solid #eef2f7;display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;padding:14px 18px;background:#fbfdff}
  .vo-search{position:relative;flex:1 1 320px;max-width:420px}
  .vo-search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#8394a7;font-size:14px}
  .vo-search input{width:100%;min-height:42px;border:1.5px solid #dce6f2;border-radius:10px;padding:9px 12px 9px 34px;background:#fff;font-weight:600;color:var(--tm-ink,#18243c)}
  .vo-search input:focus{outline:0;border-color:var(--tm-brand,#1769c2);box-shadow:0 0 0 4px rgba(23,105,194,.12)}
  .vo-pill{display:inline-flex;align-items:center;gap:6px;background:#fff5e6;color:#b45309;border:1px solid #fde3bf;border-radius:20px;font-size:12px;font-weight:800;padding:6px 12px}
  .vo-twrap{overflow-x:auto}
  #vo-table{border-collapse:separate;border-spacing:0;width:100%;min-width:920px}
  #vo-table th{background:#f7f9fc;border-bottom:2px solid #e6edf5;color:#516174;font-size:10px;font-weight:900;letter-spacing:.03em;padding:12px 16px;text-align:left;text-transform:uppercase;white-space:nowrap}
  #vo-table td{border-bottom:1px solid #eef2f6;color:#26374f;font-size:13px;font-weight:700;padding:12px 16px;vertical-align:middle}
  #vo-table tbody tr:hover td{background:#f7fbff}
  .vo-uname{font-weight:800;color:var(--tm-ink,#18243c)}
  .vo-muted{color:#8394a7;font-weight:600;font-size:11.5px}
  .vo-badge{display:inline-block;border-radius:20px;font-size:10px;font-weight:800;padding:3px 10px}
  .vo-badge-on{background:#fff5e6;color:#b45309}
  .vo-badge-active{background:#e7f6ee;color:#0a6f49}.vo-badge-off{background:#f1f5f9;color:#64748b}
  .vo-switch{position:relative;display:inline-flex;align-items:center;cursor:pointer;user-select:none;gap:9px;margin:0 0 8px;font-weight:800;font-size:12px;color:#516174}
  .vo-switch input{position:absolute;opacity:0;width:0;height:0}
  .vo-track{width:44px;height:24px;border-radius:20px;background:#cbd5e1;position:relative;transition:background .18s;flex:0 0 auto}
  .vo-track:after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .18s}
  .vo-switch input:checked + .vo-track{background:var(--tm-brand,#1769c2)}
  .vo-switch input:checked + .vo-track:after{transform:translateX(20px)}
  .vo-firms{min-width:280px;max-width:420px}
  .vo-firms.is-dim{opacity:.45;pointer-events:none}
  .vo-firms .select2-container{width:100%!important}
  .vo-foot{display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;padding:16px 18px;border-top:1px solid #eef2f7;background:#fbfdff}
  .vo-btn{align-items:center;border:0;border-radius:10px;cursor:pointer;display:inline-flex;font-weight:800;gap:8px;min-height:44px;padding:0 20px;font-size:14px;color:#fff;background:linear-gradient(135deg,var(--tm-brand,#1769c2),var(--tm-brand-dark,#0c315f))}
  .vo-btn:disabled{opacity:.7;cursor:default}
  .vo-empty{color:#9aa7b6;font-style:italic;text-align:center;padding:34px}
  @media(max-width:560px){.vo{padding:14px}.vo-hero{flex-direction:column;align-items:flex-start}}
</style>

<main class="main-content bgc-grey-100 vo">
  <div id="mainContent">
    <div class="container-fluid vo-shell">

      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

      <section class="vo-hero">
        <div class="vo-hero-l">
          <span class="vo-hero-ic"><i class="ti-eye"></i></span>
          <div>
            <h4>View-Only Users</h4>
            <p>Make a user read-only — everywhere (all firms) or only in specific firms.</p>
          </div>
        </div>
        <a href="<?= base_url('admin/setting/hub') ?>" class="vo-back"><i class="ti-arrow-left"></i> Back to Settings</a>
      </section>

      <div class="vo-note">
        <b>How it works:</b> a view-only user can <b>see</b> everything they have access to, but <b>Add, Edit, Update &amp; Delete are turned off</b>.
        Toggle <b>All firms</b> to lock them everywhere, or leave it off and pick <b>specific firms</b> — they'll be read-only only while working in those firms.
        <b>Super Admin is never restricted</b> and is not listed here.
      </div>

      <form method="post" action="<?= base_url('admin/setting/save_view_only') ?>" id="view-only-form">
        <?= csrf_field() ?>
        <div class="vo-card">
          <div class="vo-bar">
            <div class="vo-search"><i class="ti-search"></i><input type="text" id="vo-search" placeholder="Search user by name / email / mobile…"></div>
            <span class="vo-pill"><i class="ti-eye"></i> <span id="vo-count"><?= $onCount ?></span> view-only</span>
          </div>

          <div class="vo-twrap">
            <table id="vo-table">
              <thead>
                <tr>
                  <th style="width:52px;">#</th>
                  <th>User</th>
                  <th>Email / Mobile</th>
                  <th style="width:90px;">Status</th>
                  <th style="min-width:320px;">Read-Only Scope</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($users)): ?>
                  <tr><td colspan="5" class="vo-empty">No users found.</td></tr>
                <?php else: $i = 0; foreach ($users as $u): $i++;
                  $uid    = (int) $u->id;
                  $name   = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                  if ($name === '') { $name = 'User #' . $uid; }
                  $global = ((int) ($u->is_view_only ?? 0) === 1);
                  $sel    = $user_templates[$uid] ?? [];
                  $active = strtolower((string) ($u->status ?? '')) === 'active';
                  $anyOn  = $global || ! empty($sel);
                ?>
                  <tr class="vo-row" data-search="<?= esc(strtolower($name . ' ' . ($u->email ?? '') . ' ' . ($u->mobile ?? ''))) ?>">
                    <td><?= $i ?></td>
                    <td><div class="vo-uname"><?= esc($name) ?> <?php if ($anyOn): ?><span class="vo-badge vo-badge-on">VIEW ONLY</span><?php endif; ?></div></td>
                    <td>
                      <div><?= esc((string) ($u->email ?? '—')) ?></div>
                      <div class="vo-muted"><?= esc((string) ($u->mobile ?? '')) ?></div>
                    </td>
                    <td><span class="vo-badge <?= $active ? 'vo-badge-active' : 'vo-badge-off' ?>"><?= esc((string) ($u->status ?: 'Active')) ?></span></td>
                    <td>
                      <label class="vo-switch">
                        <input type="checkbox" class="vo-global" name="vo_global[]" value="<?= $uid ?>" <?= $global ? 'checked' : '' ?>>
                        <span class="vo-track"></span>
                        <span>All firms (everywhere)</span>
                      </label>
                      <div class="vo-firms<?= $global ? ' is-dim' : '' ?>">
                        <select class="vo-tpl" name="vo_tpl[<?= $uid ?>][]" multiple data-placeholder="…or pick specific firms">
                          <?php foreach ($templates as $t):
                              $tid = (int) $t->template_id;
                              $lbl = trim((string) ($t->firm_name ?? '')) . ' — FY ' . ($t->FY ?? '') . (($t->track_name ?? '') ? ' (' . $t->track_name . ')' : '');
                          ?>
                            <option value="<?= $tid ?>" <?= in_array($tid, $sel, true) ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>

          <div class="vo-foot">
            <span class="vo-muted"><?= count($templates) ?> active firm(s) available</span>
            <button type="submit" class="vo-btn" id="vo-save"><i class="ti-save"></i> Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</main>

<script src="<?= base_url('assets/global/plugins/select2/select2.min.js') ?>"></script>
<script>
(function () {
    if (window.jQuery && jQuery.fn.select2) { jQuery('.vo-tpl').select2({ width: '100%' }); }
    function rowOn(row) {
        var g = jQuery(row).find('.vo-global').is(':checked');
        var f = jQuery(row).find('.vo-tpl').val();
        return g || (f && f.length);
    }
    function updateCount() {
        var n = 0;
        jQuery('.vo-row').each(function () { if (rowOn(this)) n++; });
        jQuery('#vo-count').text(n);
    }
    jQuery(document).on('change', '.vo-global', function () {
        jQuery(this).closest('td').find('.vo-firms').toggleClass('is-dim', this.checked);
        updateCount();
    });
    jQuery(document).on('change', '.vo-tpl', updateCount);
    jQuery('#vo-search').on('keyup', function () {
        var q = jQuery(this).val().toLowerCase().trim();
        jQuery('.vo-row').each(function () { jQuery(this).toggle(jQuery(this).attr('data-search').indexOf(q) !== -1); });
    });
    jQuery('#view-only-form').on('submit', function () { jQuery('#vo-save').prop('disabled', true).html('<i class="ti-save"></i> Saving…'); });
    updateCount();
})();
</script>
