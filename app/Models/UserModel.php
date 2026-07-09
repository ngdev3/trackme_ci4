<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'name', 'email', 'mobile', 'username', 'password', 'user_type_id',
        'profile_image', 'status', 'remember_token', 'last_login_at',
        'auth_provider', 'provider_id', 'avatar_url', 'account_type',
        'must_change_password', 'mobile_login_enabled', 'parent_id',
    ];

    protected $validationRules = [
        'name'     => 'required|min_length[2]|max_length[150]',
        'email'    => 'required|valid_email|max_length[191]|is_unique[users.email,id,{id}]',
        'username' => 'required|alpha_dash|min_length[3]|max_length[100]|is_unique[users.username,id,{id}]',
        'mobile'   => 'permit_empty|max_length[20]',
        'status'   => 'in_list[0,1]',
    ];

    protected $validationMessages = [
        'email'    => ['is_unique' => 'This email is already registered.'],
        'username' => ['is_unique' => 'This username is already taken.'],
    ];

    /**
     * Full user record with its type + role names, for listing.
     */
    public function withRelations()
    {
        return $this->select('users.*, user_types.name AS user_type_name,
                GROUP_CONCAT(DISTINCT roles.name ORDER BY roles.name SEPARATOR ", ") AS role_names')
            ->join('user_types', 'user_types.id = users.user_type_id', 'left')
            ->join('user_roles', 'user_roles.user_id = users.id', 'left')
            ->join('roles', 'roles.id = user_roles.role_id', 'left')
            ->groupBy('users.id');
    }

    public function findByLogin(string $login): ?array
    {
        return $this->groupStart()
            ->where('username', $login)
            ->orWhere('email', $login)
            ->groupEnd()
            ->first();
    }

    /**
     * Find an account previously linked to a social provider.
     */
    public function findByProvider(string $provider, string $providerId): ?array
    {
        return $this->where('auth_provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    /**
     * Derive a unique, DB-safe username from an email/name seed. Checks against
     * soft-deleted rows too, since the unique index spans them.
     */
    public function generateUniqueUsername(string $seed): string
    {
        $base = strtolower((string) preg_replace('/[^a-z0-9_]/i', '', explode('@', $seed)[0] ?? ''));
        if (strlen($base) < 3) {
            $base = 'user' . $base;
        }
        $base      = substr($base, 0, 90);
        $candidate = $base;
        $i         = 0;
        while ($this->withDeleted()->where('username', $candidate)->first()) {
            $i++;
            $candidate = substr($base, 0, 88 - strlen((string) $i)) . '_' . $i;
        }

        return $candidate;
    }

    /**
     * IDs of roles assigned to a user.
     *
     * @return list<int>
     */
    public function roleIds(int $userId): array
    {
        $rows = $this->db->table('user_roles')->select('role_id')->where('user_id', $userId)->get()->getResultArray();
        return array_map(static fn ($r) => (int) $r['role_id'], $rows);
    }

    /**
     * Replace a user's role assignments.
     *
     * @param list<int> $roleIds
     */
    public function syncRoles(int $userId, array $roleIds): void
    {
        $this->db->table('user_roles')->where('user_id', $userId)->delete();
        $rows = [];
        foreach (array_unique(array_filter(array_map('intval', $roleIds))) as $rid) {
            $rows[] = ['user_id' => $userId, 'role_id' => $rid];
        }
        if ($rows !== []) {
            $this->db->table('user_roles')->insertBatch($rows);
        }
    }

    public function countActive(): int
    {
        return $this->where('status', 1)->countAllResults();
    }

    /**
     * All user ids beneath $rootId in the control hierarchy (children,
     * grandchildren, …), not including $rootId itself. Walks parent_id
     * breadth-first with a visited guard so cycles can't loop forever.
     *
     * @return list<int>
     */
    public function descendantIds(int $rootId): array
    {
        $collected = [];
        $frontier  = [$rootId];
        $guard     = 0;
        while ($frontier !== [] && $guard++ < 100) {
            $rows = $this->db->table('users')
                ->select('id')
                ->whereIn('parent_id', $frontier)
                ->where('deleted_at', null)
                ->get()->getResultArray();
            $next = [];
            foreach ($rows as $r) {
                $id = (int) $r['id'];
                if (! in_array($id, $collected, true)) {
                    $collected[] = $id;
                    $next[]      = $id;
                }
            }
            $frontier = $next;
        }
        return $collected;
    }

    /**
     * Ids the given user is allowed to manage. Super admins get null (meaning
     * "no restriction — everyone"); everyone else gets their descendants plus
     * themselves.
     *
     * @return list<int>|null
     */
    public function manageableIds(int $userId, bool $isSuper): ?array
    {
        if ($isSuper) {
            return null;
        }
        return array_values(array_unique(array_merge([$userId], $this->descendantIds($userId))));
    }
}
