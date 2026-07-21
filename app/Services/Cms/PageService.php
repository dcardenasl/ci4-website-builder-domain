<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\PageEntity;
use App\Interfaces\Cms\PageServiceInterface;
use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use App\Traits\Services\HasDeferredTranslations;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<PageEntity>
 */
class PageService extends BaseCrudService implements PageServiceInterface
{
    use HasDeferredTranslations;

    private \App\Libraries\Cms\SlugRedirectRecorder $slugRedirectRecorder;

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    private FileUrlResolver $fileUrlResolver;

    private FileReferenceSynchronizer $fileReferenceSynchronizer;

    private ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer;

    /**
     * @param RepositoryInterface<PageEntity> $pageRepository
     */
    public function __construct(
        RepositoryInterface $pageRepository,
        ResponseMapperInterface $responseMapper,
        \App\Libraries\Cms\SlugRedirectRecorder $slugRedirectRecorder,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        FileUrlResolver $fileUrlResolver,
        FileReferenceSynchronizer $fileReferenceSynchronizer,
        private readonly PublicPageReader $publicPageReader,
        ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer = null
    ) {
        parent::__construct($pageRepository, $responseMapper);
        $this->slugRedirectRecorder = $slugRedirectRecorder;
        $this->cacheInvalidator     = $cacheInvalidator;
        $this->fileUrlResolver      = $fileUrlResolver;
        $this->fileReferenceSynchronizer = $fileReferenceSynchronizer;
        $this->translationSynchronizer = $translationSynchronizer;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublic(string $lang): array
    {
        return $this->publicPageReader->listPublic($lang);
    }

    /**
     * @return array<string, mixed>
     */
    public function showPublic(string $lang, string $slug, bool $preview): array
    {
        return $this->publicPageReader->showPublic($lang, $slug, $preview);
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);

        if (! array_key_exists('status', $data) || $data['status'] === null || $data['status'] === '') {
            $data['status'] = 'draft';
        }

        if (! array_key_exists('sort_order', $data) || $data['sort_order'] === null || $data['sort_order'] === '') {
            $data['sort_order'] = 0;
        }

        $data = $this->normalizeCollectionIndexPayload($data);

        if (! array_key_exists('is_in_sitemap', $data) || $data['is_in_sitemap'] === null || $data['is_in_sitemap'] === '') {
            $data['is_in_sitemap'] = '1';
        }

        if (! array_key_exists('sitemap_changefreq', $data) || $data['sitemap_changefreq'] === null || $data['sitemap_changefreq'] === '') {
            $data['sitemap_changefreq'] = 'monthly';
        }

        $parentId = isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
        if ($parentId !== null) {
            $parent = $this->repository->find($parentId);
            if (!$parent) {
                throw new ValidationException(
                    lang('Pages.invalid_hierarchy'),
                    ['parent_id' => lang('Pages.parent_not_exists')]
                );
            }
        }

        return $this->deferTranslationsFromCreate($data);
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        $this->flushDeferredTranslations(fn (array $t) => $this->saveTranslations((int) $entity->id, $t));
        $this->fileReferenceSynchronizer->syncPage((int) $entity->id);
        $this->createVersionSnapshot((int) $entity->id, 'Initial creation');
        $this->cacheInvalidator->invalidate(['pages', 'collections']);
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);

        if (array_key_exists('parent_id', $data)) {
            $parentId = $data['parent_id'] !== null && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
            $this->validateParent($id, $parentId);
        }

        $data = $this->normalizeCollectionIndexPayload($data, $id);

        return $this->deferTranslationsFromUpdate($data);
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->flushDeferredTranslations(fn (array $t) => $this->saveTranslations((int) $entity->id, $t));
        $this->fileReferenceSynchronizer->syncPage((int) $entity->id);
        $this->createVersionSnapshot((int) $entity->id, 'Update page');
        $this->cacheInvalidator->invalidate(['pages', 'collections']);
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->fileReferenceSynchronizer->removeResourceReferences('page', (int) $entity->id);
        $this->cacheInvalidator->invalidate(['pages', 'collections']);
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        $pageIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var \App\Models\PageTranslationModel $translationModel */
        $translationModel = model(\App\Models\PageTranslationModel::class);
        $translations = $translationModel->whereIn('page_id', $pageIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            /** @var \App\Entities\PageTranslationEntity $translation */
            $resolvedTranslation = $this->fileUrlResolver->normalizePageTranslation([
                'og_image' => $translation->og_image ?? null,
                'og_image_file_id' => $translation->og_image_file_id !== null ? (int) $translation->og_image_file_id : null,
                'og_image_url' => $translation->og_image_url ?? null,
            ]);

            $translationsGrouped[$translation->page_id][] = [
                'language_id'      => (int) $translation->language_id,
                'slug'             => $translation->slug,
                'title'            => $translation->title,
                'excerpt'          => $translation->excerpt,
                'meta_title'       => $translation->meta_title,
                'meta_description' => $translation->meta_description,
                'og_image'         => $resolvedTranslation['og_image'] ?? null,
                'og_image_url'     => $resolvedTranslation['og_image']['url'] ?? null,
                'og_type'          => $translation->og_type,
                'canonical_url'    => $translation->canonical_url,
                'robots'           => $translation->robots,
                'schema_data'      => $translation->schema_data,
            ];
        }

