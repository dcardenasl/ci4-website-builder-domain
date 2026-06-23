<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMetaToCmsSettings extends Migration
{
    public function up(): void
    {
        $colExists = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'cms_settings' AND column_name = 'setting_meta'"
        );
        if (!$colExists || (int) $colExists->getRowArray()['cnt'] === 0) {
            $this->forge->addColumn('cms_settings', [
                'setting_meta' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'default' => null,
                    'comment' => 'JSON auxiliary data for file_id settings: {"url":"...", "mime_type":"..."}',
                    'after'   => 'setting_value',
                ],
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally left as a no-op.
        // This migration is now one-way to keep schema setup idempotent.
    }
}
