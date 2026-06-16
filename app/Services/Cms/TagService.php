<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\TagEntity;
use App\Interfaces\Cms\TagServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<TagEntity>
 */
class TagService extends BaseCrudService implements TagServiceInterface
{
    /** @var array<array{language_id: int, slug: string, name: string}>|null */
    private ?array $tempTranslations = null;

    /**
     * @param RepositoryInterface<TagEntity> $tagRepository
     */
    public function __construct(
        RepositoryInterface $tagRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($tagRepository, $responseMapper);
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

        $tagIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var \App\Models\TagTranslationModel $translationModel */
        $translationModel = model(\App\Models\TagTranslationModel::class);
        $translations = $translationModel->whereIn('tag_id', $tagIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            /** @var \App\Entities\TagTranslationEntity $translation */
            $translationsGrouped[$translation->tag_id][] = [
                'language_id' => (int) $translation->language_id,
                'slug'        => $translation->slug,
                'name'        => $translation->name,
            ];
        }

        foreach ($entities as $entity) {
            $entityTranslations = $translationsGrouped[$entity->id] ?? [];
            $entity->translations = $entityTranslations;
            $entity->name = $entityTranslations[0]['name'] ?? null;
            $entity->slug = $entityTranslations[0]['slug'] ?? null;
        }

        return $entities;
    }

    /**
     * @param array<array{language_id: int, slug: string, name: string}> $translations
     */
    private function saveTranslations(int $tagId, array $translations): void
    {
        /** @var \App\Models\TagTranslationModel $translationModel */
        $translationModel = model(\App\Models\TagTranslationModel::class);

        // Clear existing translations
        $translationModel->where('tag_id', $tagId)->delete();

        foreach ($translations as $translation) {
            $translationModel->insert([
                'tag_id'      => $tagId,
                'language_id' => (int) $translation['language_id'],
                'slug'        => $translation['slug'],
                'name'        => $translation['name'],
            ]);
        }
    }
}
