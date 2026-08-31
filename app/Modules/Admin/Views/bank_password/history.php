<?php helper(['url']); ?>
<style>
    .audit-header {align-items:center;display:flex;justify-content:space-between;margin-bottom:18px}
    .audit-header h3 {color:#1d2b25;font-size:21px;font-weight:700;margin:0}
    .audit-panel {background:#fff;border:1px solid #dfe5e2;border-radius:6px;border-top:3px solid #18794e;box-shadow:0 4px 14px rgba(35,61,49,.06);overflow-x:auto;padding:20px}
    .audit-table {margin:0;min-width:1050px}
    .audit-table th {background:#f3f7f5;color:#607068;font-size:10px;text-transform:uppercase}
    .audit-table td {color:#34453d;font-size:12px;vertical-align:top!important}
    .audit-action {border-radius:4px;display:inline-block;font-size:10px;font-weight:700;padding:5px 8px}
    .audit-pdf {background:#fdeaea;color:#a83232}.audit-print {background:#e7f2ec;color:#18794e}
    .audit-reason {max-width:280px;white-space:pre-wrap;word-break:break-word}
    .audit-ids {color:#52665c;font-family:Consolas,monospace;font-size:11px;max-width:260px;word-break:break-all}
</style>

<main class="main-content bgc-grey-100">
    <div id="mainContent">
        <div class="container-fluid">
            <div class="audit-header">
                <h3><i class="fa fa-history"></i> Password Export History</h3>
                <a href="<?php echo base_url('admin/bank_password/listing'); ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Password Manager</a>
            </div>
            <div class="audit-panel">
                <table class="table table-bordered table-striped audit-table">
                    <thead><tr><th>Audit ID</th><th>Action</th><th>User</th><th>User ID</th><th>Reason</th><th>Record IDs</th><th>Count</th><th>Firm / FY</th><th>IP Address</th><th>Date &amp; Time</th></tr></thead>
                    <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="10" class="text-center text-muted">No print or export history found.</td></tr>
                    <?php else: foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo (int) $log->id; ?></td>
                            <td><span class="audit-action <?php echo $log->action_type === 'Print' ? 'audit-print' : 'audit-pdf'; ?>"><?php echo html_escape($log->action_type); ?></span></td>
                            <td><?php echo html_escape($log->user_name ?: 'Unknown'); ?></td>
                            <td><?php echo (int) $log->user_id; ?></td>
                            <td><div class="audit-reason"><?php echo html_escape($log->reason); ?></div></td>
                            <td><div class="audit-ids"><?php echo html_escape($log->record_ids ?: 'None'); ?></div></td>
                            <td><?php echo (int) $log->record_count; ?></td>
                            <td><?php echo html_escape($log->firm_name ?: 'Not set'); ?><br><small><?php echo html_escape($log->FY); ?></small></td>
                            <td><?php echo html_escape($log->ip_address ?: 'Unknown'); ?></td>
                            <td><?php echo date('d-m-Y h:i A', strtotime($log->created_at)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
