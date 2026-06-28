<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use App\Libraries\Hub\HubClient;

/**
 * Canonical file URL resolver.
 *
 * Resolves Hub file IDs to public URLs by calling the Hub's internal
 * batch-meta endpoint via HubClient. Results are cached by HubClient
 * (default 300 s per file ID) so repeated resolution within a request
 * and across requests is cheap.
 *
 * The Domain's `cms` database has NO `files` table — files are owned by
 * the Hub. This class is the single point of contact for file URL resolution
 * in the Domain, keeping the boundary explicit.
 */
class FileUrlResolver
{
    private HubClient $hubClient;

    public function __construct(?HubClient $hubClient = null)
    {
        $this->hubClient = $hubClient ?? service('hubClient');
    }

    // ─── Public API (interface unchanged) ────────────────────────────────────

    public function resolve(int $fileId, string $context = 'public'): ?string
    {
        if ($fileId <= 0) {
            return null;
        }

        $map = $this->hubClient->resolvePublicFileMeta([$fileId]);
        $row = $map[$fileId] ?? null;

        return $row !== null ? $this->resolveFromRow($row, $context) : null;
    }

    /**
     * @param  list<int>         $fileIds
     * @return array<int, string>
     */
    public function resolveMany(array $fileIds, string $context = 'public'): array
    {
        $fileIds = array_values(array_unique(array_filter(
            $fileIds,
            static fn ($id): bool => is_int($id) && $id > 0
        )));

        if (empty($fileIds)) {
            return [];
        }

        $metaMap = $this->hubClient->resolvePublicFileMeta($fileIds);
        $urls    = [];

        foreach ($metaMap as $fileId => $row) {
            $resolved = $this->resolveFromRow($row, $context);
            if ($resolved !== null && $resolved !== '') {
                $urls[(int) $fileId] = $resolved;
            }
        }

        return $urls;
    }

    /**
     * Canonicalize a file-bearing URL field.
     *
     * If a file ID exists, it always wins over a stored URL.
     * Falls back to the stored URL when the Hub cannot resolve the ID.
     */
    public function resolveUrlValue(int|string|null $fileId, ?string $currentUrl = null, string $context = 'public'): ?string
    {
        $normalizedFileId = is_numeric($fileId) ? (int) $fileId : null;

        if ($normalizedFileId !== null && $normalizedFileId > 0) {
            $resolved = $this->resolve($normalizedFileId, $context);
            if ($resolved !== null && $resolved !== '') {
                return $resolved;
            }

            return $this->normalizeUrl($currentUrl);
        }

        $currentUrl = $this->normalizeUrl($currentUrl);
        if ($currentUrl === null) {
            return null;
        }

        $resolvedFileId = $this->resolveFileIdFromUrl($currentUrl);
        if ($resolvedFileId !== null) {
            return $this->resolve($resolvedFileId, $context) ?? $currentUrl;
        }

        return $currentUrl;
    }

    /**
     * @param  array<string, mixed> $translation
     * @return array<string, mixed>
     */
    public function normalizeEntryTranslation(array $translation, string $context = 'public'): array
    {
        $translation['featured_image_url'] = $this->resolveUrlValue(
            $translation['featured_file_id'] ?? null,
            isset($translation['featured_image_url']) ? (string) $translation['featured_image_url'] : null,
            $context
        );

        $translation['og_image_url'] = $this->resolveUrlValue(
            $translation['og_image_file_id'] ?? null,
            isset($translation['og_image_url']) ? (string) $translation['og_image_url'] : null,
            $context
        );

        return $translation;
    }

    /**
     * @param  array<string, mixed> $translation
     * @return array<string, mixed>
     */
    public function normalizePageTranslation(array $translation, string $context = 'public'): array
    {
        $translation['og_image_url'] = $this->resolveUrlValue(
            $translation['og_image_file_id'] ?? null,
            isset($translation['og_image_url']) ? (string) $translation['og_image_url'] : null,
            $context
        );

        return $translation;
    }

