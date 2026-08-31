<div style="padding:48px 22px;display:flex;justify-content:center;">
    <div style="max-width:520px;text-align:center;background:#fff;border:1px solid #e2e9f2;border-radius:14px;padding:40px 34px;box-shadow:0 16px 38px rgba(24,36,60,.08);">
        <div style="width:66px;height:66px;margin:0 auto 18px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1769c2,#f0a020);color:#fff;font-size:28px;">
            <i class="fa fa-cogs"></i>
        </div>
        <h1 style="font-size:22px;font-weight:900;color:#18243c;margin:0 0 8px;"><?= esc($modLabel ?? 'This screen') ?></h1>
        <p style="color:#516174;font-size:14px;line-height:1.6;margin:0 0 6px;">
            This screen is part of the CodeIgniter&nbsp;4 migration and is <b>being migrated</b>.
            The data and the listing behind it are already live — this specific action/form is queued next.
        </p>
        <p style="color:#94a3b8;font-size:12px;font-family:monospace;margin:0 0 22px;">/<?= esc($targetPath ?? '') ?></p>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= site_url('admin/dashboard') ?>" class="btn btn-primary"><i class="fa fa-home"></i> Dashboard</a>
            <a href="javascript:history.back()" class="btn btn-default"><i class="fa fa-arrow-left"></i> Go Back</a>
        </div>
    </div>
</div>
