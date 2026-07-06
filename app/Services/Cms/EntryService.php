<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\EntrySetCategoriesRequestDTO;
use App\DTO\Request\Cms\EntrySetTagsRequestDTO;
use App\DTO\Request\Cms\PublicEntryIndexRequestDTO;
use App\DTO\Request\Cms\PublicEntryShowRequestDTO;
use App\Entities\EntryEntity;
use App\Interfaces\Cms\EntryServiceInterface;
use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
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
    /** @var array<mixed>|null */
    private ?array $tempTranslations = null;

    private \App\Libraries\Cms\SlugRedirectRecorder $slugRedirectRecorder;

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    private FileUrlResolver $fileUrlResolver;

    private FileReferenceSynchronizer $fileReferenceSynchronizer;

    private EntryBlockTemplateInitializer $blockTemplateInitializer;

    private PublicEntryReader $publicReader;

    /**
     * @param RepositoryInterface<EntryEntity> $entryRepository
     */
    public function __construct(
        RepositoryInterface $entryRepository,
        ResponseMapperInterface $responseMapper,
        ?\App\Libraries\Cms\SlugRedirectRecorder $slugRedirectRecorder = null,
        ?\App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator = null,
        ?FileUrlResolver $fileUrlResolver = null,
        ?FileReferenceSynchronizer $fileReferenceSynchronizer = null,
        ?EntryBlockTemplateInitializer $blockTemplateInitializer = null,
        ?PublicEntryReader $publicReader = null
    ) {
        parent::__construct($entryRepository, $responseMapper);
        $this->slugRedirectRecorder = $slugRedirectRecorder ?? service('slugRedirectRecorder');
        $this->cacheInvalidator     = $cacheInvalidator ?? service('cacheInvalidationClient');
        $this->fileUrlResolver      = $fileUrlResolver ?? service('fileUrlResolver');
        $this->fileReferenceSynchronizer = $fileReferenceSynchronizer ?? service('fileReferenceSynchronizer');
        $this->blockTemplateInitializer = $blockTemplateInitializer ?? new EntryBlockTemplateInitializer();
        $this->publicReader = $publicReader ?? new PublicEntryReader($this->fileUrlResolver);
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

        $this->tempTranslations = $data['translations'] ?? null;
        unset($data['translations']);
        return $data;
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

        if ($this->tempTranslations !== null) {
            $this->saveTranslations((int) $entity->id, $this->tempTranslations);
        }

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
        $this->tempTranslations = null;
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

        if (array_key_exists('translations', $data)) {
            $this->tempTranslations = $data['translations'];
            unset($data['translations']);
        } else {
            $this->tempTranslations = null;
        }

        return $data;
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        if ($this->tempTranslations !== null) {
            $this->saveTranslations((int) $entity->id, $this->tempTranslations);
        }
        $this->createVersionSnapshot((int) $entity->id, 'Update entry');
        $this->cacheInvalidator->invalidate(['entries']);
        $this->tempTranslations = null;
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        $entryIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var \App\Models\EntryTranslationModel $translationModel */
        $translationModel = model(\App\Models\EntryTranslationModel::class);
        $translations = $translationModel->whereIn('entry_id', $entryIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            /** @var \App\Entities\EntryTranslationEntity $translation */
            $resolvedTranslation = $this->fileUrlResolver->normalizeEntryTranslation([
                'featured_file_id' => $translation->featured_file_id !== null ? (int) $translation->featured_file_id : null,
                'featured_image_url' => $translation->featured_image_url,
                'og_image_file_id' => $translation->og_image_file_id !== null ? (int) $translation->og_image_file_id : null,
            ]);

            $translationsGrouped[$translation->entry_id][] = [
                'language_id'      => (int) $translation->language_id,
                'slug'             => $translation->slug,
                'title'            => $translation->title,
                'excerpt'          => $translation->excerpt,
                'featured_file_id' => $translation->featured_file_id !== null ? (int) $translation->featured_file_id : null,
                'featured_image_url' => $resolvedTranslation['featured_image_url'] ?? null,
                'meta_title'       => $translation->meta_title,
                'meta_description' => $translation->meta_description,
                'og_image_file_id' => $translation->og_image_file_id !== null ? (int) $translation->og_image_file_id : null,
                'og_image_url'     => $resolvedTranslation['og_image_url'] ?? null,
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

        foreach ($entities as $entity) {
            $entityTranslations = $translationsGrouped[$entity->id] ?? [];
            $entity->translations = $entityTranslations;
            $entity->title = $entityTranslations[0]['title'] ?? null;
            $entity->slug = $entityTranslations[0]['slug'] ?? null;
            $entity->collection_key = $collectionKeyMap[(int) $entity->collection_id] ?? null;
        }

        return $entities;
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

        $translationModel->where('entry_id', $entryId)->delete();

        // Record slug redirects before batch-inserting new translations
        if (!empty($currentSlugs)) {
            $entryModel      = model(\App\Models\EntryModel::class);
            $entry           = $entryModel->find($entryId);
            $translationResolver = service('translationResolver');
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
            $normalizedTranslation = $this->fileUrlResolver->normalizeEntryTranslation([
                'featured_file_id' => $translation['featured_file_id'] ?? null,
                'featured_image_url' => $translation['featured_image_url'] ?? null,
                'og_image_file_id' => $translation['og_image_file_id'] ?? null,
            ]);

            $rows[] = [
                'entry_id'         => $entryId,
                'language_id'      => (int) $translation['language_id'],
                'slug'             => (string) $translation['slug'],
                'title'            => $translation['title'],
                'excerpt'          => $translation['excerpt'] ?? null,
                'featured_file_id' => isset($translation['featured_file_id']) && $translation['featured_file_id'] !== '' ? (int) $translation['featured_file_id'] : null,
                'featured_image_url' => $normalizedTranslation['featured_image_url'] ?? null,
                'meta_title'       => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
                'og_image_file_id' => isset($translation['og_image_file_id']) && $translation['og_image_file_id'] !== '' ? (int) $translation['og_image_file_id'] : null,
                'og_image_url'     => $normalizedTranslation['og_image_url'] ?? null,
                'og_type'          => $translation['og_type'] ?? 'article',
                'canonical_url'    => $translation['canonical_url'] ?? null,
                'robots'           => $translation['robots'] ?? null,
                'schema_data'      => isset($translation['schema_data']) ? json_encode($translation['schema_data']) : null,
            ];
        }
        if (!empty($rows)) {
            $translationModel->insertBatch($rows);
        }
    }

    public function syncCategories(
        int $entryId,
        EntrySetCategoriesRequestDTO $dto,
        ?SecurityContext $context = null
    ): DataTransferObjectInterface {
        if (!$this->repository->find($entryId)) {
            throw new NotFoundException(lang('Api.resourceNotFound'));
        }

        return $this->wrapInTransaction(function () use ($entryId, $dto): DataTransferObjectInterface {
            $categoryIds = $dto->category_ids;

            if (!empty($categoryIds)) {
                /** @var \App\Models\CategoryModel $categoryModel */
                $categoryModel = model(\App\Models\CategoryModel::class);
                $found = $categoryModel->whereIn('id', $categoryIds)->findAll();
                if (count($found) !== count(array_unique($categoryIds))) {
                    throw new ValidationException(
                        lang('Entries.invalid_categories'),
                        ['category_ids' => lang('Entries.some_categories_not_found')]
                    );
                }
            }

            $db = \Config\Database::connect();
            $db->table('cms_entry_categories')->where('entry_id', $entryId)->delete();

            if (!empty($categoryIds)) {
                $rows = [];
                foreach ($categoryIds as $order => $catId) {
                    $rows[] = [
                        'entry_id'    => $entryId,
                        'category_id' => $catId,
                        'sort_order'  => $order,
                    ];
                }
                $db->table('cms_entry_categories')->insertBatch($rows);
            }

            return PayloadResponseDTO::fromArray([
                'entry_id'     => $entryId,
                'category_ids' => $categoryIds,
            ]);
        });
    }

    public function syncTags(
        int $entryId,
        EntrySetTagsRequestDTO $dto,
        ?SecurityContext $context = null
    ): DataTransferObjectInterface {
        if (!$this->repository->find($entryId)) {
            throw new NotFoundException(lang('Api.resourceNotFound'));
        }

        return $this->wrapInTransaction(function () use ($entryId, $dto): DataTransferObjectInterface {
            $tagIds = $dto->tag_ids;

            if (!empty($tagIds)) {
                /** @var \App\Models\TagModel $tagModel */
                $tagModel = model(\App\Models\TagModel::class);
                $found = $tagModel->whereIn('id', $tagIds)->findAll();
                if (count($found) !== count(array_unique($tagIds))) {
                    throw new ValidationException(
                        lang('Entries.invalid_tags'),
                        ['tag_ids' => lang('Entries.some_tags_not_found')]
                    );
                }
            }

            $db = \Config\Database::connect();
            $db->table('cms_entry_tags')->where('entry_id', $entryId)->delete();

            if (!empty($tagIds)) {
                $rows = [];
                foreach ($tagIds as $tagId) {
                    $rows[] = [
                        'entry_id' => $entryId,
                        'tag_id'   => $tagId,
                    ];
                }
                $db->table('cms_entry_tags')->insertBatch($rows);
            }

            return PayloadResponseDTO::fromArray([
                'entry_id' => $entryId,
                'tag_ids'  => $tagIds,
            ]);
        });
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
}
