<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * ColdInventoryModel — CI4 port of Cold_inventory_mod. Pure read/analytics over
 * the Cold Lot System tables (cls_*); nothing is stored. Physical inventory is
 * DERIVED: packets IN = cls_lot.packets, packets OUT = cls_kisan_bill_lot
 * .delivered_packets on FINAL bills, BALANCE in store = IN - OUT. Scoped by the
 * current firm (template_id) via fy(). No schema change.
 */
class ColdInventoryModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Read the shared inventory filters from the query string. */
    private function filters(): array
    {
        $f = service('request')->getGet() ?? [];
        return [
            'as_on'    => ! empty($f['as_on']) ? date('Y-m-d', strtotime($f['as_on'])) : date('Y-m-d'),
            'from'     => ! empty($f['from_date']) ? date('Y-m-d', strtotime($f['from_date'])) : null,
            'to'       => ! empty($f['to_date']) ? date('Y-m-d', strtotime($f['to_date'])) : null,
            'variety'  => ! empty($f['variety']) ? (int) $f['variety'] : 0,
            'kisan_id' => ! empty($f['kisan_id']) ? (int) $f['kisan_id'] : 0,
        ];
    }

    public function current_filters(): array
    {
        return $this->filters();
    }

    /** Per-lot stock position as-on a date: inward, delivered and balance packets. */
    public function position_rows(bool $only_in_store = true): array
    {
        $db  = $this->db();
        $f   = $this->filters();
        $tid = (int) fy()->template_id;
        $asOn = $db->escape($f['as_on']);

        $b = $db->table('cls_lot l')
            ->select("c.kisan_id, k.alias_id AS kisan_alias, k.kisan_name,
                c.alias_id AS cls_alias, l.alias_id AS lot_alias, l.lot_number,
                COALESCE(NULLIF(l.variety_name,''),'Unspecified') AS variety_name, l.variety_id,
                l.inward_supply_date, e.employee_name,
                l.packets AS packets_in,
                COALESCE(d.delivered,0) AS delivered,
                (l.packets - COALESCE(d.delivered,0)) AS balance", false)
            ->join('cls_cold_lot c', 'c.id = l.cold_lot_id', 'inner')
            ->join('cls_kisan k', 'k.id = c.kisan_id', 'left')
            ->join('cls_employee e', 'e.id = c.received_by_emp_id', 'left')
            ->join(
                "(SELECT bl.lot_id, SUM(bl.delivered_packets) AS delivered
                    FROM cls_kisan_bill_lot bl
                    JOIN cls_kisan_bill bb ON bb.id = bl.bill_id
                   WHERE bb.status = 'Final' AND bb.template_id = " . $tid . "
                     AND bb.bill_date <= " . $asOn . "
                   GROUP BY bl.lot_id) d",
                'd.lot_id = l.id', 'left', false
            )
            ->where('c.template_id', $tid)
            ->where('c.status !=', 'Delete')->where('l.status !=', 'Delete')
            ->where("(l.inward_supply_date IS NULL OR l.inward_supply_date <= " . $asOn . ")", null, false);
        if ($f['variety'])  { $b->where('l.variety_id', $f['variety']); }
        if ($f['kisan_id']) { $b->where('c.kisan_id', $f['kisan_id']); }
        $b->orderBy('variety_name', 'asc')->orderBy('k.alias_id', 'asc')->orderBy('l.alias_id', 'asc');

        $rows = $b->get()->getResult();
        if ($only_in_store) {
            $rows = array_values(array_filter($rows, fn($r) => (int) $r->balance > 0));
        }
        return $rows;
    }

    /** Aggregate the per-lot position by a key (variety_name / kisan). */
    public function position_grouped(string $by): array
    {
        $rows = $this->position_rows(true);
        $groups = [];
        foreach ($rows as $r) {
            if ($by === 'kisan') {
                $key   = $r->kisan_id;
                $label = trim($r->kisan_alias . ' — ' . $r->kisan_name);
            } else {
                $key   = $r->variety_name;
                $label = $r->variety_name;
            }
            if (! isset($groups[$key])) {
                $groups[$key] = (object) ['label' => $label, 'lots' => 0, 'in' => 0, 'delivered' => 0, 'balance' => 0];
            }
            $groups[$key]->lots++;
            $groups[$key]->in        += (int) $r->packets_in;
            $groups[$key]->delivered += (int) $r->delivered;
            $groups[$key]->balance   += (int) $r->balance;
        }
        uasort($groups, fn($a, $b) => strcasecmp($a->label, $b->label));
        return array_values($groups);
    }

    /** Headline KPIs for the overview dashboard. */
    public function overview_kpis()
    {
        $rows = $this->position_rows(false);
        $k = ['in' => 0, 'delivered' => 0, 'balance' => 0, 'lots_in_store' => 0, 'varieties' => [], 'kisans' => [], 'coldlots' => []];
        foreach ($rows as $r) {
            $k['in']        += (int) $r->packets_in;
            $k['delivered'] += (int) $r->delivered;
            $bal = (int) $r->balance;
            $k['balance']   += $bal;
            if ($bal > 0) {
                $k['lots_in_store']++;
                $k['varieties'][$r->variety_name] = true;
                $k['kisans'][$r->kisan_id] = true;
                $k['coldlots'][$r->cls_alias] = true;
            }
        }
        return (object) [
            'in' => $k['in'], 'delivered' => $k['delivered'], 'balance' => $k['balance'],
            'lots_in_store' => $k['lots_in_store'], 'varieties' => count($k['varieties']),
            'kisans' => count($k['kisans']), 'coldlots' => count($k['coldlots']),
        ];
    }

    /** Stock movement register: inward (lot) + outward (bill delivery) events, date-ordered. */
    public function movements(): array
    {
        $db  = $this->db();
        $f   = $this->filters();
        $tid = (int) fy()->template_id;
        $events = [];

        $b = $db->table('cls_lot l')
            ->select("l.inward_supply_date AS dt, l.alias_id AS ref,
                k.alias_id AS kisan_alias, k.kisan_name,
                COALESCE(NULLIF(l.variety_name,''),'Unspecified') AS variety_name, l.packets AS qty", false)
            ->join('cls_cold_lot c', 'c.id = l.cold_lot_id', 'inner')
            ->join('cls_kisan k', 'k.id = c.kisan_id', 'left')
            ->where('c.template_id', $tid)->where('c.status !=', 'Delete')->where('l.status !=', 'Delete');
        if ($f['from']) { $b->where('l.inward_supply_date >=', $f['from']); }
        if ($f['to'])   { $b->where('l.inward_supply_date <=', $f['to']); }
        if ($f['variety'])  { $b->where('l.variety_id', $f['variety']); }
        if ($f['kisan_id']) { $b->where('c.kisan_id', $f['kisan_id']); }
        foreach ($b->get()->getResult() as $r) {
            $events[] = (object) ['dt' => $r->dt, 'type' => 'IN', 'ref' => $r->ref,
                'kisan' => trim($r->kisan_alias . ' — ' . $r->kisan_name), 'variety' => $r->variety_name,
                'in' => (int) $r->qty, 'out' => 0];
        }

        if ($db->tableExists('cls_kisan_bill_lot')) {
            $b = $db->table('cls_kisan_bill_lot bl')
                ->select("bb.bill_date AS dt, bb.alias_id AS ref,
                    k.alias_id AS kisan_alias, k.kisan_name,
                    COALESCE(NULLIF(bl.variety_name,''),'Unspecified') AS variety_name, bl.delivered_packets AS qty", false)
                ->join('cls_kisan_bill bb', 'bb.id = bl.bill_id', 'inner')
                ->join('cls_kisan k', 'k.id = bb.kisan_id', 'left')
                ->where('bb.template_id', $tid)->where('bb.status', 'Final')->where('bl.delivered_packets >', 0);
            if ($f['from']) { $b->where('bb.bill_date >=', $f['from']); }
            if ($f['to'])   { $b->where('bb.bill_date <=', $f['to']); }
            if ($f['variety'])  { $b->where('bl.variety_name IS NOT NULL', null, false); }
            if ($f['kisan_id']) { $b->where('bb.kisan_id', $f['kisan_id']); }
            foreach ($b->get()->getResult() as $r) {
                $events[] = (object) ['dt' => $r->dt, 'type' => 'OUT', 'ref' => $r->ref,
                    'kisan' => trim($r->kisan_alias . ' — ' . $r->kisan_name), 'variety' => $r->variety_name,
                    'in' => 0, 'out' => (int) $r->qty];
            }
        }

        usort($events, function ($a, $b) {
            $da = $a->dt ?: '9999-12-31'; $db = $b->dt ?: '9999-12-31';
            if ($da !== $db) { return strcmp($da, $db); }
            return ($a->type === $b->type) ? 0 : ($a->type === 'IN' ? -1 : 1);
        });
        return $events;
    }

    /** Variety dropdown (only varieties that appear in cold-store lots). */
    public function varieties(): array
    {
        $tid = (int) fy()->template_id;
        return $this->db()->table('cls_lot l')->distinct()
            ->select('l.variety_id AS id, l.variety_name AS name')
            ->join('cls_cold_lot c', 'c.id = l.cold_lot_id', 'inner')
            ->where('c.template_id', $tid)->where('l.status !=', 'Delete')
            ->where('l.variety_id >', 0)->where('l.variety_name !=', '')
            ->orderBy('l.variety_name', 'asc')->get()->getResult();
    }

    /** Kisan dropdown (kisans that have cold-store lots). */
    public function kisan_dropdown(): array
    {
        $tid = (int) fy()->template_id;
        return $this->db()->table('cls_kisan k')->distinct()
            ->select('k.id, k.alias_id, k.kisan_name')
            ->join('cls_cold_lot c', 'c.kisan_id = k.id', 'inner')
            ->where('c.template_id', $tid)->where('c.status !=', 'Delete')
            ->orderBy('k.alias_id', 'asc')->get()->getResult();
    }
}
