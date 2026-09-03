<?php
helper(['url', 'form']);
$is_edit = ! empty($result);
$action  = $is_edit ? base_url('admin/attendance/employee_edit/' . ID_encode($result->employee_id)) : base_url('admin/attendance/employee_add');
$val = function ($f, $d = '') use ($result) { return esc(old($f, $result->$f ?? $d)); };
$jd  = $is_edit && ! empty($result->joining_date) ? date('Y-m-d', strtotime($result->joining_date)) : old('joining_date', '');
$status = $is_edit ? ($result->status ?? 'Active') : old('status', 'Active');
?>
<style>
    .emf{--line:var(--tm-line,#dce6f2);--brand:var(--tm-brand,#1769c2);--brand-dark:var(--tm-brand-dark,#0c315f)}
    .emf-card{overflow:hidden;border:1px solid var(--line);border-radius:12px;background:#fff;box-shadow:0 18px 42px rgba(24,36,60,.08);max-width:960px;margin:0 auto}
    .emf-head{display:flex;justify-content:space-between;gap:16px;align-items:center;padding:20px 22px;color:#fff;background:linear-gradient(135deg,var(--brand-dark),var(--brand))}
    .emf-head h1{margin:0;color:#fff;font-size:24px;font-weight:850}
    .emf-body{padding:22px}
    .emf-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    .emf-f label{display:block;margin:0 0 6px;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.03em;color:#64748b}
    .emf-f.wide{grid-column:span 2}
    .emf-f .form-control{height:44px;border:1px solid var(--line);border-radius:9px;background:#fbfdff;width:100%;padding:0 12px;font-weight:600}
    .emf-f textarea.form-control{height:auto;min-height:90px;padding:10px 12px}
    .emf-f .form-control:focus{outline:0;border-color:var(--brand);box-shadow:0 0 0 4px rgba(23,105,194,.12);background:#fff}
    .emf-err{display:block;margin-top:5px;color:#e5484d;font-size:12px;font-weight:800}
    .emf-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;padding-top:18px;border-top:1px solid var(--line)}
    .emf-btn{min-height:44px;display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:9px;font-weight:900;text-decoration:none!important;border:1px solid var(--brand);background:var(--brand);color:#fff;cursor:pointer}
    .emf-btn.light{background:#eef3fa;border-color:#dce6f2;color:var(--brand-dark)!important}
    @media(max-width:991px){.emf-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:560px){.emf-grid{grid-template-columns:1fr}.emf-f.wide{grid-column:auto}}
</style>

<main class="main-content bgc-grey-100 emf">
    <div id="mainContent">
        <div class="container-fluid">
            <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger" style="max-width:960px;margin:0 auto 14px;"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
            <div class="emf-card">
                <div class="emf-head">
                    <h1><?= $is_edit ? 'Edit Employee' : 'Add Employee'; ?></h1>
                    <a href="<?= base_url('admin/attendance/employee_listing'); ?>" class="emf-btn light"><i class="fa fa-list"></i> Employee Listing</a>
                </div>
                <div class="emf-body">
                    <?= form_open($action, ['id' => 'empForm']); ?>
                    <div class="emf-grid">
                        <div class="emf-f"><label>Employee Code</label><input type="text" name="employee_code" class="form-control" value="<?= $val('employee_code') ?>"></div>
                        <div class="emf-f wide"><label>Employee Name *</label><input type="text" name="employee_name" class="form-control" value="<?= $val('employee_name') ?>" required><span class="emf-err"><?= form_error('employee_name') ?></span></div>
                        <div class="emf-f"><label>Mobile</label><input type="text" name="mobile" class="form-control" value="<?= $val('mobile') ?>"></div>
                        <div class="emf-f"><label>Designation</label><input type="text" name="designation" class="form-control" value="<?= $val('designation') ?>"></div>
                        <div class="emf-f"><label>Joining Date</label><input type="date" name="joining_date" class="form-control" value="<?= esc($jd) ?>"></div>
                        <div class="emf-f"><label>Salary</label><input type="number" step="0.01" name="salary" class="form-control" value="<?= $val('salary') ?>"></div>
                        <div class="emf-f"><label>Status *</label>
                            <select name="status" class="form-control">
                                <option value="Active" <?= $status == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?= $status == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="emf-f wide"><label>Address</label><textarea name="address" class="form-control"><?= $val('address') ?></textarea></div>
                    </div>
                    <div class="emf-actions">
                        <a href="<?= base_url('admin/attendance/employee_listing'); ?>" class="emf-btn light"><i class="fa fa-arrow-left"></i> Back</a>
                        <button type="submit" class="emf-btn"><i class="fa fa-save"></i> <?= $is_edit ? 'Update Employee' : 'Save Employee'; ?></button>
                    </div>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</main>
