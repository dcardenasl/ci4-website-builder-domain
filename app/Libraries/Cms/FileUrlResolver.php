<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Canonical file URL resolver.
 *
 * Resolves file IDs to public URLs and normalizes file-bearing payloads so the
 * CMS never has to trust admin preview URLs as persisted source of truth.
 */
class FileUrlResolver
{
    /** @var BaseConnection<mixed, mixed> */
    private BaseConnection $db;

    /**
     * @param BaseConnection<mixed, mixed>|null $db
     */
    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function resolve(int $fileId, string $context = 'public'): ?string
    {
        if ($fileId <= 0) {
            return null;
        }

        if (! $this->filesTableExists()) {
            return null;
        }

        $result = $this->db->table('files')
            ->select('id, url, path, storage_driver, variants')
            ->where('id', $fileId)
            ->get();
        $row = $result ? $result->getRowArray() : null;

        if (! is_array($row) || $row === []) {
            return null;
        }

        return $this->resolveFromRow($row, $context);
    }

    /**
     * @param list<int> $fileIds
     * @return array<int, string>
     */
    public function resolveMany(array $fileIds, string $context = 'public'): array
    {
        if (! $this->filesTableExists()) {
            return [];
        }

        $fileIds = array_values(array_unique(array_filter($fileIds, static fn ($fileId): bool => is_numeric($fileId) && (int) $fileId > 0)));
        if ($fileIds === []) {
            return [];
        }

        $result = $this->db->table('files')
            ->select('id, url, path, storage_driver, variants')
            ->whereIn('id', $fileIds)
            ->get();
        $rows = $result ? $result->getResultArray() : [];

        $urls = [];
        foreach ($rows as $row) {
            $fileId = (int) ($row['id'] ?? 0);
            if ($fileId <= 0) {
                continue;
            }

            $resolved = $this->resolveFromRow($row, $context);
            if ($resolved !== null && $resolved !== '') {
                $urls[$fileId] = $resolved;
            }
        }

        return $urls;
    }

    /**
     * Canonicalize a file-bearing URL field.
     *
     * If a file ID exists, it always wins. If the URL looks like an admin preview
     * or matches a known file URL, it is rewritten to the backend-resolved URL.
     * Otherwise the original value is preserved for non-CMS external assets.
     */
    public function resolveUrlValue(int|string|null $fileId, ?string $currentUrl = null, string $context = 'public'): ?string
    {
        $normalizedFileId = is_numeric($fileId) ? (int) $fileId : null;
        if ($normalizedFileId !== null && $normalizedFileId > 0) {
            $resolved = $this->resolve($normalizedFileId, $context);
            if ($resolved !== null && $resolved !== '') {
                return $resolved;
            }

            $currentUrl = $this->normalizeUrl($currentUrl);
            if ($currentUrl !== null) {
                return $currentUrl;
            }

            return '/files/' . $normalizedFileId . '/view';
        }

        $currentUrl = $this->normalizeUrl($currentUrl);
        if ($currentUrl === null) {
            return null;
        }

        $resolvedFileId = $this->resolveFileIdFromUrl($currentUrl);
        if ($resolvedFileId !== null) {
            return $this->resolve($resolvedFileId, $context) ?? $currentUrl;
        }

        $fileId = $this->resolveFileIdFromCanonicalUrl($currentUrl);
        if ($fileId !== null) {
            return $this->resolve($fileId, $context) ?? $currentUrl;
        }

        return $currentUrl;
    }

    /**
     * @param array<string, mixed> $translation
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
     * @param array<string, mixed> $translation
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
     * @param array<string, mixed> $blockData
     * @param array<string, array<string, mixed>> $schemaFields
     * @return array<string, mixed>
     */
    public function normalizeBlockData(array $blockData, array $schemaFields, string $context = 'public'): array
    {
        foreach ($schemaFields as $fieldKey => $fieldDef) {
            $type = strtolower((string) ($fieldDef['type'] ?? 'string'));

            if ($type === 'file') {
                $fileIdKey = $fieldKey . '_file_id';
                $urlKey    = $fieldKey . '_url';
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
     * URLs are reverse-resolved when possible so legacy payloads still backfill
     * references even if the file ID was not persisted.
     *
     * @param array<string, mixed> $blockData
     * @param array<string, array<string, mixed>> $schemaFields
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

        return array_values(array_unique(array_filter($fileIds, static fn ($fileId): bool => is_int($fileId) && $fileId > 0)));
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

        return $this->resolveFileIdFromUrl($url) ?? $this->resolveFileIdFromCanonicalUrl($url);
    }

    public function resolveFileIdFromUrl(string $url): ?int
    {
        if (preg_match('~/files/(\d+)/(?:view|download)(?:\?.*)?$~', $url, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    public function resolveFileIdFromCanonicalUrl(string $url): ?int
    {
        if (! $this->filesTableExists()) {
            return null;
        }

        $result = $this->db->table('files')
            ->select('id')
            ->where('url', $url)
            ->get();
        $row = $result ? $result->getRowArray() : null;

        if (! is_array($row) || ! isset($row['id'])) {
            return null;
        }

        return (int) $row['id'];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveFromRow(array $row, string $context): ?string
    {
        $variants = $row['variants'] ?? null;
        if (is_string($variants) && $variants !== '') {
            $decoded = json_decode($variants, true);
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

        $url = $this->normalizeUrl($row['url'] ?? null);
        if ($url !== null) {
            return $url;
        }

        $path = $this->normalizeUrl($row['path'] ?? null);
        if ($path !== null) {
            return base_url('uploads/' . ltrim($path, '/'));
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function preferredVariantKeys(string $context): array
    {
        return match ($context) {
            'admin', 'thumbnail', 'thumb' => ['thumb', 'sm', 'md', 'lg'],
            'og', 'hero', 'banner', 'social' => ['lg', 'md', 'sm', 'thumb'],
            default => ['lg', 'md', 'sm', 'thumb'],
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

    private function filesTableExists(): bool
    {
        try {
            return $this->db->tableExists('files');
        } catch (\Throwable) {
            return false;
        }
    }
}
