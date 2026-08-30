<?php
$row    = isset($row) ? $row : null;
$units  = isset($units) ? $units : array('Qtl', 'Kg', 'Ton', 'Bag');
$error  = isset($error) ? $error : '';
$esc    = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$isEdit = ($row !== null);

$v_name = $isEdit ? $row->product_name : (isset($old['product_name']) ? $old['product_name'] : '');
$v_unit = $isEdit ? (isset($row->unit) ? $row->unit : 'Qtl') : (isset($old['unit']) ? $old['unit'] : 'Qtl');
$v_hsn  = $isEdit ? $row->hsn_code : (isset($old['hsn_code']) ? $old['hsn_code'] : '');
$v_stat = $isEdit ? $row->status : (isset($old['status']) ? $old['status'] : 'Active');
$action = $isEdit ? base_url('admin/item_master/edit/' . ID_encode($row->id)) : base_url('admin/item_master/add');
?>
<style>
    .im-form-scope { color: #18243c; }
    .im-form-shell { max-width: 720px; margin: 0 auto; }
    .im-form-hero { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
        padding: 18px 22px; margin-bottom: 16px; border-radius: 14px; color: #fff;
        background: linear-gradient(125deg, #0f2748, #1d4ed8 60%, #3b1e6e); box-shadow: 0 16px 38px rgba(16,32,72,.26); }
    .im-form-hero h1 { margin: 0; font-size: 20px; font-weight: 900; }
    .im-form-hero h1 small { display: block; font-size: 12px; font-weight: 700; color: rgba(235,242,255,.85); margin-top: 3px; }
    .im-back { display: inline-flex; align-items: center; gap: 7px; min-height: 38px; padding: 0 14px; border-radius: 9px; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.28); color: #fff !important; font-weight: 800; font-size: 12.5px; text-decoration: none; }
    .im-back:hover { background: rgba(255,255,255,.26); text-decoration: none; }
    .im-form-card { border: 1px solid #e3e9f2; border-radius: 14px; background: #fff; box-shadow: 0 14px 34px rgba(24,36,60,.07); padding: 22px 24px; }
    .im-fg { margin-bottom: 18px; }
    .im-fg label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 800; color: #263655; }
    .im-fg label .req { color: #b91c1c; }
    .im-fg .form-control { min-height: 46px; border: 1px solid #dce6f2; border-radius: 10px; background: #fbfdff; font-weight: 700; color: #14213d; }
    .im-fg .form-control:focus { border-color: #1769c2; box-shadow: 0 0 0 4px rgba(23,105,194,.12); }
    .im-hint { margin-top: 5px; font-size: 11.5px; color: #8794a8; font-weight: 700; }
    .im-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .im-err { margin-bottom: 16px; padding: 11px 14px; border-radius: 10px; background: #fee2e2; color: #b42318; border: 1px solid #fecaca; font-weight: 800; font-size: 13px; }
    .im-form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }
    .im-btn { min-height: 44px; padding: 0 22px; border-radius: 10px; font-weight: 900; font-size: 13px; border: 0; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .im-btn-cancel { background: #eef2f7; color: #475569; text-decoration: none; }
    .im-btn-save { background: linear-gradient(135deg,#1769c2,#0c315f); color: #fff; box-shadow: 0 12px 26px rgba(23,105,194,.3); }
    @media (max-width: 560px) { .im-row { grid-template-columns: 1fr; } }
</style>

<main class="main-content im-form-scope">
    <div id="mainContent">
        <div class="container-fluid im-form-shell">
            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>

            <section class="im-form-hero">
                <h1><?= $isEdit ? 'Edit Item' : 'Add Item' ?>
                    <small><?= $isEdit ? 'Update this stock item' : 'Create a product / item usable in Stock' ?></small>
                </h1>
                <a href="<?php echo base_url('admin/item_master/listing'); ?>" class="im-back"><i class="fa fa-arrow-left"></i> Back to list</a>
            </section>

            <div class="im-form-card">
                <?php if ($error !== ''): ?><div class="im-err"><i class="fa fa-exclamation-circle"></i> <?= $esc($error) ?></div><?php endif; ?>

                <form method="post" action="<?= $action ?>" autocomplete="off">
                    <div class="im-fg">
                        <label>Item Name <span class="req">*</span></label>
                        <input type="text" name="product_name" class="form-control" required maxlength="100"
                               placeholder="e.g. Basmati Rice, Paddy 1121, Bran" value="<?= $esc($v_name) ?>">
                        <div class="im-hint">This name appears in the Stock module product picker.</div>
                    </div>

                    <div class="im-row">
                        <div class="im-fg">
                            <label>Unit <span class="req">*</span></label>
                            <select name="unit" class="form-control" required>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $esc($u) ?>" <?= ($v_unit === $u ? 'selected' : '') ?>><?= $esc($u) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="im-hint">Default stock unit for this item.</div>
                        </div>

                        <div class="im-fg">
                            <label>HSN Code <span class="req">*</span></label>
                            <input type="text" name="hsn_code" class="form-control" required maxlength="20"
                                   placeholder="e.g. 1006" value="<?= $esc($v_hsn) ?>">
                            <div class="im-hint">For tax / invoice classification.</div>
                        </div>
                    </div>

                    <div class="im-fg">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?= ($v_stat === 'Active' ? 'selected' : '') ?>>Active</option>
                            <option value="Inactive" <?= ($v_stat === 'Inactive' ? 'selected' : '') ?>>Inactive</option>
                        </select>
                        <div class="im-hint">Only Active items show in the Stock picker.</div>
                    </div>

                    <div class="im-form-actions">
                        <a href="<?php echo base_url('admin/item_master/listing'); ?>" class="im-btn im-btn-cancel">Cancel</a>
                        <button type="submit" class="im-btn im-btn-save"><i class="fa fa-check"></i> <?= $isEdit ? 'Update Item' : 'Save Item' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
