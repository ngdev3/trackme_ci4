<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * RicemillModel — CI4 port of admin/models/Ricemill_mod. Public rice-mill website
 * inquiries (aa_ricemill_inquiry), soft-deleted (is_deleted), status workflow
 * New→Contacted→Converted/Rejected, with follow-up remarks.
 */
class RicemillModel
{
    private const TABLE = 'aa_ricemill_inquiry';
    public static array $STATUSES = ['New', 'Contacted', 'Converted', 'Rejected'];

    protected function db()
    {
        return Database::connect();
    }

    public function countInquiry(): int
    {
        return $this->db()->table(self::TABLE)->where('is_deleted', 0)->countAllResults();
    }

    public function getInquiry(string $status = ''): array
    {
        $b = $this->db()->table(self::TABLE)->where('is_deleted', 0);
        if ($status !== '' && in_array($status, self::$STATUSES, true)) {
            $b->where('status', $status);
        }
        return $b->orderBy('id', 'DESC')->get()->getResult();
    }

    public function statusCounts(): array
    {
        $out = ['All' => 0];
        foreach (self::$STATUSES as $s) { $out[$s] = 0; }
        $rows = $this->db()->table(self::TABLE)->select('status, COUNT(*) AS cnt')->where('is_deleted', 0)->groupBy('status')->get()->getResult();
        foreach ($rows as $r) {
            if (isset($out[$r->status])) { $out[$r->status] = (int) $r->cnt; }
            $out['All'] += (int) $r->cnt;
        }
        return $out;
    }

    public function view(int $id)
    {
        return $this->db()->table(self::TABLE)->where('id', $id)->get()->getRow();
    }

    public function updateStatus(int $id, string $status, ?int $adminId = null): bool
    {
        if (! in_array($status, self::$STATUSES, true)) { return false; }
        $this->db()->table(self::TABLE)->where('id', $id)->where('is_deleted', 0)->update([
            'status' => $status, 'handled_by' => $adminId, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function addRemark(int $id, string $remark, string $adminName = '', ?int $adminId = null): bool
    {
        $remark = trim($remark);
        if ($remark === '') { return false; }
        $row = $this->view($id);
        if (! $row) { return false; }
        $stamp = '[' . date('d-M-Y H:i') . ($adminName !== '' ? ' · ' . $adminName : '') . '] ';
        $existing = trim((string) $row->follow_up_remark);
        $combined = $existing === '' ? ($stamp . $remark) : ($existing . "\n" . $stamp . $remark);
        $this->db()->table(self::TABLE)->where('id', $id)->update([
            'follow_up_remark' => $combined, 'handled_by' => $adminId, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function softDelete(int $id, ?int $adminId = null): bool
    {
        $this->db()->table(self::TABLE)->where('id', $id)->update([
            'is_deleted' => 1, 'handled_by' => $adminId, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }
}
