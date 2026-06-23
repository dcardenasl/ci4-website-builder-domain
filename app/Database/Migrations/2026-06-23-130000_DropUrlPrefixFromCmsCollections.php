<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropUrlPrefixFromCmsCollections extends Migration
{
    public function up(): void
    {
        $indexExists = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS
             WHERE table_schema = DATABASE()
               AND table_name = 'cms_collections'
               AND index_name = 'uk_collection_prefix'"
        );

        if ($indexExists && (int) $indexExists->getRowArray()['cnt'] > 0) {
            $this->db->query("ALTER TABLE `cms_collections`
                DROP INDEX `uk_collection_prefix`");
        }

        $columnExists = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE table_schema = DATABASE()
               AND table_name = 'cms_collections'
               AND column_name = 'url_prefix'"
        );

        if ($columnExists && (int) $columnExists->getRowArray()['cnt'] > 0) {
            $this->db->query("ALTER TABLE `cms_collections`
                DROP COLUMN `url_prefix`");
        }
    }

    public function down(): void
    {
        // Intentionally left as a no-op.
        // The collection slug now lives in translations and there is no
        // supported rollback path to the old top-level prefix column.
    }
}
