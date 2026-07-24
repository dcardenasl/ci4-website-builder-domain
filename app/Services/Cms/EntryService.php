<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\EntrySetCategoriesRequestDTO;
use App\DTO\Request\Cms\EntrySetTagsRequestDTO;
use App\DTO\Request\Cms\EntrySyncTaxonomyRequestDTO;
use App\DTO\Request\Cms\PublicEntryIndexRequestDTO;
use App\DTO\Request\Cms\PublicEntryShowRequestDTO;
use App\Entities\EntryEntity;
use App\Interfaces\Cms\EntryServiceInterface;
use App\Libraries\Cms\EntryTaxonomyPivotResolver;
use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use App\Traits\Services\HasDeferredTranslations;
use dcardenasl\Ci4ApiCore\Dto\Common\PayloadResponseDTO;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<EntryEntity>
 */
class EntryService extends BaseCrudService implements EntryServiceInterface
{
    use HasDeferredTranslations;

    private \App\Libraries\Cms\SlugRedirectRecorder $slugRedirectRecorder;

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    private FileUrlResolver $fileUrlResolver;

    private FileReferenceSynchronizer $fileReferenceSynchronizer;

    private EntryBlockTemplateInitializer $blockTemplateInitializer;

    private PublicEntryReader $publicReader;

    private EntryTaxonomyPivotResolver $taxonomyPivotResolver;

    private \App\Libraries\Cms\TranslationResolver $translationResolver;

    private ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer;

    /**
     * @param RepositoryInterface<EntryEntity> $entryRepository
     */
    public function __construct(
        RepositoryInterface $entryRepository,
        ResponseMapperInterface $responseMapper,
        \App\Libraries\Cms\SlugRedirectRecorder $slugRedirectRecorder,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        FileUrlResolver $fileUrlResolver,
        FileReferenceSynchronizer $fileReferenceSynchronizer,
        \App\Libraries\Cms\TranslationResolver $translationResolver,
        PublicEntryReader $publicReader,
        EntryTaxonomyPivotResolver $taxonomyPivotResolver,
        EntryBlockTemplateInitializer $blockTemplateInitializer,
        ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer = null
    ) {
        parent::__construct($entryRepository, $responseMapper);
        $this->slugRedirectRecorder = $slugRedirectRecorder;
        $this->cacheInvalidator     = $cacheInvalidator;
        $this->fileUrlResolver      = $fileUrlResolver;
        $this->fileReferenceSynchronizer = $fileReferenceSynchronizer;
        $this->translationResolver = $translationResolver;
        $this->publicReader = $publicReader;
        $this->taxonomyPivotResolver = $taxonomyPivotResolver;
        $this->blockTemplateInitializer = $blockTemplateInitializer;
        $this->translationSynchronizer = $translationSynchronizer;
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);

        $collectionId = isset($data['collection_id']) ? (int) $data['collection_id'] : null;
        $collection = null;
        if ($collectionId !== null) {
            $collectionModel = model(\App\Models\CollectionModel::class);
            $collection = $collectionModel->find($collectionId);
            if (!$collection) {
                throw new ValidationException(
                    lang('Entries.invalid_collection'),
                    ['collection_id' => lang('Entries.collection_not_exists')]
                );
            }
        }

        if ($collection instanceof \App\Entities\CollectionEntity) {
            $data = $this->applyCreationDefaults($data, $collection);
        }

