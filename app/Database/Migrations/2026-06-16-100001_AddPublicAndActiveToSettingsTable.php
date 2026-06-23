<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPublicAndActiveToSettingsTable extends Migration
{
    public function up(): void
    {
        $colExists = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'cms_settings' AND column_name = 'is_public'"
        );
        if (!$colExists || (int) $colExists->getRowArray()['cnt'] === 0) {
            $this->forge->addColumn('cms_settings', [
                'is_public' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'comment'    => 'Whether this setting should be exposed via public API endpoints',
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'comment'    => 'Whether this setting is active/enabled',
                ],
            ]);
        }

        $indexExists = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS WHERE table_schema = DATABASE() AND table_name = 'cms_settings' AND index_name = 'idx_setting_public_active'"
        );
        if (!$indexExists || (int) $indexExists->getRowArray()['cnt'] === 0) {
            $this->db->query('ALTER TABLE `cms_settings` ADD INDEX `idx_setting_public_active` (`is_public`, `is_active`, `sort_order`)');
        }
    }

    public function down(): void
    {
        // Intentionally left as a no-op.
        // These columns/indexes are part of the supported schema now and the
        // rollback path is not used in normal operation or test setup.
    }
}
