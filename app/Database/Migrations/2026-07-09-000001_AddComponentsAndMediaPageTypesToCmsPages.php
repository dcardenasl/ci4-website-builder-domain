<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddComponentsAndMediaPageTypesToCmsPages extends Migration
{
    public function up(): void
    {
        $this->db->query(
            "ALTER TABLE `cms_pages` " .
            "MODIFY COLUMN `page_type` " .
            "ENUM('home','generic','contact','privacy','terms','404','500','maintenance','about','history','events','components','media','collection_index') NOT NULL DEFAULT 'generic'"
        );
    }

    public function down(): void
    {
        $this->db->query(
            "ALTER TABLE `cms_pages` " .
            "MODIFY COLUMN `page_type` " .
            "ENUM('home','generic','contact','privacy','terms','404','500','maintenance','about','history','events','collection_index') NOT NULL DEFAULT 'generic'"
        );
    }
}
