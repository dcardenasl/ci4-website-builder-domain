<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * AddComponentsAndMediaPageTypesToCmsPages (2026-07-09-000001) rebuilt the
 * page_type ENUM to add 'components' and 'media' but accidentally dropped
 * 'portfolio' in the process. Any row stored as 'portfolio' before that
 * migration silently became '' (empty string) once the column no longer
 * allowed the value, which in turn broke SitePortfolioPageSeeder's legacy-page
 * repair lookup (WHERE page_type IN ('portfolio')). This migration restores
 * 'portfolio' alongside the other page types added since.
 */
final class FixPortfolioPageTypeEnumRegression extends Migration
{
    public function up(): void
    {
        $this->db->query(
            'ALTER TABLE `cms_pages` ' .
            'MODIFY COLUMN `page_type` ' .
            "ENUM('home','generic','contact','privacy','terms','404','500','maintenance','about','history','events','components','media','collection_index','portfolio') NOT NULL DEFAULT 'generic'"
        );
    }

    public function down(): void
    {
        $this->db->query(
            'ALTER TABLE `cms_pages` ' .
            'MODIFY COLUMN `page_type` ' .
            "ENUM('home','generic','contact','privacy','terms','404','500','maintenance','about','history','events','components','media','collection_index') NOT NULL DEFAULT 'generic'"
        );
    }
}