    /**
     * Normalize all file-bearing fields in a block payload according to its schema.
     *
     * @param  array<string, mixed>               $blockData
     * @param  array<string, array<string, mixed>> $schemaFields
     * @return array<string, mixed>
     */
    public function normalizeBlockData(array $blockData, array $schemaFields, string $context = 'public'): array
    {
        foreach ($schemaFields as $fieldKey => $fieldDef) {
            $type = strtolower((string) ($fieldDef['type'] ?? 'string'));

            if ($type === 'file') {
                $fileIdKey          = $fieldKey . '_file_id';
                $urlKey             = $fieldKey . '_url';
                $blockData[$urlKey] = $this->resolveUrlValue(
                    $blockData[$fileIdKey] ?? null,
                    isset($blockData[$urlKey]) ? (string) $blockData[$urlKey] : null,
                    $context
                );
                continue;
            }

            if ($type === 'repeater') {
                $items = $blockData[$fieldKey] ?? [];
                if (! is_array($items)) {
                    continue;
                }

                $itemFields = is_array($fieldDef['item_fields'] ?? null) ? (array) $fieldDef['item_fields'] : [];
                $normalized = [];
                foreach ($items as $item) {
                    $normalized[] = is_array($item)
                        ? $this->normalizeBlockData($item, $itemFields, $context)
                        : $item;
                }

                $blockData[$fieldKey] = $normalized;
                continue;
            }

            if (in_array($type, ['group', 'fieldset'], true)) {
                $nestedFields = is_array($fieldDef['fields'] ?? null) ? (array) $fieldDef['fields'] : [];
                $nestedData   = $blockData[$fieldKey] ?? null;
                if (is_array($nestedData) && $nestedFields !== []) {
                    $blockData[$fieldKey] = $this->normalizeBlockData($nestedData, $nestedFields, $context);
                }
            }
        }

        return $blockData;
    }

    /**
     * Collect every file ID referenced by a block payload.
     *
     * @param  array<string, mixed>               $blockData
     * @param  array<string, array<string, mixed>> $schemaFields
     * @return list<int>
     */
    public function collectBlockFileIds(array $blockData, array $schemaFields): array
    {
        $fileIds = [];

        foreach ($schemaFields as $fieldKey => $fieldDef) {
            $type = strtolower((string) ($fieldDef['type'] ?? 'string'));

            if ($type === 'file') {
                $fileIds[] = $this->resolveFileIdFromValue(
                    $blockData[$fieldKey . '_file_id'] ?? null,
                    isset($blockData[$fieldKey . '_url']) ? (string) $blockData[$fieldKey . '_url'] : null
                );
                continue;
            }

            if ($type === 'repeater') {
                $items = $blockData[$fieldKey] ?? [];
                if (! is_array($items)) {
                    continue;
                }

                $itemFields = is_array($fieldDef['item_fields'] ?? null) ? (array) $fieldDef['item_fields'] : [];
                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $fileIds = array_merge($fileIds, $this->collectBlockFileIds($item, $itemFields));
                }
                continue;
            }

            if (in_array($type, ['group', 'fieldset'], true)) {
                $nestedFields = is_array($fieldDef['fields'] ?? null) ? (array) $fieldDef['fields'] : [];
                $nestedData   = $blockData[$fieldKey] ?? null;
                if (is_array($nestedData) && $nestedFields !== []) {
                    $fileIds = array_merge($fileIds, $this->collectBlockFileIds($nestedData, $nestedFields));
                }
            }
        }

        return array_values(array_unique(array_filter(
            $fileIds,
            static fn ($id): bool => is_int($id) && $id > 0
        )));
    }

    public function resolveFileIdFromValue(int|string|null $fileId, ?string $url = null): ?int
    {
        if (is_numeric($fileId) && (int) $fileId > 0) {
            return (int) $fileId;
        }

        $url = $this->normalizeUrl($url);
        if ($url === null) {
            return null;
        }

        return $this->resolveFileIdFromUrl($url);
    }

    public function resolveFileIdFromUrl(string $url): ?int
    {
        if (preg_match('~/files/(\d+)/(?:view|download)(?:\?.*)?$~', $url, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $row
     */
    private function resolveFromRow(array $row, string $context): ?string
    {
        $variants = $row['variants'] ?? null;
        if (is_string($variants) && $variants !== '') {
            $decoded  = json_decode($variants, true);
            $variants = is_array($decoded) ? $decoded : null;
        }

        if (is_array($variants) && $variants !== []) {
            foreach ($this->preferredVariantKeys($context) as $variantKey) {
                if (! isset($variants[$variantKey]) || ! is_array($variants[$variantKey])) {
                    continue;
                }

                $variantUrl = $this->normalizeUrl($variants[$variantKey]['url'] ?? null);
                if ($variantUrl !== null) {
                    return $variantUrl;
                }
            }
        }

        return $this->normalizeUrl($row['url'] ?? null);
    }

    /** @return list<string> */
    private function preferredVariantKeys(string $context): array
    {
        return match ($context) {
            'admin', 'thumbnail', 'thumb' => ['thumb', 'sm', 'md', 'lg'],
            default                       => ['lg', 'md', 'sm', 'thumb'],
        };
    }

    private function normalizeUrl(int|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $url = trim((string) $value);

        return $url !== '' ? $url : null;
    }
}
