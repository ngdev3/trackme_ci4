<?php
$r = isset($rates) ? $rates : array('cgst' => '2.5', 'sgst' => '2.5', 'igst' => '0');
$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
?>
<style>
    .gs-scope { color: #18243c; } .gs-shell { max-width: 720px; margin: 0 auto; }
    .gs-hero { display: flex; align-items: center; gap: 13px; padding: 18px 22px; margin-bottom: 16px; border-radius: 14px; color: #fff;
        background: radial-gradient(circle at 90% -30%, rgba(120,170,255,.5), transparent 40%), linear-gradient(125deg, #0f2748, #1d4ed8 60%, #2f9e6f); box-shadow: 0 18px 42px rgba(16,32,72,.26); }
    .gs-hero-ic { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; font-size: 20px; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.26); }
    .gs-hero h1 { margin: 0; font-size: 21px; font-weight: 900; } .gs-hero small { display: block; font-size: 12px; font-weight: 700; color: rgba(235,242,255,.85); margin-top: 3px; }
    .gs-card { border: 1px solid #e3e9f2; border-radius: 14px; background: #fff; box-shadow: 0 14px 34px rgba(24,36,60,.07); padding: 22px 24px; }
    .gs-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 18px; }
    .gs-fg label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 800; color: #263655; }
    .gs-input { position: relative; }
    .gs-input .form-control { min-height: 48px; border: 1px solid #dce6f2; border-radius: 10px; background: #fbfdff; font-weight: 800; color: #14213d; font-size: 16px; padding-right: 30px; }
    .gs-input .form-control:focus { border-color: #1769c2; box-shadow: 0 0 0 4px rgba(23,105,194,.12); }
    .gs-input .pct { position: absolute; right: 12px; top: 12px; color: #8794a8; font-weight: 800; }
    .gs-hint { color: #8794a8; font-size: 12px; font-weight: 700; margin-top: -6px; margin-bottom: 16px; }
    .gs-note { padding: 11px 14px; border-radius: 10px; background: #eef3fb; border: 1px solid #d5e0f2; color: #2f4b7c; font-size: 12.5px; font-weight: 700; margin-bottom: 18px; }
    .gs-total { text-align: center; margin: 4px 0 16px; font-size: 13px; font-weight: 800; color: #2f9e6f; }
    .gs-actions { display: flex; justify-content: flex-end; }
    .gs-btn { min-height: 46px; padding: 0 26px; border-radius: 11px; font-weight: 900; font-size: 14px; border: 0; cursor: pointer; background: linear-gradient(135deg,#1769c2,#0c315f); color: #fff; box-shadow: 0 12px 26px rgba(23,105,194,.3); display: inline-flex; align-items: center; gap: 8px; }
    @media (max-width: 560px) { .gs-row { grid-template-columns: 1fr; } }
</style>

<main class="main-content gs-scope">
    <div id="mainContent">
        <div class="container-fluid gs-shell">
            <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')); ?></div><?php endif; ?>
            <section class="gs-hero">
                <div class="gs-hero-ic"><i class="fa fa-percent"></i></div>
                <div><h1>GST Settings <small>Default CGST / SGST / IGST rates for new Tax Invoices &amp; E-Invoices &middot; Super Admin</small></h1></div>
            </section>

            <div class="gs-card">
                <div class="gs-note"><i class="fa fa-info-circle"></i> These rates auto-fill the GST fields when creating a new Tax Invoice / E-Invoice. They can still be changed per invoice.</div>
                <form method="post" action="<?= base_url('admin/gst_setting') ?>" autocomplete="off">
                    <div class="gs-row">
                        <div class="gs-fg">
                            <label>CGST</label>
                            <div class="gs-input"><input type="number" name="cgst" id="gsC" class="form-control" min="0" max="100" step="0.001" required value="<?= $esc($r['cgst']) ?>"><span class="pct">%</span></div>
                        </div>
                        <div class="gs-fg">
                            <label>SGST</label>
                            <div class="gs-input"><input type="number" name="sgst" id="gsS" class="form-control" min="0" max="100" step="0.001" required value="<?= $esc($r['sgst']) ?>"><span class="pct">%</span></div>
                        </div>
                        <div class="gs-fg">
                            <label>IGST</label>
                            <div class="gs-input"><input type="number" name="igst" id="gsI" class="form-control" min="0" max="100" step="0.001" required value="<?= $esc($r['igst']) ?>"><span class="pct">%</span></div>
                        </div>
                    </div>
                    <div class="gs-hint">Intra-state = CGST + SGST. Inter-state = IGST. Set the rates your firm normally applies.</div>
                    <div class="gs-total" id="gsTotal"></div>
                    <div class="gs-actions">
                        <button type="submit" class="gs-btn"><i class="fa fa-check"></i> Save GST Rates</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    (function(){
        function num(id){ var e=document.getElementById(id); return e?parseFloat(e.value||0)||0:0; }
        function upd(){ document.getElementById('gsTotal').textContent = 'Intra-state total GST: ' + (num('gsC')+num('gsS')).toFixed(2) + '%  ·  Inter-state (IGST): ' + num('gsI').toFixed(2) + '%'; }
        ['gsC','gsS','gsI'].forEach(function(id){ var e=document.getElementById(id); if(e){ e.addEventListener('input',upd); } });
        upd();
    })();
</script>
