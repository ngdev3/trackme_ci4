<?php
helper(['url', 'app']);
$from_display = !empty($filters['from_date']) ? date('Y-m-d', strtotime($filters['from_date'])) : '';
$to_display = !empty($filters['to_date']) ? date('Y-m-d', strtotime($filters['to_date'])) : '';
$export_query = http_build_query(array(
    'employee_id' => $filters['employee_id'],
    'period' => $filters['period'],
    'from_date' => $from_display,
    'to_date' => $to_display,
));
?>

<style>
    .att-report{--ink:var(--tm-ink,#18243c);--muted:var(--tm-muted,#718096);--line:var(--tm-line,#dce6f2);--brand:var(--tm-brand,#1769c2);--brand-dark:var(--tm-brand-dark,#0c315f);--brand-soft:var(--tm-brand-soft,#eaf3ff)}.report-shell{padding:0!important;border:0!important;background:transparent!important}.report-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;padding:22px;border-radius:8px;color:#fff;background:linear-gradient(135deg,var(--brand-dark),color-mix(in srgb,var(--brand) 60%,#101827));box-shadow:0 18px 44px rgba(24,36,60,.15)}.report-head h1{margin:0 0 6px;color:#fff;font-size:28px;font-weight:850}.report-head p{margin:0;color:rgba(242,248,255,.8)}.report-export-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap}.report-export-btn{height:40px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 13px;border:1px solid rgba(255,255,255,.28);border-radius:8px;background:#fff;color:var(--brand-dark)!important;font-weight:900;text-decoration:none!important}.report-card{border:1px solid var(--line);border-radius:8px;background:#fff;box-shadow:0 18px 42px rgba(24,36,60,.08);margin-bottom:16px}.report-filter{display:grid;grid-template-columns:1.4fr repeat(4,1fr) auto;gap:12px;padding:16px;align-items:end}.report-field label{display:block;margin-bottom:7px;font-size:12px;font-weight:900;color:var(--ink)}.report-field .form-control{height:42px;border:1px solid var(--line);border-radius:8px}.report-btn{height:42px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 16px;border:1px solid var(--brand);border-radius:8px;background:var(--brand);color:#fff;font-weight:900}.report-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;padding:0 16px 16px}.report-stat{padding:14px;border:1px solid var(--line);border-radius:8px;background:#fbfdff}.report-stat span{display:block;margin-bottom:6px;color:var(--muted);font-size:11px;font-weight:900;text-transform:uppercase}.report-stat strong{font-size:21px;color:var(--ink)}.report-table-wrap{padding:16px;overflow-x:auto}.att-report table{min-width:920px}.att-report th{background:var(--brand-soft);color:var(--brand-dark);font-size:12px;text-transform:uppercase}.att-report th,.att-report td{padding:12px!important;vertical-align:middle!important}@media(max-width:991px){.report-head{align-items:stretch;flex-direction:column}.report-export-actions{justify-content:flex-start}.report-filter,.report-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:640px){.report-filter,.report-summary{grid-template-columns:1fr}}
</style>

<main class="main-content bgc-grey-100 att-report">
    <div id="mainContent">
        <div class="container-fluid">
            <?= get_flashdata(); ?>
            <div class="report-shell bgc-white bd bdrs-3 p-20 mB-20">
                <section class="report-head">
                    <div>
                        <h1>Attendance Report</h1>
                        <p>Filter any employee by today, weekly, monthly, yearly, or custom date range.</p>
                    </div>
                    <div class="report-export-actions">
                        <a class="report-export-btn" href="<?= base_url('admin/attendance/report_print?' . $export_query); ?>" target="_blank"><i class="fa fa-print"></i> Print</a>
                        <a class="report-export-btn" href="<?= base_url('admin/attendance/report_csv?' . $export_query); ?>"><i class="fa fa-file-excel-o"></i> CSV</a>
                        <a class="report-export-btn" href="<?= base_url('admin/attendance/report_pdf?' . $export_query); ?>"><i class="fa fa-file-pdf-o"></i> PDF</a>
                    </div>
                </section>

                <section class="report-card">
                    <form method="get" action="<?= base_url('admin/attendance/report'); ?>" class="report-filter">
                        <div class="report-field">
                            <label>Employee</label>
                            <select name="employee_id" class="form-control">
                                <option value="">All Employees</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee->employee_id; ?>" <?= $filters['employee_id'] == $employee->employee_id ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($employee->employee_name . (!empty($employee->employee_code) ? ' (' . $employee->employee_code . ')' : '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="report-field">
                            <label>Period</label>
                            <select name="period" class="form-control">
                                <?php foreach (array('today' => 'Today', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'custom' => 'Custom') as $key => $label): ?>
                                    <option value="<?= $key; ?>" <?= $filters['period'] == $key ? 'selected' : ''; ?>><?= $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="report-field"><label>From</label><input type="text" name="from_date" class="form-control date-picker" value="<?= $from_display; ?>"></div>
                        <div class="report-field"><label>To</label><input type="text" name="to_date" class="form-control date-picker" value="<?= $to_display; ?>"></div>
                        <button type="submit" class="report-btn"><i class="fa fa-search"></i> Report</button>
                    </form>
                    <div class="report-summary">
                        <div class="report-stat"><span>Total</span><strong><?= (int) $summary['total']; ?></strong></div>
                        <div class="report-stat"><span>Present</span><strong><?= (int) $summary['present']; ?></strong></div>
                        <div class="report-stat"><span>Absent</span><strong><?= (int) $summary['absent']; ?></strong></div>
                        <div class="report-stat"><span>Half Day</span><strong><?= (int) $summary['half_day']; ?></strong></div>
                        <div class="report-stat"><span>Leave</span><strong><?= (int) $summary['leave']; ?></strong></div>
                    </div>
                </section>

                <section class="report-card">
                    <div class="report-table-wrap">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($rows)): $i = 1; foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= $i++; ?></td>
                                        <td><?= date('Y-m-d', strtotime($row->attendance_date)); ?></td>
                                        <td><?= htmlspecialchars(!empty($row->employee_name) ? $row->employee_name : $row->person_name); ?></td>
                                        <td><?= htmlspecialchars($row->attendance_status); ?></td>
                                        <td><?= htmlspecialchars(trim($row->check_in . ' - ' . $row->check_out, ' -')); ?></td>
                                        <td><?= htmlspecialchars($row->remark); ?></td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="6" class="text-center">No attendance found for selected filter.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<script>
    function normalizeAttendanceIsoDate(value) {
        value = $.trim(value || '');
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return value;
        }

        var slashDate = value.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (slashDate) {
            return slashDate[3] + '-' + ('0' + slashDate[1]).slice(-2) + '-' + ('0' + slashDate[2]).slice(-2);
        }

        var dashDate = value.match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
        if (dashDate) {
            return dashDate[3] + '-' + ('0' + dashDate[2]).slice(-2) + '-' + ('0' + dashDate[1]).slice(-2);
        }

        return value;
    }

    function initAttendanceIsoDatePicker() {
        if (!$.fn.datepicker) {
            return;
        }

        $('.date-picker').each(function () {
            var $field = $(this);
            $field.attr('placeholder', 'YYYY-MM-DD');

            try {
                $field.datepicker('destroy');
            } catch (e) {}

            $field.datepicker({
                format: 'yyyy-mm-dd',
                dateFormat: 'yy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                onSelect: function () {
                    this.value = normalizeAttendanceIsoDate(this.value);
                }
            });

            $field.val(normalizeAttendanceIsoDate($field.val()));
        });
    }

    $(document).ready(function () {
        initAttendanceIsoDatePicker();
        setTimeout(initAttendanceIsoDatePicker, 300);

        $(document).on('input blur change changeDate hide', '.date-picker', function () {
            var field = this;
            setTimeout(function () {
                field.value = normalizeAttendanceIsoDate(field.value);
            }, 0);
            setTimeout(function () {
                field.value = normalizeAttendanceIsoDate(field.value);
            }, 80);
        });

        $('form.report-filter').on('submit', function () {
            $('.date-picker').each(function () {
                this.value = normalizeAttendanceIsoDate(this.value);
            });
        });
    });
</script>
