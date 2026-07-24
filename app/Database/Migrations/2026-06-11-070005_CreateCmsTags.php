<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsTags extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('is_active', false, false, 'idx_tag_active');

        $this->forge->createTable('cms_tags', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'tag_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'language_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tag_id', 'language_id'], 'uk_tag_lang');
        $this->forge->addUniqueKey(['language_id', 'slug'], 'uk_tag_slug_lang');
        $this->forge->addForeignKey('tag_id', 'cms_tags', 'id', '', 'CASCADE', 'fk_tagtrans_tag');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_tagtrans_lang');

        $this->forge->createTable('cms_tag_translations', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_tag_translations', true);
        $this->forge->dropTable('cms_tags', true);
        $db->enableForeignKeyChecks();
    }
}