        return $this->deferTranslationsFromCreate($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function applyCreationDefaults(array $data, \App\Entities\CollectionEntity $collection): array
    {
        $data['workflow_status'] = (string) ($data['workflow_status'] ?? 'draft');

        if ((bool) $collection->requires_approval && $data['workflow_status'] === 'published') {
            $data['workflow_status'] = 'in_review';
        }

        $data['view_count'] = isset($data['view_count']) && $data['view_count'] !== '' ? (int) $data['view_count'] : 0;
        $data['sort_order'] = isset($data['sort_order']) && $data['sort_order'] !== '' ? (int) $data['sort_order'] : 0;
        $data['is_in_sitemap'] = array_key_exists('is_in_sitemap', $data) ? (int) (bool) $data['is_in_sitemap'] : 1;

        if (($data['sitemap_priority'] ?? null) === null || $data['sitemap_priority'] === '') {
            $data['sitemap_priority'] = $collection->default_sitemap_priority !== null
                ? (float) $collection->default_sitemap_priority
                : 0.5;
        }

        if (($data['sitemap_changefreq'] ?? null) === null || $data['sitemap_changefreq'] === '') {
            $data['sitemap_changefreq'] = (string) ($collection->default_changefreq ?: 'monthly');
        }

        if ($data['workflow_status'] === 'published') {
            if (($data['published_at'] ?? null) === null || $data['published_at'] === '') {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
            $data['scheduled_at'] = null;
        } elseif ($data['workflow_status'] === 'draft' || $data['workflow_status'] === 'in_review') {
            if (! array_key_exists('published_at', $data) || ($data['published_at'] ?? null) === '') {
                $data['published_at'] = null;
            }
        }

        return $data;
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);

        $this->flushDeferredTranslations(fn (array $t) => $this->saveTranslations((int) $entity->id, $t));

        // wizard_extra is a transient payload: pre-fill matching block fields, then clear it.
        $rawExtra = ($entity instanceof EntryEntity) ? $entity->wizard_extra : null;
        $wizardExtra = null;
        if ($rawExtra !== null) {
            $extraArray = is_object($rawExtra) ? (array) $rawExtra : (is_array($rawExtra) ? $rawExtra : null);
            if ($extraArray !== null && $extraArray !== []) {
                $wizardExtra = $extraArray;
            }
        }

        $consumedKeys = $this->blockTemplateInitializer->initialize($entity, $wizardExtra);

        if ($wizardExtra !== null) {
            $residual = array_diff_key($wizardExtra, array_flip($consumedKeys));

            /** @var \App\Models\EntryModel $entryModel */
            $entryModel = model(\App\Models\EntryModel::class);
            $entryModel->where('id', (int) $entity->id)->set(
                'wizard_extra',
                !empty($residual)
                    ? json_encode($residual, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null
            )->update();
        }

        $this->fileReferenceSynchronizer->syncEntry((int) $entity->id);
        $this->createVersionSnapshot((int) $entity->id, 'Initial creation');
        $this->cacheInvalidator->invalidate(['entries']);
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);

        if (array_key_exists('collection_id', $data)) {
            $collectionId = $data['collection_id'] !== null ? (int) $data['collection_id'] : null;
            if ($collectionId !== null) {
                $collectionModel = model(\App\Models\CollectionModel::class);
                $collection = $collectionModel->find($collectionId);
                if (!$collection) {
                    throw new ValidationException(
                        lang('Entries.invalid_collection'),
                        ['collection_id' => lang('Entries.collection_not_exists')]
                    );
                }
            }
        }

        return $this->deferTranslationsFromUpdate($data);
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->flushDeferredTranslations(fn (array $t) => $this->saveTranslations((int) $entity->id, $t));
        $this->fileReferenceSynchronizer->syncEntry((int) $entity->id);
        $this->createVersionSnapshot((int) $entity->id, 'Update entry');
        $this->cacheInvalidator->invalidate(['entries']);
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->fileReferenceSynchronizer->removeResourceReferences('entry', (int) $entity->id);
        $this->cacheInvalidator->invalidate(['entries']);
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        $entryIds = array_values(array_map(
            static fn ($entity) => (int) $entity->id,
            $entities
        ));

        /** @var \App\Models\EntryTranslationModel $translationModel */
        $translationModel = model(\App\Models\EntryTranslationModel::class);
        $translations = $translationModel->whereIn('entry_id', $entryIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            /** @var \App\Entities\EntryTranslationEntity $translation */
            $resolvedTranslation = $this->fileUrlResolver->normalizeEntryTranslation([
                'featured_image' => $translation->featured_image ?? null,
                'featured_file_id' => $translation->featured_file_id !== null ? (int) $translation->featured_file_id : null,
                'featured_image_url' => $translation->featured_image_url,
                'og_image' => $translation->og_image ?? null,
                'og_image_file_id' => $translation->og_image_file_id !== null ? (int) $translation->og_image_file_id : null,
                'og_image_url' => $translation->og_image_url ?? null,
            ]);

            $translationsGrouped[$translation->entry_id][] = [
                'language_id'      => (int) $translation->language_id,
                'slug'             => $translation->slug,
                'title'            => $translation->title,
                'excerpt'          => $translation->excerpt,
                'featured_image'   => $resolvedTranslation['featured_image'] ?? null,
                'meta_title'       => $translation->meta_title,
                'meta_description' => $translation->meta_description,
                'og_image'         => $resolvedTranslation['og_image'] ?? null,
                'og_type'          => $translation->og_type,
                'canonical_url'    => $translation->canonical_url,
                'robots'           => $translation->robots,
                'schema_data'      => $translation->schema_data,
            ];
        }

        $collectionIds = array_unique(array_map(fn ($e) => (int) $e->collection_id, $entities));
        /** @var \App\Models\CollectionModel $collectionModel */
        $collectionModel = model(\App\Models\CollectionModel::class);
        $collections = $collectionModel->whereIn('id', $collectionIds)->findAll();
        $collectionKeyMap = [];
        foreach ($collections as $col) {
            if ($col instanceof \App\Entities\CollectionEntity) {
                $collectionKeyMap[(int) $col->id] = $col->collection_key;
            }
        }

        $categoryMap = $this->taxonomyPivotResolver->resolveCategoryIds($entryIds);
        $tagMap      = $this->taxonomyPivotResolver->resolveTagIds($entryIds);

        foreach ($entities as $entity) {
            $entityTranslations = $translationsGrouped[$entity->id] ?? [];
            $entity->translations = $entityTranslations;
            $entity->title = $entityTranslations[0]['title'] ?? null;
            $entity->slug = $entityTranslations[0]['slug'] ?? null;
            $entity->collection_key = $collectionKeyMap[(int) $entity->collection_id] ?? null;
            $entity->categories = $categoryMap[(int) $entity->id] ?? [];
            $entity->tags = $tagMap[(int) $entity->id] ?? [];
        }

        return $entities;
    }

    protected function mapToResponse(object $entity): DataTransferObjectInterface
    {
        if ($entity instanceof EntryEntity) {
            $payload = $entity->toArray();
            $payload['categories'] = is_array($entity->categories ?? null) ? $entity->categories : [];
            $payload['tags'] = is_array($entity->tags ?? null) ? $entity->tags : [];

            return $this->responseMapper->map($payload);
        }

        return parent::mapToResponse($entity);
    }

    /**
     * @param array<mixed> $translations
     */
    private function saveTranslations(int $entryId, array $translations): void
    {
        /** @var \App\Models\EntryTranslationModel $translationModel */
        $translationModel = model(\App\Models\EntryTranslationModel::class);

        foreach ($translations as $translation) {
            $langId = (int) $translation['language_id'];
            $slug = (string) $translation['slug'];

            $existing = $translationModel
                ->where('language_id', $langId)
                ->where('slug', $slug)
                ->where('entry_id !=', $entryId)
                ->first();

            if ($existing) {
                throw new ValidationException(
                    lang('Entries.slug_must_be_unique'),
                    ['slug' => lang('Entries.slug_already_taken', [$slug])]
                );
            }
        }

        // Query current translations to compare slugs
        $currentTranslations = $translationModel->where('entry_id', $entryId)->findAll();
        $currentSlugs = [];
        foreach ($currentTranslations as $ct) {
            if ($ct instanceof \App\Entities\EntryTranslationEntity) {
                $currentSlugs[(int)$ct->language_id] = $ct->slug;
            }
        }

        // Record slug redirects before batch-inserting new translations
        if (!empty($currentSlugs)) {
            $entryModel      = model(\App\Models\EntryModel::class);
            $entry           = $entryModel->find($entryId);
            $translationResolver = $this->translationResolver;
            $languageCodeMap = [];
            if ($entry instanceof \App\Entities\EntryEntity) {
                $currentLangResult = \Config\Database::connect()->table('cms_languages')
                    ->where('is_active', 1)
                    ->get();
                $languageRows = $currentLangResult instanceof \CodeIgniter\Database\ResultInterface
                    ? $currentLangResult->getResultArray()
                    : [];
                $languageCodeMap = [];
                foreach ($languageRows as $languageRow) {
                    if (isset($languageRow['id'], $languageRow['code'])) {
                        $languageCodeMap[(int) $languageRow['id']] = (string) $languageRow['code'];
                    }
                }
            }

            foreach ($translations as $translation) {
                $langId  = (int) $translation['language_id'];
                $newSlug = (string) $translation['slug'];
                if (isset($currentSlugs[$langId]) && $currentSlugs[$langId] !== $newSlug) {
                    $langCode = $languageCodeMap[$langId] ?? null;
                    $resolvedPrefix = '';
                    if ($langCode !== null && $entry instanceof \App\Entities\EntryEntity) {
                        $resolvedCollection = $translationResolver->resolve('collection', (int) $entry->collection_id, $langCode);
                        $resolvedPrefix = trim((string) ($resolvedCollection['slug'] ?? ''), '/');
                    }

                    $oldFullPath = ($resolvedPrefix !== '' ? $resolvedPrefix . '/' : '') . $currentSlugs[$langId];
                    $this->slugRedirectRecorder->record('entry', $entryId, $langId, $currentSlugs[$langId], $newSlug, $oldFullPath);
                }
            }
        }

        $rows = [];
        foreach ($translations as $translation) {
            $featuredImage = $this->fileUrlResolver->normalizeMediaReference(
                $translation['featured_image'] ?? [
                    'file_id' => $translation['featured_file_id'] ?? null,
                    'url'     => isset($translation['featured_image_url']) ? (string) $translation['featured_image_url'] : null,
                ]
            );
            $ogImage = $this->fileUrlResolver->normalizeMediaReference(
                $translation['og_image'] ?? [
                    'file_id' => $translation['og_image_file_id'] ?? null,
                    'url'     => isset($translation['og_image_url']) ? (string) $translation['og_image_url'] : null,
                ]
            );

            $rows[] = [
                'entry_id'         => $entryId,
                'language_id'      => (int) $translation['language_id'],
                'slug'             => (string) $translation['slug'],
                'title'            => $translation['title'],
                'excerpt'          => $translation['excerpt'] ?? null,
                'featured_file_id' => $featuredImage['file_id'],
                'featured_image_url' => $featuredImage['url'],
                'meta_title'       => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
                'og_image_file_id' => $ogImage['file_id'],
                'og_image_url'     => $ogImage['url'],
                'og_type'          => $translation['og_type'] ?? 'article',
                'canonical_url'    => $translation['canonical_url'] ?? null,
                'robots'           => $translation['robots'] ?? null,
                'schema_data'      => isset($translation['schema_data']) ? json_encode($translation['schema_data']) : null,
            ];
        }
        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $translationModel,
            'entry_id',
            $entryId,
            $rows,
            static fn (array $row): array => $row,
        );
    }

