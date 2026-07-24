<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsLanguages extends Migration
{
    public function up(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        if ($db->tableExists('cms_languages')) {
            return;
        }

        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'code'                 => ['type' => 'VARCHAR', 'constraint' => 10],
            'name'                 => ['type' => 'VARCHAR', 'constraint' => 50],
            'native_name'          => ['type' => 'VARCHAR', 'constraint' => 50],
            'is_default'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'fallback_language_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'sort_order'           => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code', 'uk_lang_code');
        $this->forge->addKey(['is_active', 'sort_order'], false, false, 'idx_lang_active_sort');
        $this->forge->addForeignKey('fallback_language_id', 'cms_languages', 'id', '', 'SET NULL', 'fk_lang_fallback');

        $this->forge->createTable('cms_languages', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        if (! $db->tableExists('cms_languages')) {
            return;
        }

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_languages', true);
        $db->enableForeignKeyChecks();
    }
}
