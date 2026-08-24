<?php

namespace App\Models;

use CodeIgniter\Model;

class PartyModel extends Model
{
    protected $table         = 'parties';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields = [
        'company_id', 'name', 'party_type', 'party_role', 'mobile', 'email', 'address',
        'gst_number', 'opening_balance', 'opening_type', 'notes',
    ];

    /** Selectable party roles (value => label). */
    public const ROLES = ['customer' => 'Customer', 'supplier' => 'Supplier', 'both' => 'Both'];

    /** The master record for one party in a company, or null. */
    public function forName(int $companyId, string $name): ?array
    {
        return $this->where('company_id', $companyId)->where('name', trim($name))->first();
    }

    /** Master records for a company keyed by party name (for merging into lists). */
    public function mapForCompany(int $companyId): array
    {
        $out = [];
        foreach ($this->where('company_id', $companyId)->findAll() as $r) {
            $out[$r['name']] = $r;
        }
        return $out;
    }

    /**
     * Create or update a party's master details. If the party is renamed, the
     * old master row is moved to the new name (merging into an existing one).
     */
    public function save_details(int $companyId, string $oldName, array $data): void
    {
        $newName = trim((string) ($data['name'] ?? $oldName));
        $data['company_id'] = $companyId;
        $data['name']       = $newName;

        // Find an existing master row for the old or the new name.
        $existing = $this->forName($companyId, $oldName);
        if (! $existing && $newName !== $oldName) {
            $existing = $this->forName($companyId, $newName);
        }
        if ($existing) {
            $this->update((int) $existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }
}
