<?php

declare(strict_types=1);

namespace App\Libraries\Cms;

use CodeIgniter\Database\BaseConnection;

/**
 * Resolves human-readable titles for a batch of (owner_type, owner_id) pairs —
 * the "who references this resource" lookup shared by every CMS usage report
 * (forms embedded in blocks, block types used by instances, ...). Always
 * batches by owner type (one query per type, never per row) and applies the
 * same locale -> CMS default language -> first-available-translation fallback
 * priority everywhere, so usage reports can't regress into an N+1 query or an
 * arbitrary language pick the way two independent copies of this logic once did
 * (FormService and BlockTypeService, unified 2026-07-19).
 */
class OwnerUsageResolver
{
    /** @var array<string, array{table: string, fk: string, column: string}> */
    private const OWNER_TABLES = [
        'page' => ['table' => 'cms_page_translations', 'fk' => 'page_id', 'column' => 'title'],
        'entry' => ['table' => 'cms_entry_translations', 'fk' => 'entry_id', 'column' => 'title'],
        'collection' => ['table' => 'cms_collection_translations', 'fk' => 'collection_id', 'column' => 'name'],
        'menu' => ['table' => 'cms_menu_translations', 'fk' => 'menu_id', 'column' => 'name'],
    ];

    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    public function __construct(private readonly BaseConnection $db)
    {
    }

    /**
     * @param list<array{owner_type: string, owner_id: int}> $owners
     * @return array<string, string> title keyed by "{owner_type}:{owner_id}"
     */
    public function resolveTitles(array $owners, ?string $locale = null): array
    {
        $defaultLanguageId = $this->resolveLanguageId(null);
        $preferredLanguageId = (is_string($locale) && trim($locale) !== '')
            ? $this->resolveLanguageId($locale)
            : $defaultLanguageId;

        $languagePriority = array_values(array_unique(array_filter(
            [$preferredLanguageId, $defaultLanguageId],
            static fn (?int $languageId): bool => $languageId !== null && $languageId > 0
        )));

        $titles = [];

        foreach (self::OWNER_TABLES as $ownerType => $definition) {
            $ownerIds = [];
            foreach ($owners as $owner) {
                if (($owner['owner_type'] ?? null) !== $ownerType) {
                    continue;
                }

                $ownerId = (int) ($owner['owner_id'] ?? 0);
                if ($ownerId > 0) {
                    $ownerIds[] = $ownerId;
                }
            }

            $ownerIds = array_values(array_unique($ownerIds));
            if ($ownerIds === []) {
                continue;
            }

            $result = $this->db->table($definition['table'])
                ->select($definition['fk'] . ' as owner_id, language_id, ' . $definition['column'] . ' as title')
                ->whereIn($definition['fk'], $ownerIds)
                ->orderBy('language_id', 'ASC')
                ->get();
            $translationRows = $result ? $result->getResultArray() : [];

            /** @var array<int, array<int, string>> $byOwnerAndLanguage */
            $byOwnerAndLanguage = [];
            foreach ($translationRows as $translationRow) {
                $ownerId = (int) ($translationRow['owner_id'] ?? 0);
                $languageId = (int) ($translationRow['language_id'] ?? 0);
                $title = trim((string) ($translationRow['title'] ?? ''));
                if ($ownerId > 0 && $languageId > 0 && $title !== '') {
                    $byOwnerAndLanguage[$ownerId][$languageId] = $title;
                }
            }

            foreach ($ownerIds as $ownerId) {
                $available = $byOwnerAndLanguage[$ownerId] ?? [];
                foreach ($languagePriority as $languageId) {
                    if (isset($available[$languageId])) {
                        $titles[$ownerType . ':' . $ownerId] = $available[$languageId];
                        continue 2;
                    }
                }

                $firstTitle = reset($available);
                if (is_string($firstTitle) && $firstTitle !== '') {
                    $titles[$ownerType . ':' . $ownerId] = $firstTitle;
                }
            }
        }

        return $titles;
    }

    private function resolveLanguageId(?string $locale): ?int
    {
        if (is_string($locale) && trim($locale) !== '') {
            $result = $this->db->table('cms_languages')
                ->select('id')
                ->where('code', trim($locale))
                ->get();
            $row = $result ? $result->getRowArray() : null;

            if (is_array($row) && isset($row['id'])) {
                return (int) $row['id'];
            }
        }

        $result = $this->db->table('cms_languages')
            ->select('id')
            ->where('is_default', 1)
            ->get();
        $row = $result ? $result->getRowArray() : null;

        if (is_array($row) && isset($row['id'])) {
            return (int) $row['id'];
        }

        $result = $this->db->table('cms_languages')
            ->select('id')
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->get();
        $row = $result ? $result->getRowArray() : null;

        return is_array($row) && isset($row['id']) ? (int) $row['id'] : null;
    }
}
