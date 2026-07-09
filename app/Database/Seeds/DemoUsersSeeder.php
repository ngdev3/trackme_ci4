<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Inserts 1,000 demo users so the Users page (table design, charts, AJAX
 * sort/paginate, load) can be tested with realistic volume. Idempotent —
 * re-running first removes any previously seeded demo users.
 *
 *   php spark db:seed DemoUsersSeeder
 *
 * Remove them later with:  DELETE FROM users WHERE username LIKE 'demo\_%';
 */
class DemoUsersSeeder extends Seeder
{
    public function run()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        // Clean any prior demo data (and its role links) so re-runs stay clean.
        $prior = $db->table('users')->select('id')->like('username', 'demo_', 'after')->get()->getResultArray();
        if ($prior !== []) {
            $ids = array_column($prior, 'id');
            $db->table('user_roles')->whereIn('user_id', $ids)->delete();
            $db->table('users')->whereIn('id', $ids)->delete();
        }

        $typeIds = array_column($db->table('user_types')->where('deleted_at', null)->get()->getResultArray(), 'id');
        $roleIds = array_column($db->table('roles')->where('is_superadmin', 0)->where('deleted_at', null)->get()->getResultArray(), 'id');
        $pass    = password_hash('Test@123', PASSWORD_DEFAULT);

        $first = ['Aarav', 'Vivaan', 'Aditya', 'Vihaan', 'Arjun', 'Sai', 'Reyansh', 'Krishna', 'Ishaan', 'Rohan',
                  'Ananya', 'Diya', 'Aadhya', 'Saanvi', 'Myra', 'Anika', 'Aarohi', 'Riya', 'Ira', 'Priya',
                  'John', 'Emma', 'Liam', 'Olivia', 'Noah', 'Ava', 'James', 'Sophia', 'Ben', 'Mia',
                  'Rahul', 'Neha', 'Amit', 'Pooja', 'Vikram', 'Sneha', 'Karan', 'Divya', 'Manish', 'Kavya'];
        $last  = ['Sharma', 'Verma', 'Gupta', 'Patel', 'Singh', 'Kumar', 'Rao', 'Nair', 'Reddy', 'Iyer',
                  'Shah', 'Mehta', 'Jain', 'Bose', 'Das', 'Khan', 'Ali', 'Smith', 'Brown', 'Jones',
                  'Taylor', 'Wilson', 'Davis', 'Lee', 'Clark', 'Lewis', 'Walker', 'Hall', 'Young', 'King'];

        $users = [];
        for ($i = 1; $i <= 1000; $i++) {
            $fn = $first[array_rand($first)];
            $ln = $last[array_rand($last)];
            $users[] = [
                'name'          => "{$fn} {$ln}",
                'email'         => "demo{$i}." . strtolower($fn) . '@example.com',
                'username'      => 'demo_' . strtolower($fn . $ln) . $i,
                'password'      => $pass,
                'mobile'        => '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                'user_type_id'  => $typeIds ? $typeIds[array_rand($typeIds)] : null,
                'account_type'  => 'super_admin',
                'auth_provider' => 'local',
                'status'        => random_int(1, 10) <= 8 ? 1 : 0, // ~80% active
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        foreach (array_chunk($users, 250) as $chunk) {
            $db->table('users')->insertBatch($chunk);
        }

        // Give each demo user a random (non-super) role.
        if ($roleIds !== []) {
            $demo = $db->table('users')->select('id')->like('username', 'demo_', 'after')->get()->getResultArray();
            $links = [];
            foreach ($demo as $d) {
                $links[] = ['user_id' => (int) $d['id'], 'role_id' => $roleIds[array_rand($roleIds)]];
            }
            foreach (array_chunk($links, 400) as $chunk) {
                $db->table('user_roles')->insertBatch($chunk);
            }
        }
    }
}
