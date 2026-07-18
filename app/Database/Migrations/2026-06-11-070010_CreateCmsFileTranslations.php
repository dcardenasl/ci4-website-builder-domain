<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsFileTranslations extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'file_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'language_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'alt_text'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'caption'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'credit'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['file_id', 'language_id'], 'uk_file_lang');
        $this->forge->addKey('language_id', false, false, 'idx_filetrans_lang');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_filetrans_lang');

        $this->forge->createTable('cms_file_translations', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_file_translations', true);
        $db->enableForeignKeyChecks();
    }
}
