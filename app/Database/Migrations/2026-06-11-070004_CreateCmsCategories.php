<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsCategories extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'collection_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'parent_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'sort_order'    => ['type' => 'INT', 'default' => 0],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['collection_id', 'is_active', 'sort_order'], false, false, 'idx_cat_collection');
        $this->forge->addKey('parent_id', false, false, 'idx_cat_parent');
        $this->forge->addForeignKey('collection_id', 'cms_collections', 'id', '', 'CASCADE', 'fk_cat_collection');
        $this->forge->addForeignKey('parent_id', 'cms_categories', 'id', '', 'CASCADE', 'fk_cat_parent');

        $this->forge->createTable('cms_categories', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'category_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'language_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'      => ['type' => 'TEXT', 'null' => true],
            'meta_title'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta_description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['category_id', 'language_id'], 'uk_cat_lang');
        $this->forge->addKey(['language_id', 'slug'], false, false, 'idx_cattrans_slug');
        $this->forge->addForeignKey('category_id', 'cms_categories', 'id', '', 'CASCADE', 'fk_cattrans_cat');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_cattrans_lang');

        $this->forge->createTable('cms_category_translations', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_category_translations', true);
        $this->forge->dropTable('cms_categories', true);
        $db->enableForeignKeyChecks();
    }
}
