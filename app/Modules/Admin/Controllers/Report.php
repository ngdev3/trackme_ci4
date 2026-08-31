<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\ReportModel;

/**
 * Report — CI4 port of admin/Report (ledger slice).
 *
 * Ports the Account Ledger (admin/report/ledger): GET renders the page; POST
 * (AJAX) returns the selected account's cash + KisanVahi ledger as JSON. The
 * remaining report pages (search, byaccount_name, rokad_parcha, deleted_entries)
 * port on top of this + the shared account-picker foundation.
 */
class Report extends BaseController
{
    protected $helpers = ['url', 'form', 'text', 'app', 'cr_cache', 'accounting'];

    private function model(): ReportModel
    {
        return new ReportModel();
    }

    /** Account Ledger — running balance of one account (cash + KisanVahi). */
    public function ledger()
    {
        if ($this->request->is('post')) {
            $search = (string) $this->request->getPost('search_name');
            $parts  = explode('_', $search);
            $account_no    = $parts[1] ?? '';
            $account_label = $parts[0] ?? '';

            if ($account_no === '') {
                return $this->response->setJSON(['status' => 'error', 'msg' => 'Please select a valid account from the list.']);
            }

            // Access control: blocked accounts cannot be viewed.
            if (BlackList_Search_USER_IDS($account_no)) {
                return $this->response->setJSON(['status' => 'error', 'msg' => 'You are not allowed to view this account.']);
            }

            // Audit every ledger access (who viewed which account).
            $who = currentuserinfo()->first_name . ' ' . currentuserinfo()->last_name;
            notificationData([
                'name'         => 'Account Ledger viewed for - <b>' . $account_label . '  Accessed By User : ' . $who . '</b>',
                'type'         => 'Ledger', 'module_title' => $search, 'module_name' => 'Report', 'user_name' => $who,
            ], 'added');

            $from = trim((string) $this->request->getPost('from_date'));
            $to   = trim((string) $this->request->getPost('to_date'));
            $from = $from !== '' ? date('Y-m-d', strtotime($from)) : '';
            $to   = $to !== '' ? date('Y-m-d', strtotime($to)) : '';

            $ledger = $this->model()->accountLedger($account_no, $from, $to);
            return $this->response->setJSON([
                'status' => 'success',
                'ledger' => $ledger,
                'from'   => $from,
                'to'     => $to,
                'label'  => $account_label,
            ]);
        }

        return _layout('\App\Modules\Admin\Views\report\ledger', [
            'title' => 'Track (The Rest Accounting Key) || Account Ledger',
        ]);
    }

    /** Account Report — expenses/deposit/KisanVahi totals for one account (JSON). */
    public function search()
    {
        if ($this->request->is('post')) {
            $parts = explode('_', (string) $this->request->getPost('search_name'));
            $acc   = $parts[1] ?? '';

            // Blocked account: refuse (CI3 returns false → empty response).
            if (BlackList_Search_USER_IDS($acc)) {
                return $this->response->setJSON([]);
            }

            // Audit every account-report fetch.
            $who = currentuserinfo()->first_name . ' ' . currentuserinfo()->last_name;
            notificationData([
                'name'         => 'You are trying to Fetch the report of - <b>' . ($parts[0] ?? '') . '  Accessed By User : ' . $who . '</b>',
                'type'         => 'Searched', 'module_title' => (string) $this->request->getPost('search_name'), 'module_name' => 'Report', 'user_name' => $who,
            ], 'added');

            $m        = $this->model();
            $expenses = $m->fetchtheFinalAmountexpenses($acc);
            $deposit  = $m->fetchtheFinalAmountdeposit($acc);
            $data = [
                'expenses'            => $expenses,
                'totalMappedKisanVahi'=> $m->totalMappedKisanVahi($acc),
                'deposit'             => $deposit,
                'kisanvahi_Amount'    => $m->fetchtheFinalAmountKisanVahi($acc),
                'UTR_Amount'          => $m->getKisanVahiUTRAmount($acc),
                'getAccountName'      => $m->getAccountName($acc),
                'Finaldeposit'        => '',
                'Finalexpenses'       => '',
            ];
            $exp = (float) ($expenses->expenses ?? 0);
            $dep = (float) ($deposit->deposit ?? 0);
            if ($exp != 0 && $exp > $dep) {
                $data['Finalexpenses'] = $exp - $dep;
            } elseif ($dep != 0 && $dep > $exp) {
                $data['Finaldeposit'] = $dep - $exp;
            }

            $m->logSearchResult($data, $parts);
            return $this->response->setJSON($data);
        }

        return _layout('\App\Modules\Admin\Views\report\search', [
            'title' => 'Track (The Rest Accounting Key) || Search Report',
        ]);
    }

