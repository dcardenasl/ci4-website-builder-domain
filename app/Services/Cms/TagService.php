<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\TagEntity;
use App\Interfaces\Cms\TagServiceInterface;
use App\Traits\Services\HasTranslatableTaxonomyLifecycle;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<TagEntity>
 */
class TagService extends BaseCrudService implements TagServiceInterface
{
    use HasTranslatableTaxonomyLifecycle;

    private \App\Libraries\Cms\TranslationResolver $translationResolver;

    private ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer;

    /**
     * @param RepositoryInterface<TagEntity> $tagRepository
     */
    public function __construct(
        RepositoryInterface $tagRepository,
        ResponseMapperInterface $responseMapper,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        \App\Libraries\Cms\TranslationResolver $translationResolver,
        ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer = null
    ) {
        parent::__construct($tagRepository, $responseMapper);
        $this->cacheInvalidator = $cacheInvalidator;
        $this->translationResolver = $translationResolver;
        $this->translationSynchronizer = $translationSynchronizer;
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
    protected function saveTranslations(int $tagId, array $translations): void
    {
        /** @var \App\Models\TagTranslationModel $translationModel */
        $translationModel = model(\App\Models\TagTranslationModel::class);

        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $translationModel,
            'tag_id',
            $tagId,
            $translations,
            static fn (array $translation): array => [
                'language_id' => (int) $translation['language_id'],
                'slug'        => $translation['slug'],
                'name'        => $translation['name'],
            ],
        );
    }
}
