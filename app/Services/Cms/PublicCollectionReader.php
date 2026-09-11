<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\CollectionEntity;
use App\Entities\LanguageEntity;
use App\Entities\PageEntity;
use App\Libraries\Cms\SlugRouter;
use App\Libraries\Cms\TranslationResolver;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;

/**
 * Read-optimized public (unauthenticated) collection listing: translation
 * resolution, localized slugs, and collection_index page resolution.
 *
 * Extracted from PublicCollectionController, which used to run all of this
 * inline. Composed by CollectionService for listPublic(), mirroring the
 * EntryService/PublicEntryReader split.
 */
class PublicCollectionReader
{
    /**
     * @param RepositoryInterface<PageEntity> $pageRepository
     */
    public function __construct(
        private readonly RepositoryInterface $pageRepository,
        private readonly TranslationResolver $translationResolver,
        private readonly SlugRouter $slugRouter,
        private readonly ?\App\Libraries\Translation\CollectionPublicSlugProjection $publicSlugProjection = null,
    ) {
    }

    /**
     * @param list<CollectionEntity> $collections
     * @param list<LanguageEntity> $activeLanguages
     * @return list<array<string, mixed>>
     */
    public function listPublic(array $collections, array $activeLanguages, string $lang): array
    {
        $resolvedCollections = [];

        foreach ($collections as $collection) {
            if (!$collection instanceof CollectionEntity) {
                continue;
            }

            $resolved = $this->translationResolver->resolve('collection', (int) $collection->id, $lang);
            $indexPage = $this->resolveCollectionIndexPage((int) $collection->id);
            $indexPageData = $indexPage !== null
                ? $this->resolveIndexPageData((int) $indexPage->id, $activeLanguages)
                : null;

            $localizedSlugs = $this->publicSlugs((int) $collection->id, $activeLanguages);

            $collectionPayload = array_merge($collection->toArray(), [
                'slug'                     => $resolved['slug'] ?? null,
                'name'                     => $resolved['name'] ?? '',
                'description'              => $resolved['description'] ?? null,
                'listing_title'            => $resolved['listing_title'] ?? null,
                'listing_intro'            => $resolved['listing_intro'] ?? null,
                'default_meta_title'       => $resolved['default_meta_title'] ?? null,
                'default_meta_description' => $resolved['default_meta_description'] ?? null,
                'entry_cta_label'          => $resolved['entry_cta_label'] ?? null,
                'localized_slugs'          => $localizedSlugs,
                'is_fallback'              => $resolved['is_fallback'] ?? false,
                'index_page'               => $indexPageData,
            ]);

            $collectionPayload['listing_title'] = collection_display_title($collectionPayload);
            $collectionPayload['listing_intro'] = collection_display_intro($collectionPayload);

            $resolvedCollections[] = $collectionPayload;
        }

        return $resolvedCollections;
    }

    /**
     * @param list<LanguageEntity> $activeLanguages
     * @return array<string, string>
     */
    private function publicSlugs(int $collectionId, array $activeLanguages): array
    {
        $sidecarSlugs = $this->publicSlugProjection?->slugs($collectionId) ?? [];
        if ($sidecarSlugs !== []) {
            return $sidecarSlugs;
        }

        $localizedSlugs = [];
        foreach ($activeLanguages as $activeLanguage) {
            if (! $activeLanguage instanceof LanguageEntity) {
                continue;
            }
            $translation = $this->translationResolver->resolve('collection', $collectionId, $activeLanguage->code);
            $slug = $translation['slug'] ?? null;
            if (is_string($slug) && $slug !== '') {
                $localizedSlugs[$activeLanguage->code] = $slug;
            }
        }

        return $localizedSlugs;
    }

    private function resolveCollectionIndexPage(int $collectionId): ?PageEntity
    {
        $page = $this->pageRepository->getModel()
            ->where('collection_id', $collectionId)
            ->where('page_type', 'collection_index')
            ->where('status', 'published')
            ->first();

        return $page instanceof PageEntity ? $page : null;
    }

    /**
     * @param list<LanguageEntity> $activeLanguages
     * @return array<string, mixed>
     */
    private function resolveIndexPageData(int $pageId, array $activeLanguages): array
    {
        $localizedSlugs = [];
        $localizedUrls = [];

        foreach ($activeLanguages as $language) {
            if (!$language instanceof LanguageEntity) {
                continue;
            }

            $slug = $this->slugRouter->resolveSlug($language->code, 'page', $pageId);
            if (is_string($slug) && $slug !== '') {
                $localizedSlugs[$language->code] = $slug;
                $localizedUrls[$language->code] = site_url('/' . $language->code . '/' . ltrim($slug, '/'));
            }
        }

        return [
            'id'              => $pageId,
            'localized_slugs' => $localizedSlugs,
            'localized_urls'  => $localizedUrls,
        ];
    }
}
