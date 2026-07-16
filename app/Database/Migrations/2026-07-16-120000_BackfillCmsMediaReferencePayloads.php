<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\Migrations\Concerns\LegacyMediaReferenceResolver;
use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use CodeIgniter\Database\Migration;

/**
 * Normalizes every stored CMS media_reference payload to the canonical nested
 * shape so the editor can stop tolerating legacy flat keys.
 */
final class BackfillCmsMediaReferencePayloads extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('cms_content_blocks') || ! $this->db->tableExists('cms_block_instances')) {
            return;
        }

        $resolver = $this->legacyResolver();
        $synchronizer = new FileReferenceSynchronizer($this->db, $resolver);

        $blocks = $this->db->table('cms_content_blocks')
            ->select('id, schema_definition')
            ->get()
            ->getResultArray();

        foreach ($blocks as $block) {
            $blockId = (int) ($block['id'] ?? 0);
            if ($blockId <= 0) {
                continue;
            }

            $schema = $this->decodeJsonArray($block['schema_definition'] ?? null);
            $fields = (array) ($schema['fields'] ?? []);
            $configFields = (array) ($schema['config_fields'] ?? []);
            if ($fields === [] && $configFields === []) {
                continue;
            }

            $instances = $this->db->table('cms_block_instances')
                ->select('id, block_config')
                ->where('block_id', $blockId)
                ->get()
                ->getResultArray();

            foreach ($instances as $instance) {
                $instanceId = (int) ($instance['id'] ?? 0);
                if ($instanceId <= 0) {
                    continue;
                }

                $instanceChanged = false;

                $blockConfig = $this->decodeJsonArray($instance['block_config'] ?? null);
                $normalizedConfig = $this->normalizePayloadBySchema($blockConfig, $configFields, $resolver, $instanceChanged);
                if ($instanceChanged) {
                    $this->db->table('cms_block_instances')
                        ->where('id', $instanceId)
                        ->update([
                            'block_config' => json_encode($normalizedConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                }

                $translations = $this->db->table('cms_block_instance_translations')
                    ->select('id, block_data')
                    ->where('instance_id', $instanceId)
                    ->get()
                    ->getResultArray();

                foreach ($translations as $translation) {
                    $translationId = (int) ($translation['id'] ?? 0);
                    if ($translationId <= 0) {
                        continue;
                    }

                    $translationChanged = false;
                    $blockData = $this->decodeJsonArray($translation['block_data'] ?? null);
                    $normalizedData = $this->normalizePayloadBySchema($blockData, $fields, $resolver, $translationChanged);

                    if (! $translationChanged) {
                        continue;
                    }

                    $this->db->table('cms_block_instance_translations')
                        ->where('id', $translationId)
                        ->update([
                            'block_data' => json_encode($normalizedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                    $instanceChanged = true;
                }

                if ($instanceChanged) {
                    $synchronizer->syncBlockInstance($instanceId);
                }
            }
        }
    }

    public function down(): void
    {
        // Forward-only normalization.
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, array<string, mixed>> $schemaFields
     * @return array<string, mixed>
     */
    private function normalizePayloadBySchema(array $payload, array $schemaFields, FileUrlResolver $resolver, bool &$changed): array
    {
        foreach ($schemaFields as $fieldKey => $fieldDef) {
            $type = strtolower((string) ($fieldDef['type'] ?? 'string'));

            if ($type === 'media_reference') {
                [$payload, $fieldChanged] = $this->normalizeMediaReferenceField($payload, (string) $fieldKey, $resolver);
                $changed = $changed || $fieldChanged;
                continue;
            }

            if ($type === 'repeater') {
                $items = $payload[$fieldKey] ?? [];
                if (! is_array($items)) {
                    continue;
                }

                $itemFields = is_array($fieldDef['item_fields'] ?? null) ? (array) $fieldDef['item_fields'] : [];
                $normalizedItems = [];
                $itemsChanged = false;

                foreach ($items as $item) {
                    if (! is_array($item)) {
                        $normalizedItems[] = $item;
                        continue;
                    }

                    $itemChanged = false;
                    $normalizedItems[] = $this->normalizePayloadBySchema($item, $itemFields, $resolver, $itemChanged);
                    $itemsChanged = $itemsChanged || $itemChanged;
                }

                if ($itemsChanged) {
                    $payload[$fieldKey] = $normalizedItems;
                    $changed = true;
                }
                continue;
            }

            if (in_array($type, ['group', 'fieldset'], true)) {
                $nestedFields = is_array($fieldDef['fields'] ?? null) ? (array) $fieldDef['fields'] : [];
                $nestedData = $payload[$fieldKey] ?? null;
                if (is_array($nestedData) && $nestedFields !== []) {
                    $nestedChanged = false;
                    $payload[$fieldKey] = $this->normalizePayloadBySchema($nestedData, $nestedFields, $resolver, $nestedChanged);
                    $changed = $changed || $nestedChanged;
                }
            }
        }

        return $payload;
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

        $legacySourceKind = $payload[$fieldKey . '_source_kind'] ?? $payload[$fieldKey . '_sourceKind'] ?? null;
        $legacyFileId = $payload[$fieldKey . '_file_id'] ?? $payload[$fieldKey . '_fileId'] ?? null;
        $legacyUrl = $payload[$fieldKey . '_url'] ?? $payload[$fieldKey . '_external_url'] ?? $payload[$fieldKey . '_externalUrl'] ?? null;

        if ($reference === []) {
            if ($legacySourceKind !== null || $legacyFileId !== null || $legacyUrl !== null) {
                $reference = [
                    'source_kind' => $legacySourceKind,
                    'file_id' => $legacyFileId,
                    'url' => $legacyUrl,
                ];
                $fieldChanged = true;
            }
        }

        $normalized = $resolver->normalizeMediaReference($reference);

        $payload[$fieldKey] = $normalized;
        $fieldChanged = $fieldChanged || $reference !== $normalized;

        foreach ([
            $fieldKey . '_source_kind',
            $fieldKey . '_sourceKind',
            $fieldKey . '_file_id',
            $fieldKey . '_fileId',
            $fieldKey . '_url',
            $fieldKey . '_external_url',
            $fieldKey . '_externalUrl',
            $fieldKey . '_preview_url',
        ] as $legacyKey) {
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
