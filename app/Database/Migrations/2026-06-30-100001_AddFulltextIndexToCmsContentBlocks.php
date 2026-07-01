<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFulltextIndexToCmsContentBlocks extends Migration
{
    public function up(): void
    {
        // The admin block-type catalog search relies on the shared searchable trait.
        // This table was missing the FULLTEXT index that trait expects when fulltext
        // search is enabled, which caused the catalog search to explode instead of
        // returning an empty-state result.
        $this->db->query(
            'ALTER TABLE `cms_content_blocks` ADD FULLTEXT KEY `ft_cms_content_blocks_search` (`block_key`, `name`)'
        );
    }

    public function down(): void
    {
        $this->db->query(
            'ALTER TABLE `cms_content_blocks` DROP INDEX `ft_cms_content_blocks_search`'
        );
    }
}
