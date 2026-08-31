<?php helper(['url']); ?>
<style>
    .ci-page{color:#18243c;padding:24px}.ci-shell{margin:0 auto;max-width:1240px}
    .ci-hero{align-items:center;background:#fff;border:1px solid #dce6f2;border-radius:12px;box-shadow:0 16px 38px rgba(24,36,60,.08);display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;margin-bottom:18px;padding:20px 24px}
    .ci-hero-copy{align-items:center;display:flex;gap:14px}
    .ci-hero-icon{align-items:center;background:#e8f2ff;border-radius:12px;color:#1769c2;display:flex;font-size:22px;height:50px;justify-content:center;width:50px}
    .ci-title{color:#18243c;font-size:23px;font-weight:900;margin:0}.ci-sub{color:#718096;font-size:13px;font-weight:700;margin:5px 0 0}
    .ci-actions{display:flex;flex-wrap:wrap;gap:8px}
    .ci-btn{align-items:center;border:0;border-radius:9px!important;cursor:pointer;display:inline-flex;font-weight:800;gap:7px;min-height:42px;padding:9px 15px;text-decoration:none}
    .ci-btn-primary{background:#1769c2;color:#fff}.ci-btn-primary:hover{background:#0f57a8;color:#fff}
    .ci-btn-ghost{background:#fff;border:1px solid #d8e1eb!important;color:#52657a}.ci-btn-ghost:hover{background:#eef4fb;color:#1769c2}
    .ci-ason{align-items:center;background:#f3f8ff;border:1px solid #d5e6fb;border-radius:9px;color:#1c5aa6;display:inline-flex;font-size:12px;font-weight:800;gap:8px;padding:8px 12px}
    .ci-ason input{border:1px solid #cdddf0;border-radius:6px;font-weight:700;padding:5px 8px}
    .ci-cards{display:grid;gap:14px;grid-template-columns:repeat(4,1fr);margin-bottom:18px}
    .ci-card{background:#fff;border:1px solid #dce6f2;border-radius:12px;box-shadow:0 10px 26px rgba(24,36,60,.06);padding:16px 18px;position:relative;overflow:hidden}
    .ci-card:before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;background:linear-gradient(180deg,#1769c2,#5aa2ea)}
    .ci-card.g:before{background:linear-gradient(180deg,#059669,#34d399)}
    .ci-card.o:before{background:linear-gradient(180deg,#ea580c,#fbbf24)}
    .ci-card.p:before{background:linear-gradient(180deg,#7b4bd0,#a78bfa)}
    .ci-c-label{color:#8394a7;font-size:10px;font-weight:900;letter-spacing:.04em;text-transform:uppercase}
    .ci-c-value{color:#18243c;font-size:26px;font-weight:900;margin-top:6px}
    .ci-c-sub{color:#7a8aa0;font-size:11px;font-weight:700;margin-top:2px}
    .ci-panel{background:#fff;border:1px solid #dce6f2;border-radius:12px;box-shadow:0 16px 38px rgba(24,36,60,.08);overflow:hidden}
    .ci-panel-head{align-items:center;border-bottom:1px solid #eef2f7;display:flex;justify-content:space-between;padding:15px 20px}
    .ci-panel-head h5{font-size:15px;font-weight:900;margin:0}
    .ci-vrow{align-items:center;border-bottom:1px solid #f1f4f9;display:grid;gap:14px;grid-template-columns:1.4fr 3fr 90px 90px;padding:12px 20px}
    .ci-vrow:last-child{border-bottom:0}
    .ci-vname{font-weight:800;color:#26374f}
    .ci-bar{background:#eef2f7;border-radius:20px;height:12px;overflow:hidden}
    .ci-bar span{background:linear-gradient(90deg,#1769c2,#5aa2ea);display:block;height:100%}
    .ci-vbal{font-weight:900;color:#1769c2;text-align:right}
    .ci-vin{color:#8394a7;font-weight:700;font-size:12px;text-align:right}
    .ci-empty{color:#9aa7b6;font-style:italic;padding:24px;text-align:center}
    @media(max-width:900px){.ci-cards{grid-template-columns:1fr 1fr}.ci-vrow{grid-template-columns:1.2fr 2fr 70px}.ci-vin{display:none}}
    @media(max-width:600px){.ci-page{padding:14px}.ci-cards{grid-template-columns:1fr}}
</style>

<?php $maxBal = 1; foreach ($by_variety as $v) { if ($v->balance > $maxBal) $maxBal = $v->balance; } ?>

<main class="main-content bgc-grey-100 ci-page">
  <div id="mainContent">
    <div class="container-fluid ci-shell">
      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>

      <section class="ci-hero">
        <div class="ci-hero-copy">
          <span class="ci-hero-icon"><i class="ti-package"></i></span>
          <div>
            <h4 class="ci-title">Cold Storage Inventory Control</h4>
            <p class="ci-sub">Live packets-in-store position derived from cold lots (in) and Final bill deliveries (out).</p>
          </div>
        </div>
        <div class="ci-actions">
          <form method="get" class="ci-ason" style="margin:0">
            <i class="ti-calendar"></i> As on
            <input type="date" name="as_on" value="<?= esc($filters['as_on']) ?>" onchange="this.form.submit()">
          </form>
          <a href="<?= base_url('admin/cold_inventory/report/variety') ?>" class="ci-btn ci-btn-primary"><i class="ti-list"></i> Stock Position</a>
          <a href="<?= base_url('admin/cold_inventory/report/movement') ?>" class="ci-btn ci-btn-ghost"><i class="ti-exchange-vertical"></i> Movement Register</a>
        </div>
      </section>

      <section class="ci-cards">
        <div class="ci-card"><div class="ci-c-label">Balance in Store</div><div class="ci-c-value"><?= number_format((int) $kpi->balance) ?></div><div class="ci-c-sub">packets right now</div></div>
        <div class="ci-card g"><div class="ci-c-label">Total Received</div><div class="ci-c-value"><?= number_format((int) $kpi->in) ?></div><div class="ci-c-sub"><?= number_format((int) $kpi->delivered) ?> delivered</div></div>
        <div class="ci-card o"><div class="ci-c-label">Lots in Store</div><div class="ci-c-value"><?= number_format((int) $kpi->lots_in_store) ?></div><div class="ci-c-sub"><?= number_format((int) $kpi->coldlots) ?> cold-lot entries</div></div>
        <div class="ci-card p"><div class="ci-c-label">Varieties / Kisans</div><div class="ci-c-value"><?= number_format((int) $kpi->varieties) ?> / <?= number_format((int) $kpi->kisans) ?></div><div class="ci-c-sub">with stock in store</div></div>
      </section>

      <section class="ci-panel">
        <div class="ci-panel-head">
          <h5><i class="ti-bar-chart"></i> Variety-wise Occupancy (balance in store)</h5>
          <a href="<?= base_url('admin/cold_inventory/report/variety') ?>" class="ci-btn ci-btn-ghost" style="min-height:34px;padding:6px 12px">View full report</a>
        </div>
        <?php if (empty($by_variety)): ?>
          <div class="ci-empty">No stock currently in store for the selected date.</div>
        <?php else: foreach ($by_variety as $v): if ($v->balance <= 0) continue; ?>
          <div class="ci-vrow">
            <div class="ci-vname"><?= esc($v->label) ?></div>
            <div class="ci-bar"><span style="width:<?= max(3, round($v->balance / $maxBal * 100)) ?>%"></span></div>
            <div class="ci-vbal"><?= number_format((int) $v->balance) ?></div>
            <div class="ci-vin">of <?= number_format((int) $v->in) ?></div>
          </div>
        <?php endforeach; endif; ?>
      </section>
    </div>
  </div>
</main>
