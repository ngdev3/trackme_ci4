<?php
/** Settings hub — current workspace + quick links to the ported settings areas. */
helper(['url']);
$firm = $firm ?? null;
$cards = [
    ['Change Firm / FY', 'ti-exchange-vertical', 'Switch the active workspace (firm + financial year).', 'javascript:void(0)', 'exampleModal'],
];
if (function_exists('erp_is_super_admin') && erp_is_super_admin()) {
    $cards[] = ['View-Only Users', 'ti-eye', 'Make a user global read-only (block add/edit/update/delete app-wide).', base_url('admin/setting/view_only'), ''];
}
$cards = array_merge($cards, [
    ['GST Settings', 'ti-receipt', 'E-invoice / GST portal credentials and defaults.', base_url('admin/gst_setting'), ''],
    ['Users', 'ti-user', 'Manage panel users and their status.', base_url('admin/users/listing'), ''],
    ['Role Permissions', 'ti-lock', 'Module access per role.', base_url('admin/role_permissions'), ''],
    ['User Permissions', 'ti-key', 'Per-user module overrides.', base_url('admin/user_permissions'), ''],
    ['HSN Code Master', 'ti-list', 'Commodity HSN codes used across invoices & stock.', base_url('admin/hsn/listing'), ''],
]);
?>
<main class="main-content"><div id="mainContent"><div class="container-fluid" style="max-width:1080px;margin:0 auto;padding-top:6px;">

  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
    <div>
      <h3 style="font-weight:900;margin:0;">Settings</h3>
      <div class="text-muted" style="font-size:13px;">Workspace &amp; panel configuration</div>
    </div>
    <?php if ($firm): ?>
      <div style="background:#eef6ff;border:1px solid #cfe3fb;border-radius:12px;padding:10px 16px;font-weight:800;color:#18243c;">
        <i class="ti-briefcase" style="color:#1769c2;"></i>
        <?= esc($firm->firm_name ?? '—') ?>
        <span style="color:#1769c2;">· ID-</span><?= esc(($firm->template_id ?? '') . '_' . ($firm->track_name ?? ($firm->FY ?? ''))) ?>
      </div>
    <?php endif; ?>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
    <?php foreach ($cards as $c): [$label, $icon, $desc, $href, $modal] = $c; ?>
      <a href="<?= $href ?>" <?= $modal ? 'data-toggle="modal" data-target="#' . esc($modal) . '"' : '' ?>
         style="display:block;text-decoration:none;background:#fff;border:1px solid #e3e9f2;border-radius:14px;padding:18px 18px;transition:box-shadow .15s,transform .15s;"
         onmouseover="this.style.boxShadow='0 10px 26px rgba(16,32,72,.12)';this.style.transform='translateY(-2px)';"
         onmouseout="this.style.boxShadow='';this.style.transform='';">
        <div style="width:44px;height:44px;border-radius:11px;background:#eef6ff;color:#1769c2;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:12px;">
          <i class="<?= esc($icon) ?>"></i>
        </div>
        <div style="font-weight:800;color:#18243c;font-size:15px;"><?= esc($label) ?></div>
        <div class="text-muted" style="font-size:12.5px;margin-top:4px;line-height:1.5;"><?= esc($desc) ?></div>
      </a>
    <?php endforeach; ?>
  </div>

</div></div></main>
