<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;

/**
 * Batch-resolves the cms_entry_categories / cms_entry_tags pivot tables for a
 * set of entry ids in one query per table (never one query per entry). Used
 * by both EntryService (admin enrichment — ids only) and PublicEntryReader
 * (public reads — localized name/slug with default-language fallback), which
 * independently reimplemented the same batching pattern over the same two
 * tables before being unified here (2026-07-19).
 */
class EntryTaxonomyPivotResolver
{
    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    public function __construct(private readonly BaseConnection $db)
    {
    }

    /**
     * @param list<int> $entryIds
     * @return array<int, list<array{id: int, sort_order: int}>>
     */
    public function resolveCategoryIds(array $entryIds): array
    {
        if ($entryIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
        $sql = "
            SELECT entry_id, category_id, sort_order
            FROM cms_entry_categories
            WHERE entry_id IN ({$placeholders})
            ORDER BY entry_id ASC, sort_order ASC, category_id ASC
        ";

        $result = $this->db->query($sql, $entryIds);
        if (! $result instanceof BaseResult) {
            return [];
        }

        $map = [];
        foreach ($result->getResultArray() as $row) {
            $entryId = (int) ($row['entry_id'] ?? 0);
            if ($entryId <= 0) {
                continue;
            }

            $map[$entryId][] = [
                'id' => (int) ($row['category_id'] ?? 0),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
            ];
        }

        return $map;
    }

    /**
     * @param list<int> $entryIds
     * @return array<int, list<array{id: int}>>
     */
    public function resolveTagIds(array $entryIds): array
    {
        if ($entryIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
        $sql = "
            SELECT entry_id, tag_id
            FROM cms_entry_tags
            WHERE entry_id IN ({$placeholders})
            ORDER BY entry_id ASC, tag_id ASC
        ";

        $result = $this->db->query($sql, $entryIds);
        if (! $result instanceof BaseResult) {
            return [];
        }

        $map = [];
        foreach ($result->getResultArray() as $row) {
            $entryId = (int) ($row['entry_id'] ?? 0);
            if ($entryId <= 0) {
                continue;
            }

            $map[$entryId][] = [
                'id' => (int) ($row['tag_id'] ?? 0),
            ];
        }

        return $map;
    }

    /**
     * @param list<int> $entryIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function resolveLocalizedCategories(array $entryIds, int $langId, int $defaultLangId): array
    {
        if ($entryIds === []) {
            return [];
        }

        $langIds           = array_unique([$langId, $defaultLangId]);
        $langPlaceholders  = implode(',', array_fill(0, count($langIds), '?'));
        $entryPlaceholders = implode(',', array_fill(0, count($entryIds), '?'));

        $sql = "
            SELECT ec.entry_id, ec.category_id, ec.sort_order,
                   ct.name, ct.slug, ct.description, ct.language_id
            FROM cms_entry_categories ec
            LEFT JOIN cms_category_translations ct
                ON ct.category_id = ec.category_id
               AND ct.language_id IN ({$langPlaceholders})
            WHERE ec.entry_id IN ({$entryPlaceholders})
            ORDER BY ec.entry_id ASC, ec.sort_order ASC, ct.language_id DESC
        ";

        $result = $this->db->query($sql, array_merge($langIds, $entryIds));
        if (! $result instanceof BaseResult) {
            return [];
        }

        $map      = [];
        $seenCats = [];

        foreach ($result->getResultArray() as $row) {
            $eid   = (int) $row['entry_id'];
            $catId = (int) $row['category_id'];
            $lid   = (int) ($row['language_id'] ?? 0);

            if (!isset($map[$eid])) {
                $map[$eid]      = [];
                $seenCats[$eid] = [];
            }

            if (isset($seenCats[$eid][$catId]) && $lid !== $langId) {
                continue;
            }

            $seenCats[$eid][$catId] = true;
            $map[$eid][] = [
                'id'          => $catId,
                'name'        => $row['name'],
                'slug'        => $row['slug'],
                'description' => $row['description'],
                'is_fallback' => $lid !== $langId,
            ];
        }

        return $map;
    }

    /**
     * @param list<int> $entryIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function resolveLocalizedTags(array $entryIds, int $langId, int $defaultLangId): array
    {
        if ($entryIds === []) {
            return [];
        }

        $langIds           = array_unique([$langId, $defaultLangId]);
        $langPlaceholders  = implode(',', array_fill(0, count($langIds), '?'));
        $entryPlaceholders = implode(',', array_fill(0, count($entryIds), '?'));

        $sql = "
            SELECT et.entry_id, et.tag_id,
                   tt.name, tt.slug, tt.language_id
            FROM cms_entry_tags et
            LEFT JOIN cms_tag_translations tt
                ON tt.tag_id = et.tag_id
               AND tt.language_id IN ({$langPlaceholders})
            WHERE et.entry_id IN ({$entryPlaceholders})
            ORDER BY et.entry_id ASC, et.tag_id ASC, tt.language_id DESC
        ";

        $result = $this->db->query($sql, array_merge($langIds, $entryIds));
        if (! $result instanceof BaseResult) {
            return [];
        }

        $map      = [];
        $seenTags = [];

        foreach ($result->getResultArray() as $row) {
            $eid   = (int) $row['entry_id'];
            $tagId = (int) $row['tag_id'];
            $lid   = (int) ($row['language_id'] ?? 0);

            if (!isset($map[$eid])) {
                $map[$eid]      = [];
                $seenTags[$eid] = [];
            }

            if (isset($seenTags[$eid][$tagId]) && $lid !== $langId) {
                continue;
            }

            $seenTags[$eid][$tagId] = true;
            $map[$eid][] = [
                'id'          => $tagId,
                'name'        => $row['name'],
                'slug'        => $row['slug'],
                'is_fallback' => $lid !== $langId,
            ];
        }

        return $map;
    }
}
