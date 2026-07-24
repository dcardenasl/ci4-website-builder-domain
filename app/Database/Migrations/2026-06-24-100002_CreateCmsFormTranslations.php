<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsFormTranslations extends Migration
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
            'language_id' => [
                'type'     => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null'     => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'submit_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Enviar',
                'null'       => false,
            ],
            'success_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['form_id', 'language_id']);
        $this->forge->addForeignKey('form_id', 'cms_forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cms_form_translations', true);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_form_translations', true);
        $db->enableForeignKeyChecks();
    }
}
