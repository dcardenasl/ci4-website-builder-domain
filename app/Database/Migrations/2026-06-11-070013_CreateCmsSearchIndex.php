<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsSearchIndex extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'entity_type'   => ['type' => 'ENUM', 'constraint' => ['page', 'entry']],
            'entity_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'language_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'url'           => ['type' => 'VARCHAR', 'constraint' => 500],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 500],
            'excerpt'       => ['type' => 'TEXT', 'null' => true],
            'body'          => ['type' => 'LONGTEXT', 'null' => true],
            'collection_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'category_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'tags_csv'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_published'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'published_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addField('`reindexed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('FULLTEXT KEY `ft_search_content` (`title`, `excerpt`, `body`)');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['entity_type', 'entity_id', 'language_id'], 'uk_search_entity');
        $this->forge->addKey(['language_id', 'is_published', 'published_at'], false, false, 'idx_search_lang_pub');
        $this->forge->addKey('collection_id', false, false, 'idx_search_collection');

        $this->forge->createTable('cms_search_index', false, ['ENGINE' => 'InnoDB']);

    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_search_index', true);
        $db->enableForeignKeyChecks();
    }
}
