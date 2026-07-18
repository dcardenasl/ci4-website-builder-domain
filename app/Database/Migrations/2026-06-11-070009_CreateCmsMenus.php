<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsMenus extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'menu_key'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'location'   => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'header'],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('menu_key', 'uk_menu_key');
        $this->forge->addKey(['location', 'is_active'], false, false, 'idx_menu_location');

        $this->forge->createTable('cms_menus', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'menu_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'language_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['menu_id', 'language_id'], 'uk_menu_lang');
        $this->forge->addForeignKey('menu_id', 'cms_menus', 'id', '', 'CASCADE', 'fk_menutrans_menu');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_menutrans_lang');

        $this->forge->createTable('cms_menu_translations', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'menu_id'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'parent_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'link_type'     => ['type' => 'ENUM', 'constraint' => ['page', 'entry', 'collection_listing', 'custom_url', 'no_link']],
            'page_id'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'entry_id'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'collection_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'link_target'   => ['type' => 'ENUM', 'constraint' => ['_self', '_blank'], 'default' => '_self'],
            'icon'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'css_class'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'sort_order'    => ['type' => 'INT', 'default' => 0],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addField(
            "CONSTRAINT `chk_menuitem_link` CHECK (
                (`link_type` = 'page' AND `page_id` IS NOT NULL AND `entry_id` IS NULL AND `collection_id` IS NULL)
                OR (`link_type` = 'entry' AND `entry_id` IS NOT NULL AND `page_id` IS NULL AND `collection_id` IS NULL)
                OR (`link_type` = 'collection_listing' AND `collection_id` IS NOT NULL AND `page_id` IS NULL AND `entry_id` IS NULL)
                OR (`link_type` = 'custom_url' AND `page_id` IS NULL AND `entry_id` IS NULL AND `collection_id` IS NULL)
                OR (`link_type` = 'no_link' AND `page_id` IS NULL AND `entry_id` IS NULL AND `collection_id` IS NULL)
            )"
        );

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['menu_id', 'parent_id', 'sort_order'], false, false, 'idx_menuitem_menu_parent');
        $this->forge->addKey('page_id', false, false, 'idx_menuitem_page');
        $this->forge->addKey('entry_id', false, false, 'idx_menuitem_entry');
        $this->forge->addKey('collection_id', false, false, 'idx_menuitem_collection');
        $this->forge->addForeignKey('menu_id', 'cms_menus', 'id', '', 'CASCADE', 'fk_menuitem_menu');
        $this->forge->addForeignKey('parent_id', 'cms_menu_items', 'id', '', 'CASCADE', 'fk_menuitem_parent');
        $this->forge->addForeignKey('page_id', 'cms_pages', 'id', '', 'CASCADE', 'fk_menuitem_page');
        $this->forge->addForeignKey('entry_id', 'cms_entries', 'id', '', 'CASCADE', 'fk_menuitem_entry');
        $this->forge->addForeignKey('collection_id', 'cms_collections', 'id', '', 'CASCADE', 'fk_menuitem_collection');

        $this->forge->createTable('cms_menu_items', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'menu_item_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'language_id'  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'label'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'custom_url'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['menu_item_id', 'language_id'], 'uk_menuitem_lang');
        $this->forge->addKey('language_id', false, false, 'idx_menuitemtrans_lang');
        $this->forge->addForeignKey('menu_item_id', 'cms_menu_items', 'id', '', 'CASCADE', 'fk_menuitemtrans_item');
        $this->forge->addForeignKey('language_id', 'cms_languages', 'id', '', 'CASCADE', 'fk_menuitemtrans_lang');

        $this->forge->createTable('cms_menu_item_translations', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;

        $db->disableForeignKeyChecks();
        $this->forge->dropTable('cms_menu_item_translations', true);
        $this->forge->dropTable('cms_menu_items', true);
        $this->forge->dropTable('cms_menu_translations', true);
        $this->forge->dropTable('cms_menus', true);
        $db->enableForeignKeyChecks();
    }
}
