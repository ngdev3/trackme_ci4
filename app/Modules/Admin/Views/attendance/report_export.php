<?php
$from_display = !empty($filters['from_date']) ? date('Y-m-d', strtotime($filters['from_date'])) : '';
$to_display = !empty($filters['to_date']) ? date('Y-m-d', strtotime($filters['to_date'])) : '';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #18243c; font-size: 12px; margin: 24px; }
        h1 { margin: 0 0 6px; font-size: 22px; }
        .meta { margin-bottom: 14px; color: #506078; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary td { border: 1px solid #dce6f2; padding: 8px; }
        .summary strong { display: block; font-size: 16px; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #dce6f2; padding: 7px; vertical-align: top; }
        table.report th { background: #eaf3ff; color: #0c315f; font-size: 11px; text-transform: uppercase; }
        .text-center { text-align: center; }
        @media print {
            body { margin: 12mm; }
        }
    </style>
</head>
<body>
    <h1>Attendance Report</h1>
    <div class="meta">
        Period: <?= htmlspecialchars(ucfirst($filters['period'])); ?> |
        From: <?= $from_display; ?> |
        To: <?= $to_display; ?>
    </div>

    <table class="summary">
        <tr>
            <td>Total <strong><?= (int) $summary['total']; ?></strong></td>
            <td>Present <strong><?= (int) $summary['present']; ?></strong></td>
            <td>Absent <strong><?= (int) $summary['absent']; ?></strong></td>
            <td>Half Day <strong><?= (int) $summary['half_day']; ?></strong></td>
            <td>Leave <strong><?= (int) $summary['leave']; ?></strong></td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th>S.No.</th>
                <th>Date</th>
                <th>Employee Code</th>
                <th>Employee</th>
                <th>Designation</th>
                <th>Status</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rows)): $i = 1; foreach ($rows as $row): ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= date('Y-m-d', strtotime($row->attendance_date)); ?></td>
                    <td><?= htmlspecialchars($row->employee_code); ?></td>
                    <td><?= htmlspecialchars(!empty($row->employee_name) ? $row->employee_name : $row->person_name); ?></td>
                    <td><?= htmlspecialchars($row->designation); ?></td>
                    <td><?= htmlspecialchars($row->attendance_status); ?></td>
                    <td><?= htmlspecialchars($row->check_in); ?></td>
                    <td><?= htmlspecialchars($row->check_out); ?></td>
                    <td><?= htmlspecialchars($row->remark); ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="9" class="text-center">No attendance found for selected filter.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($auto_print)): ?>
        <script>
            window.onload = function () {
                window.print();
            };
        </script>
    <?php endif; ?>
</body>
</html>
