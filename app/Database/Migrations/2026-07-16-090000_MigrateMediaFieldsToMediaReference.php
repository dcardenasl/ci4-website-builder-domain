<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\Migrations\Concerns\LegacyMediaReferenceResolver;
use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use CodeIgniter\Database\Migration;

/**
 * Generalizes the media_reference migration applied to the `image` block
 * (2026-07-15-090000) to every other block type whose schema still carried a
 * shared asset (logo, poster, document, photo...) as a translatable `file`
 * field instead of a block-level `config_fields` entry. Content editors saw
 * the file picker duplicated per language with no indication the asset was
 * meant to be shared — see audits/2026-07-16 UX review.
 */
final class MigrateMediaFieldsToMediaReference extends Migration
{
    /** @var array<string, string> block_key => field_key to move from `fields` into `config_fields` */
    private const TARGETS = [
        'slide_banner'      => 'image',
        'hero_banner'       => 'image',
        'card_item'         => 'image',
        'slide_card'        => 'image',
        'asset_item'        => 'logo',
        'video_player'      => 'poster',
        'gallery_item'      => 'image',
        'document_download' => 'document',
        'timeline_item'     => 'image',
        'pdf_viewer'        => 'pdf_file',
        'team_member'       => 'photo',
    ];

    public function up(): void
    {
        if (! $this->db->tableExists('cms_content_blocks')) {
            return;
        }

        $defaultLanguageId = $this->defaultLanguageId();
        $resolver          = $this->legacyResolver();
        $synchronizer       = new FileReferenceSynchronizer($this->db, $resolver);

        foreach (self::TARGETS as $blockKey => $fieldKey) {
            $this->migrateBlockType($blockKey, $fieldKey, $defaultLanguageId, $synchronizer);
        }
    }

    public function down(): void
    {
        // Forward-only, same rationale as 2026-07-15-090000_MigrateImageBlockToMediaReference.
    }

