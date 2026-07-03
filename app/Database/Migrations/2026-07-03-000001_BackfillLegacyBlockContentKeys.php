<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Backfills legacy rich-text payload keys into the canonical `content` field.
 *
 * Older block rows may store their HTML under `body`, `html`, or `text`.
 * The public and admin renderers now read `content` consistently, so we
 * normalize stored rows to remove the legacy contract drift.
 */
class BackfillLegacyBlockContentKeys extends Migration
{
    public function up(): void
    {
        $rows = $this->db->table('cms_block_instance_translations')
            ->select('id, block_data')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $raw = $row['block_data'] ?? null;
            if (! is_string($raw) || $raw === '') {
                continue;
            }

            $data = json_decode($raw, true);
            if (! is_array($data)) {
                continue;
            }

            $normalized = $this->normalizeBlockTextPayload($data);
            if ($normalized === $data) {
                continue;
            }

            $this->db->table('cms_block_instance_translations')
                ->where('id', (int) $row['id'])
                ->update([
                    'block_data' => json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
        }
    }

    public function down(): void
    {
        // Safe no-op: the migration only adds canonical aliases and does not
        // destroy information. Rolling back would reintroduce drift.
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeBlockTextPayload(array $data): array
    {
        $content = trim((string) ($data['content'] ?? ''));
        if ($content !== '') {
            return $data;
        }

        foreach (['body', 'html', 'text'] as $legacyKey) {
            $legacyValue = $data[$legacyKey] ?? '';
            if (! is_string($legacyValue) || trim($legacyValue) === '') {
                continue;
            }

            $data['content'] = $legacyValue;
            break;
        }

        return $data;
    }
}
