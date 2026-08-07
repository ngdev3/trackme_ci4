<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Registry of the mobile-app REST endpoints (api/v1/*). Rows are synced from the
 * route collection; the Super Admin can health-check each one (is it alive?) and
 * toggle it active/inactive (inactive → the API returns 503 to the app).
 */
class CreateApiEndpoints extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'http_method' => ['type' => 'VARCHAR', 'constraint' => 10],
            'path'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'handler'     => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'grp'         => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'auth'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'bearer'], // bearer|public
            'params'      => ['type' => 'TEXT', 'null' => true],   // JSON {path:[],query:[],body:[]}
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'http_status' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'health'      => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true], // online|down|error|missing
            'response_ms' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'last_checked'=> ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['http_method', 'path']);
        $this->forge->createTable('api_endpoints', true);
    }

    public function down()
    {
        $this->forge->dropTable('api_endpoints', true);
    }
}
