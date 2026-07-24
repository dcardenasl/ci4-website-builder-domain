<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsRedirects extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'old_path'      => ['type' => 'VARCHAR', 'constraint' => 500],
            'new_url'       => ['type' => 'VARCHAR', 'constraint' => 500],
            'redirect_type' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 301],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'hit_count'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'last_hit_at'   => ['type' => 'DATETIME', 'null' => true],
            'note'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('old_path', 'uk_redirect_old_path');
        $this->forge->addKey('is_active', false, false, 'idx_redirect_active');

        $this->forge->createTable('cms_redirects', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'entity_type'   => ['type' => 'ENUM', 'constraint' => ['page', 'entry', 'category', 'tag', 'collection']],
            'entity_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'language_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'old_slug'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'old_full_path' => ['type' => 'VARCHAR', 'constraint' => 500],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['language_id', 'old_full_path'], false, false, 'idx_slugredir_lookup');
        $this->forge->addKey(['entity_type', 'entity_id'], false, false, 'idx_slugredir_entity');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_slugredir_lang');

        $this->forge->createTable('cms_slug_redirects', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_slug_redirects', true);
        $this->forge->dropTable('cms_redirects', true);
        $db->enableForeignKeyChecks();
    }
}
