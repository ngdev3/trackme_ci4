<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhanceLoginLogs extends Migration
{
    public function up()
    {
        $columns = [
            'login_at'          => ['type' => 'DATETIME', 'null' => true, 'after' => 'message'],
            'logout_at'         => ['type' => 'DATETIME', 'null' => true, 'after' => 'login_at'],
            'last_activity_at'  => ['type' => 'DATETIME', 'null' => true, 'after' => 'logout_at'],
            'session_duration'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'last_activity_at'],
            'browser'           => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'user_agent'],
            'operating_system'  => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'browser'],
            'device_type'       => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'operating_system'],
            'failure_reason'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'status'],
            'is_suspicious'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'failure_reason'],
            'suspicious_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'is_suspicious'],
        ];

        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, 'login_logs')) {
                $this->forge->addColumn('login_logs', [$name => $definition]);
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('login_logs')
            ->groupStart()
                ->where('login_at', null)
                ->orWhere('last_activity_at', null)
            ->groupEnd()
            ->update([
                'login_at'         => new \CodeIgniter\Database\RawSql('COALESCE(login_at, created_at)'),
                'last_activity_at' => new \CodeIgniter\Database\RawSql('COALESCE(last_activity_at, created_at, "' . $now . '")'),
            ]);
    }

    public function down()
    {
        foreach ([
            'login_at', 'logout_at', 'last_activity_at', 'session_duration',
            'browser', 'operating_system', 'device_type', 'failure_reason',
            'is_suspicious', 'suspicious_reason',
        ] as $column) {
            if ($this->db->fieldExists($column, 'login_logs')) {
                $this->forge->dropColumn('login_logs', $column);
            }
        }
    }
}
