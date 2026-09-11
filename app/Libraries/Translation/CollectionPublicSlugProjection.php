<?php

declare(strict_types=1);

namespace App\Libraries\Translation;

use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Localization\PublicSlugStore;
use dcardenasl\Ci4ApiCore\Services\HasPublicSlugs;

/**
 * Bridges the legacy collection translation table to the generic public-slug
 * sidecar. The bridge is deliberately resource-specific only at this boundary
 * so the store and slug rules remain reusable by future CMS entities.
 */
final class CollectionPublicSlugProjection
{
    use HasPublicSlugs;

    private LegacyCollectionTranslationStore $translationStore;

    /** @param BaseConnection<mixed, mixed> $database */
    public function __construct(
        private readonly BaseConnection $database,
        private readonly PublicSlugStore $store,
    ) {
        $this->translationStore = new LegacyCollectionTranslationStore($database);
        $this->slugStore = $store;
        $this->slugResourceType = 'collection';
        $this->slugSourceField = 'name';
    }

    public function sync(int $collectionId): void
    {
        if ($collectionId < 1) {
            return;
        }

        $translations = [];
        foreach ($this->translationStore->manualSlugs($collectionId) as $locale => $slug) {
            $translations[] = ['locale' => $locale, 'slug' => $slug];
        }

        $payload = ['translations' => $translations];
        $manualSlugs = $this->extractManualSlugs($payload);
        $this->syncPublicSlugs((object) ['id' => $collectionId]);

        // The legacy table exposes an explicit slug field while the core
        // lifecycle derives its source field from the translation store.
        // Reapply that explicit editor value after the generic lifecycle so
        // legacy slugs keep their established public URLs during migration.
        if ($manualSlugs !== []) {
            $this->store->syncForResource('collection', $collectionId, [], $manualSlugs);
        }
    }

    /** @return int number of collections projected */
    public function backfill(): int
    {
        $result = $this->database->table('cms_collections')->select('id')->get();
        if ($result === false) {
            return 0;
        }

        $count = 0;
        foreach ($result->getResultArray() as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $this->sync($id);
            $count++;
        }

        return $count;
    }

    /** @return array<string, string> */
    public function slugs(int $collectionId): array
    {
        return $this->store->slugsForResource('collection', $collectionId);
    }

    public function resolve(string $slug): ?int
    {
        return $this->store->resolveResourceId('collection', $slug);
    }
}
