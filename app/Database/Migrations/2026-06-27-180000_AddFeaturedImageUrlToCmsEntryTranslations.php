<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFeaturedImageUrlToCmsEntryTranslations extends Migration
{
    public function up(): void
    {
        // fieldExists() trusts a per-connection schema cache that does not
        // auto-invalidate after a DDL change made earlier in the same
        // request/process — force a fresh read (see the collection_type
        // migrations for the incident that surfaced this).
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('featured_image_url', 'cms_entry_translations')) {
            try {
                $this->forge->addColumn('cms_entry_translations', [
                    'featured_image_url' => [
                        'type' => 'VARCHAR',
                        'constraint' => 2048,
                        'null' => true,
                        'after' => 'featured_file_id',
                    ],
                ]);
            } catch (\Throwable) {
                // Ignore if the schema was already migrated by another path.
            }
        }
    }

    public function down(): void
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('featured_image_url', 'cms_entry_translations')) {
            try {
                $this->forge->dropColumn('cms_entry_translations', 'featured_image_url');
            } catch (\Throwable) {
                // Ignore when the column is already gone.
            }
        }
    }
}
