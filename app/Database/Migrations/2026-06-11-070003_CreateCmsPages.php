<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsPages extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'parent_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'collection_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'page_type'     => [
                'type' => 'ENUM',
                'constraint' => ['home', 'generic', 'contact', 'privacy', 'terms', '404', '500', 'maintenance', 'collection_index'],
                'default' => 'generic',
            ],
        ]);
        // Generated column — forge doesn't support GENERATED ALWAYS AS syntax
        $this->forge->addField(
            "`type_singleton` VARCHAR(20) GENERATED ALWAYS AS (" .
            "CASE WHEN `page_type` IN ('home','404','500','maintenance','contact','privacy','terms') " .
            "AND `deleted_at` IS NULL THEN `page_type` ELSE NULL END) STORED"
        );
        $this->forge->addField([
            'status'             => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'archived'], 'default' => 'draft'],
            'published_at'       => ['type' => 'DATETIME', 'null' => true],
            'scheduled_at'       => ['type' => 'DATETIME', 'null' => true],
            'sort_order'         => ['type' => 'INT', 'default' => 0],
            'sitemap_priority'   => ['type' => 'DECIMAL', 'constraint' => '2,1', 'null' => true],
            'sitemap_changefreq' => ['type' => 'ENUM', 'constraint' => ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'], 'null' => true, 'default' => 'monthly'],
            'is_in_sitemap'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('type_singleton', 'uk_page_type_singleton');
        $this->forge->addUniqueKey('collection_id', 'uk_page_collection_id');
        $this->forge->addKey(['parent_id', 'sort_order'], false, false, 'idx_page_parent_sort');
        $this->forge->addKey('collection_id', false, false, 'idx_page_collection_id');
        $this->forge->addKey(['status', 'deleted_at'], false, false, 'idx_page_status');
        $this->forge->addForeignKey('parent_id', 'cms_pages', 'id', '', 'CASCADE', 'fk_page_parent');
        $this->forge->addForeignKey('collection_id', 'cms_collections', 'id', '', 'CASCADE', 'fk_page_collection');

        $this->forge->createTable('cms_pages', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'page_id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'language_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'excerpt'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'meta_title'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta_description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'og_image_file_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'og_image_url'     => ['type' => 'VARCHAR', 'constraint' => 2048, 'null' => true],
            'og_type'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'canonical_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'robots'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'schema_data'      => ['type' => 'JSON', 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addField('FULLTEXT KEY `ft_page_search` (`title`, `excerpt`)');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['page_id', 'language_id'], 'uk_page_lang');
        $this->forge->addUniqueKey(['language_id', 'slug'], 'uk_page_slug_lang');
        $this->forge->addKey('language_id', false, false, 'idx_pagetrans_lang');
        $this->forge->addForeignKey('page_id', 'cms_pages', 'id', '', 'CASCADE', 'fk_pagetrans_page');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_pagetrans_lang');

        $this->forge->createTable('cms_page_translations', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'page_id'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'version_number' => ['type' => 'INT'],
            'snapshot'       => ['type' => 'JSON'],
            'created_by'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'note'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['page_id', 'version_number'], 'uk_page_version');
        $this->forge->addKey('created_at', false, false, 'idx_pageversion_created');
        $this->forge->addForeignKey('page_id', 'cms_pages', 'id', '', 'CASCADE', 'fk_pageversion_page');

        $this->forge->createTable('cms_page_versions', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_page_versions', true);
        $this->forge->dropTable('cms_page_translations', true);
        $this->forge->dropTable('cms_pages', true);
        $db->enableForeignKeyChecks();
    }
}
