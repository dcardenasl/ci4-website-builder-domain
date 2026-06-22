<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Migrates image block translations that still use the old `file_id` + `file_url`
 * keys to the canonical `image_file_id` + `image_url` convention.
 *
 * The old convention was used before the schema-driven `{field}_file_id` pattern
 * was established. Running this seeder is idempotent — it only updates rows where
 * `image_file_id` is not yet set.
 *
 * Run BEFORE deploying BlockInstanceSerializer changes that remove the legacy paths.
 */
class BackfillImageBlockConventionSeeder extends Seeder
{
    public function run(): void
    {
        $this->db->query("
            UPDATE cms_block_instance_translations bt
            JOIN cms_block_instances bi ON bi.id = bt.instance_id
            JOIN cms_content_blocks btype ON btype.id = bi.block_id
            SET bt.block_data = JSON_SET(
                bt.block_data,
                '$.image_file_id', JSON_EXTRACT(bt.block_data, '$.file_id'),
                '$.image_url',     IFNULL(JSON_EXTRACT(bt.block_data, '$.file_url'), '')
            )
            WHERE btype.block_key = 'image'
              AND JSON_EXTRACT(bt.block_data, '$.file_id') IS NOT NULL
              AND JSON_EXTRACT(bt.block_data, '$.image_file_id') IS NULL
        ");
    }
}