    /** Account Statement — per-account expenses/deposit/net across the firm/FY. */
    public function byaccount_name()
    {
        if ($this->request->getPost('start_date') !== null) {
            session()->set('start_date', $this->request->getPost('start_date'));
        }
        if ($this->request->getPost('end_date') !== null) {
            session()->set('end_date', $this->request->getPost('end_date'));
        }
        $sRaw  = session()->get('start_date');
        $eRaw  = session()->get('end_date');
        $start = (! empty($sRaw) && strtotime($sRaw)) ? date('Y-m-d', strtotime($sRaw)) : '';
        $end   = (! empty($eRaw) && strtotime($eRaw)) ? date('Y-m-d', strtotime($eRaw)) : '';

        $rows = $this->model()->Billing_details($start !== '' ? $start : null, $end !== '' ? $end : null);
        $rows = $rows ?: [];

        // Enrich each row with account type + ledger group (guarded, best-effort).
        if (! empty($rows)) {
            $ids = [];
            foreach ($rows as $r) {
                $ids[] = (int) $r->account_no;
            }
            $meta = $this->model()->statementMeta($ids);
            foreach ($rows as $r) {
                $mrow = $meta[(int) $r->account_no] ?? null;
                $type = $mrow->account_type ?? '';
                $r->account_type_label = ($type !== '' && function_exists('acc_account_type_label')) ? acc_account_type_label($type) : '';
                $r->group_name         = $mrow->group_name ?? '';
            }
        }

        return _layout('\App\Modules\Admin\Views\report\view', [
            'title'     => 'Account Statement',
            'users'     => $rows,
            'stmt_from' => $start,
            'stmt_to'   => $end,
        ]);
    }

    /** Session statement date range as [start, end] (Y-m-d, '' when unset). */
    private function statement_range(): array
    {
        $s = session()->get('start_date');
        $e = session()->get('end_date');
        return [
            (! empty($s) && strtotime($s)) ? date('Y-m-d', strtotime($s)) : '',
            (! empty($e) && strtotime($e)) ? date('Y-m-d', strtotime($e)) : '',
        ];
    }

