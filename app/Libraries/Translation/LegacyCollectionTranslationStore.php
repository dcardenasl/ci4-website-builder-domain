<?php

declare(strict_types=1);

namespace App\Libraries\Translation;

use CodeIgniter\Database\BaseConnection;

/**
 * Read adapter consumed by the core HasPublicSlugs lifecycle.
 *
 * The base starter still stores collection translations in its legacy table.
 * Keeping that detail here lets the generic core trait own slug extraction,
 * fallback and sidecar synchronization until the EAV migration is intentional.
 */
final class LegacyCollectionTranslationStore
{
    /** @param BaseConnection<mixed, mixed> $database */
    public function __construct(private readonly BaseConnection $database)
    {
    }

    /**
     * @return list<array{locale: string, fields: array<string, string>}>
     */
    public function forResource(string $resourceType, int $resourceId): array
    {
        if ($resourceType !== 'collection' || $resourceId < 1) {
            return [];
        }

        $result = $this->database->table('cms_collection_translations')
            ->select('cms_languages.code, cms_collection_translations.name')
            ->join('cms_languages', 'cms_languages.id = cms_collection_translations.language_id')
            ->where('cms_collection_translations.collection_id', $resourceId)
            ->get();

        if ($result === false) {
            return [];
        }

        $rows = [];
        foreach ($result->getResultArray() as $row) {
            $locale = strtolower(str_replace('_', '-', trim((string) ($row['code'] ?? ''))));
            if ($locale === '') {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $rows[] = ['locale' => $locale, 'fields' => $name !== '' ? ['name' => $name] : []];
        }

        return $rows;
    }

    /** @return array<string, string> */
    public function manualSlugs(int $resourceId): array
    {
        if ($resourceId < 1) {
            return [];
        }

        $result = $this->database->table('cms_collection_translations')
            ->select('cms_languages.code, cms_collection_translations.slug')
            ->join('cms_languages', 'cms_languages.id = cms_collection_translations.language_id')
            ->where('cms_collection_translations.collection_id', $resourceId)
            ->get();

        if ($result === false) {
            return [];
        }

        $slugs = [];
        foreach ($result->getResultArray() as $row) {
            $locale = strtolower(str_replace('_', '-', trim((string) ($row['code'] ?? ''))));
            $slug = trim((string) ($row['slug'] ?? ''), " \t\n\r\0\x0B/");
            if ($locale !== '' && $slug !== '') {
                $slugs[$locale] = $slug;
            }
        }

        return $slugs;
    }
}
