<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsCollections extends Migration
{
    public function up(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        if (! $db->tableExists('cms_collections')) {
            $this->forge->addField([
                'id'                       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'collection_key'           => ['type' => 'VARCHAR', 'constraint' => 50],
                'collection_type'          => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'other'],
                'is_active'                => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'requires_approval'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'enables_categories'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'enables_tags'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'default_sitemap_priority' => ['type' => 'DECIMAL', 'constraint' => '2,1', 'null' => true, 'default' => 0.6],
                'default_changefreq'       => ['type' => 'ENUM', 'constraint' => ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'], 'null' => true, 'default' => 'weekly'],
                'block_template'           => ['type' => 'JSON', 'null' => true],
                'wizard_config'            => ['type' => 'JSON', 'null' => true],
                'sort_order'               => ['type' => 'INT', 'default' => 0],
            ]);
            $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
            $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey('collection_key', 'uk_collection_key');

            $this->forge->createTable('cms_collections', false, ['ENGINE' => 'InnoDB']);
        }

        if (! $db->tableExists('cms_collection_translations')) {
            $this->forge->addField([
                'id'                       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'collection_id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
                'language_id'              => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
                'slug'                     => ['type' => 'VARCHAR', 'constraint' => 150],
                'name'                     => ['type' => 'VARCHAR', 'constraint' => 150],
                'description'              => ['type' => 'TEXT', 'null' => true],
                'listing_title'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'listing_intro'            => ['type' => 'TEXT', 'null' => true],
                'default_meta_title'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'default_meta_description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'entry_cta_label'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            ]);

            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['collection_id', 'language_id'], 'uk_collection_lang');
            $this->forge->addUniqueKey(['language_id', 'slug'], 'uk_collection_slug_lang');
            $this->forge->addKey('language_id', false, false, 'idx_colltrans_lang');
            $this->forge->addForeignKey('collection_id', 'cms_collections', 'id', '', 'CASCADE', 'fk_colltrans_collection');
            $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_colltrans_lang');

            $this->forge->createTable('cms_collection_translations', false, ['ENGINE' => 'InnoDB']);
        }
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        if ($db->tableExists('cms_collection_translations')) {
            $this->forge->dropTable('cms_collection_translations', true);
        }
        if ($db->tableExists('cms_collections')) {
            $this->forge->dropTable('cms_collections', true);
        }
        $db->enableForeignKeyChecks();
    }
}