        foreach ($entities as $entity) {
            $entity->translations = $translationsGrouped[$entity->id] ?? [];
        }

        return $entities;
    }

    /**
     * @param array<mixed> $translations
     */
    private function saveTranslations(int $pageId, array $translations): void
    {
        /** @var \App\Models\PageTranslationModel $translationModel */
        $translationModel = model(\App\Models\PageTranslationModel::class);

        foreach ($translations as $translation) {
            $langId = (int) $translation['language_id'];
            $slug = (string) $translation['slug'];
            $ogImage = $this->fileUrlResolver->normalizeMediaReference(
                $translation['og_image'] ?? [
                    'file_id' => $translation['og_image_file_id'] ?? null,
                    'url'     => isset($translation['og_image_url']) ? (string) $translation['og_image_url'] : null,
                ]
            );

            $existing = $translationModel
                ->where('language_id', $langId)
                ->where('slug', $slug)
                ->where('page_id !=', $pageId)
                ->first();

            if ($existing) {
                throw new ValidationException(
                    lang('Pages.slug_must_be_unique'),
                    ['slug' => lang('Pages.slug_already_taken', [$slug])]
                );
            }
        }

        // Query current translations to compare slugs
        $currentTranslations = $translationModel->where('page_id', $pageId)->findAll();
        $currentSlugs = [];
        foreach ($currentTranslations as $ct) {
            if ($ct instanceof \App\Entities\PageTranslationEntity) {
                $currentSlugs[(int)$ct->language_id] = $ct->slug;
            }
        }

        $rows = [];
        foreach ($translations as $translation) {
            $langId = (int) $translation['language_id'];
            $newSlug = (string) $translation['slug'];

            // Record redirection if slug changed
            if (isset($currentSlugs[$langId]) && $currentSlugs[$langId] !== $newSlug) {
                $oldFullPath = $this->buildCurrentFullPath($pageId, $langId);
                $this->slugRedirectRecorder->record('page', $pageId, $langId, $currentSlugs[$langId], $newSlug, $oldFullPath);
            }

            $rows[] = [
                'language_id'      => $langId,
                'slug'             => $newSlug,
                'title'            => $translation['title'],
                'excerpt'          => $translation['excerpt'] ?? null,
                'meta_title'       => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
                'og_image_file_id' => $ogImage['file_id'],
                'og_image_url'     => $ogImage['url'],
                'og_type'          => $translation['og_type'] ?? null,
                'canonical_url'    => $translation['canonical_url'] ?? null,
                'robots'           => $translation['robots'] ?? null,
                'schema_data'      => isset($translation['schema_data']) ? json_encode($translation['schema_data']) : null,
            ];
        }

        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $translationModel,
            'page_id',
            $pageId,
            $rows,
            static fn (array $row): array => $row,
        );
    }

    public function createVersionSnapshot(int $pageId, string $note = ''): void
    {
        $page = $this->repository->find($pageId);
        if (!$page) {
            return;
        }

        /** @var \App\Models\PageTranslationModel $translationModel */
        $translationModel = model(\App\Models\PageTranslationModel::class);
        $translations = $translationModel->where('page_id', $pageId)->findAll();

        $translationsData = [];
        foreach ($translations as $t) {
            if ($t instanceof \CodeIgniter\Entity\Entity) {
                $translationsData[] = $t->toArray();
            }
        }

        $snapshot = [
            'page'         => $page->toArray(),
            'translations' => $translationsData,
        ];

        /** @var \App\Models\PageVersionModel $versionModel */
        $versionModel = model(\App\Models\PageVersionModel::class);
        $lastVersion = $versionModel->where('page_id', $pageId)
            ->orderBy('version_number', 'DESC')
            ->first();

        $nextVersionNumber = 1;
        if ($lastVersion instanceof \App\Entities\PageVersionEntity) {
            $nextVersionNumber = (int) $lastVersion->version_number + 1;
        }

        $versionModel->insert([
            'page_id'        => $pageId,
            'version_number' => $nextVersionNumber,
            'snapshot'       => json_encode($snapshot),
            'note'           => $note,
        ]);
    }

    private function buildCurrentFullPath(int $pageId, int $langId): string
    {
        /** @var \App\Models\PageTranslationModel $translationModel */
        $translationModel = model(\App\Models\PageTranslationModel::class);
        /** @var \App\Models\PageModel $pageModel */
        $pageModel = model(\App\Models\PageModel::class);

        $segments = [];
        $currentId = $pageId;

        while ($currentId !== null) {
            $page = $pageModel->withDeleted()->find($currentId);
            if (!$page instanceof \App\Entities\PageEntity) {
                break;
            }

            $trans = $translationModel
                ->where('page_id', $currentId)
                ->where('language_id', $langId)
                ->first();

            if ($trans instanceof \App\Entities\PageTranslationEntity) {
                array_unshift($segments, $trans->slug);
            }

            $currentId = $page->parent_id !== null ? (int) $page->parent_id : null;
        }

        return implode('/', $segments);
    }

    private function validateParent(int $id, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($id === $parentId) {
            throw new ValidationException(
                lang('Pages.invalid_hierarchy'),
                ['parent_id' => lang('Pages.cannot_be_own_parent')]
            );
        }

        $parent = $this->repository->find($parentId);
        if (!$parent) {
            throw new ValidationException(
                lang('Pages.invalid_hierarchy'),
                ['parent_id' => lang('Pages.parent_not_exists')]
            );
        }

        $currentParentId = $parent->parent_id;
        while ($currentParentId !== null) {
            if ((int) $currentParentId === $id) {
                throw new ValidationException(
                    lang('Pages.invalid_hierarchy'),
                    ['parent_id' => lang('Pages.circular_reference')]
                );
            }

            $ancestor = $this->repository->find($currentParentId);
            $currentParentId = $ancestor ? $ancestor->parent_id : null;
        }
    }

    /**
     * Normalizes and validates the collection index relationship for pages.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeCollectionIndexPayload(array $data, ?int $pageId = null): array
    {
        $currentPageType = null;
        if ($pageId !== null) {
            $currentPage = $this->repository->find($pageId);
            if ($currentPage instanceof PageEntity) {
                $currentPageType = $currentPage->page_type;
            }
        }

        $pageType = array_key_exists('page_type', $data)
            ? (string) ($data['page_type'] ?? '')
            : ($currentPageType ?? '');

        if ($pageType !== 'collection_index') {
            $data['collection_id'] = null;
            return $data;
        }

        if (! array_key_exists('collection_id', $data) && $pageId !== null) {
            $currentPage = $this->repository->find($pageId);
            if ($currentPage instanceof PageEntity && (int) ($currentPage->collection_id ?? 0) > 0) {
                $data['collection_id'] = (int) $currentPage->collection_id;
            }
        }

        $collectionId = isset($data['collection_id']) && $data['collection_id'] !== '' ? (int) $data['collection_id'] : null;

        // At this point, we know $pageType === 'collection_index' (or we would have returned earlier)
        if ($collectionId === null) {
            throw new ValidationException(
                lang('Pages.invalid_hierarchy'),
                ['collection_id' => lang('Pages.collection_required_for_index')]
            );
        }

        $this->assertCollectionExists($collectionId);
        $this->assertCollectionIndexUniqueness($collectionId, $pageId);
        $data['collection_id'] = $collectionId;

        return $data;
    }

    private function assertCollectionExists(int $collectionId): void
    {
        /** @var \App\Models\CollectionModel $collectionModel */
        $collectionModel = model(\App\Models\CollectionModel::class);
        $collection = $collectionModel->find($collectionId);

        if (! $collection instanceof \App\Entities\CollectionEntity || (int) ($collection->is_active ?? 0) !== 1) {
            throw new ValidationException(
                lang('Pages.invalid_hierarchy'),
                ['collection_id' => lang('Pages.collection_not_exists')]
            );
        }
    }

    private function assertCollectionIndexUniqueness(int $collectionId, ?int $pageId = null): void
    {
        /** @var \App\Models\PageModel $pageModel */
        $pageModel = model(\App\Models\PageModel::class);

        $builder = $pageModel->builder()
            ->where('page_type', 'collection_index')
            ->where('collection_id', $collectionId)
            ->where('deleted_at IS NULL', null, false);

        if ($pageId !== null) {
            $builder->where('id !=', $pageId);
        }

        if ($builder->countAllResults() > 0) {
            throw new ValidationException(
                lang('Pages.invalid_hierarchy'),
                ['collection_id' => lang('Pages.collection_index_already_exists')]
            );
        }
    }

    public function isSlugAvailable(string $slug, int $languageId, ?int $currentId = null): bool
    {
        return (new \App\Models\PageTranslationModel())->isSlugAvailable($slug, $languageId, $currentId);
    }
}
