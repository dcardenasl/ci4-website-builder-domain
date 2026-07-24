<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsFormFieldTranslations extends Migration
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
            'form_field_id' => [
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
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'placeholder' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'help_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'option_labels' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'error_required' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'error_invalid' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['form_field_id', 'language_id']);
        $this->forge->addForeignKey('form_field_id', 'cms_form_fields', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cms_form_field_translations', true);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_form_field_translations', true);
        $db->enableForeignKeyChecks();
    }
}
