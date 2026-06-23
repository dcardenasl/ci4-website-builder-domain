<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Extends the cms_pages.page_type ENUM to include custom page types
 * used by the institutional seeders (About, History, Events).
 */
class AddPageTypesAboutHistoryEvents extends Migration
{
    public function up(): void
    {
        $this->db->query(
            "ALTER TABLE `cms_pages`
             MODIFY COLUMN `page_type`
             ENUM('home','generic','contact','privacy','terms','404','500','maintenance','about','history','events')
             NOT NULL DEFAULT 'generic'"
        );
    }

    public function down(): void
    {
        // Revert to original ENUM — rows with new types will become '' (empty string) on strict=OFF
        // or fail on strict=ON. Safe to run only when no rows use the removed values.
        $this->db->query(
            "ALTER TABLE `cms_pages`
             MODIFY COLUMN `page_type`
             ENUM('home','generic','contact','privacy','terms','404','500','maintenance')
             NOT NULL DEFAULT 'generic'"
        );
    }
}
