<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\Migrations\Concerns\LegacyMediaReferenceResolver;
use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use CodeIgniter\Database\Migration;

final class MigrateImageBlockToMediaReference extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('cms_content_blocks')) {
            return;
        }

        $block = $this->db->table('cms_content_blocks')
            ->select('id, schema_definition')
            ->where('block_key', 'image')
            ->get()
            ->getRowArray();

        if (! is_array($block)) {
            return;
        }

        $schema = $this->imageBlockSchema();
        $this->db->table('cms_content_blocks')
            ->where('id', (int) $block['id'])
            ->update([
                'schema_definition' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

        if (! $this->db->tableExists('cms_block_instances')) {
            return;
        }

        $instances = $this->db->table('cms_block_instances')
            ->select('id, block_config')
            ->where('block_id', (int) $block['id'])
            ->get()
            ->getResultArray();

        $resolver = $this->legacyResolver();
        $synchronizer = new FileReferenceSynchronizer($this->db, $resolver);
        $defaultLanguageId = $this->defaultLanguageId();

        foreach ($instances as $instance) {
            $instanceId = (int) ($instance['id'] ?? 0);
            if ($instanceId <= 0) {
                continue;
            }

            $blockConfig = $this->decodeJsonArray($instance['block_config'] ?? null);
            $translations = $this->db->table('cms_block_instance_translations')
                ->where('instance_id', $instanceId)
                ->get()
                ->getResultArray();

            $reference = $this->canonicalImageReferenceFromConfig($blockConfig['image'] ?? null);
            if ($reference === null) {
                $reference = $this->canonicalImageReferenceFromTranslations($translations, $defaultLanguageId);
            }

            if ($reference !== null) {
                $blockConfig['image'] = $reference;
                $this->db->table('cms_block_instances')
                    ->where('id', $instanceId)
                    ->update([
                        'block_config' => json_encode($blockConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
            }

            $synchronizer->syncBlockInstance($instanceId);
        }
    }

    public function down(): void
    {
        // The media reference contract is forward-only. Reverting would reintroduce
        // a mixed ownership model for image assets and recreate the translation
        // ambiguity this migration removes.
    }

    /**
     * @return array<string, mixed>
     */
    private function imageBlockSchema(): array
    {
        return [
            'fields' => [
                'alt' => ['type' => 'string', 'label' => 'Texto Alternativo', 'required' => false],
                'caption' => ['type' => 'string', 'label' => 'Pie de Foto', 'required' => false],
            ],
            'config_fields' => [
                'image' => ['type' => 'media_reference', 'label' => 'Imagen', 'required' => false, 'accept' => 'image'],
                'css_class' => ['type' => 'string', 'label' => 'Clase CSS', 'required' => false, 'default' => ''],
                'aspect_ratio' => [
                    'type' => 'select',
                    'label' => 'Proporción',
                    'options' => ['auto', '16/9', '4/3', '1/1'],
                    'default' => 'auto',
                    'required' => false,
                ],
            ],
        ];
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
    private function canonicalImageReferenceFromConfig(mixed $reference): ?array
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
    private function canonicalImageReferenceFromTranslations(array $translations, ?int $defaultLanguageId): ?array
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

            $candidate = $this->canonicalImageReferenceFromBlockData($blockData);
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
    private function canonicalImageReferenceFromBlockData(array $blockData): ?array
    {
        $legacyReference = $blockData['image'] ?? null;
        if (is_array($legacyReference) || is_string($legacyReference) || is_int($legacyReference)) {
            $normalized = $this->canonicalImageReferenceFromConfig($legacyReference);
            if ($normalized !== null && ($normalized['file_id'] !== null || trim((string) ($normalized['url'] ?? '')) !== '')) {
                return $normalized;
            }
        }

        $fileId = $blockData['image_file_id'] ?? $blockData['file_id'] ?? null;
        $url = $this->normalizeUrl($blockData['image_url'] ?? $blockData['file_url'] ?? null);

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
