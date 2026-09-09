<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

/**
 * Merges resolved file metadata into every media field a block schema declares,
 * normalizing each one to the canonical nested reference.
 *
 * Extracted from the serializer so the persisted read path and the editor
 * preview projector resolve media the same way: a draft has to show the image a
 * visitor would get, not a different one.
 */
final class SchemaMediaMerger
{
    /**
     * Merge resolved media_reference and repeater fields into block_data.
     *
     * @param  array<string, mixed>        $blockData
     * @param  array<string, array<string, mixed>> $schemaFields
     * @param  array<int, array{url: string|null, variants: array<string, mixed>|null}> $fileMetaMap keyed by file_id
     * @return array<string, mixed>
     */
    public static function merge(array $blockData, array $schemaFields, array $fileMetaMap, FileUrlResolver $files): array
    {
        foreach ($schemaFields as $fieldKey => $fieldDef) {
            $type = $fieldDef['type'] ?? 'string';

            if ($type === 'media_reference') {
                self::mergeReference($blockData, $fieldKey, $fileMetaMap, $files);
            } elseif ($type === 'repeater') {
                $items      = $blockData[$fieldKey] ?? [];
                $itemFields = $fieldDef['item_fields'] ?? [];
                if (!is_array($items) || !is_array($itemFields)) {
                    continue;
                }

                $enriched = [];
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        $enriched[] = $item;
                        continue;
                    }
                    $enriched[] = self::merge($item, $itemFields, $fileMetaMap, $files);
                }
                $blockData[$fieldKey] = $enriched;
            } elseif (in_array($type, ['group', 'fieldset'], true)) {
                $nestedFields = $fieldDef['fields'] ?? [];
                $nestedData   = $blockData[$fieldKey] ?? [];
                if (is_array($nestedData) && is_array($nestedFields)) {
                    $blockData[$fieldKey] = self::merge($nestedData, $nestedFields, $fileMetaMap, $files);
                }
            }
        }

        return $blockData;
    }

    /**
     * Normalize a media_reference field into the canonical nested payload.
     *
     * @param array<string, mixed> $blockData
     * @param array<int, array{url: string|null, variants: array<string, mixed>|null}> $fileMetaMap
     */
    private static function mergeReference(array &$blockData, string $fieldKey, array $fileMetaMap, FileUrlResolver $files): void
    {
        $reference = is_array($blockData[$fieldKey] ?? null) ? $blockData[$fieldKey] : [];
        $sourceKind = strtolower(trim((string) ($reference['source_kind'] ?? '')));
        $url = isset($reference['url']) && is_scalar($reference['url'])
            ? trim((string) $reference['url'])
            : '';
        $url = $url !== '' ? $url : null;
        $variants = is_array($reference['variants'] ?? null) ? $reference['variants'] : null;

        if ($sourceKind === 'external_url') {
            $blockData[$fieldKey] = [
                'source_kind' => 'external_url',
                'file_id'     => null,
                'url'         => $url,
                'variants'    => null,
            ];
            return;
        }

        $fileId = $files->resolveMediaReferenceFileId($reference);
        if ($fileId !== null) {
            $meta = $fileMetaMap[$fileId] ?? null;
            $blockData[$fieldKey] = [
                'source_kind' => 'hub_file',
                'file_id'     => $fileId,
                'url'         => $meta['url'] ?? $url,
                'variants'    => $meta['variants'] ?? $variants,
            ];
            return;
        }

        $blockData[$fieldKey] = [
            'source_kind' => $sourceKind === 'hub_file' ? 'hub_file' : 'external_url',
            'file_id'     => null,
            'url'         => $url,
            'variants'    => null,
        ];
    }
}
