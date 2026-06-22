<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\DTO\Request\Cms\EntrySetCategoriesRequestDTO;
use App\DTO\Request\Cms\EntrySetTagsRequestDTO;
use App\DTO\Request\Cms\PublicEntryIndexRequestDTO;
use App\DTO\Request\Cms\PublicEntryShowRequestDTO;
use App\Entities\EntryEntity;
use App\Interfaces\Cms\EntryServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\Common\PayloadResponseDTO;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\PaginatedResponseDTO;
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

    /**
     * @param RepositoryInterface<EntryEntity> $entryRepository
     */
    public function __construct(
        RepositoryInterface $entryRepository,
        ResponseMapperInterface $responseMapper,
        ?\App\Libraries\Cms\SlugRedirectRecorder $slugRedirectRecorder = null
    ) {
        parent::__construct($entryRepository, $responseMapper);
        $this->slugRedirectRecorder = $slugRedirectRecorder ?? service('slugRedirectRecorder');
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);

        $collectionId = isset($data['collection_id']) ? (int) $data['collection_id'] : null;
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

        $this->tempTranslations = $data['translations'] ?? null;
        unset($data['translations']);
        return $data;
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        if ($this->tempTranslations !== null) {
            $this->saveTranslations((int) $entity->id, $this->tempTranslations);
        }
        $this->createVersionSnapshot((int) $entity->id, 'Initial creation');
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
            $translationsGrouped[$translation->entry_id][] = [
                'language_id'      => (int) $translation->language_id,
                'slug'             => $translation->slug,
                'title'            => $translation->title,
                'excerpt'          => $translation->excerpt,
                'featured_file_id' => $translation->featured_file_id !== null ? (int) $translation->featured_file_id : null,
                'meta_title'       => $translation->meta_title,
                'meta_description' => $translation->meta_description,
                'og_image_file_id' => $translation->og_image_file_id !== null ? (int) $translation->og_image_file_id : null,
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
            $collectionModel = model(\App\Models\CollectionModel::class);
            $prefix          = '';
            if ($entry instanceof \App\Entities\EntryEntity) {
                $collection = $collectionModel->find((int) $entry->collection_id);
                if ($collection instanceof \App\Entities\CollectionEntity) {
                    $prefix = trim($collection->url_prefix, '/') . '/';
                }
            }

            foreach ($translations as $translation) {
                $langId  = (int) $translation['language_id'];
                $newSlug = (string) $translation['slug'];
                if (isset($currentSlugs[$langId]) && $currentSlugs[$langId] !== $newSlug) {
                    $oldFullPath = $prefix . $currentSlugs[$langId];
                    $this->slugRedirectRecorder->record('entry', $entryId, $langId, $currentSlugs[$langId], $newSlug, $oldFullPath);
                }
            }
        }

        $rows = [];
        foreach ($translations as $translation) {
            $rows[] = [
                'entry_id'         => $entryId,
                'language_id'      => (int) $translation['language_id'],
                'slug'             => (string) $translation['slug'],
                'title'            => $translation['title'],
                'excerpt'          => $translation['excerpt'] ?? null,
                'featured_file_id' => isset($translation['featured_file_id']) && $translation['featured_file_id'] !== '' ? (int) $translation['featured_file_id'] : null,
                'meta_title'       => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
                'og_image_file_id' => isset($translation['og_image_file_id']) && $translation['og_image_file_id'] !== '' ? (int) $translation['og_image_file_id'] : null,
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
        /** @var \App\Models\CollectionModel $collectionModel */
        $collectionModel = model(\App\Models\CollectionModel::class);
        $collection = $collectionModel
            ->where('collection_key', $dto->collection_key)
            ->where('is_active', 1)
            ->first();

        if (!$collection instanceof \App\Entities\CollectionEntity) {
            throw new NotFoundException(lang('Collections.not_found'));
        }

        ['langId' => $langId, 'defaultLangId' => $defaultLangId] = $this->resolveLanguageIds($dto->lang);

        /** @var \App\Models\EntryModel $entryModel */
        $entryModel = model(\App\Models\EntryModel::class);

        if ($dto->category !== null) {
            /** @var \App\Models\CategoryTranslationModel $catTransModel */
            $catTransModel = model(\App\Models\CategoryTranslationModel::class);
            $catTrans = $catTransModel->where('slug', $dto->category)->first();
            if (!$catTrans instanceof \App\Entities\CategoryTranslationEntity) {
                return PaginatedResponseDTO::fromArray([
                    'data'     => [],
                    'total'    => 0,
                    'page'     => $dto->page,
                    'per_page' => $dto->per_page,
                ]);
            }
            $entryModel->join('cms_entry_categories', 'cms_entry_categories.entry_id = cms_entries.id')
                ->where('cms_entry_categories.category_id', (int) $catTrans->category_id);
        }

        if ($dto->tag !== null) {
            /** @var \App\Models\TagTranslationModel $tagTransModel */
            $tagTransModel = model(\App\Models\TagTranslationModel::class);
            $tagTrans = $tagTransModel->where('slug', $dto->tag)->first();
            if (!$tagTrans instanceof \App\Entities\TagTranslationEntity) {
                return PaginatedResponseDTO::fromArray([
                    'data'     => [],
                    'total'    => 0,
                    'page'     => $dto->page,
                    'per_page' => $dto->per_page,
                ]);
            }
            $entryModel->join('cms_entry_tags', 'cms_entry_tags.entry_id = cms_entries.id')
                ->where('cms_entry_tags.tag_id', (int) $tagTrans->tag_id);
        }

        $now    = date('Y-m-d H:i:s');
        $offset = ($dto->page - 1) * $dto->per_page;

        $builder = $entryModel
            ->where('collection_id', (int) $collection->id)
            ->where('workflow_status', 'published')
            ->groupStart()
                ->where('published_at IS NULL')
                ->orWhere('published_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('scheduled_at IS NULL')
                ->orWhere('scheduled_at <=', $now)
            ->groupEnd();

        $total   = (int) $builder->countAllResults(false);
        $entries = $builder
            ->orderBy('cms_entries.sort_order', 'ASC')
            ->orderBy('cms_entries.created_at', 'DESC')
            ->findAll($dto->per_page, $offset);

        if (empty($entries)) {
            return PaginatedResponseDTO::fromArray([
                'data'     => [],
                'total'    => $total,
                'page'     => $dto->page,
                'per_page' => $dto->per_page,
            ]);
        }

        $entryIds = [];
        foreach ($entries as $e) {
            if ($e instanceof EntryEntity) {
                $entryIds[] = (int) $e->id;
            }
        }

        $entryTransMap  = $this->batchResolveEntryTranslations($entryIds, $langId, $defaultLangId);
        $categoriesMap  = $this->batchResolveCategoryPivot($entryIds, $langId, $defaultLangId);
        $tagsMap        = $this->batchResolveTagPivot($entryIds, $langId, $defaultLangId);

        $data = [];
        foreach ($entries as $entry) {
            if (!$entry instanceof EntryEntity) {
                continue;
            }
            $entryId = (int) $entry->id;
            $item    = array_merge($entry->toArray(), $entryTransMap[$entryId] ?? []);
            $item['categories'] = $categoriesMap[$entryId] ?? [];
            $item['tags']       = $tagsMap[$entryId] ?? [];
            $data[] = $item;
        }

        return PaginatedResponseDTO::fromArray([
            'data'     => $data,
            'total'    => (int) $total,
            'page'     => $dto->page,
            'per_page' => $dto->per_page,
        ]);
    }

    public function showPublic(PublicEntryShowRequestDTO $dto): DataTransferObjectInterface
    {
        /** @var \App\Models\CollectionModel $collectionModel */
        $collectionModel = model(\App\Models\CollectionModel::class);
        $collection = $collectionModel
            ->where('collection_key', $dto->collection_key)
            ->where('is_active', 1)
            ->first();

        if (!$collection instanceof \App\Entities\CollectionEntity) {
            throw new NotFoundException(lang('Collections.not_found'));
        }

        ['langId' => $langId, 'defaultLangId' => $defaultLangId] = $this->resolveLanguageIds($dto->lang);

        /** @var \App\Models\EntryTranslationModel $translationModel */
        $translationModel = model(\App\Models\EntryTranslationModel::class);
        $entryTranslation = $translationModel
            ->where('slug', $dto->slug)
            ->where('language_id', $langId)
            ->first();

        if (!$entryTranslation instanceof \App\Entities\EntryTranslationEntity
            && $defaultLangId !== $langId
        ) {
            $entryTranslation = $translationModel
                ->where('slug', $dto->slug)
                ->where('language_id', $defaultLangId)
                ->first();
        }

        if (!$entryTranslation instanceof \App\Entities\EntryTranslationEntity) {
            throw new NotFoundException(lang('Entries.not_found'));
        }

        $entryId = (int) $entryTranslation->entry_id;

        $now    = date('Y-m-d H:i:s');
        /** @var \App\Models\EntryModel $entryModel */
        $entryModel = model(\App\Models\EntryModel::class);
        $entry = $entryModel
            ->where('id', $entryId)
            ->where('collection_id', (int) $collection->id)
            ->where('workflow_status', 'published')
            ->groupStart()
                ->where('published_at IS NULL')
                ->orWhere('published_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('scheduled_at IS NULL')
                ->orWhere('scheduled_at <=', $now)
            ->groupEnd()
            ->first();

        if (!$entry instanceof EntryEntity) {
            throw new NotFoundException(lang('Entries.not_found'));
        }

        $entryTransMap = $this->batchResolveEntryTranslations([$entryId], $langId, $defaultLangId);
        $categoriesMap = $this->batchResolveCategoryPivot([$entryId], $langId, $defaultLangId);
        $tagsMap       = $this->batchResolveTagPivot([$entryId], $langId, $defaultLangId);

        $blockSerializer = service('blockInstanceSerializer');
        $blocks = $blockSerializer->forContent('entry', $entryId, $dto->lang);

        $data               = array_merge($entry->toArray(), $entryTransMap[$entryId] ?? []);
        $data['categories'] = $categoriesMap[$entryId] ?? [];
        $data['tags']       = $tagsMap[$entryId] ?? [];
        $data['blocks']     = $blocks;

        // Get all translations of this entry to construct localized slugs
        /** @var list<\App\Entities\EntryTranslationEntity> $allTranslations */
        $allTranslations = $translationModel->where('entry_id', $entryId)->findAll();
        /** @var \App\Models\LanguageModel $langModel */
        $langModel = model(\App\Models\LanguageModel::class);
        /** @var list<\App\Entities\LanguageEntity> $activeLanguages */
        $activeLanguages = $langModel->where('is_active', 1)->findAll();
        $langCodeMap = [];
        foreach ($activeLanguages as $al) {
            $langCodeMap[$al->id] = $al->code;
        }

        $localizedSlugs = [];
        foreach ($allTranslations as $at) {
            $code = $langCodeMap[$at->language_id] ?? null;
            if ($code !== null) {
                $localizedSlugs[$code] = $at->slug;
            }
        }
        $data['localized_slugs'] = $localizedSlugs;

        return PayloadResponseDTO::fromArray($data);
    }

    /**
     * Resolves target and default language IDs in a single query.
     *
     * @return array{langId: int, defaultLangId: int}
     */
    private function resolveLanguageIds(string $langCode): array
    {
        /** @var \App\Models\LanguageModel $langModel */
        $langModel = model(\App\Models\LanguageModel::class);

        $rows = $langModel
            ->groupStart()
                ->where('code', $langCode)
                ->orWhere('is_default', 1)
            ->groupEnd()
            ->where('is_active', 1)
            ->findAll();

        $langId        = null;
        $defaultLangId = null;

        foreach ($rows as $row) {
            if (!$row instanceof \App\Entities\LanguageEntity) {
                continue;
            }
            if ($row->code === $langCode) {
                $langId = (int) $row->id;
            }
            if ((int) $row->is_default === 1) {
                $defaultLangId = (int) $row->id;
            }
        }

        if ($langId === null && $defaultLangId === null) {
            throw new NotFoundException(lang('Entries.language_not_found'));
        }

        $resolvedLangId    = $langId ?? $defaultLangId;
        $resolvedDefaultId = $defaultLangId ?? $langId;

        return ['langId' => $resolvedLangId, 'defaultLangId' => $resolvedDefaultId];
    }

    /**
     * @param  list<int>  $entryIds
     * @return array<int, array<string, mixed>>
     */
    private function batchResolveEntryTranslations(array $entryIds, int $langId, int $defaultLangId): array
    {
        if (empty($entryIds)) {
            return [];
        }

        /** @var \App\Models\EntryTranslationModel $model */
        $model = model(\App\Models\EntryTranslationModel::class);
        $rows  = $model
            ->whereIn('entry_id', $entryIds)
            ->whereIn('language_id', array_unique([$langId, $defaultLangId]))
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            if (!$row instanceof \App\Entities\EntryTranslationEntity) {
                continue;
            }
            $eid = (int) $row->entry_id;
            $lid = (int) $row->language_id;
            if (!isset($map[$eid]) || $lid === $langId) {
                $map[$eid] = [
                    'slug'             => $row->slug,
                    'title'            => $row->title,
                    'excerpt'          => $row->excerpt,
                    'featured_file_id' => $row->featured_file_id,
                    'meta_title'       => $row->meta_title,
                    'meta_description' => $row->meta_description,
                    'og_image_file_id' => $row->og_image_file_id,
                    'og_type'          => $row->og_type,
                    'canonical_url'    => $row->canonical_url,
                    'robots'           => $row->robots,
                    'schema_data'      => $row->schema_data,
                    'is_fallback'      => $lid !== $langId,
                ];
            }
        }

        return $map;
    }

    /**
     * @param  list<int>  $entryIds
     * @return array<int, list<array<string, mixed>>>
     */
    private function batchResolveCategoryPivot(array $entryIds, int $langId, int $defaultLangId): array
    {
        if (empty($entryIds)) {
            return [];
        }

        $langIds          = array_unique([$langId, $defaultLangId]);
        $langPlaceholders = implode(',', array_fill(0, count($langIds), '?'));
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

        $db     = \Config\Database::connect();
        $result = $db->query($sql, array_merge($langIds, $entryIds));

        if (!$result instanceof \CodeIgniter\Database\BaseResult) {
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
     * @param  list<int>  $entryIds
     * @return array<int, list<array<string, mixed>>>
     */
    private function batchResolveTagPivot(array $entryIds, int $langId, int $defaultLangId): array
    {
        if (empty($entryIds)) {
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

        $db     = \Config\Database::connect();
        $result = $db->query($sql, array_merge($langIds, $entryIds));

        if (!$result instanceof \CodeIgniter\Database\BaseResult) {
            return [];
        }

        $map      = [];
        $seenTags = [];

        foreach ($result->getResultArray() as $row) {
            $eid   = (int) $row['entry_id'];
            $tagId = (int) $row['tag_id'];
            $lid   = (int) ($row['language_id'] ?? 0);

            if (!isset($map[$eid])) {
                $map[$eid]       = [];
                $seenTags[$eid]  = [];
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