    private function migrateBlockType(string $blockKey, string $fieldKey, ?int $defaultLanguageId, FileReferenceSynchronizer $synchronizer): void
    {
        $block = $this->db->table('cms_content_blocks')
            ->select('id, schema_definition')
            ->where('block_key', $blockKey)
            ->get()
            ->getRowArray();

        if (! is_array($block)) {
            return;
        }

        $blockId = (int) $block['id'];
        $schema  = $this->decodeJsonArray($block['schema_definition'] ?? null);
        $fields       = (array) ($schema['fields'] ?? []);
        $configFields = (array) ($schema['config_fields'] ?? []);

        if (isset($fields[$fieldKey])) {
            $fieldDef = (array) $fields[$fieldKey];
            unset($fields[$fieldKey]);
            $configFields[$fieldKey] = [
                'type'     => 'media_reference',
                'label'    => $fieldDef['label'] ?? $fieldKey,
                'required' => $fieldDef['required'] ?? false,
                'accept'   => $fieldDef['accept'] ?? 'image',
            ];

            $schema['fields']        = $fields;
            $schema['config_fields'] = $configFields;

            $this->db->table('cms_content_blocks')
                ->where('id', $blockId)
                ->update([
                    'schema_definition' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
        } elseif (! isset($configFields[$fieldKey]) || ($configFields[$fieldKey]['type'] ?? null) !== 'media_reference') {
            // Neither a legacy `fields` entry nor an already-migrated config field — nothing to do.
            return;
        }

        if (! $this->db->tableExists('cms_block_instances')) {
            return;
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

            $blockConfig = $this->decodeJsonArray($instance['block_config'] ?? null);

            $reference = $this->canonicalReferenceFromConfig($blockConfig[$fieldKey] ?? null);
            if ($reference === null) {
                $translations = $this->db->table('cms_block_instance_translations')
                    ->where('instance_id', $instanceId)
                    ->get()
                    ->getResultArray();
                $reference = $this->canonicalReferenceFromTranslations($translations, $fieldKey, $defaultLanguageId);
            }

            if ($reference !== null) {
                $blockConfig[$fieldKey] = $reference;
                $this->db->table('cms_block_instances')
                    ->where('id', $instanceId)
                    ->update([
                        'block_config' => json_encode($blockConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
            }

            $synchronizer->syncBlockInstance($instanceId);
        }
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

    /**
     * @param mixed $reference
     * @return array{source_kind: string, file_id: int|null, url: string|null}|null
     */
    private function canonicalReferenceFromConfig(mixed $reference): ?array
    {
        if ($reference === null || $reference === '') {
            return null;
        }

        if (is_string($reference) || is_int($reference)) {
            return [
                'source_kind' => 'external_url',
                'file_id' => null,
                'url' => trim((string) $reference) !== '' ? trim((string) $reference) : null,
            ];
        }

        if (! is_array($reference)) {
            return null;
        }

        $sourceKind = strtolower(trim((string) ($reference['source_kind'] ?? $reference['source'] ?? '')));
        $url = $this->normalizeUrl($reference['url'] ?? $reference['external_url'] ?? null);
        $fileId = is_numeric($reference['file_id'] ?? null) ? (int) $reference['file_id'] : null;
        $fileIdFromUrl = $url !== null ? $this->fileIdFromUrl($url) : null;

        if ($sourceKind === 'external_url' || $sourceKind === 'external' || $sourceKind === 'url') {
            return [
                'source_kind' => 'external_url',
                'file_id' => null,
                'url' => $url,
            ];
        }

        if ($sourceKind === 'hub_file' || $sourceKind === 'file' || $sourceKind === 'hub') {
            return [
                'source_kind' => 'hub_file',
                'file_id' => $fileId ?? $fileIdFromUrl,
                'url' => $url,
            ];
        }

        if ($fileId !== null || $fileIdFromUrl !== null) {
            return [
                'source_kind' => 'hub_file',
                'file_id' => $fileId ?? $fileIdFromUrl,
                'url' => $url,
            ];
        }

        return [
            'source_kind' => 'external_url',
            'file_id' => null,
            'url' => $url,
        ];
    }

    /**
     * @param list<array<string, mixed>> $translations
     * @return array{source_kind: string, file_id: int|null, url: string|null}|null
     */
    private function canonicalReferenceFromTranslations(array $translations, string $fieldKey, ?int $defaultLanguageId): ?array
    {
        $ordered = $translations;
        usort($ordered, static function (array $a, array $b) use ($defaultLanguageId): int {
            $aDefault = $defaultLanguageId !== null && (int) ($a['language_id'] ?? 0) === $defaultLanguageId;
            $bDefault = $defaultLanguageId !== null && (int) ($b['language_id'] ?? 0) === $defaultLanguageId;

            return $aDefault === $bDefault ? 0 : ($aDefault ? -1 : 1);
        });

        foreach ($ordered as $translation) {
            $blockData = $this->decodeJsonArray($translation['block_data'] ?? null);
            if ($blockData === []) {
                continue;
            }

            $candidate = $this->canonicalReferenceFromBlockData($blockData, $fieldKey);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $blockData
     * @return array{source_kind: string, file_id: int|null, url: string|null}|null
     */
    private function canonicalReferenceFromBlockData(array $blockData, string $fieldKey): ?array
    {
        $legacyReference = $blockData[$fieldKey] ?? null;
        if (is_array($legacyReference) || is_string($legacyReference) || is_int($legacyReference)) {
            $normalized = $this->canonicalReferenceFromConfig($legacyReference);
            if ($normalized !== null && ($normalized['file_id'] !== null || trim((string) ($normalized['url'] ?? '')) !== '')) {
                return $normalized;
            }
        }

        $fileId = $blockData[$fieldKey . '_file_id'] ?? $blockData['file_id'] ?? null;
        $url = $this->normalizeUrl($blockData[$fieldKey . '_url'] ?? $blockData['file_url'] ?? null);

        if (! is_numeric($fileId) && $url === null) {
            return null;
        }

        if (is_numeric($fileId)) {
            return [
                'source_kind' => 'hub_file',
                'file_id' => (int) $fileId,
                'url' => $url,
            ];
        }

        return [
            'source_kind' => 'external_url',
            'file_id' => null,
            'url' => $url,
        ];
    }

    private function normalizeUrl(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $url = trim((string) $value);

        return $url !== '' ? $url : null;
    }

    private function fileIdFromUrl(string $url): ?int
    {
        if (preg_match('~/files/(\d+)/(?:view|download)(?:\?.*)?$~', $url, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function defaultLanguageId(): ?int
    {
        if (! $this->db->tableExists('cms_languages')) {
            return null;
        }

        $row = $this->db->table('cms_languages')
            ->select('id')
            ->where('is_default', 1)
            ->limit(1)
            ->get()
            ->getRowArray();

        return is_array($row) && isset($row['id']) ? (int) $row['id'] : null;
    }

    private function legacyResolver(): FileUrlResolver
    {
        return new LegacyMediaReferenceResolver();
    }
}
