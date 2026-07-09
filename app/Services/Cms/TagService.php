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

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;
    private \App\Libraries\Cms\TranslationResolver $translationResolver;

    /**
     * @param RepositoryInterface<TagEntity> $tagRepository
     */
    public function __construct(
        RepositoryInterface $tagRepository,
        ResponseMapperInterface $responseMapper,
        ?\App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator = null
    ) {
        parent::__construct($tagRepository, $responseMapper);
        $this->cacheInvalidator = $cacheInvalidator ?? service('cacheInvalidationClient');
        $this->translationResolver = service('translationResolver');
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
        $this->cacheInvalidator->invalidate(['taxonomies', 'entries']);
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
        $this->cacheInvalidator->invalidate(['taxonomies', 'entries']);
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
     * Public list of active tags for a collection.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listPublic(string $lang, string $collectionKey): array
    {
        /** @var \App\Models\CollectionModel $collectionModel */
        $collectionModel = model(\App\Models\CollectionModel::class);
        $collection = $collectionModel
            ->where('collection_key', $collectionKey)
            ->where('is_active', 1)
            ->first();

        if (! $collection instanceof \App\Entities\CollectionEntity) {
            return [];
        }

        /** @var \App\Models\TagModel $tagModel */
        $tagModel = model(\App\Models\TagModel::class);
        $tags = $tagModel
            ->where('is_active', 1)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        if ($tags === []) {
            return [];
        }

        $result = [];
        foreach ($tags as $tag) {
            if (! $tag instanceof \App\Entities\TagEntity) {
                continue;
            }

            $resolved = $this->translationResolver->resolve('tag', (int) $tag->id, $lang);
            $result[] = [
                'id'          => (int) $tag->id,
                'slug'        => $resolved['slug'] ?? '',
                'name'        => $resolved['name'] ?? '',
                'is_fallback' => $resolved['is_fallback'] ?? false,
            ];
        }

        return $result;
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
