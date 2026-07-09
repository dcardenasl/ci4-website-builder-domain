<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillPortfolioFeaturedImageUrls extends Migration
{
    public function up(): void
    {
        if (
            ! $this->db->tableExists('cms_collections')
            || ! $this->db->tableExists('cms_entries')
            || ! $this->db->tableExists('cms_entry_translations')
            || ! $this->db->tableExists('cms_block_instances')
            || ! $this->db->tableExists('cms_block_instance_translations')
            || ! $this->db->tableExists('cms_content_blocks')
        ) {
            return;
        }

        $this->db->query("
            UPDATE cms_entry_translations et
            JOIN cms_entries e ON e.id = et.entry_id
            JOIN cms_collections c ON c.id = e.collection_id
            JOIN cms_block_instances bi
                ON bi.owner_type = 'entry'
               AND bi.owner_id = e.id
               AND bi.sort_order = 1
            JOIN cms_content_blocks cb
                ON cb.id = bi.block_id
               AND cb.block_key = 'image'
            JOIN cms_block_instance_translations bt
                ON bt.instance_id = bi.id
               AND bt.language_id = et.language_id
            SET et.featured_image_url = JSON_UNQUOTE(JSON_EXTRACT(bt.block_data, '$.image_url'))
            WHERE c.collection_key = 'portafolio'
              AND (et.featured_image_url IS NULL OR et.featured_image_url = '')
              AND JSON_UNQUOTE(JSON_EXTRACT(bt.block_data, '$.image_url')) IS NOT NULL
              AND JSON_UNQUOTE(JSON_EXTRACT(bt.block_data, '$.image_url')) <> ''
        ");
    }

    public function down(): void
    {
        // Forward-only data backfill. Reverting would remove valid image URLs
        // from existing portfolio entries, so the rollback is intentionally a no-op.
    }
}
