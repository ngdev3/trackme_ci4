<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds geolocation columns to login_logs so the super-admin login-logs view can
 * show where a user signed in from. Populated two ways: precise device GPS
 * (source='gps', sent by the mobile app once the user grants location access)
 * or a coarse IP lookup (source='ip') resolved server-side at login time.
 */
class AddLocationToLoginLogs extends Migration
{
    public function up()
    {
        $columns = [
            'latitude'          => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true, 'after' => 'device_type'],
            'longitude'         => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true, 'after' => 'latitude'],
            'location_accuracy' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'longitude'],
            'location_source'   => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'location_accuracy'], // gps|ip
            'location_label'    => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true, 'after' => 'location_source'],
        ];

        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, 'login_logs')) {
                $this->forge->addColumn('login_logs', [$name => $definition]);
            }
        }
    }

    public function down()
    {
        foreach (['latitude', 'longitude', 'location_accuracy', 'location_source', 'location_label'] as $column) {
            if ($this->db->fieldExists($column, 'login_logs')) {
                $this->forge->dropColumn('login_logs', $column);
            }
        }
    }
}
