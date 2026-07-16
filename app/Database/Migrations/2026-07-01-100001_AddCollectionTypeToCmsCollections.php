<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCollectionTypeToCmsCollections extends Migration
{
    public function up(): void
    {
        // fieldExists() reads from BaseConnection's per-connection schema cache,
        // which does not auto-invalidate after a DDL change made earlier in the
        // same request/process (e.g. by another migration in this same run).
        // Force a fresh read so this guard reflects the DB's actual current state.
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('collection_type', 'cms_collections')) {
            try {
                $this->forge->addColumn('cms_collections', [
                    'collection_type' => [
                        'type' => 'ENUM',
                        'constraint' => ['blog', 'news', 'portfolio', 'services', 'other'],
                        'null' => false,
                        'default' => 'other',
                        'after' => 'collection_key',
                    ],
                ]);
            } catch (\Throwable) {
                // Ignore if the column already exists or the schema is partially applied.
            }
        }

        try {
            $this->db->query("UPDATE `cms_collections` SET `collection_type` = 'news' WHERE `collection_key` IN ('noticias', 'news')");
        } catch (\Throwable) {
            // Ignore backfill failures when the column is unavailable in the current schema.
        }
    }

    public function down(): void
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('collection_type', 'cms_collections')) {
            try {
                $this->forge->dropColumn('cms_collections', 'collection_type');
            } catch (\Throwable) {
                // Ignore when the column has already been removed.
            }
        }
    }
}
