<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCollectionIndexPageRelationToCmsPages extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('cms_pages')) {
            return;
        }

        $this->db->resetDataCache();

        $this->db->query(
            "ALTER TABLE `cms_pages`
             MODIFY COLUMN `page_type`
             ENUM('home','generic','contact','privacy','terms','404','500','maintenance','about','history','events','portfolio','collection_index')
             NOT NULL DEFAULT 'generic'"
        );

        if (! $this->db->fieldExists('collection_id', 'cms_pages')) {
            $this->forge->addColumn('cms_pages', [
                'collection_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'parent_id'],
            ]);
        } else {
            $this->db->query(
                "ALTER TABLE `cms_pages` MODIFY COLUMN `collection_id` INT(10) UNSIGNED NULL AFTER `parent_id`"
            );
        }

        if (! $this->db->query("SHOW INDEX FROM `cms_pages` WHERE Key_name = 'idx_page_collection_id'")->getResultArray()) {
            $this->db->query("ALTER TABLE `cms_pages` ADD KEY `idx_page_collection_id` (`collection_id`)");
        }

        if (! $this->db->query("SHOW INDEX FROM `cms_pages` WHERE Key_name = 'uk_page_collection_id'")->getResultArray()) {
            $this->db->query("ALTER TABLE `cms_pages` ADD UNIQUE KEY `uk_page_collection_id` (`collection_id`)");
        }

        if (! $this->foreignKeyExists('cms_pages', 'fk_page_collection')) {
            $orphans = $this->db->query(
                "SELECT p.id
                 FROM `cms_pages` AS p
                 LEFT JOIN `cms_collections` AS c ON c.id = p.collection_id
                 WHERE p.collection_id IS NOT NULL AND c.id IS NULL
                 LIMIT 1"
            )->getResultArray();

            if ($orphans !== []) {
                throw new \RuntimeException('Cannot add fk_page_collection: orphan collection_id values exist in cms_pages.');
            }

            $this->db->query(
                "ALTER TABLE `cms_pages`
                 ADD CONSTRAINT `fk_page_collection`
                 FOREIGN KEY (`collection_id`) REFERENCES `cms_collections`(`id`)
                 ON DELETE CASCADE"
            );
        }

    }

    public function down(): void
    {
        if (! $this->db->tableExists('cms_pages')) {
            return;
        }

        $this->db->resetDataCache();

        if ($this->foreignKeyExists('cms_pages', 'fk_page_collection')) {
            $this->db->query("ALTER TABLE `cms_pages` DROP FOREIGN KEY `fk_page_collection`");
        }

        if ($this->db->query("SHOW INDEX FROM `cms_pages` WHERE Key_name = 'uk_page_collection_id'")->getResultArray()) {
            $this->db->query("ALTER TABLE `cms_pages` DROP KEY `uk_page_collection_id`");
        }

        if ($this->db->query("SHOW INDEX FROM `cms_pages` WHERE Key_name = 'idx_page_collection_id'")->getResultArray()) {
            $this->db->query("ALTER TABLE `cms_pages` DROP KEY `idx_page_collection_id`");
        }

        if ($this->db->fieldExists('collection_id', 'cms_pages')) {
            $this->db->query("ALTER TABLE `cms_pages` DROP COLUMN `collection_id`");
        }

        $this->db->query(
            "ALTER TABLE `cms_pages`
             MODIFY COLUMN `page_type`
             ENUM('home','generic','contact','privacy','terms','404','500','maintenance','about','history','events','portfolio')
             NOT NULL DEFAULT 'generic'"
        );
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $rows = $this->db->query(
            "SELECT CONSTRAINT_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$table, $constraintName]
        )->getResultArray();

        return $rows !== [];
    }
}
