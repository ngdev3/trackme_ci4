<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 191],
            'mobile'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'username'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'password'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_type_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'profile_image'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'remember_token' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'last_login_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('username');
        $this->forge->addKey('user_type_id');
        $this->forge->createTable('users', true);
    }

    public function down()
    {
        $this->forge->dropTable('users', true);
    }
}
