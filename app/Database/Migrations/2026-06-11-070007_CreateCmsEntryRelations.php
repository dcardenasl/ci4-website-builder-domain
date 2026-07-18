<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsEntryRelations extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'entry_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'tag_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey(['entry_id', 'tag_id']);
        $this->forge->addKey('tag_id', false, false, 'idx_entrytags_tag');
        $this->forge->addForeignKey('entry_id', 'cms_entries', 'id', '', 'CASCADE', 'fk_entrytags_entry');
        $this->forge->addForeignKey('tag_id', 'cms_tags', 'id', '', 'CASCADE', 'fk_entrytags_tag');

        $this->forge->createTable('cms_entry_tags', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'entry_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'category_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'sort_order'  => ['type' => 'INT', 'default' => 0],
        ]);

        $this->forge->addPrimaryKey(['entry_id', 'category_id']);
        $this->forge->addKey('category_id', false, false, 'idx_entrycats_category');
        $this->forge->addForeignKey('entry_id', 'cms_entries', 'id', '', 'CASCADE', 'fk_entrycats_entry');
        $this->forge->addForeignKey('category_id', 'cms_categories', 'id', '', 'CASCADE', 'fk_entrycats_cat');

        $this->forge->createTable('cms_entry_categories', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'entry_id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'related_entry_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'relation_type'    => ['type' => 'ENUM', 'constraint' => ['related', 'recommended', 'prerequisite', 'sequel'], 'default' => 'related'],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addField('CONSTRAINT `chk_related_not_self` CHECK (`entry_id` <> `related_entry_id`)');

        $this->forge->addPrimaryKey(['entry_id', 'related_entry_id']);
        $this->forge->addKey('related_entry_id', false, false, 'idx_related_target');
        $this->forge->addForeignKey('entry_id', 'cms_entries', 'id', '', 'CASCADE', 'fk_related_entry');
        $this->forge->addForeignKey('related_entry_id', 'cms_entries', 'id', '', 'CASCADE', 'fk_related_target');

        $this->forge->createTable('cms_entry_related', false, ['ENGINE' => 'InnoDB']);

    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_entry_related', true);
        $this->forge->dropTable('cms_entry_categories', true);
        $this->forge->dropTable('cms_entry_tags', true);
        $db->enableForeignKeyChecks();
    }
}