    public function syncCategories(
        int $entryId,
        EntrySetCategoriesRequestDTO $dto,
        ?SecurityContext $context = null
    ): DataTransferObjectInterface {
        /** @var EntryEntity|null $entry */
        $entry = $this->repository->find($entryId);
        if (! $entry) {
            throw new NotFoundException(lang('Api.resourceNotFound'));
        }

        return $this->wrapInTransaction(function () use ($entryId, $dto, $entry): DataTransferObjectInterface {
            $this->replaceEntryCategories($entryId, $dto->category_ids, $entry);

            return PayloadResponseDTO::fromArray([
                'entry_id'     => $entryId,
                'category_ids' => $dto->category_ids,
            ]);
        });
    }

    public function syncTags(
        int $entryId,
        EntrySetTagsRequestDTO $dto,
        ?SecurityContext $context = null
    ): DataTransferObjectInterface {
        if (! $this->repository->find($entryId)) {
            throw new NotFoundException(lang('Api.resourceNotFound'));
        }

        return $this->wrapInTransaction(function () use ($entryId, $dto): DataTransferObjectInterface {
            $this->replaceEntryTags($entryId, $dto->tag_ids);

            return PayloadResponseDTO::fromArray([
                'entry_id' => $entryId,
                'tag_ids'  => $dto->tag_ids,
            ]);
        });
    }

