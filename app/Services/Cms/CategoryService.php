<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\CategoryEntity;
use App\Interfaces\Cms\CategoryServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<CategoryEntity>
 */
class CategoryService extends BaseCrudService implements CategoryServiceInterface
{
    /** @var array<array{language_id: int, slug: string, name: string, description?: string, meta_title?: string, meta_description?: string}>|null */
    private ?array $tempTranslations = null;

    /**
     * @param RepositoryInterface<CategoryEntity> $categoryRepository
     */
    public function __construct(
        RepositoryInterface $categoryRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($categoryRepository, $responseMapper);
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);
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
        $this->tempTranslations = null;
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);
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
        $this->tempTranslations = null;
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        $categoryIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var \App\Models\CategoryTranslationModel $translationModel */
        $translationModel = model(\App\Models\CategoryTranslationModel::class);
        $translations = $translationModel->whereIn('category_id', $categoryIds)->findAll();

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
            $entity->translations = $translationsGrouped[$entity->id] ?? [];
        }

        return $entities;
    }

    /**
     * @param array<array{language_id: int, slug: string, name: string, description?: string, meta_title?: string, meta_description?: string}> $translations
     */
    private function saveTranslations(int $categoryId, array $translations): void
    {
        /** @var \App\Models\CategoryTranslationModel $translationModel */
        $translationModel = model(\App\Models\CategoryTranslationModel::class);

        // Clear existing translations
        $translationModel->where('category_id', $categoryId)->delete();

        foreach ($translations as $translation) {
            $translationModel->insert([
                'category_id'      => $categoryId,
                'language_id'      => (int) $translation['language_id'],
                'slug'             => $translation['slug'],
                'name'             => $translation['name'],
                'description'      => $translation['description'] ?? null,
                'meta_title'       => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
            ]);
        }
    }
}
