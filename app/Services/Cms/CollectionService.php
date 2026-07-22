<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\CollectionEntity;
use App\Entities\LanguageEntity;
use App\Interfaces\Cms\CollectionServiceInterface;
use App\Traits\Services\HasDeferredTranslations;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<CollectionEntity>
 */
class CollectionService extends BaseCrudService implements CollectionServiceInterface
{
    use HasDeferredTranslations;

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    private ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer;

    /**
     * @param RepositoryInterface<CollectionEntity> $collectionRepository
     * @param RepositoryInterface<LanguageEntity> $languageRepository
     */
    public function __construct(
        RepositoryInterface $collectionRepository,
        ResponseMapperInterface $responseMapper,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        private readonly RepositoryInterface $languageRepository,
        private readonly PublicCollectionReader $publicCollectionReader,
        ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer = null
    ) {
        parent::__construct($collectionRepository, $responseMapper);
        $this->cacheInvalidator = $cacheInvalidator;
        $this->translationSynchronizer = $translationSynchronizer;
    }

    /**
     * List all active collections resolved by the request language.
     *
     * @return list<array<string, mixed>>
     */
    public function listPublic(string $lang): array
    {
        /** @var list<CollectionEntity> $collections */
        $collections = $this->repository->getModel()
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        /** @var list<LanguageEntity> $activeLanguages */
        $activeLanguages = $this->languageRepository->getModel()
            ->where('is_active', 1)
            ->findAll();

        return $this->publicCollectionReader->listPublic($collections, $activeLanguages, $lang);
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);
        unset($data['use_preset']);

        // Key uniqueness check
        $existingKey = $this->repository->findBy('collection_key', $data['collection_key']);
        if ($existingKey) {
            throw new ValidationException(
                lang('Collections.key_must_be_unique'),
                ['collection_key' => lang('Collections.key_already_taken', [$data['collection_key']])]
            );
        }

        $data = $this->deferTranslationsFromCreate($data);

        if ($this->tempTranslations !== null) {
            $this->assertTranslationSlugsAreAvailable($this->tempTranslations);
        }

        return $data;
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        $this->flushDeferredTranslations(fn (array $t) => $this->saveTranslations((int) $entity->id, $t));
        $this->cacheInvalidator->invalidate(['collections', 'entries']);
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);
        unset($data['use_preset']);

        if (array_key_exists('collection_key', $data)) {
            $existing = $this->repository->findBy('collection_key', $data['collection_key']);
            if ($existing && (int) $existing->id !== $id) {
                throw new ValidationException(
                    lang('Collections.key_must_be_unique'),
                    ['collection_key' => lang('Collections.key_already_taken', [$data['collection_key']])]
                );
            }
        }

        $data = $this->deferTranslationsFromUpdate($data);

        if ($this->tempTranslations !== null) {
            $this->assertTranslationSlugsAreAvailable($this->tempTranslations, $id);
        }

        return $data;
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->flushDeferredTranslations(fn (array $t) => $this->saveTranslations((int) $entity->id, $t));
        $this->cacheInvalidator->invalidate(['collections', 'entries']);
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['collections', 'entries']);
    }

    protected function beforeDelete(int $id, ?SecurityContext $context): void
    {
        /** @var \App\Models\EntryModel $entryModel */
        $entryModel = model(\App\Models\EntryModel::class);

        $activeCount = $entryModel->where('collection_id', $id)->countAllResults();
        if ($activeCount > 0) {
            throw new ValidationException(
                lang('Collections.cannot_delete_has_entries'),
                ['entries' => lang('Collections.delete_entries_first', [$activeCount])]
            );
        }

        // Soft-deleted entries still hold the FK — purge them so the collection DELETE isn't blocked.
        $entryModel->withDeleted()->where('collection_id', $id)->purgeDeleted();
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        $collectionIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var \App\Models\CollectionTranslationModel $translationModel */
        $translationModel = model(\App\Models\CollectionTranslationModel::class);
        $translations = $translationModel->whereIn('collection_id', $collectionIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            if ($translation instanceof \App\Entities\CollectionTranslationEntity) {
                $translationsGrouped[$translation->collection_id][] = [
                    'language_id'              => (int) $translation->language_id,
                    'slug'                     => $translation->slug,
                    'name'                     => $translation->name,
                    'description'              => $translation->description,
                    'listing_title'            => $translation->listing_title,
                    'listing_intro'            => $translation->listing_intro,
                    'default_meta_title'       => $translation->default_meta_title,
                    'default_meta_description' => $translation->default_meta_description,
                    'entry_cta_label'          => $translation->entry_cta_label,
                ];
            }
        }

        foreach ($entities as $entity) {
            $entity->translations = $translationsGrouped[$entity->id] ?? [];
        }

        return $entities;
    }

    /**
     * @param array<mixed> $translations
     */
    private function saveTranslations(int $collectionId, array $translations): void
    {
        /** @var \App\Models\CollectionTranslationModel $translationModel */
        $translationModel = model(\App\Models\CollectionTranslationModel::class);

        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $translationModel,
            'collection_id',
            $collectionId,
            $translations,
            static fn (array $translation): array => [
                'language_id'              => (int) $translation['language_id'],
                'slug'                     => isset($translation['slug']) ? trim((string) $translation['slug'], " \t\n\r\0\x0B/") : null,
                'name'                     => (string) $translation['name'],
                'description'              => $translation['description'] ?? null,
                'listing_title'            => $translation['listing_title'] ?? null,
                'listing_intro'            => $translation['listing_intro'] ?? null,
                'default_meta_title'       => $translation['default_meta_title'] ?? null,
                'default_meta_description' => $translation['default_meta_description'] ?? null,
                'entry_cta_label'          => $translation['entry_cta_label'] ?? null,
            ],
        );
    }

    /**
     * @param array<mixed> $translations
     */
    private function assertTranslationSlugsAreAvailable(array $translations, ?int $currentCollectionId = null): void
    {
        /** @var \App\Models\CollectionTranslationModel $translationModel */
        $translationModel = model(\App\Models\CollectionTranslationModel::class);

        foreach ($translations as $translation) {
            if (! is_array($translation)) {
                continue;
            }

            $slug = trim((string) ($translation['slug'] ?? ''), " \t\n\r\0\x0B/");
            $languageId = (int) ($translation['language_id'] ?? 0);

            if ($slug === '' || $languageId <= 0) {
                continue;
            }

            if (! $translationModel->isSlugAvailable($slug, $languageId, $currentCollectionId)) {
                throw new ValidationException(
                    lang('Collections.slug_must_be_unique'),
                    ['translations' => lang('Collections.slug_already_taken', [$slug])]
                );
            }
        }
    }

    public function isSlugAvailable(string $slug, int $languageId, ?int $currentId = null): bool
    {
        return (new \App\Models\CollectionTranslationModel())->isSlugAvailable($slug, $languageId, $currentId);
    }
}
