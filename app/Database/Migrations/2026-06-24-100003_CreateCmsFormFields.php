<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsFormFields extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'form_id' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null'     => false,
            ],
            'field_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'field_type' => [
                'type'       => 'ENUM',
                'constraint' => ['text', 'email', 'phone', 'textarea', 'select', 'radio', 'checkbox', 'date', 'number', 'url'],
                'null'       => false,
                'default'    => 'text',
            ],
            'options' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'display_order' => [
                'type'     => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'default'  => 0,
                'null'     => false,
            ],
            'is_required' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null'    => false,
            ],
            'is_active' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null'    => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['form_id', 'field_key']);
        $this->forge->addKey('form_id');
        $this->forge->addForeignKey('form_id', 'cms_forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cms_form_fields', true);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_form_fields', true);
        $db->enableForeignKeyChecks();
    }
}
