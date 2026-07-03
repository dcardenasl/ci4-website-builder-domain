<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Extends the cms_pages.page_type ENUM to include 'portfolio'
 * and also updates type_singleton generated column expression to include 'portfolio'.
 */
class AddPortfolioPageTypeToCmsPages extends Migration
{
    public function up(): void
    {
        // 1. First drop the generated column type_singleton as it depends on page_type
        $this->db->query("ALTER TABLE `cms_pages` DROP COLUMN `type_singleton`");

        // 2. Modify page_type ENUM to include 'portfolio'
        $this->db->query(
            "ALTER TABLE `cms_pages`
             MODIFY COLUMN `page_type`
             ENUM('home','generic','contact','privacy','terms','404','500','maintenance','about','history','events','portfolio')
             NOT NULL DEFAULT 'generic'"
        );

        // 3. Re-add the generated column type_singleton including 'portfolio' in CASE
        $this->db->query(
            "ALTER TABLE `cms_pages` ADD COLUMN `type_singleton` VARCHAR(20) GENERATED ALWAYS AS (" .
            "CASE WHEN `page_type` IN ('home','404','500','maintenance','contact','privacy','terms','portfolio') " .
            "AND `deleted_at` IS NULL THEN `page_type` ELSE NULL END) STORED"
        );

        // 4. Also add a unique key on type_singleton as it was dropped when the column was dropped
        $this->db->query("ALTER TABLE `cms_pages` ADD UNIQUE KEY `uk_page_type_singleton` (`type_singleton`)");
    }

    public function down(): void
    {
        // 1. Drop generated column
        $this->db->query("ALTER TABLE `cms_pages` DROP COLUMN `type_singleton`");

        // 2. Revert page_type ENUM (portfolio rows will become empty string or generic)
        $this->db->query(
            "ALTER TABLE `cms_pages`
             MODIFY COLUMN `page_type`
             ENUM('home','generic','contact','privacy','terms','404','500','maintenance','about','history','events')
             NOT NULL DEFAULT 'generic'"
        );

        // 3. Re-add original type_singleton expression
        $this->db->query(
            "ALTER TABLE `cms_pages` ADD COLUMN `type_singleton` VARCHAR(20) GENERATED ALWAYS AS (" .
            "CASE WHEN `page_type` IN ('home','404','500','maintenance','contact','privacy','terms') " .
            "AND `deleted_at` IS NULL THEN `page_type` ELSE NULL END) STORED"
        );

        // 4. Re-add unique key
        $this->db->query("ALTER TABLE `cms_pages` ADD UNIQUE KEY `uk_page_type_singleton` (`type_singleton`)");
    }
}
