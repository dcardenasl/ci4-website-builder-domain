<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Keep page_type focused on system behavior. Editorial/demo classifications
 * are represented by the page content and slug, not by a separate type.
 */
final class NormalizeCmsPageTypes extends Migration
{
    private const ALLOWED_TYPES = "'home','generic','contact','privacy','terms','404','500','maintenance','collection_index'";

    public function up(): void
    {
        $this->db->query("UPDATE cms_pages SET page_type = 'generic' WHERE page_type IN ('about','history','events','components','media','portfolio')");

        $this->db->query(
            "ALTER TABLE cms_pages MODIFY page_type ENUM(" . self::ALLOWED_TYPES . ") NOT NULL DEFAULT 'generic'"
        );
    }

    public function down(): void
    {
        // The normalization is intentionally not reversed: the removed values
        // were editorial labels and cannot be reconstructed reliably.
    }
}
