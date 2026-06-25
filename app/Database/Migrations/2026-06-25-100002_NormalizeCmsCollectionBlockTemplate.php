<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Libraries\Cms\BlockTemplateNormalizer;
use CodeIgniter\Database\Migration;

class NormalizeCmsCollectionBlockTemplate extends Migration
{
    public function up(): void
    {
        $this->normalizeExistingTemplates();

        if ($this->supportsJsonColumn()) {
            $this->db->query(
                "ALTER TABLE cms_collections
                 MODIFY block_template JSON NULL
                 COMMENT 'JSON v1.0 schema defining required/optional blocks inherited by entries'
                 AFTER default_changefreq"
            );
        }
    }

    public function down(): void
    {
        if ($this->supportsJsonColumn()) {
            $this->db->query(
                "ALTER TABLE cms_collections
                 MODIFY block_template LONGTEXT NULL
                 COMMENT 'JSON v1.0 schema defining required/optional blocks inherited by entries'
                 AFTER default_changefreq"
            );
        }
    }

    private function normalizeExistingTemplates(): void
    {
        $rows = $this->db->query(
            'SELECT id, block_template FROM cms_collections WHERE block_template IS NOT NULL AND block_template <> ""'
        )->getResultArray();

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            try {
                $normalized = BlockTemplateNormalizer::normalize((string) ($row['block_template'] ?? ''));
            } catch (\Throwable $e) {
                log_message('warning', sprintf(
                    '[NormalizeCmsCollectionBlockTemplate] Skipped collection %d: %s',
                    $id,
                    $e->getMessage()
                ));
                continue;
            }

            if ($normalized === null) {
                $this->db->table('cms_collections')
                    ->where('id', $id)
                    ->update(['block_template' => null]);
                continue;
            }

            $normalizedJson = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (! is_string($normalizedJson)) {
                continue;
            }

            if ((string) ($row['block_template'] ?? '') !== $normalizedJson) {
                $this->db->table('cms_collections')
                    ->where('id', $id)
                    ->update(['block_template' => $normalizedJson]);
            }
        }
    }

    private function supportsJsonColumn(): bool
    {
        $driver = strtolower((string) ($this->db->DBDriver ?? ''));
        if ($driver !== 'mysqli') {
            return false;
        }

        $row = $this->db->query('SELECT VERSION() AS version')->getRowArray();
        $version = (string) ($row['version'] ?? '');
        if ($version === '' || stripos($version, 'mariadb') !== false) {
            return false;
        }

        $cleanVersion = preg_replace('/[^0-9.].*$/', '', $version) ?: $version;

        return version_compare($cleanVersion, '5.7.8', '>=');
    }
}
