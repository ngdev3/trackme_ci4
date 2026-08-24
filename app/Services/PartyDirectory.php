<?php

namespace App\Services;

use App\Models\PartyModel;
use App\Models\TransactionModel;

/**
 * Party accounts directory — merges the transaction-derived party aggregates
 * (count, Jama, Naam, net, last date) with each party's editable master details
 * (contact, GST, opening balance) and computes a balance-sheet balance. Shared by
 * the web Party Accounts screen and the mobile API so both behave identically.
 */
class PartyDirectory
{
    /** Editable party rows for a company, aggregates + master + current balance. */
    public function list(int $companyId, string $q = ''): array
    {
        $rows   = (new TransactionModel())->partyAccounts($companyId, $q);
        $master = (new PartyModel())->mapForCompany($companyId);

        foreach ($rows as &$r) {
            $r = $this->merge($r, $master[$r['name']] ?? []);
        }
        unset($r);

        // Parties that have a master record but no transactions yet (rare, but
        // an opening-balance-only party belongs on the list too).
        $names = array_column($rows, 'name');
        foreach ($master as $name => $m) {
            if (in_array($name, $names, true)) {
                continue;
            }
            if ($q !== '' && stripos($name, $q) === false) {
                continue;
            }
            $rows[] = $this->merge([
                'name' => $name, 'party_type' => $m['party_type'] ?? '',
                'count' => 0, 'jama' => 0.0, 'naam' => 0.0, 'net' => 0.0, 'last_date' => null,
            ], $m);
        }

        usort($rows, static fn ($a, $b) => strcasecmp((string) $a['name'], (string) $b['name']));
        return $rows;
    }

    /** Save a party's rename/re-type (across transactions) + master details. */
    public function save(int $companyId, array $post): array
    {
        $old  = trim((string) ($post['old_name'] ?? ''));
        $new  = trim((string) ($post['new_name'] ?? ''));
        $type = trim((string) ($post['party_type'] ?? ''));

        $res = (new TransactionModel())->renameParty($companyId, $old, $new, $type !== '' ? $type : null);

        $role = trim((string) ($post['party_role'] ?? ''));
        (new PartyModel())->save_details($companyId, $old, [
            'name'            => $new !== '' ? $new : $old,
            'party_type'      => $type !== '' ? $type : null,
            'party_role'      => in_array($role, ['customer', 'supplier', 'both'], true) ? $role : null,
            'mobile'          => $this->clean($post['mobile'] ?? ''),
            'email'           => $this->clean($post['email'] ?? ''),
            'address'         => $this->clean($post['address'] ?? ''),
            'gst_number'      => $this->clean($post['gst_number'] ?? ''),
            'opening_balance' => (float) ($post['opening_balance'] ?? 0),
            'opening_type'    => in_array(($post['opening_type'] ?? 'dr'), ['dr', 'cr'], true) ? $post['opening_type'] : 'dr',
            'notes'           => $this->clean($post['notes'] ?? ''),
        ]);

        return $res;
    }

    private function merge(array $r, array $m): array
    {
        $r['party_role']      = $m['party_role'] ?? '';
        $r['mobile']          = $m['mobile'] ?? '';
        $r['email']           = $m['email'] ?? '';
        $r['address']         = $m['address'] ?? '';
        $r['gst_number']      = $m['gst_number'] ?? '';
        $r['opening_balance'] = (float) ($m['opening_balance'] ?? 0);
        $r['opening_type']    = $m['opening_type'] ?? 'dr';
        $r['notes']           = $m['notes'] ?? '';
        if (! empty($m['party_type']) && empty($r['party_type'])) {
            $r['party_type'] = $m['party_type'];
        }
        $openSigned  = ($r['opening_type'] === 'cr' ? -1 : 1) * $r['opening_balance'];
        $r['balance'] = round($openSigned + (float) $r['net'], 2);
        return $r;
    }

    private function clean($v): ?string
    {
        $v = trim((string) $v);
        return $v !== '' ? $v : null;
    }
}
