<?php

namespace App\Libraries;

use App\Models\InvCorrectionModel;
use Config\Database;

/**
 * Read-side analytics for inventory: the daily-closing figures (Task 7) and the
 * owner dashboard + report datasets (Task 8). Kept separate from InventoryService
 * (the write seam) so heavy aggregate queries live in one optimised place and can
 * be reused by the web controllers and the mobile API.
 */
class InventoryReport
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /** Add the company scope to a builder when not super-admin (null = all). */
    private function scope($builder, ?int $companyId, string $col = 'company_id')
    {
        if ($companyId !== null) {
            $builder->where($col, $companyId);
        }
        return $builder;
    }

    // =================================================================
    // Task 7 — Daily closing figures
    // =================================================================

    /**
     * The day's movement summary plus the live closing balance. Everything the
     * Daily Closing screen and its report/snapshot need.
     *
     * @return array<string,float|int>
     */
    public function dailySummary(?int $companyId, string $date): array
    {
        // Movement totals for the date, split by type.
        $b = $this->db->table('inv_movements')
            ->select("
                SUM(CASE WHEN movement_type='inward'  THEN bags ELSE 0 END) AS received_bags,
                SUM(CASE WHEN movement_type='outward' THEN bags ELSE 0 END) AS dispatched_bags,
                SUM(CASE WHEN movement_type='inward'  THEN weight ELSE 0 END) AS received_weight,
                SUM(CASE WHEN movement_type='outward' THEN weight ELSE 0 END) AS dispatched_weight,
                SUM(CASE WHEN movement_type='adjustment' THEN direction*bags ELSE 0 END) AS adjustment_bags,
                COUNT(*) AS entry_count", false)
            ->where('deleted_at', null)
            ->where('DATE(created_at)', $date);
        $this->scope($b, $companyId);
        $mv = $b->get()->getRowArray() ?: [];

        $received   = (float) ($mv['received_bags'] ?? 0);
        $dispatched = (float) ($mv['dispatched_bags'] ?? 0);
        $adjustment = (float) ($mv['adjustment_bags'] ?? 0);

        // Live current total stock = the closing balance.
        $sb = $this->db->table('inv_stock')->selectSum('bags');
        $this->scope($sb, $companyId);
        $closing = (float) ($sb->get()->getRowArray()['bags'] ?? 0);

        // Opening derived so that opening + received − dispatched + adjustment = closing.
        $opening    = round($closing - $received + $dispatched - $adjustment, 2);
        $difference = $adjustment; // closing − (opening + received − dispatched)

        return [
            'received_bags'       => round($received, 2),
            'dispatched_bags'     => round($dispatched, 2),
            'received_weight'     => round((float) ($mv['received_weight'] ?? 0), 2),
            'dispatched_weight'   => round((float) ($mv['dispatched_weight'] ?? 0), 2),
            'adjustment_bags'     => round($adjustment, 2),
            'opening_bags'        => $opening,
            'closing_bags'        => round($closing, 2),
            'difference_bags'     => round($difference, 2),
            'entry_count'         => (int) ($mv['entry_count'] ?? 0),
            'pending_corrections' => (new InvCorrectionModel())->pendingCount($companyId),
        ];
    }

    // =================================================================
    // Simple flow — Stock Position (day / month / year)
    // =================================================================

    /**
     * Per-product stock position for a date range: opening balance (everything
     * before the range), purchases and sales inside it (quantity + ₹), and the
     * closing balance. Powers the day / month / year Stock Position screen.
     *
     * @return array{rows: list<array>, totals: array}
     */
    public function stockPosition(?int $companyId, string $from, string $to): array
    {
        $fromDT = $from . ' 00:00:00';

        $b = $this->db->table('inv_products p')
            ->select("p.id, p.name, p.unit,
                COALESCE(SUM(CASE WHEN m.created_at < '{$fromDT}' THEN m.direction * m.bags ELSE 0 END),0) AS opening,
                COALESCE(SUM(CASE WHEN DATE(m.created_at) BETWEEN '{$from}' AND '{$to}' AND m.movement_type='inward'  THEN m.bags ELSE 0 END),0) AS in_qty,
                COALESCE(SUM(CASE WHEN DATE(m.created_at) BETWEEN '{$from}' AND '{$to}' AND m.movement_type='inward'  THEN m.amount ELSE 0 END),0) AS in_amt,
                COALESCE(SUM(CASE WHEN DATE(m.created_at) BETWEEN '{$from}' AND '{$to}' AND m.movement_type='outward' THEN m.bags ELSE 0 END),0) AS out_qty,
                COALESCE(SUM(CASE WHEN DATE(m.created_at) BETWEEN '{$from}' AND '{$to}' AND m.movement_type='outward' THEN m.amount ELSE 0 END),0) AS out_amt,
                COALESCE(SUM(CASE WHEN DATE(m.created_at) BETWEEN '{$from}' AND '{$to}' THEN m.direction * m.bags ELSE 0 END),0) AS period_net", false)
            ->join('inv_movements m', 'm.product_id = p.id AND m.deleted_at IS NULL', 'left')
            ->where('p.deleted_at', null)->where('p.status', 1)
            ->groupBy('p.id')->orderBy('p.name', 'ASC');
        $this->scope($b, $companyId, 'p.company_id');
        $rows = $b->get()->getResultArray();

        $out = [];
        $t = ['opening' => 0.0, 'in_qty' => 0.0, 'in_amt' => 0.0, 'out_qty' => 0.0, 'out_amt' => 0.0, 'closing' => 0.0];
        foreach ($rows as $r) {
            $opening = (float) $r['opening'];
            $closing = $opening + (float) $r['period_net'];
            $row = [
                'name'     => $r['name'],
                'unit'     => $r['unit'] ?: 'bag',
                'opening'  => $opening,
                'in_qty'   => (float) $r['in_qty'],
                'in_amt'   => (float) $r['in_amt'],
                'out_qty'  => (float) $r['out_qty'],
                'out_amt'  => (float) $r['out_amt'],
                'closing'  => $closing,
            ];
            $out[] = $row;
            $t['opening'] += $opening;
            $t['in_qty']  += (float) $r['in_qty'];
            $t['in_amt']  += (float) $r['in_amt'];
            $t['out_qty'] += (float) $r['out_qty'];
            $t['out_amt'] += (float) $r['out_amt'];
            $t['closing'] += $closing;
        }
        return ['rows' => $out, 'totals' => $t];
    }

    // =================================================================
    // Setup completion score + next steps (Inventory Setup page)
    // =================================================================

    /**
     * Score how completely a company has set up its inventory and return an
     * ordered checklist of next steps. Each step is weighted so the percentage
     * reflects real readiness (products/godowns matter more than a low-stock
     * nicety). Drives the "Setup Completion" card on the masters page.
     *
     * @return array{score:int, done:int, total:int, complete:bool, steps:list<array>, next:?array}
     */
    public function setupScore(?int $companyId): array
    {
        // One cheap pass over each master for the counts we need.
        $prod = $this->db->table('inv_products')->select(
            'COUNT(*) AS n,
             SUM(CASE WHEN avg_weight > 0 THEN 1 ELSE 0 END) AS w,
             SUM(CASE WHEN rate > 0 THEN 1 ELSE 0 END) AS r,
             SUM(CASE WHEN low_stock > 0 THEN 1 ELSE 0 END) AS ls', false)
            ->where('deleted_at', null)->where('status', 1);
        $this->scope($prod, $companyId);
        $p = $prod->get()->getRowArray() ?: ['n' => 0, 'w' => 0, 'r' => 0, 'ls' => 0];

        $wh = $this->db->table('inv_warehouses')->select(
            'COUNT(*) AS n, SUM(CASE WHEN capacity > 0 THEN 1 ELSE 0 END) AS cap', false)
            ->where('deleted_at', null)->where('status', 1);
        $this->scope($wh, $companyId);
        $w = $wh->get()->getRowArray() ?: ['n' => 0, 'cap' => 0];

        $pt = $this->db->table('inv_parties')->select(
            "COUNT(*) AS n,
             SUM(CASE WHEN type IN ('supplier','both') THEN 1 ELSE 0 END) AS sup,
             SUM(CASE WHEN type IN ('customer','both') THEN 1 ELSE 0 END) AS cus", false)
            ->where('deleted_at', null)->where('status', 1);
        $this->scope($pt, $companyId);
        $party = $pt->get()->getRowArray() ?: ['n' => 0, 'sup' => 0, 'cus' => 0];

        $mvb = $this->db->table('inv_movements')->where('deleted_at', null);
        $this->scope($mvb, $companyId);
        $moves = (int) $mvb->countAllResults();

        $pn  = (int) $p['n'];
        $wn  = (int) $w['n'];
        $ptn = (int) $party['n'];

        // Each step: weight, done?, human detail, and where to act.
        $steps = [
            [
                'key' => 'products', 'weight' => 20, 'icon' => 'bi-box',
                'label' => 'Add your products', 'desc' => 'The goods you store (e.g. Potato, Rice).',
                'done' => $pn > 0, 'detail' => $pn > 0 ? "{$pn} added" : 'None yet',
                'cta' => $pn > 0 ? 'Add more' : 'Add product', 'target' => 'product',
            ],
            [
                'key' => 'godowns', 'weight' => 20, 'icon' => 'bi-buildings',
                'label' => 'Add your godowns', 'desc' => 'Where stock is kept.',
                'done' => $wn > 0, 'detail' => $wn > 0 ? "{$wn} added" : 'None yet',
                'cta' => $wn > 0 ? 'Add more' : 'Add godown', 'target' => 'warehouse',
            ],
            [
                'key' => 'parties', 'weight' => 15, 'icon' => 'bi-people',
                'label' => 'Add suppliers & customers', 'desc' => 'Who you buy from and sell to.',
                'done' => (int) $party['sup'] > 0 && (int) $party['cus'] > 0,
                'detail' => $ptn > 0 ? ((int) $party['sup'] . ' supplier·' . (int) $party['cus'] . ' customer') : 'None yet',
                'cta' => $ptn > 0 ? 'Add more' : 'Add party', 'target' => 'party',
            ],
            [
                'key' => 'weights', 'weight' => 10, 'icon' => 'bi-basket',
                'label' => 'Set average bag weights', 'desc' => 'Lets weight auto-fill from bag counts.',
                'done' => $pn > 0 && (int) $p['w'] === $pn,
                'detail' => $pn > 0 ? ((int) $p['w'] . ' of ' . $pn . ' products') : 'Add products first',
                'cta' => 'Set weights', 'target' => 'product',
            ],
            [
                'key' => 'rates', 'weight' => 10, 'icon' => 'bi-cash-stack',
                'label' => 'Set product rates', 'desc' => 'Needed to value your inventory.',
                'done' => $pn > 0 && (int) $p['r'] === $pn,
                'detail' => $pn > 0 ? ((int) $p['r'] . ' of ' . $pn . ' priced') : 'Add products first',
                'cta' => 'Set rates', 'target' => 'product',
            ],
            [
                'key' => 'capacity', 'weight' => 10, 'icon' => 'bi-rulers',
                'label' => 'Set godown capacity', 'desc' => 'Powers warehouse utilisation.',
                'done' => $wn > 0 && (int) $w['cap'] === $wn,
                'detail' => $wn > 0 ? ((int) $w['cap'] . ' of ' . $wn . ' godowns') : 'Add godowns first',
                'cta' => 'Set capacity', 'target' => 'warehouse',
            ],
            [
                'key' => 'lowstock', 'weight' => 5, 'icon' => 'bi-exclamation-triangle',
                'label' => 'Set low-stock alerts', 'desc' => 'Get warned before you run out.',
                'done' => $pn > 0 && (int) $p['ls'] > 0,
                'detail' => $pn > 0 ? ((int) $p['ls'] . ' products with alerts') : 'Add products first',
                'cta' => 'Set alerts', 'target' => 'product',
            ],
            [
                'key' => 'stock', 'weight' => 10, 'icon' => 'bi-box-arrow-in-down',
                'label' => 'Record your first stock', 'desc' => 'Do a Stock Inward to go live.',
                'done' => $moves > 0, 'detail' => $moves > 0 ? "{$moves} movements" : 'No entries yet',
                'cta' => 'Stock Inward', 'url' => site_url('inventory/inward'),
            ],
        ];

        $earned = 0;
        $doneN  = 0;
        $next   = null;
        foreach ($steps as $s) {
            if ($s['done']) {
                $earned += $s['weight'];
                $doneN++;
            } elseif ($next === null) {
                $next = $s;
            }
        }

        return [
            'score'    => (int) round($earned),
            'done'     => $doneN,
            'total'    => count($steps),
            'complete' => $doneN === count($steps),
            'steps'    => $steps,
            'next'     => $next,
        ];
    }

    // =================================================================
    // Task 8 — Owner dashboard
    // =================================================================

    /** Every card the owner dashboard shows, in one pass. */
    public function dashboard(?int $companyId): array
    {
        $today = date('Y-m-d');
        $daily = $this->dailySummary($companyId, $today);

        // Current inventory: total bags/weight and valuation (bags × product rate).
        $sb = $this->db->table('inv_stock s')
            ->select('SUM(s.bags) AS bags, SUM(s.weight) AS weight, SUM(s.bags * COALESCE(p.rate,0)) AS value', false)
            ->join('inv_products p', 'p.id = s.product_id', 'left');
        $this->scope($sb, $companyId, 's.company_id');
        $stock = $sb->get()->getRowArray() ?: [];

        // Net stock difference recorded via adjustments (lifetime signed).
        $adb = $this->db->table('inv_movements')->select('SUM(direction*bags) AS diff', false)
            ->where('movement_type', 'adjustment')->where('deleted_at', null);
        $this->scope($adb, $companyId);
        $netDiff = (float) ($adb->get()->getRowArray()['diff'] ?? 0);

        // Warehouse utilisation = used bags / total capacity (capacity 0 = unlimited, excluded).
        $wb = $this->db->table('inv_warehouses w')
            ->select('SUM(CASE WHEN w.capacity > 0 THEN w.capacity ELSE 0 END) AS cap,
                      SUM(CASE WHEN w.capacity > 0 THEN COALESCE(s.bags,0) ELSE 0 END) AS used', false)
            ->join('(SELECT warehouse_id, SUM(bags) AS bags FROM inv_stock GROUP BY warehouse_id) s', 's.warehouse_id = w.id', 'left')
            ->where('w.deleted_at', null);
        $this->scope($wb, $companyId, 'w.company_id');
        $wu   = $wb->get()->getRowArray() ?: [];
        $cap  = (float) ($wu['cap'] ?? 0);
        $used = (float) ($wu['used'] ?? 0);
        $util = $cap > 0 ? round($used / $cap * 100, 1) : 0.0;

        // Old stock: bags still sitting in lots older than 30 days.
        $osb = $this->db->table('inv_lots')->select('SUM(remaining_bags) AS bags', false)
            ->where('remaining_bags >', 0)->where('deleted_at', null)
            ->where('created_at <', date('Y-m-d H:i:s', strtotime('-30 days')));
        $this->scope($osb, $companyId);
        $oldStock = (float) ($osb->get()->getRowArray()['bags'] ?? 0);

        // User activity: entries per user today.
        $uab = $this->db->table('inv_movements m')
            ->select('u.name, COUNT(*) AS entries', false)
            ->join('users u', 'u.id = m.created_by', 'left')
            ->where('m.deleted_at', null)->where('DATE(m.created_at)', $today)
            ->groupBy('m.created_by')->orderBy('entries', 'DESC')->limit(6);
        $this->scope($uab, $companyId, 'm.company_id');
        $userActivity = $uab->get()->getResultArray();

        return [
            'current_bags'        => round((float) ($stock['bags'] ?? 0), 2),
            'current_weight'      => round((float) ($stock['weight'] ?? 0), 2),
            'inventory_value'     => round((float) ($stock['value'] ?? 0), 2),
            'received_today'      => $daily['received_bags'],
            'dispatched_today'    => $daily['dispatched_bags'],
            'pending_corrections' => $daily['pending_corrections'],
            'stock_difference'    => round($netDiff, 2),
            'warehouse_util'      => $util,
            'warehouse_cap'       => $cap,
            'warehouse_used'      => $used,
            'old_stock'           => round($oldStock, 2),
            'user_activity'       => $userActivity,
            'product_count'       => (int) $this->countScoped('inv_products', $companyId),
            'warehouse_count'     => (int) $this->countScoped('inv_warehouses', $companyId),
        ];
    }

    private function countScoped(string $table, ?int $companyId): int
    {
        $b = $this->db->table($table)->where('deleted_at', null)->where('status', 1);
        $this->scope($b, $companyId);
        return $b->countAllResults();
    }

    // =================================================================
    // Task 8 — Report datasets (normalised: columns + rows + totals)
    // =================================================================

    private const MONEY = 1; // formatting hints handled in the views/exports

    /** Product-wise current inventory (bags, weight, value). */
    public function productWise(?int $companyId): array
    {
        $b = $this->db->table('inv_stock s')
            ->select('p.name AS product, SUM(s.bags) AS bags, SUM(s.weight) AS weight, COALESCE(p.rate,0) AS rate, SUM(s.bags*COALESCE(p.rate,0)) AS value', false)
            ->join('inv_products p', 'p.id = s.product_id', 'left')
            ->groupBy('s.product_id')->orderBy('p.name', 'ASC');
        $this->scope($b, $companyId, 's.company_id');
        $rows = $b->get()->getResultArray();

        $out = [];
        $tb = $tw = $tv = 0.0;
        foreach ($rows as $r) {
            $tb += (float) $r['bags']; $tw += (float) $r['weight']; $tv += (float) $r['value'];
            $out[] = [$r['product'] ?: '—', $this->n0($r['bags']), $this->n2($r['weight']), $this->n2($r['rate']), $this->n2($r['value'])];
        }
        return [
            'columns' => ['Product', 'Bags', 'Weight (kg)', 'Rate', 'Value'],
            'rows'    => $out,
            'totals'  => ['Total', $this->n0($tb), $this->n2($tw), '', $this->n2($tv)],
            'align'   => ['l', 'r', 'r', 'r', 'r'],
        ];
    }

    /** Warehouse-wise inventory with utilisation. */
    public function warehouseWise(?int $companyId): array
    {
        $b = $this->db->table('inv_warehouses w')
            ->select('w.name, w.location, w.capacity, COALESCE(SUM(s.bags),0) AS bags, COALESCE(SUM(s.weight),0) AS weight', false)
            ->join('inv_stock s', 's.warehouse_id = w.id', 'left')
            ->where('w.deleted_at', null)
            ->groupBy('w.id')->orderBy('w.name', 'ASC');
        $this->scope($b, $companyId, 'w.company_id');
        $rows = $b->get()->getResultArray();

        $out = []; $tb = $tw = 0.0;
        foreach ($rows as $r) {
            $cap = (int) $r['capacity'];
            $util = $cap > 0 ? round((float) $r['bags'] / $cap * 100, 1) . '%' : '—';
            $tb += (float) $r['bags']; $tw += (float) $r['weight'];
            $out[] = [$r['name'], $r['location'] ?: '—', $this->n0($r['bags']), $this->n2($r['weight']), $cap > 0 ? $this->n0($cap) : '∞', $util];
        }
        return [
            'columns' => ['Godown', 'Location', 'Bags', 'Weight (kg)', 'Capacity', 'Utilisation'],
            'rows'    => $out,
            'totals'  => ['Total', '', $this->n0($tb), $this->n2($tw), '', ''],
            'align'   => ['l', 'l', 'r', 'r', 'r', 'r'],
        ];
    }

    /** Party-wise received / dispatched totals. */
    public function partyWise(?int $companyId): array
    {
        $b = $this->db->table('inv_movements m')
            ->select("pt.name AS party, pt.type,
                SUM(CASE WHEN m.movement_type='inward' THEN m.bags ELSE 0 END) AS inb,
                SUM(CASE WHEN m.movement_type='outward' THEN m.bags ELSE 0 END) AS outb,
                COUNT(*) AS entries", false)
            ->join('inv_parties pt', 'pt.id = m.party_id', 'inner')
            ->where('m.deleted_at', null)
            ->groupBy('m.party_id')->orderBy('pt.name', 'ASC');
        $this->scope($b, $companyId, 'm.company_id');
        $rows = $b->get()->getResultArray();

        $out = []; $ti = $to = 0.0;
        foreach ($rows as $r) {
            $ti += (float) $r['inb']; $to += (float) $r['outb'];
            $out[] = [$r['party'], ucfirst((string) $r['type']), $this->n0($r['inb']), $this->n0($r['outb']), (string) $r['entries']];
        }
        return [
            'columns' => ['Party', 'Type', 'Received', 'Dispatched', 'Entries'],
            'rows'    => $out,
            'totals'  => ['Total', '', $this->n0($ti), $this->n0($to), ''],
            'align'   => ['l', 'l', 'r', 'r', 'r'],
        ];
    }

    /** Lot-wise inventory (opening vs remaining). */
    public function lotWise(?int $companyId): array
    {
        $b = $this->db->table('inv_lots l')
            ->select('l.lot_no, p.name AS product, w.name AS godown, l.opening_bags, l.remaining_bags, l.rack, l.created_at', false)
            ->join('inv_products p', 'p.id = l.product_id', 'left')
            ->join('inv_warehouses w', 'w.id = l.warehouse_id', 'left')
            ->where('l.deleted_at', null)
            ->orderBy('l.id', 'DESC')->limit(500);
        $this->scope($b, $companyId, 'l.company_id');
        $rows = $b->get()->getResultArray();

        $out = []; $to = $tr = 0.0;
        foreach ($rows as $r) {
            $to += (float) $r['opening_bags']; $tr += (float) $r['remaining_bags'];
            $out[] = [$r['lot_no'], $r['product'], $r['godown'], $this->n0($r['opening_bags']), $this->n0($r['remaining_bags']), $r['rack'] ?: '—', date('d-m-Y', strtotime($r['created_at']))];
        }
        return [
            'columns' => ['Lot No', 'Product', 'Godown', 'Opening', 'Remaining', 'Rack', 'Date'],
            'rows'    => $out,
            'totals'  => ['Total', '', '', $this->n0($to), $this->n0($tr), '', ''],
            'align'   => ['l', 'l', 'l', 'r', 'r', 'l', 'l'],
        ];
    }

    /** Daily inward / outward movement report for a date range. */
    public function dailyFlow(?int $companyId, string $type, string $from, string $to): array
    {
        $b = $this->db->table('inv_movements m')
            ->select('m.created_at, m.entry_no, p.name AS product, w.name AS godown, pt.name AS party, m.bags, m.weight, m.vehicle_no', false)
            ->join('inv_products p', 'p.id = m.product_id', 'left')
            ->join('inv_warehouses w', 'w.id = m.warehouse_id', 'left')
            ->join('inv_parties pt', 'pt.id = m.party_id', 'left')
            ->where('m.movement_type', $type)->where('m.deleted_at', null)
            ->where('DATE(m.created_at) >=', $from)->where('DATE(m.created_at) <=', $to)
            ->orderBy('m.created_at', 'ASC');
        $this->scope($b, $companyId, 'm.company_id');
        $rows = $b->get()->getResultArray();

        $out = []; $tb = $tw = 0.0;
        foreach ($rows as $r) {
            $tb += (float) $r['bags']; $tw += (float) $r['weight'];
            $line = [date('d-m-Y', strtotime($r['created_at'])), $r['entry_no'], $r['product'], $r['godown'], $r['party'] ?: '—', $this->n0($r['bags']), $this->n2($r['weight'])];
            if ($type === 'outward') {
                $line[] = $r['vehicle_no'] ?: '—';
            }
            $out[] = $line;
        }
        $cols   = ['Date', 'Entry No', 'Product', 'Godown', 'Party', 'Bags', 'Weight (kg)'];
        $totals = ['Total', '', '', '', '', $this->n0($tb), $this->n2($tw)];
        $align  = ['l', 'l', 'l', 'l', 'l', 'r', 'r'];
        if ($type === 'outward') {
            $cols[] = 'Vehicle'; $totals[] = ''; $align[] = 'l';
        }
        return ['columns' => $cols, 'rows' => $out, 'totals' => $totals, 'align' => $align];
    }

    /** Stock-difference report (all correction requests + outcome). */
    public function stockDifference(?int $companyId, string $from, string $to): array
    {
        $b = $this->db->table('inv_corrections c')
            ->select('c.created_at, p.name AS product, w.name AS godown, c.system_bags, c.physical_bags, c.difference, c.reason, c.status', false)
            ->join('inv_products p', 'p.id = c.product_id', 'left')
            ->join('inv_warehouses w', 'w.id = c.warehouse_id', 'left')
            ->where('c.deleted_at', null)
            ->where('DATE(c.created_at) >=', $from)->where('DATE(c.created_at) <=', $to)
            ->orderBy('c.id', 'DESC');
        $this->scope($b, $companyId, 'c.company_id');
        $rows = $b->get()->getResultArray();

        $reasons = InvCorrectionModel::REASONS;
        $out = []; $td = 0.0;
        foreach ($rows as $r) {
            $td += (float) $r['difference'];
            $out[] = [
                date('d-m-Y', strtotime($r['created_at'])), $r['product'], $r['godown'],
                $this->n0($r['system_bags']), $this->n0($r['physical_bags']),
                ((float) $r['difference'] > 0 ? '+' : '') . $this->n0($r['difference']),
                $reasons[$r['reason']] ?? ucfirst((string) $r['reason']), ucfirst((string) $r['status']),
            ];
        }
        return [
            'columns' => ['Date', 'Product', 'Godown', 'System', 'Physical', 'Difference', 'Reason', 'Status'],
            'rows'    => $out,
            'totals'  => ['Total', '', '', '', '', $this->n0($td), '', ''],
            'align'   => ['l', 'l', 'l', 'r', 'r', 'r', 'l', 'l'],
        ];
    }

    /** Pending-approval report (correction requests awaiting owner/admin). */
    public function pendingApproval(?int $companyId): array
    {
        $b = $this->db->table('inv_corrections c')
            ->select('c.created_at, p.name AS product, w.name AS godown, c.system_bags, c.physical_bags, c.difference, c.reason, u.name AS requester', false)
            ->join('inv_products p', 'p.id = c.product_id', 'left')
            ->join('inv_warehouses w', 'w.id = c.warehouse_id', 'left')
            ->join('users u', 'u.id = c.requested_by', 'left')
            ->where('c.deleted_at', null)->where('c.status', 'pending')
            ->orderBy('c.id', 'DESC');
        $this->scope($b, $companyId, 'c.company_id');
        $rows = $b->get()->getResultArray();

        $reasons = InvCorrectionModel::REASONS;
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                date('d-m-Y', strtotime($r['created_at'])), $r['product'], $r['godown'],
                $this->n0($r['system_bags']), $this->n0($r['physical_bags']),
                ((float) $r['difference'] > 0 ? '+' : '') . $this->n0($r['difference']),
                $reasons[$r['reason']] ?? ucfirst((string) $r['reason']), $r['requester'] ?: '—',
            ];
        }
        return [
            'columns' => ['Date', 'Product', 'Godown', 'System', 'Physical', 'Difference', 'Reason', 'Requested By'],
            'rows'    => $out,
            'align'   => ['l', 'l', 'l', 'r', 'r', 'r', 'l', 'l'],
        ];
    }

    /** Full movement ledger for a date range. */
    public function movement(?int $companyId, string $from, string $to): array
    {
        $b = $this->db->table('inv_movements m')
            ->select('m.created_at, m.entry_no, m.movement_type, m.direction, p.name AS product, w.name AS godown, pt.name AS party, m.bags, m.source', false)
            ->join('inv_products p', 'p.id = m.product_id', 'left')
            ->join('inv_warehouses w', 'w.id = m.warehouse_id', 'left')
            ->join('inv_parties pt', 'pt.id = m.party_id', 'left')
            ->where('m.deleted_at', null)
            ->where('DATE(m.created_at) >=', $from)->where('DATE(m.created_at) <=', $to)
            ->orderBy('m.created_at', 'ASC');
        $this->scope($b, $companyId, 'm.company_id');
        $rows = $b->get()->getResultArray();

        $out = []; $tin = $tout = 0.0;
        foreach ($rows as $r) {
            $in  = (int) $r['direction'] === 1 ? (float) $r['bags'] : 0.0;
            $out2 = (int) $r['direction'] === -1 ? (float) $r['bags'] : 0.0;
            $tin += $in; $tout += $out2;
            $out[] = [
                date('d-m-Y H:i', strtotime($r['created_at'])), $r['entry_no'], ucfirst((string) $r['movement_type']),
                $r['product'], $r['godown'], $r['party'] ?: '—',
                $in > 0 ? $this->n0($in) : '', $out2 > 0 ? $this->n0($out2) : '', ucfirst((string) $r['source']),
            ];
        }
        return [
            'columns' => ['Date', 'Entry No', 'Type', 'Product', 'Godown', 'Party', 'In', 'Out', 'Source'],
            'rows'    => $out,
            'totals'  => ['Total', '', '', '', '', '', $this->n0($tin), $this->n0($tout), ''],
            'align'   => ['l', 'l', 'l', 'l', 'l', 'l', 'r', 'r', 'l'],
        ];
    }

    private function n0($n): string
    {
        return number_format((float) $n, 0);
    }

    private function n2($n): string
    {
        return number_format((float) $n, 2);
    }
}
