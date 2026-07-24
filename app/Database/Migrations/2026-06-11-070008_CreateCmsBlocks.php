<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsBlocks extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'block_key'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'category'          => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'general'],
            'icon'              => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'schema_definition' => ['type' => 'JSON'],
            'supports_pages'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'supports_entries'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_container'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'        => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addField('FULLTEXT KEY `ft_cms_content_blocks_search` (`block_key`, `name`)');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('block_key', 'uk_block_key');
        $this->forge->addKey(['category', 'sort_order'], false, false, 'idx_block_category');

        $this->forge->createTable('cms_content_blocks', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'block_id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'owner_type'         => ['type' => 'ENUM', 'constraint' => ['page', 'entry']],
            'owner_id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'parent_instance_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'sort_order'         => ['type' => 'INT', 'default' => 0],
            'column_index'       => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'is_active'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'block_config'       => ['type' => 'JSON', 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['owner_type', 'owner_id', 'sort_order'], false, false, 'idx_blockinst_owner');
        $this->forge->addKey(['parent_instance_id', 'sort_order'], false, false, 'idx_blockinst_parent');
        $this->forge->addKey('block_id', false, false, 'idx_blockinst_block');
        $this->forge->addForeignKey('block_id', 'cms_content_blocks', 'id', '', 'RESTRICT', 'fk_blockinst_block');
        $this->forge->addForeignKey('parent_instance_id', 'cms_block_instances', 'id', '', 'CASCADE', 'fk_blockinst_parent');

        $this->forge->createTable('cms_block_instances', false, ['ENGINE' => 'InnoDB']);

        // Domain-owned registry of Hub file IDs referenced by CMS resources.
        // There is deliberately no FK to `files`: that table belongs to the Hub.
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'hub_file_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'resource_type'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'resource_id'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'block_instance_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'role'              => ['type' => 'VARCHAR', 'constraint' => 50],
            'label'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['resource_type', 'resource_id', 'role'], 'uk_cms_file_reference_resource_role');
        $this->forge->addKey('hub_file_id', false, false, 'idx_cms_file_reference_hub_file');
        $this->forge->addKey('block_instance_id', false, false, 'idx_cms_file_reference_block');
        $this->forge->addForeignKey('block_instance_id', 'cms_block_instances', 'id', '', 'CASCADE', 'fk_cms_file_reference_block');
        $this->forge->createTable('cms_file_references', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'instance_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'language_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'block_data'   => ['type' => 'JSON'],
            'is_published' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['instance_id', 'language_id'], 'uk_blocktrans_lang');
        $this->forge->addKey('language_id', false, false, 'idx_blocktrans_lang');
        $this->forge->addForeignKey('instance_id', 'cms_block_instances', 'id', '', 'CASCADE', 'fk_blocktrans_inst');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_blocktrans_lang');

        $this->forge->createTable('cms_block_instance_translations', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_block_instance_translations', true);
        $this->forge->dropTable('cms_file_references', true);
        $this->forge->dropTable('cms_block_instances', true);
        $this->forge->dropTable('cms_content_blocks', true);
        $db->enableForeignKeyChecks();
    }
}