    /** Live firm/user identity for the report PDF header. */
    private function report_header_fields(): array
    {
        $f    = fy();
        $firm = (is_object($f) && ! empty($f->firm_name)) ? $f->firm_name
              : (is_object($f) && ! empty($f->template_name) ? $f->template_name : '');
        $template = (is_object($f) && ! empty($f->template_name)) ? $f->template_name : '';
        if ($template !== '' && $template === $firm) {
            $template = '';
        }
        $mill  = (is_object($f) && ! empty($f->mill_name)) ? $f->mill_name : '';
        $u     = currentuserinfo();
        $genby = $u ? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) : '';
        return [
            'firm' => $firm, 'mill' => $mill, 'template' => $template,
            'fy' => (is_object($f) && ! empty($f->FY)) ? $f->FY : '', 'generated_by' => $genby,
        ];
    }

    /** Attach account_type + group to statement rows (shared with the page). */
    private function enrich_statement_rows(array &$rows): void
    {
        if (empty($rows)) {
            return;
        }
        $ids = [];
        foreach ($rows as $r) {
            $ids[] = (int) $r->account_no;
        }
        $meta = $this->model()->statementMeta($ids);
        foreach ($rows as $r) {
            $mrow = $meta[(int) $r->account_no] ?? null;
            $type = $mrow->account_type ?? '';
            $r->account_type_label = ($type !== '' && function_exists('acc_account_type_label')) ? acc_account_type_label($type) : '';
            $r->group_name         = $mrow->group_name ?? '';
        }
    }

    /** Export the Account Statement (CSV / Excel / Hindi PDF). */
    public function byaccount_name_export($fmt = 'csv')
    {
        helper('accounting');
        [$start, $end] = $this->statement_range();
        $rows = $this->model()->Billing_details($start !== '' ? $start : null, $end !== '' ? $end : null) ?: [];
        $this->enrich_statement_rows($rows);

        $ids = $this->request->getPost('ids');
        if (! is_array($ids)) {
            $get_ids = (string) $this->request->getGet('ids');
            $ids = $get_ids !== '' ? explode(',', $get_ids) : [];
        }
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));

        $columns = [
            'sno' => 'S.No', 'id' => 'Account ID', 'name' => 'Account Name', 'type' => 'Type', 'group' => 'Group',
            'naam' => 'नाम (Dr)', 'jama' => 'जमा (Cr)', 'balance' => 'Balance', 'status' => 'Status',
        ];
        $out = [];
        $i = 0;
        $t_dr = 0.0;
        $t_cr = 0.0;
        foreach ($rows as $u) {
            if (! empty($ids) && ! in_array((int) $u->account_no, $ids, true)) {
                continue;
            }
            $i++;
            $f  = (float) $u->finalamt;
            $st = $f > 0.004 ? 'जमा (Cr)' : ($f < -0.004 ? 'नाम (Dr)' : 'Nil');
            $t_dr += (float) $u->expenses;
            $t_cr += (float) $u->deposit;
            $out[] = [
                'sno' => $i, 'id' => (int) $u->account_no, 'name' => $u->name,
                'type' => $u->account_type_label ?? '', 'group' => $u->group_name ?? '',
                'naam' => round($u->expenses, 2), 'jama' => round($u->deposit, 2),
                'balance' => round(abs($f), 2), 'status' => $st,
            ];
        }
        $period     = ($start !== '' && $end !== '') ? (date('d-m-Y', strtotime($start)) . ' to ' . date('d-m-Y', strtotime($end))) : ('Full FY ' . fy()->FY);
        $title      = 'Account Statement (' . $period . ')';
        $firm_title = fy()->template_name . ' — ' . $title;
        $filename   = 'account-statement-' . date('Ymd_His');

        $exp = new \App\Libraries\Register_export();
        if ($fmt === 'excel') {
            $exp->excel($firm_title, $columns, $out, $filename);
        } elseif ($fmt === 'pdf') {
            $net    = $t_cr - $t_dr;
            $totals = ['name' => 'TOTAL (' . $i . ' parties)', 'naam' => round($t_dr, 2), 'jama' => round($t_cr, 2)];
            $meta   = [
                'Period' => $period, 'Parties' => $i . (! empty($ids) ? ' (filtered view)' : ''),
                'Total नाम (Dr)' => '₹ ' . acc_money($t_dr), 'Total जमा (Cr)' => '₹ ' . acc_money($t_cr),
                'Net Position' => '₹ ' . acc_money(abs($net)) . ' ' . ($net > 0 ? 'जमा (Cr)' : ($net < 0 ? 'नाम (Dr)' : 'Nil')),
            ];
            $exp->pdf_hindi($title, $columns, $out, $filename, null, $totals, $meta, $this->report_header_fields());
        } else {
            $exp->csv($firm_title, $columns, $out, $filename, true);
        }
    }

    /** AJAX partial: one party's नाम/जमा detail modal (cash + KisanVahi). */
    public function account_ledger_modal($account_id = 0)
    {
        $account_id = (int) $account_id;
        [$from, $to] = $this->statement_range();
        return view('\App\Modules\Admin\Views\report\_account_modal', [
            'ledger' => $this->model()->accountLedger($account_id, $from, $to),
            'from'   => $from,
            'to'     => $to,
        ]);
    }

    /** Export ONE party's नाम/जमा detail (CSV / Excel / Hindi PDF). */
    public function account_ledger_export($account_id = 0, $fmt = 'csv')
    {
        helper('accounting');
        $account_id = (int) $account_id;
        [$from, $to] = $this->statement_range();
        $ledger   = $this->model()->accountLedger($account_id, $from, $to);
        $acc      = (isset($ledger->account) && is_object($ledger->account)) ? $ledger->account : null;
        $acc_name = $acc->name ?? ('Account #' . $account_id);

        $columns = [
            'sno' => 'S.No', 'book' => 'Book', 'date' => 'Date', 'particulars' => 'Particulars',
            'voucher' => 'Voucher', 'naam' => 'नाम (Dr)', 'jama' => 'जमा (Cr)', 'balance' => 'Balance',
        ];
        $out = [];
        $i = 0;
        $t_dr = 0.0;
        $t_cr = 0.0;
        $open_sum = 0.0;
        foreach (['cash' => 'Rokad', 'kisanvahi' => 'KisanVahi'] as $key => $label) {
            $sec = $ledger->$key ?? null;
            if (! $sec) {
                continue;
            }
            $open_sum += (float) $sec->opening;
            if (empty($sec->rows)) {
                continue;
            }
            $bal = (float) $sec->opening;
            foreach ($sec->rows as $r) {
                $dr = (float) $r->debit;
                $cr = (float) $r->credit;
                $bal += $cr - $dr;
                $t_dr += $dr;
                $t_cr += $cr;
                $i++;
                $out[] = [
                    'sno' => $i, 'book' => $label,
                    'date' => ($r->date ? date('d-m-Y', strtotime($r->date)) : ''),
                    'particulars' => $r->particulars, 'voucher' => $r->voucher,
                    'naam' => round($dr, 2), 'jama' => round($cr, 2), 'balance' => round($bal, 2),
                ];
            }
        }
        $period     = ($from !== '' && $to !== '') ? (date('d-m-Y', strtotime($from)) . ' to ' . date('d-m-Y', strtotime($to))) : ('Full FY ' . fy()->FY);
        $title      = 'Account Ledger Detail — ' . $acc_name . ' (' . $period . ')';
        $firm_title = fy()->template_name . ' — ' . $title;
        $filename   = 'ledger-' . $account_id . '-' . date('Ymd_His');

        $exp = new \App\Libraries\Register_export();
        if ($fmt === 'excel') {
            $exp->excel($firm_title, $columns, $out, $filename);
        } elseif ($fmt === 'pdf') {
            $closing = $open_sum + $t_cr - $t_dr;
            $totals  = ['particulars' => 'TOTAL', 'naam' => round($t_dr, 2), 'jama' => round($t_cr, 2)];
            $meta    = [
                'Party' => $acc_name, 'Period' => $period, 'Entries' => $i,
                'Total नाम (Dr)' => '₹ ' . acc_money($t_dr), 'Total जमा (Cr)' => '₹ ' . acc_money($t_cr),
                'Closing Balance' => '₹ ' . acc_money(abs($closing)) . ' ' . ($closing > 0 ? 'जमा (Cr)' : ($closing < 0 ? 'नाम (Dr)' : 'Nil')),
            ];
            $exp->pdf_hindi($title, $columns, $out, $filename, null, $totals, $meta, $this->report_header_fields());
        } else {
            $exp->csv($firm_title, $columns, $out, $filename, true);
        }
    }

    /** Rokad Parcha — daily cash book (Jama/Naam) with carry-forward opening. */
    public function rokad_parcha()
    {
        $m = $this->model();

        if ($this->request->is('post') && $this->request->getPost('search_name') !== null) {
            $ts       = strtotime((string) $this->request->getPost('search_name'));
            $new_date = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
            session()->set('setParchaDate', $new_date);
        }

        // First visit only: default to two days ago; keep the last date on reloads.
        if (! $this->request->getPost('search_name') && ! session()->get('setParchaDate')) {
            session()->set('setParchaDate', date('Y-m-d', strtotime('-2 day')));
        }

        $m->ensure_parcha_group_column();

        $naam = $m->naam_Billing_details();
        $jama = $m->jama_Billing_details();

        $rp_ids = [];
        foreach ([$naam, $jama] as $set) {
            if (! empty($set)) {
                foreach ($set as $row) {
                    $rp_ids[] = $row->rokad_id;
                }
            }
        }

        return _layout('\App\Modules\Admin\Views\report\view_rokad_parcha', [
            'title'           => 'Track (The Rest Accounting Key) || Search Report',
            'naam'            => $naam,
            'jama'            => $jama,
            'restore_counts'  => $m->restore_counts_for($rp_ids),
            'opening_balance' => $m->rokad_cash_opening(session()->get('setParchaDate')),
            'cash_label'      => $m->rokad_cash_account_label(),
        ]);
    }

    /** Deleted Rokad Entries — trash listing page. */
    public function deleted_entries()
    {
        $m = $this->model();
        return _layout('\App\Modules\Admin\Views\report\deleted_entries', [
            'title'       => 'Track (The Rest Accounting Key) || Deleted Rokad Entries',
            'parties'     => $m->deleted_parties(),
            'users'       => $m->deleted_users(),
            'summary'     => $m->deleted_entries_summary(),
            'can_restore' => (! function_exists('erp_current_user_can')) || erp_current_user_can('report', 'delete'),
        ]);
    }

    /** DataTables server-side feed for the deleted entries trash. */
    public function deleted_entries_data()
    {
        $post        = $this->request->getPost();
        $m           = $this->model();
        $totalData   = $m->deleted_entries_count();
        $rows        = $m->deleted_entries_list();
        $can_restore = (! function_exists('erp_current_user_can')) || erp_current_user_can('report', 'delete');

        $palette = ['#2563eb', '#0ea5e9', '#7c3aed', '#db2777', '#e11d48', '#ea580c', '#ca8a04', '#16a34a', '#0d9488', '#4f46e5', '#9333ea', '#0891b2'];
        $data = [];
        foreach ($rows as $r) {
            $isWeb = (strtolower(trim((string) $r->entry_source)) === 'web' || trim((string) $r->entry_source) === '');
            $isDep = ($r->type_of_account === 'deposit');
            $nm    = trim((string) $r->party_name) !== '' ? $r->party_name : $r->account_name;
            $init  = $nm !== '' ? mb_strtoupper(mb_substr($nm, 0, 1, 'UTF-8'), 'UTF-8') : '#';
            $color = $palette[ord(strtoupper(substr($nm !== '' ? $nm : '#', 0, 1))) % count($palette)];

            $src  = $isWeb
                ? '<span class="de-pill de-pill-web"><i class="ti-desktop"></i> Web</span>'
                : '<span class="de-pill de-pill-app"><i class="ti-mobile"></i> App</span>';
            $type = $isDep
                ? '<span class="de-pill de-pill-dep">Deposit</span>'
                : '<span class="de-pill de-pill-exp">Expense</span>';

            $entry  = '<div class="de-entry"><b>#' . (int) $r->rokad_id . '</b><span>' . (! empty($r->rokad_date) ? date('d-m-Y', strtotime($r->rokad_date)) : '-') . '</span></div>';
            $party  = '<div class="de-party"><span class="de-av" style="background:' . $color . '">' . esc($init) . '</span>'
                . '<div class="de-party-t"><b title="' . esc($nm) . '">' . esc($nm) . '</b>'
                . ($r->account_no ? '<span>A/c ' . esc($r->account_no) . '</span>' : '') . '</div></div>';
            $amount = '<span class="de-amt ' . ($isDep ? 'pos' : 'neg') . '">&#8377; ' . number_format((float) $r->karch_amount, 2) . '</span>';

            $cby     = trim($r->created_by_name) !== '' ? $r->created_by_name : ('#' . $r->added_by);
            $created = '<div class="de-who"><b>' . esc($cby) . '</b><span>' . (! empty($r->added_type) ? date('d-m-Y h:i A', strtotime($r->added_type)) : '-') . '</span></div>';
            $dby     = trim($r->deleted_by_name) !== '' ? $r->deleted_by_name : ('#' . $r->deleted_by);
            $deleted = '<div class="de-who de-who-danger"><b>' . esc($dby) . '</b><span>' . (! empty($r->deleted_date) ? date('d-m-Y h:i A', strtotime($r->deleted_date)) : '-') . '</span></div>';
            $reason  = ! empty($r->delete_reason) ? '<span class="de-reason" title="' . esc($r->delete_reason) . '">' . esc($r->delete_reason) . '</span>' : '<span class="text-muted">&mdash;</span>';

            $restore_btn = $can_restore
                ? '<a class="de-act de-act-restore" title="Restore" href="javascript:void(0)" onclick="restoreEntry(' . (int) $r->rokad_id . ')"><i class="ti-back-left"></i></a>'
                : '';
            $actions = '<div class="de-actions"><a class="de-act de-act-view" title="View" href="javascript:void(0)" onclick="showDeleted(' . (int) $r->rokad_id . ')"><i class="ti-eye"></i></a>' . $restore_btn . '</div>';

            $data[] = [$entry, $party, $type, $amount, $src, $created, $deleted, $reason, $actions];
        }

        return $this->response->setJSON([
            'draw'            => (int) ($post['draw'] ?? 0),
            'recordsTotal'    => (int) $totalData,
            'recordsFiltered' => (int) $totalData,
            'data'            => $data,
            'summary'         => $m->deleted_entries_summary(),
        ]);
    }

    /** Detail popup for one deleted entry (JSON, incl. media + geo when available). */
    public function deleted_entry_detail()
    {
        $id  = (int) $this->request->getPost('id');
        $row = $this->model()->deleted_entry_detail($id);
        if (empty($row)) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'Entry not found']);
        }
        $r          = (array) $row;
        $r['image'] = ! empty($row->image_path) ? base_url($row->image_path) : '';
        $r['voice'] = ! empty($row->voice_note_path) ? base_url($row->voice_note_path) : '';
        $r['video'] = ! empty($row->video_note_path) ? base_url($row->video_note_path) : '';
        $tr         = function_exists('entry_trace_for') ? entry_trace_for('rokad', $id) : null;
        $r['ip']    = ($tr && ! empty($tr->ip_address)) ? $tr->ip_address : '';
        $r['lat']   = ($tr && $tr->latitude !== null && $tr->latitude !== '') ? $tr->latitude : '';
        $r['lng']   = ($tr && $tr->longitude !== null && $tr->longitude !== '') ? $tr->longitude : '';
        $r['status'] = 'success';
        return $this->response->setJSON($r);
    }

    /** Restore a soft-deleted rokad entry (JSON). RBAC: report.delete. */
    public function restore_entry()
    {
        if (function_exists('erp_current_user_can') && ! erp_current_user_can('report', 'delete')) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'You are not authorized to restore entries.']);
        }
        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'Invalid entry.']);
        }
        $ok = $this->model()->restore_parcha($id);
        if ($ok && function_exists('notify')) {
            notify('Rokad entry <b>#' . $id . '</b> restored', base_url('admin/report/deleted_entries'), ['event' => 'updated', 'remark' => 'Rokad entry #' . $id . ' was restored from deleted entries.']);
        }
        return $this->response->setJSON([
            'status' => $ok ? 'success' : 'error',
            'msg'    => $ok ? 'Entry restored successfully.' : 'Unable to restore (already restored or not found).',
        ]);
    }

    /** Persist a drag-and-drop group move for one Rokad Parcha entry (JSON). */
    public function rokad_parcha_move()
    {
        $rokad_id = (int) $this->request->getPost('rokad_id');
        $group    = (string) $this->request->getPost('group');
        $ok       = $this->model()->set_parcha_group($rokad_id, $group);
        return $this->response->setJSON(
            $ok ? ['status' => 'success'] : ['status' => 'error', 'msg' => 'Could not move the entry.']
        );
    }
}
