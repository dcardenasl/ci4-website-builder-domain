<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\Migrations\Concerns\LegacyMediaReferenceResolver;
use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use CodeIgniter\Database\Migration;

/**
 * Completes the media_reference migration for `document_gallery.documents[].file`
 * — the one remaining repeater file field, left out of
 * 2026-07-16-110000_MigrateNestedImageFieldsToMediaReference because that pass
 * only auto-converts `file` fields whose accept is (or defaults to) `image`,
 * and left out of 2026-07-16-120000_BackfillCmsMediaReferencePayloads's data
 * normalization because it only normalizes fields the schema already marks as
 * `media_reference` at the time it runs.
 */
final class MigrateDocumentGalleryFileToMediaReference extends Migration
{
    private const BLOCK_KEY = 'document_gallery';
    private const REPEATER_KEY = 'documents';
    private const ITEM_FIELD_KEY = 'file';

    public function up(): void
    {
        if (! $this->db->tableExists('cms_content_blocks')) {
            return;
        }

        $block = $this->db->table('cms_content_blocks')
            ->select('id, schema_definition')
            ->where('block_key', self::BLOCK_KEY)
            ->get()
            ->getRowArray();

        if (! is_array($block)) {
            return;
        }

        $blockId = (int) $block['id'];
        $schema  = $this->decodeJsonArray($block['schema_definition'] ?? null);
        $fields  = (array) ($schema['fields'] ?? []);
        $itemFields = is_array($fields[self::REPEATER_KEY]['item_fields'] ?? null)
            ? (array) $fields[self::REPEATER_KEY]['item_fields']
            : [];

        $alreadyMigrated = ($itemFields[self::ITEM_FIELD_KEY]['type'] ?? null) === 'media_reference';

        if (! $alreadyMigrated) {
            if (! isset($itemFields[self::ITEM_FIELD_KEY])) {
                return;
            }

            $itemFields[self::ITEM_FIELD_KEY]['type'] = 'media_reference';
            $fields[self::REPEATER_KEY]['item_fields'] = $itemFields;
            $schema['fields'] = $fields;

            $this->db->table('cms_content_blocks')
                ->where('id', $blockId)
                ->update([
                    'schema_definition' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
        }

        if (! $this->db->tableExists('cms_block_instances')) {
            return;
        }

        $resolver     = $this->legacyResolver();
        $synchronizer = new FileReferenceSynchronizer($this->db, $resolver);

        $instances = $this->db->table('cms_block_instances')
            ->select('id')
            ->where('block_id', $blockId)
            ->get()
            ->getResultArray();

        foreach ($instances as $instance) {
            $instanceId = (int) ($instance['id'] ?? 0);
            if ($instanceId <= 0) {
                continue;
            }

            $translations = $this->db->table('cms_block_instance_translations')
                ->select('id, block_data')
                ->where('instance_id', $instanceId)
                ->get()
                ->getResultArray();

            $instanceChanged = false;

            foreach ($translations as $translation) {
                $translationId = (int) ($translation['id'] ?? 0);
                if ($translationId <= 0) {
                    continue;
                }

                $blockData = $this->decodeJsonArray($translation['block_data'] ?? null);
                $documents = is_array($blockData[self::REPEATER_KEY] ?? null) ? (array) $blockData[self::REPEATER_KEY] : [];
                if ($documents === []) {
                    continue;
                }

                $changed = false;
                foreach ($documents as $idx => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    [$normalizedItem, $itemChanged] = $this->normalizeMediaReferenceField($item, self::ITEM_FIELD_KEY, $resolver);
                    if ($itemChanged) {
                        $documents[$idx] = $normalizedItem;
                        $changed = true;
                    }
                }

                if (! $changed) {
                    continue;
                }

                $blockData[self::REPEATER_KEY] = $documents;
                $this->db->table('cms_block_instance_translations')
                    ->where('id', $translationId)
                    ->update([
                        'block_data' => json_encode($blockData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                $instanceChanged = true;
            }

            if ($instanceChanged) {
                $synchronizer->syncBlockInstance($instanceId);
            }
        }
    }

    public function down(): void
    {
        // Forward-only, same rationale as the other media_reference migrations.
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function normalizeMediaReferenceField(array $payload, string $fieldKey, FileUrlResolver $resolver): array
    {
        $fieldChanged = false;
        $reference = $payload[$fieldKey] ?? null;

        if (! is_array($reference)) {
            $reference = [];
        }

        $legacyFileId = $payload[$fieldKey . '_file_id'] ?? null;
        $legacyUrl    = $payload[$fieldKey . '_url'] ?? null;

        if ($reference === [] && ($legacyFileId !== null || $legacyUrl !== null)) {
            $reference = [
                'file_id' => $legacyFileId,
                'url'     => $legacyUrl,
            ];
            $fieldChanged = true;
        }

        $normalized = $resolver->normalizeMediaReference($reference);
        $payload[$fieldKey] = $normalized;
        $fieldChanged = $fieldChanged || $reference !== $normalized;

        foreach ([$fieldKey . '_file_id', $fieldKey . '_url', $fieldKey . '_preview_url'] as $legacyKey) {
            if (array_key_exists($legacyKey, $payload)) {
                unset($payload[$legacyKey]);
                $fieldChanged = true;
            }
        }

        return [$payload, $fieldChanged];
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function legacyResolver(): FileUrlResolver
    {
        return new LegacyMediaReferenceResolver();
    }
}
