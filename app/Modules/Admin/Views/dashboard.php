<?php
$ctx  = service('fyContext');
$user = $ctx->userInfo();
$firm = $ctx->fyRow();
?>
<div class="page-content-inner" style="padding:22px;">
    <div class="page-title" style="margin-bottom:18px;">
        <h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0;">Dashboard</h1>
        <p style="color:#718096;font-size:13px;margin:4px 0 0;">Welcome back, <?= esc(trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) ?>. You are running CI4 on PHP <?= PHP_VERSION ?>.</p>
    </div>

    <div class="row">
        <div class="col-sm-6 col-md-3">
            <div class="bgc-white bdrs-3 p-20 mB-20" style="background:#fff;border-radius:10px;padding:20px;margin-bottom:20px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
                <div style="color:#718096;font-size:11px;font-weight:900;text-transform:uppercase;">Firm</div>
                <div style="color:#18243c;font-size:20px;font-weight:900;margin-top:6px;"><?= esc($firm->firm_name ?? '—') ?></div>
                <div style="color:#94a3b8;font-size:12px;">Template #<?= esc($ctx->templateId() ?? '—') ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="bgc-white bdrs-3 p-20 mB-20" style="background:#fff;border-radius:10px;padding:20px;margin-bottom:20px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
                <div style="color:#718096;font-size:11px;font-weight:900;text-transform:uppercase;">Financial Year</div>
                <div style="color:#18243c;font-size:20px;font-weight:900;margin-top:6px;"><?= esc($ctx->fyYear() ?? '—') ?></div>
                <div style="color:#94a3b8;font-size:12px;">Product type <?= esc($ctx->productType() ?? '—') ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="bgc-white bdrs-3 p-20 mB-20" style="background:#fff;border-radius:10px;padding:20px;margin-bottom:20px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
                <div style="color:#718096;font-size:11px;font-weight:900;text-transform:uppercase;">Signed in as</div>
                <div style="color:#18243c;font-size:20px;font-weight:900;margin-top:6px;"><?= esc(trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) ?: 'User' ?></div>
                <div style="color:#94a3b8;font-size:12px;"><?= esc($user->email ?? '') ?> · <?= $ctx->isSuperAdmin() ? 'Super Admin' : 'user_type ' . esc($user->user_type ?? '') ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="bgc-white bdrs-3 p-20 mB-20" style="background:#fff;border-radius:10px;padding:20px;margin-bottom:20px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
                <div style="color:#718096;font-size:11px;font-weight:900;text-transform:uppercase;">Platform</div>
                <div style="color:#18243c;font-size:20px;font-weight:900;margin-top:6px;">CodeIgniter <?= \CodeIgniter\CodeIgniter::CI_VERSION ?></div>
                <div style="color:#94a3b8;font-size:12px;">Metronic theme · migrated</div>
            </div>
        </div>
    </div>

    <div class="bgc-white bdrs-3 p-20" style="background:#fff;border-radius:10px;padding:22px;box-shadow:0 12px 26px rgba(24,36,60,.06);">
        <h4 style="font-weight:900;color:#18243c;margin:0 0 8px;">🎉 Real admin shell — CI4 + Metronic</h4>
        <p style="color:#516174;line-height:1.6;margin:0;">
            This page is rendered by the ported <code>layouts/admin.php</code> using your existing Metronic assets,
            with the sidebar built from <code>erp_module_registry()</code> and filtered by <code>erp_current_user_can()</code>
            — so you only see the modules you're permitted to. The links resolve as each admin module is ported.
        </p>
    </div>
</div>
