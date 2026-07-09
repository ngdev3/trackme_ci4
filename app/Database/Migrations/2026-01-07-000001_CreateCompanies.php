<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Companies / firms. Each row is an isolated tenant owned by the user that
 * created it. Company-scoped data (accounting groups, settings, and future
 * modules) references company_id so one company's data never mixes with
 * another's.
 */
class CreateCompanies extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'owner_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 191],
            'financial_year_from'   => ['type' => 'DATE'],
            'books_beginning_from'  => ['type' => 'DATE'],
            'state'                 => ['type' => 'VARCHAR', 'constraint' => 100],
            'country'               => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'India'],
            'gst_registration_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'gst_number'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'address'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'mobile'                => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'                 => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'business_type'         => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'status'                => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('owner_id');
        $this->forge->createTable('companies', true);
    }

    public function down()
    {
        $this->forge->dropTable('companies', true);
    }
}
