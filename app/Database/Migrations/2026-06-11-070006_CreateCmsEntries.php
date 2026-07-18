<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsEntries extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'collection_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'author_id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'workflow_status'    => ['type' => 'ENUM', 'constraint' => ['draft', 'in_review', 'approved', 'published', 'archived'], 'default' => 'draft'],
            'published_at'       => ['type' => 'DATETIME', 'null' => true],
            'scheduled_at'       => ['type' => 'DATETIME', 'null' => true],
            'is_featured'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'view_count'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'sort_order'         => ['type' => 'INT', 'default' => 0],
            'wizard_extra'       => ['type' => 'JSON', 'null' => true],
            'sitemap_priority'   => ['type' => 'DECIMAL', 'constraint' => '2,1', 'null' => true],
            'sitemap_changefreq' => ['type' => 'ENUM', 'constraint' => ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'], 'null' => true],
            'is_in_sitemap'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['collection_id', 'workflow_status', 'deleted_at'], false, false, 'idx_entry_collection_status');
        $this->forge->addKey('author_id', false, false, 'idx_entry_author');
        $this->forge->addKey(['is_featured', 'workflow_status'], false, false, 'idx_entry_featured');
        $this->forge->addKey(['collection_id', 'sort_order'], false, false, 'idx_entry_sort');
        $this->forge->addForeignKey('collection_id', 'cms_collections', 'id', '', 'RESTRICT', 'fk_entry_collection');

        $this->forge->createTable('cms_entries', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'entry_id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'language_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'excerpt'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'featured_file_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'featured_image_url' => ['type' => 'VARCHAR', 'constraint' => 2048, 'null' => true],
            'meta_title'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta_description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'og_image_file_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'og_type'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => 'article'],
            'canonical_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'robots'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'schema_data'      => ['type' => 'JSON', 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addField('FULLTEXT KEY `ft_entry_search` (`title`, `excerpt`)');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['entry_id', 'language_id'], 'uk_entry_lang');
        $this->forge->addUniqueKey(['language_id', 'slug'], 'uk_entry_slug_lang');
        $this->forge->addKey('language_id', false, false, 'idx_entrytrans_lang');
        $this->forge->addForeignKey('entry_id', 'cms_entries', 'id', '', 'CASCADE', 'fk_entrytrans_entry');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_entrytrans_lang');

        $this->forge->createTable('cms_entry_translations', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'entry_id'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'version_number' => ['type' => 'INT'],
            'snapshot'       => ['type' => 'JSON'],
            'created_by'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'note'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['entry_id', 'version_number'], 'uk_entry_version');
        $this->forge->addKey('created_at', false, false, 'idx_entryversion_created');
        $this->forge->addForeignKey('entry_id', 'cms_entries', 'id', '', 'CASCADE', 'fk_entryversion_entry');

        $this->forge->createTable('cms_entry_versions', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_entry_versions', true);
        $this->forge->dropTable('cms_entry_translations', true);
        $this->forge->dropTable('cms_entries', true);
        $db->enableForeignKeyChecks();
    }
}
