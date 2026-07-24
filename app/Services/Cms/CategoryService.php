<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\CategoryEntity;
use App\Interfaces\Cms\CategoryServiceInterface;
use App\Traits\Services\HasTranslatableTaxonomyLifecycle;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<CategoryEntity>
 */
class CategoryService extends BaseCrudService implements CategoryServiceInterface
{
    use HasTranslatableTaxonomyLifecycle;

    protected \App\Libraries\Cms\TranslationResolver $translationResolver;

    private ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer;

    /**
     * @param RepositoryInterface<CategoryEntity> $categoryRepository
     */
    public function __construct(
        RepositoryInterface $categoryRepository,
        ResponseMapperInterface $responseMapper,
        \App\Libraries\Cms\TranslationResolver $translationResolver,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer = null
    ) {
        parent::__construct($categoryRepository, $responseMapper);
        $this->translationResolver = $translationResolver;
        $this->cacheInvalidator    = $cacheInvalidator;
        $this->translationSynchronizer = $translationSynchronizer;
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        $categoryIds = array_map(fn ($entity) => (int) $entity->id, $entities);
        $collectionIds = [];
        foreach ($entities as $entity) {
            if (isset($entity->collection_id) && is_numeric($entity->collection_id)) {
                $collectionIds[] = (int) $entity->collection_id;
            }
        }
        $collectionIds = array_values(array_unique(array_filter($collectionIds)));

        /** @var \App\Models\CategoryTranslationModel $translationModel */
        $translationModel = model(\App\Models\CategoryTranslationModel::class);
        $translations = $translationModel->whereIn('category_id', $categoryIds)->findAll();
        $collectionTranslationModel = model(\App\Models\CollectionTranslationModel::class);
        $collectionTranslations = $collectionIds !== []
            ? $collectionTranslationModel->whereIn('collection_id', $collectionIds)->findAll()
            : [];

        $categoryLabelById = [];
        foreach ($translations as $translation) {
            /** @var \App\Entities\CategoryTranslationEntity $translation */
            if (! isset($categoryLabelById[$translation->category_id])) {
                $categoryLabelById[$translation->category_id] = $translation->name ?: $translation->slug ?: null;
            }
        }

        $collectionLabelById = [];
        foreach ($collectionTranslations as $translation) {
            /** @var \App\Entities\CollectionTranslationEntity $translation */
            if (! isset($collectionLabelById[$translation->collection_id])) {
                $collectionLabelById[$translation->collection_id] = $translation->name ?: $translation->slug ?: null;
            }
        }

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            /** @var \App\Entities\CategoryTranslationEntity $translation */
            $translationsGrouped[$translation->category_id][] = [
                'language_id'      => (int) $translation->language_id,
                'slug'             => $translation->slug,
                'name'             => $translation->name,
                'description'      => $translation->description,
                'meta_title'       => $translation->meta_title,
                'meta_description' => $translation->meta_description,
            ];
        }

        foreach ($entities as $entity) {
            $entityTranslations = $translationsGrouped[$entity->id] ?? [];
            $entity->translations = $entityTranslations;
            $entity->name = $entityTranslations[0]['name'] ?? null;
            $entity->slug = $entityTranslations[0]['slug'] ?? null;
            $entity->collection_name = $collectionLabelById[(int) $entity->collection_id] ?? null;
            $entity->parent_label = isset($entity->parent_id) && is_numeric($entity->parent_id)
                ? ($categoryLabelById[(int) $entity->parent_id] ?? null)
                : null;
        }

        return $entities;
    }

    /**
     * List active categories for a collection, with language-resolved translations.
     *
     * @return array<int, array{id: int, slug: string, name: string, description: string|null}>
     */
    public function listPublic(string $lang, string $collectionKey): array
    {
        /** @var \App\Models\CollectionModel $collectionModel */
        $collectionModel = model(\App\Models\CollectionModel::class);
        $collection      = $collectionModel->where('collection_key', $collectionKey)
            ->where('is_active', 1)
            ->first();

        if ($collection === null) {
            return [];
        }

        if (!$collection instanceof \App\Entities\CollectionEntity) {
            return [];
        }

        /** @var \App\Models\CategoryModel $categoryModel */
        $categoryModel = model(\App\Models\CategoryModel::class);
        $categories    = $categoryModel
            ->where('collection_id', (int) $collection->id)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        if ($categories === []) {
            return [];
        }

        $translationResolver = $this->translationResolver;
        $result              = [];

        foreach ($categories as $category) {
            /** @var \App\Entities\CategoryEntity $category */
            $resolved = $translationResolver->resolve('category', (int) $category->id, $lang);

            $result[] = [
                'id'          => (int) $category->id,
                'slug'        => $resolved['slug'] ?? '',
                'name'        => $resolved['name'] ?? '',
                'description' => $resolved['description'] ?? null,
                'is_fallback' => $resolved['is_fallback'] ?? false,
            ];
        }

        return $result;
    }

    /**
     * @param array<array{language_id: int, slug: string, name: string, description?: string, meta_title?: string, meta_description?: string}> $translations
     */
    protected function saveTranslations(int $categoryId, array $translations): void
    {
        /** @var \App\Models\CategoryTranslationModel $translationModel */
        $translationModel = model(\App\Models\CategoryTranslationModel::class);

        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $translationModel,
            'category_id',
            $categoryId,
            $translations,
            static fn (array $translation): array => [
                'language_id'      => (int) $translation['language_id'],
                'slug'             => $translation['slug'],
                'name'             => $translation['name'],
                'description'      => $translation['description'] ?? null,
                'meta_title'       => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
            ],
        );
    }

    public function isSlugAvailable(string $slug, int $languageId, ?int $currentId = null): bool
    {
        return (new \App\Models\CategoryTranslationModel())->isSlugAvailable($slug, $languageId, $currentId);
    }
}
