<?php

declare(strict_types=1);

namespace App\Database\Migrations\Concerns;

use App\Libraries\Cms\FileUrlResolver;

/**
 * Minimal, DB-free FileUrlResolver used by the media_reference migrations
 * (2026-07-15-090000, 2026-07-16-090000/120000/130000). Migrations run before
 * the app's file service is necessarily reachable, so this resolves hub file
 * URLs by convention (`/files/{id}/view`) instead of calling the Hub API —
 * good enough for normalizing already-stored legacy references.
 *
 * Extracted after four migrations each carried a byte-identical anonymous
 * class implementing this (2026-07-16 dedup pass).
 */
final class LegacyMediaReferenceResolver extends FileUrlResolver
{
    public function __construct()
    {
    }

    public function resolve(int $fileId, string $context = 'public'): ?string
    {
        return $fileId > 0 ? '/files/' . $fileId . '/view' : null;
    }

    /**
     * @param list<int> $fileIds
     * @return array<int, string>
     */
    public function resolveMany(array $fileIds, string $context = 'public'): array
    {
        $map = [];
        foreach ($fileIds as $fileId) {
            if (is_numeric($fileId) && (int) $fileId > 0) {
                $map[(int) $fileId] = '/files/' . (int) $fileId . '/view';
            }
        }

        return $map;
    }

    public function resolveUrlValue(int|string|null $fileId, ?string $currentUrl = null, string $context = 'public'): ?string
    {
        if (is_numeric($fileId) && (int) $fileId > 0) {
            return '/files/' . (int) $fileId . '/view';
        }

        return $currentUrl !== null && trim($currentUrl) !== '' ? trim($currentUrl) : null;
    }

    public function resolveFileIdFromValue(int|string|null $fileId, ?string $url = null): ?int
    {
        if (is_numeric($fileId) && (int) $fileId > 0) {
            return (int) $fileId;
        }

        if (is_string($url) && preg_match('~/files/(\d+)/(?:view|download)(?:\?.*)?$~', $url, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    public function resolveFileIdFromUrl(string $url): ?int
    {
        if (preg_match('~/files/(\d+)/(?:view|download)(?:\?.*)?$~', $url, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * @return array{source_kind: string, file_id: int|null, url: string|null}
     */
    public function normalizeMediaReference(mixed $reference, string $context = 'public'): array
    {
        if (! is_array($reference)) {
            $reference = is_scalar($reference) ? ['url' => (string) $reference] : [];
        }

        $fileId = $this->resolveFileIdFromValue($reference['file_id'] ?? null, isset($reference['url']) ? (string) $reference['url'] : null);
        $url = isset($reference['url']) && is_scalar($reference['url']) ? trim((string) $reference['url']) : null;

        return [
            'source_kind' => $fileId !== null ? 'hub_file' : 'external_url',
            'file_id' => $fileId,
            'url' => $url !== '' ? $url : null,
        ];
    }
}