    public function syncTaxonomy(
        int $entryId,
        EntrySyncTaxonomyRequestDTO $dto,
        ?SecurityContext $context = null
    ): DataTransferObjectInterface {
        /** @var EntryEntity|null $entry */
        $entry = $this->repository->find($entryId);
        if (! $entry) {
            throw new NotFoundException(lang('Api.resourceNotFound'));
        }

        return $this->wrapInTransaction(function () use ($entryId, $dto, $entry): DataTransferObjectInterface {
            $this->replaceEntryCategories($entryId, $dto->category_ids, $entry);
            $this->replaceEntryTags($entryId, $dto->tag_ids);

            return PayloadResponseDTO::fromArray([
                'entry_id' => $entryId,
                'category_ids' => $dto->category_ids,
                'tag_ids' => $dto->tag_ids,
            ]);
        });
    }

    /**
     * Validate that every id in $categoryIds exists and belongs to the
     * entry's own collection, then replace the entry's category pivot rows
     * with exactly that set (preserving array order as sort_order). Shared
     * by syncCategories() and syncTaxonomy() so the two never drift.
     *
     * @param list<int> $categoryIds
     */
    private function replaceEntryCategories(int $entryId, array $categoryIds, EntryEntity $entry): void
    {
        if ($categoryIds !== []) {
            /** @var \App\Models\CategoryModel $categoryModel */
            $categoryModel = model(\App\Models\CategoryModel::class);
            $found = $categoryModel->whereIn('id', $categoryIds)->findAll();
            if (count($found) !== count($categoryIds)) {
                throw new ValidationException(
                    lang('Entries.invalid_categories'),
                    ['category_ids' => lang('Entries.some_categories_not_found')]
                );
            }

            $entryCollectionId = (int) ($entry->collection_id ?? 0);
            foreach ($found as $category) {
                if ((int) ($category->collection_id ?? 0) !== $entryCollectionId) {
                    throw new ValidationException(
                        lang('Entries.invalid_categories'),
                        ['category_ids' => lang('Entries.some_categories_not_found')]
                    );
                }
            }
        }

        $db = \Config\Database::connect();
        $db->table('cms_entry_categories')->where('entry_id', $entryId)->delete();

        if ($categoryIds !== []) {
            $rows = [];
            foreach ($categoryIds as $order => $categoryId) {
                $rows[] = [
                    'entry_id'    => $entryId,
                    'category_id' => $categoryId,
                    'sort_order'  => $order,
                ];
            }
            $db->table('cms_entry_categories')->insertBatch($rows);
        }
    }

