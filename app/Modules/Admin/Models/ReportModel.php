<?php

namespace App\Modules\Admin\Models;

use Config\Database;
use DateTime;

/**
 * ReportModel — CI4 port of admin/models/Report_mod (ledger slice).
 *
 * Ports the Account Ledger read: a single account's cash (aa_rokad) and
 * KisanVahi (kisanvahidata) movements as two independent sections, each with
 * its pre-period opening balance, scoped by FY + product_type + template_id.
 * Debit = नाम, Credit = जमा (credit-positive). Entry-trace IP/geo enrichment is
 * attached only when that helper is available. (Other report reads port with
 * their pages.)
 */
class ReportModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** The account master row (aa_account_name) for the ledger header. */
    public function getAccountName($id)
    {
        return $this->db()->table('aa_account_name as acn')->select('acn.*')
            ->where('account_id', $id)->get()->getRow();
    }

    /** Absolutise a stored media path for a browser link (or '' when empty). */
    private function _ledgerMediaUrl($path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        return preg_match('#^https?://#i', $path) ? $path : base_url($path);
    }

    /**
     * Account-wise ledger as TWO sections (cash + kisanvahi), each { opening, rows }.
     */
    public function accountLedger($account_no, string $from = '', string $to = '')
    {
        $db = $this->db();
        $fy = fy();

        $byDate = static function ($a, $b) {
            $ta = strtotime($a->date);
            $tb = strtotime($b->date);
            return $ta <=> $tb;
        };

        /* ================= Cash (aa_rokad) ================= */
        $b = $db->table('aa_rokad as ar')
            ->select("ar.rokad_id, ar.rokad_date, ar.account_name,
                ar.party_invoice_no, ar.quantity, ar.karch_amount, ar.type_of_account,
                ar.remark, ar.entry_source, ar.added_by, ar.added_type,
                ar.image_path, ar.voice_note_path, ar.video_note_path,
                TRIM(CONCAT(COALESCE(cu.first_name,''),' ',COALESCE(cu.last_name,''))) as added_by_name", false)
            ->join('users as cu', 'cu.id = ar.added_by', 'left')
            ->where('ar.account_no', $account_no)
            ->where('ar.status !=', 'Delete')
            ->where('ar.FY', $fy->FY)
            ->where('ar.product_type', $fy->product_type)
            ->where('ar.template_id', $fy->template_id);
        if ($from !== '') {
            $b->where('ar.rokad_date >=', $from);
        }
        if ($to !== '') {
            $b->where('ar.rokad_date <=', $to);
        }
        $cash = $b->get()->getResult();

        $cashRows = [];
        foreach ($cash as $r) {
            $is_credit = (strtolower((string) $r->type_of_account) === 'deposit');
            $amt       = (float) $r->karch_amount;
            $voucher   = trim((string) $r->party_invoice_no);
            if ($r->quantity !== null && trim((string) $r->quantity) !== '') {
                $voucher = ($voucher === '' ? '' : $voucher . ' / ') . $r->quantity;
            }
            $src        = strtolower(trim((string) $r->entry_source));
            $cashRows[] = (object) [
                'date'          => $r->rokad_date,
                'particulars'   => $r->account_name,
                'voucher'       => $voucher,
                'kind'          => 'cash',
                'rokad_id'      => (int) $r->rokad_id,
                'debit'         => $is_credit ? 0.0 : $amt,
                'credit'        => $is_credit ? $amt : 0.0,
                'remark'        => (string) $r->remark,
                'source_label'  => ($src === 'app') ? 'App' : 'Web',
                'added_by_name' => trim((string) $r->added_by_name) !== '' ? $r->added_by_name : (! empty($r->added_by) ? '#' . $r->added_by : ''),
                'added_on'      => ! empty($r->added_type) ? date('d-m-Y h:i A', strtotime($r->added_type)) : '',
                'image_url'     => $this->_ledgerMediaUrl($r->image_path ?? ''),
                'voice_url'     => $this->_ledgerMediaUrl($r->voice_note_path ?? ''),
                'video_url'     => $this->_ledgerMediaUrl($r->video_note_path ?? ''),
            ];
        }
        usort($cashRows, $byDate);

        // Entry Trace IP/geo (audit) enrichment — only when that helper is ported.
        if (function_exists('entry_traces_for_batch') && ! empty($cashRows)) {
            $rk_ids = [];
            foreach ($cashRows as $cr) {
                $rk_ids[] = $cr->rokad_id;
            }
            $rk_tr = entry_traces_for_batch('rokad', $rk_ids);
            foreach ($cashRows as $cr) {
                $t       = $rk_tr[$cr->rokad_id] ?? null;
                $cr->ip  = ($t && ! empty($t->ip_address)) ? $t->ip_address : '';
                $cr->lat = ($t && $t->latitude !== null && $t->latitude !== '') ? $t->latitude : '';
                $cr->lng = ($t && $t->longitude !== null && $t->longitude !== '') ? $t->longitude : '';
            }
        }

        $cashOpening = 0.0;
        if ($from !== '') {
            $row = $db->table('aa_rokad')
                ->select("SUM(CASE WHEN type_of_account='deposit' THEN karch_amount ELSE -karch_amount END) as bal", false)
                ->where('account_no', $account_no)
                ->where('status !=', 'Delete')
                ->where('FY', $fy->FY)
                ->where('product_type', $fy->product_type)
                ->where('template_id', $fy->template_id)
                ->where('rokad_date <', $from)
                ->get()->getRow();
            $cashOpening = (float) ($row->bal ?? 0);
        }

        /* ================= KisanVahi (kisanvahidata) ================= */
        $b = $db->table('kisanvahidata as kd')->select('kd.*', false)
            ->where('kd.account_no', $account_no)
            ->where('kd.FY', $fy->FY)
            ->where('kd.product_type', $fy->product_type)
            ->where('kd.template_id', $fy->template_id);
        if ($from !== '') {
            $b->where("STR_TO_DATE(kd.Purchase_Date, '%d-%m-%Y') >= " . $db->escape($from), null, false);
        }
        if ($to !== '') {
            $b->where("STR_TO_DATE(kd.Purchase_Date, '%d-%m-%Y') <= " . $db->escape($to), null, false);
        }
        $kv = $b->get()->getResult();

        $kvRows = [];
        foreach ($kv as $r) {
            $amt   = (float) $r->Ammount;
            $paid  = isset($r->paid_amount) ? (float) $r->paid_amount : 0.0;
            $qty   = isset($r->Quantity) ? trim((string) $r->Quantity) : '';
            $pd    = trim((string) $r->Purchase_Date);
            $pdObj = DateTime::createFromFormat('d-m-Y', $pd);
            $normDate = $pdObj ? $pdObj->format('Y-m-d') : (($ts = strtotime($pd)) ? date('Y-m-d', $ts) : $pd);
            $kvRows[] = (object) [
                'date'          => $normDate,
                'particulars'   => $r->Farmer_name,
                'voucher'       => 'KV #' . $r->Farmer_ID . ($qty !== '' ? ' / ' . $qty : ''),
                'kind'          => 'kisanvahi',
                'farmer_id'     => $r->Farmer_ID,
                'quantity'      => $qty,
                'debit'         => $amt,   // नाम (Amount)
                'credit'        => $paid,  // जमा (Paid)
                'remark'        => isset($r->remark) ? (string) $r->remark : '',
                'source_label'  => 'KV',
                'added_by_name' => '',
                'added_on'      => '',
                'image_url'     => '',
                'voice_url'     => '',
                'video_url'     => '',
            ];
        }
        usort($kvRows, $byDate);

        $kvOpening = 0.0;
        if ($from !== '') {
            $row = $db->table('kisanvahidata')
                ->select("SUM(COALESCE(paid_amount,0) - COALESCE(Ammount,0)) as bal", false)
                ->where('account_no', $account_no)
                ->where('FY', $fy->FY)
                ->where('product_type', $fy->product_type)
                ->where('template_id', $fy->template_id)
                ->where("STR_TO_DATE(Purchase_Date, '%d-%m-%Y') < " . $db->escape($from), null, false)
                ->get()->getRow();
            $kvOpening = (float) ($row->bal ?? 0);
        }

        return (object) [
            'account'   => $this->getAccountName($account_no),
            'cash'      => (object) ['opening' => $cashOpening, 'rows' => $cashRows],
            'kisanvahi' => (object) ['opening' => $kvOpening, 'rows' => $kvRows],
        ];
    }

    /* =====================================================================
     * ROKAD PARCHA (daily cash book)
     * ===================================================================== */

    /** The "shri rokad nagad" cash account id used for carry-forward. */
    public const ROKAD_CASH_ACCOUNT_ID = 15;

    private function rokad_temp_id(): int
    {
        return (int) (is_object(fy()) ? fy()->template_id : 0);
    }

    /** First entry date of this firm/FY (the book's day-1); null if no entries. */
    private function rokad_book_start($temp_id)
    {
        $r = $this->db()->query("SELECT MIN(rokad_date) md FROM aa_rokad
            WHERE status <> 'Delete' AND template_id = '" . $temp_id . "'")->getRow();
        return ($r && ! empty($r->md)) ? $r->md : null;
    }

    /** Selected parcha date (session), normalised to Y-m-d. */
    private function rokad_selected_date(): string
    {
        $d  = (string) (session()->get('setParchaDate') ?? '');
        $ts = $d !== '' ? strtotime($d) : false;
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }

    /** SQL flag (is_bill) marking a rokad row as a bill-generated cross entry. */
    private function rokad_bill_flag_sql(): string
    {
        return "(EXISTS(SELECT 1 FROM invoice_system bi WHERE bi.rokadh_jama_id = ar.rokad_id OR bi.rokadh_nama_id = ar.rokad_id)"
            . " OR EXISTS(SELECT 1 FROM tax_invoice_system bt WHERE bt.rokadh_jama_id = ar.rokad_id OR bt.rokadh_nama_id = ar.rokad_id)"
            . " OR EXISTS(SELECT 1 FROM uninvoice_system bu WHERE bu.rokadh_jama_id = ar.rokad_id OR bu.rokadh_nama_id = ar.rokad_id)) AS is_bill";
    }

    /** Lazily add aa_rokad.parcha_group (manual drag-drop group override). */
    public function ensure_parcha_group_column(): void
    {
        $db   = $this->db();
        $name = $db->getDatabase();
        $exists = $db->query("SELECT COUNT(*) c FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = " . $db->escape($name) . "
              AND TABLE_NAME = 'aa_rokad' AND COLUMN_NAME = 'parcha_group'")->getRow()->c;
        if ((int) $exists === 0) {
            $db->query("ALTER TABLE aa_rokad ADD COLUMN parcha_group VARCHAR(20) NULL DEFAULT NULL");
        }
    }

    /** Persist a manual group override for one rokad entry (current firm). */
    public function set_parcha_group($rokad_id, $group): bool
    {
        $allowed = ['bills', 'nagad', 'bank', 'cash'];
        $group   = strtolower(trim((string) $group));
        if (! in_array($group, $allowed, true)) {
            return false;
        }
        $this->ensure_parcha_group_column();
        $temp_id = $this->rokad_temp_id();
        $this->db()->table('aa_rokad')->where('rokad_id', (int) $rokad_id)
            ->where('template_id', $temp_id)->update(['parcha_group' => $group]);
        return $this->db()->error()['code'] == 0;
    }

    /** Deposit-side rows for the selected date (CI3 name kept: naam=deposit set). */
    public function naam_Billing_details()
    {
        $defaultDate = $this->rokad_selected_date();
        $temp_id     = $this->rokad_temp_id();
        $cash        = (int) self::ROKAD_CASH_ACCOUNT_ID;
        $bookStart   = $this->rokad_book_start($temp_id);
        $whereCash   = ($bookStart !== null && $defaultDate > $bookStart) ? (" AND ar.account_no <> " . $cash) : "";
        $q = $this->db()->query("SELECT ar.*, an.account_id, an.name AS name, an.account_type AS account_type, TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) AS added_by_name, " . $this->rokad_bill_flag_sql() . " FROM aa_rokad ar LEFT JOIN aa_account_name an ON ar.account_no = an.account_id LEFT JOIN users u ON ar.added_by = u.id WHERE ar.type_of_account = 'deposit' AND ar.status <> 'Delete'" . $whereCash . " AND ar.rokad_date = '" . $defaultDate . "' AND ar.template_id = '" . $temp_id . "' ");
        return $q->getNumRows() > 0 ? $q->getResult() : false;
    }

    /** Expense-side rows for the selected date (CI3 name kept: jama=expenses set). */
    public function jama_Billing_details()
    {
        $defaultDate = $this->rokad_selected_date();
        $temp_id     = $this->rokad_temp_id();
        $cash        = (int) self::ROKAD_CASH_ACCOUNT_ID;
        $bookStart   = $this->rokad_book_start($temp_id);
        $whereCash   = ($bookStart !== null && $defaultDate > $bookStart) ? (" AND ar.account_no <> " . $cash) : "";
        $q = $this->db()->query("SELECT ar.*, an.account_id , an.name as name, an.account_type AS account_type, TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) AS added_by_name, " . $this->rokad_bill_flag_sql() . " FROM aa_rokad ar LEFT JOIN aa_account_name an ON ar.account_no = an.account_id LEFT JOIN users u ON ar.added_by = u.id WHERE ar.type_of_account = 'expenses' AND ar.status <> 'Delete'" . $whereCash . " AND ar.rokad_date = '" . $defaultDate . "' AND ar.template_id = '" . $temp_id . "';");
        return $q->getNumRows() > 0 ? $q->getResult() : false;
    }

    /** Carry-forward opening (आगे लाया) for a parcha date; 0 on/before book day-1. */
    public function rokad_cash_opening($date): float
    {
        $temp_id = $this->rokad_temp_id();
        $cash    = (int) self::ROKAD_CASH_ACCOUNT_ID;
        $ts      = strtotime((string) $date);
        $date    = $ts ? date('Y-m-d', $ts) : date('Y-m-d');

        $uses = $this->db()->query("SELECT 1 FROM aa_rokad
            WHERE account_no = " . $cash . " AND status <> 'Delete' AND template_id = '" . $temp_id . "' LIMIT 1")->getNumRows();
        if (! $uses) {
            return 0.0;
        }

        $bookStart = $this->rokad_book_start($temp_id);
        if ($bookStart === null || $date <= $bookStart) {
            return 0.0;
        }

        $seed = (float) $this->db()->query("SELECT COALESCE(SUM(CASE WHEN type_of_account='deposit' THEN karch_amount ELSE -karch_amount END),0) v
            FROM aa_rokad WHERE account_no = " . $cash . " AND status <> 'Delete' AND template_id = '" . $temp_id . "' AND rokad_date = '" . $bookStart . "'")->getRow()->v;

        $rn = (float) $this->db()->query("SELECT COALESCE(SUM(CASE WHEN type_of_account='deposit' THEN karch_amount ELSE -karch_amount END),0) v
            FROM aa_rokad WHERE account_no <> " . $cash . " AND status <> 'Delete' AND template_id = '" . $temp_id . "'
              AND rokad_date >= '" . $bookStart . "' AND rokad_date < '" . $date . "'")->getRow()->v;

        return $seed + $rn;
    }

    /** Display label for the rokad cash account, e.g. "shri rokad nagad_15". */
    public function rokad_cash_account_label(): string
    {
        $cash = (int) self::ROKAD_CASH_ACCOUNT_ID;
        $q    = $this->db()->query("SELECT name FROM aa_account_name WHERE account_id = " . $cash . " LIMIT 1");
        $name = ($q->getNumRows() > 0) ? trim($q->getRow()->name) : 'shri rokad nagad';
        return $name . '_' . $cash;
    }

    /** [rokad_id => restore count] from aa_rokad_restore_log. */
    public function restore_counts_for($ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids))));
        if (empty($ids) || ! $this->db()->tableExists('aa_rokad_restore_log')) {
            return [];
        }
        $rows = $this->db()->table('aa_rokad_restore_log')
            ->select('rokad_id, COUNT(*) AS n', false)
            ->whereIn('rokad_id', $ids)->groupBy('rokad_id')->get()->getResult();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->rokad_id] = (int) $r->n;
        }
        return $out;
    }

    /* =====================================================================
     * ACCOUNT REPORT (search) + ACCOUNT STATEMENT (byaccount_name)
     * ===================================================================== */

    /** Total expenses for an account: aa_rokad expenses + kisanvahidata amount. */
    public function fetchtheFinalAmountexpenses($id)
    {
        $fy = fy();
        $rokad = $this->db()->table('aa_rokad')->select('SUM(karch_amount) as expenses')
            ->where('FY', $fy->FY)->where('product_type', $fy->product_type)->where('template_id', $fy->template_id)
            ->where('type_of_account', 'expenses')->where('status !=', 'Delete')->where('account_no', $id)
            ->get()->getRow();
        $kv = $this->db()->table('kisanvahidata')->select('SUM(Ammount) as expenses')
            ->where('FY', $fy->FY)->where('product_type', $fy->product_type)->where('template_id', $fy->template_id)
            ->where('account_no', $id)->get()->getRow();
        return (object) [
            'expenses'      => ($kv->expenses ?? 0) + ($rokad->expenses ?? 0),
            'kisanvahiName' => (int) ($kv->expenses ?? 0),
            'jama'          => (int) ($rokad->expenses ?? 0),
        ];
    }

    /** Total deposits for an account (aa_rokad deposit side). */
    public function fetchtheFinalAmountdeposit($id)
    {
        $fy = fy();
        return $this->db()->table('aa_rokad')->select('SUM(karch_amount) as deposit')
            ->where('type_of_account', 'deposit')->where('status !=', 'Delete')
            ->where('FY', $fy->FY)->where('product_type', $fy->product_type)->where('template_id', $fy->template_id)
            ->where('account_no', $id)->get()->getRow();
    }

    /** Count of mapped KisanVahi registrations for an account. */
    public function totalMappedKisanVahi($id)
    {
        $fy = fy();
        return $this->db()->table('reg_kisanvahidata')->select('count(Kisan_ID) as kisan_id')
            ->where('FY', $fy->FY)->where('product_type', $fy->product_type)->where('template_id', $fy->template_id)
            ->where('account_no', $id)->get()->getRow();
    }

    /** KisanVahi amount/quantity for an account (+ is_Kisan flag). */
    public function fetchtheFinalAmountKisanVahi($id)
    {
        $fy = fy();
        return $this->db()->table('kisanvahidata')
            ->select('SUM(Ammount) as Amount, SUM(Quantity) as Quantity, acn.is_Kisan')
            ->join('aa_account_name as acn', 'acn.account_id = kisanvahidata.account_no', 'left')
            ->where('FY', $fy->FY)->where('product_type', $fy->product_type)->where('template_id', $fy->template_id)
            ->where('account_no', $id)->get()->getRow();
    }

    /** Paid (UTR) KisanVahi amount/qty/count for an account. */
    public function getKisanVahiUTRAmount($id)
    {
        $fy = fy();
        return $this->db()->table('kisanvahidata')
            ->select('SUM(paid_amount) as Amount, SUM(Quantity) as Quantity, Count(Kisan_ID) as Count')
            ->where('FY', $fy->FY)->where('paid_status', 1)
            ->where('product_type', $fy->product_type)->where('template_id', $fy->template_id)
            ->where('account_no', $id)->get()->getRow();
    }

    /** Upsert the account's last search result into aa_searchlog (audit). */
    public function logSearchResult(array $data, array $searchName): array
    {
        $rec = [
            'name'          => $searchName[0] ?? '',
            'account_no'    => $searchName[1] ?? '',
            'expenses'      => $data['expenses']->expenses ?? 0,
            'deposit'       => $data['deposit']->deposit ?? 0,
            'finaldeposit'  => $data['Finaldeposit'] ?? '',
            'finalexpenses' => $data['Finalexpenses'] ?? '',
            'added_date'    => date('Y-m-d H:i:s'),
            'updated_date'  => date('Y-m-d H:i:s'),
        ];
        $exists = $this->db()->table('aa_searchlog')->where('account_no', $searchName[1] ?? '')->countAllResults();
        if ($exists > 0) {
            $this->db()->table('aa_searchlog')->where('search_id', $searchName[1] ?? '')->update($rec);
            return ['status' => 'success', 'msg' => 'updated'];
        }
        $this->db()->table('aa_searchlog')->insert($rec);
        return $this->db()->insertID() ? ['status' => 'success', 'msg' => 'added'] : ['status' => 'error', 'error_msg' => 'Invalid Request'];
    }

    /**
     * Account Statement rows: per-account expenses/deposit/net/entries across
     * aa_rokad + done KisanVahi, for the firm/FY. Filters by [start,end] only
     * when BOTH bounds are present.
     */
    public function Billing_details($start_date = null, $end_date = null)
    {
        $fy    = fy();
        $range = (! empty($start_date) && ! empty($end_date))
            ? " AND aa_rokad.rokad_date BETWEEN '" . $start_date . "' AND '" . $end_date . "'"
            : "";
        $sql = "SELECT sum(expenses) as expenses,sum(deposit) as deposit, sum(deposit-expenses) as finalamt,count(*) as entries,account_no,aa_account_name.name FROM ("
            . "SELECT (CASE WHEN type_of_account='deposit' THEN karch_amount ELSE 0 END) as deposit,(CASE WHEN type_of_account='expenses' THEN karch_amount ELSE 0 END) as expenses,account_no FROM aa_rokad "
            . "WHERE status <> 'Delete' AND FY = '" . $fy->FY . "' AND product_type = '" . $fy->product_type . "' AND template_id = '" . $fy->template_id . "'" . $range . " "
            . "UNION ALL SELECT (0) as deposit,Ammount as expenses,account_no FROM kisanvahidata "
            . "WHERE status_rec = 'done' AND FY = '" . $fy->FY . "' AND product_type = '" . $fy->product_type . "' AND template_id = '" . $fy->template_id . "' "
            . ") finaltbl LEFT JOIN aa_account_name on aa_account_name.account_id=finaltbl.account_no GROUP BY finaltbl.account_no";
        $q = $this->db()->query($sql);
        return $q->getNumRows() > 0 ? $q->getResult() : false;
    }

    /** account_id => {account_type, group_name} enrichment for statement rows. */
    public function statementMeta(array $ids): array
    {
        $db   = $this->db();
        $meta = [];
        $ids  = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids) || ! $db->fieldExists('account_type', 'aa_account_name')) {
            return $meta;
        }
        $hasGroup = $db->tableExists('aa_accounting_group') && $db->fieldExists('account_group_id', 'aa_account_name');
        $b = $db->table('aa_account_name an')
            ->select('an.account_id, an.account_type' . ($hasGroup ? ', ag.name AS group_name' : ', NULL AS group_name'), false)
            ->whereIn('an.account_id', $ids);
        if ($hasGroup) {
            $b->join('aa_accounting_group ag', 'ag.id = an.account_group_id', 'left');
        }
        foreach ($b->get()->getResult() as $m) {
            $meta[(int) $m->account_id] = $m;
        }
        return $meta;
    }

    /* =====================================================================
     * DELETED ROKAD ENTRIES (trash + restore)
     * ===================================================================== */

    /** Apply the shared trash filters (POST or GET) to a builder over aa_rokad `ar`. */
    private function deleted_entries_filters($b): void
    {
        $req = service('request');
        $b->where('ar.status', 'Delete')->where('ar.template_id', fy()->template_id);

        $from   = $req->getPostGet('from_date');
        $to     = $req->getPostGet('to_date');
        $party  = $req->getPostGet('party');
        $user   = $req->getPostGet('user');
        $source = $req->getPostGet('source');
        $type   = $req->getPostGet('type');

        if (! empty($from) && ! empty($to)) {
            $b->where('ar.rokad_date >=', date('Y-m-d', strtotime($from)))
              ->where('ar.rokad_date <=', date('Y-m-d', strtotime($to)));
        }
        if (! empty($party) && $party !== 'none') {
            $b->where('ar.account_no', $party);
        }
        if (! empty($user) && $user !== 'none') {
            $b->where('ar.deleted_by', $user);
        }
        if (! empty($source) && $source !== 'none') {
            $b->where('ar.entry_source', $source);
        }
        if (! empty($type) && $type !== 'none') {
            $b->where('ar.type_of_account', $type);
        }

        $post = $req->getPost();
        if (! empty($post['search']['value'])) {
            $s = $post['search']['value'];
            $b->groupStart()->like('ar.account_name', $s)->orLike('ar.rokad_id', $s)->orLike('ar.delete_reason', $s)->groupEnd();
        }
    }

    public function deleted_entries_count(): int
    {
        $b = $this->db()->table('aa_rokad as ar');
        $this->deleted_entries_filters($b);
        return $b->countAllResults();
    }

    public function deleted_entries_list(): array
    {
        $post = service('request')->getPost();
        $b = $this->db()->table('aa_rokad as ar')
            ->select("ar.*, acn.name as party_name,
                TRIM(CONCAT(COALESCE(cu.first_name,''),' ',COALESCE(cu.last_name,''))) as created_by_name,
                TRIM(CONCAT(COALESCE(du.first_name,''),' ',COALESCE(du.last_name,''))) as deleted_by_name", false)
            ->join('aa_account_name as acn', 'acn.account_id = ar.account_no', 'left')
            ->join('users as cu', 'cu.id = ar.added_by', 'left')
            ->join('users as du', 'du.id = ar.deleted_by', 'left');
        $this->deleted_entries_filters($b);
        $b->orderBy('ar.deleted_date', 'desc');
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    /** Count + amount totals for the current filter (KPI cards). */
    public function deleted_entries_summary(): array
    {
        $b = $this->db()->table('aa_rokad as ar')
            ->select("COUNT(*) AS cnt,
                COALESCE(SUM(ar.karch_amount),0) AS total_amt,
                COALESCE(SUM(CASE WHEN ar.type_of_account='deposit'  THEN ar.karch_amount ELSE 0 END),0) AS deposit_amt,
                COALESCE(SUM(CASE WHEN ar.type_of_account='expenses' THEN ar.karch_amount ELSE 0 END),0) AS expense_amt", false);
        $this->deleted_entries_filters($b);
        $row = $b->get()->getRow();
        return [
            'count'   => $row ? (int) $row->cnt : 0,
            'amount'  => $row ? (float) $row->total_amt : 0,
            'deposit' => $row ? (float) $row->deposit_amt : 0,
            'expense' => $row ? (float) $row->expense_amt : 0,
        ];
    }

    public function deleted_entry_detail($id)
    {
        return $this->db()->table('aa_rokad as ar')
            ->select("ar.*, acn.name as party_name,
                TRIM(CONCAT(COALESCE(cu.first_name,''),' ',COALESCE(cu.last_name,''))) as created_by_name,
                TRIM(CONCAT(COALESCE(du.first_name,''),' ',COALESCE(du.last_name,''))) as deleted_by_name", false)
            ->join('aa_account_name as acn', 'acn.account_id = ar.account_no', 'left')
            ->join('users as cu', 'cu.id = ar.added_by', 'left')
            ->join('users as du', 'du.id = ar.deleted_by', 'left')
            ->where('ar.rokad_id', $id)->where('ar.status', 'Delete')
            ->get()->getRow();
    }

    /** Distinct deleted-entry parties (filter dropdown). */
    public function deleted_parties(): array
    {
        return $this->db()->table('aa_rokad as ar')->distinct()
            ->select('ar.account_no, acn.name', false)
            ->join('aa_account_name as acn', 'acn.account_id = ar.account_no', 'left')
            ->where('ar.status', 'Delete')->where('ar.template_id', fy()->template_id)
            ->where("ar.account_no <> ''")->orderBy('acn.name')
            ->get()->getResult();
    }

    /** Distinct users who deleted entries (filter dropdown). */
    public function deleted_users(): array
    {
        return $this->db()->table('aa_rokad as ar')->distinct()
            ->select("ar.deleted_by, TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as name", false)
            ->join('users as u', 'u.id = ar.deleted_by', 'left')
            ->where('ar.status', 'Delete')->where('ar.template_id', fy()->template_id)
            ->where('ar.deleted_by IS NOT NULL')
            ->get()->getResult();
    }

    /** Restore a soft-deleted entry + log it. Returns true on success. */
    public function restore_parcha($id): bool
    {
        $db    = $this->db();
        $entry = $db->table('aa_rokad')->where('rokad_id', $id)->where('status', 'Delete')->get()->getRow();
        if (empty($entry)) {
            return false;
        }

        $db->table('aa_rokad')->where('rokad_id', $id)->where('status', 'Delete')->update([
            'status'        => 'Active',
            'delete_reason' => null,
            'deleted_by'    => null,
            'deleted_date'  => null,
        ]);

        if ($db->affectedRows() > 0) {
            // aa_rokad_restore_log.id may not be AUTO_INCREMENT on some installs —
            // supply MAX(id)+1 ourselves so a 2nd restore doesn't collide on id=0.
            $next = $db->query("SELECT COALESCE(MAX(id),0)+1 AS n FROM aa_rokad_restore_log")->getRow();
            $log  = [
                'rokad_id'      => $id,
                'restored_by'   => currentuserinfo()->id,
                'restored_date' => date('Y-m-d H:i:s'),
                'template_id'   => $entry->template_id,
            ];
            if ($next && (int) $next->n > 0) {
                $log['id'] = (int) $next->n;
            }
            $db->table('aa_rokad_restore_log')->insert($log);
            return true;
        }
        return false;
    }
}