    /**
     * Validate that every id in $tagIds exists, then replace the entry's tag
     * pivot rows with exactly that set. Shared by syncTags() and
     * syncTaxonomy() so the two never drift.
     *
     * @param list<int> $tagIds
     */
    private function replaceEntryTags(int $entryId, array $tagIds): void
    {
        if ($tagIds !== []) {
            /** @var \App\Models\TagModel $tagModel */
            $tagModel = model(\App\Models\TagModel::class);
            $found = $tagModel->whereIn('id', $tagIds)->findAll();
            if (count($found) !== count($tagIds)) {
                throw new ValidationException(
                    lang('Entries.invalid_tags'),
                    ['tag_ids' => lang('Entries.some_tags_not_found')]
                );
            }
        }

        $db = \Config\Database::connect();
        $db->table('cms_entry_tags')->where('entry_id', $entryId)->delete();

        if ($tagIds !== []) {
            $rows = [];
            foreach ($tagIds as $tagId) {
                $rows[] = ['entry_id' => $entryId, 'tag_id' => $tagId];
            }
            $db->table('cms_entry_tags')->insertBatch($rows);
        }
    }

    public function listPublic(PublicEntryIndexRequestDTO $dto): DataTransferObjectInterface
    {
        return $this->publicReader->listPublic($dto);
    }

    public function showPublic(PublicEntryShowRequestDTO $dto): DataTransferObjectInterface
    {
        return $this->publicReader->showPublic($dto);
    }

    public function createVersionSnapshot(int $entryId, string $note = ''): void
    {
        $entry = $this->repository->find($entryId);
        if (!$entry) {
            return;
        }

        /** @var \App\Models\EntryTranslationModel $translationModel */
        $translationModel = model(\App\Models\EntryTranslationModel::class);
        $translations = $translationModel->where('entry_id', $entryId)->findAll();

        $translationsData = [];
        foreach ($translations as $t) {
            if ($t instanceof \CodeIgniter\Entity\Entity) {
                $translationsData[] = $t->toArray();
            }
        }

        $snapshot = [
            'entry'        => $entry->toArray(),
            'translations' => $translationsData,
        ];

        /** @var \App\Models\EntryVersionModel $versionModel */
        $versionModel = model(\App\Models\EntryVersionModel::class);
        $lastVersion = $versionModel->where('entry_id', $entryId)
            ->orderBy('version_number', 'DESC')
            ->first();

        $nextVersionNumber = 1;
        if ($lastVersion instanceof \App\Entities\EntryVersionEntity) {
            $nextVersionNumber = (int) $lastVersion->version_number + 1;
        }

        $versionModel->insert([
            'entry_id'       => $entryId,
            'version_number' => $nextVersionNumber,
            'snapshot'       => json_encode($snapshot),
            'note'           => $note,
        ]);
    }

    public function isSlugAvailable(string $slug, int $languageId, ?int $currentId = null): bool
    {
        return (new \App\Models\EntryTranslationModel())->isSlugAvailable($slug, $languageId, $currentId);
    }
}
