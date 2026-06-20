<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\CollectionEntity;
use App\Interfaces\Cms\CollectionServiceInterface;
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
    /** @var array<mixed>|null */
    private ?array $tempTranslations = null;

    /**
     * @param RepositoryInterface<CollectionEntity> $collectionRepository
     */
    public function __construct(
        RepositoryInterface $collectionRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($collectionRepository, $responseMapper);
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);

        // Key uniqueness check
        $existingKey = $this->repository->findBy('collection_key', $data['collection_key']);
        if ($existingKey) {
            throw new ValidationException(
                lang('Collections.key_must_be_unique'),
                ['collection_key' => lang('Collections.key_already_taken', [$data['collection_key']])]
            );
        }

        // Prefix uniqueness check
        $existingPrefix = $this->repository->findBy('url_prefix', $data['url_prefix']);
        if ($existingPrefix) {
            throw new ValidationException(
                lang('Collections.prefix_must_be_unique'),
                ['url_prefix' => lang('Collections.prefix_already_taken', [$data['url_prefix']])]
            );
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
        $this->tempTranslations = null;
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);

        if (array_key_exists('collection_key', $data)) {
            $existing = $this->repository->findBy('collection_key', $data['collection_key']);
            if ($existing && (int) $existing->id !== $id) {
                throw new ValidationException(
                    lang('Collections.key_must_be_unique'),
                    ['collection_key' => lang('Collections.key_already_taken', [$data['collection_key']])]
                );
            }
        }

        if (array_key_exists('url_prefix', $data)) {
            $existing = $this->repository->findBy('url_prefix', $data['url_prefix']);
            if ($existing && (int) $existing->id !== $id) {
                throw new ValidationException(
                    lang('Collections.prefix_must_be_unique'),
                    ['url_prefix' => lang('Collections.prefix_already_taken', [$data['url_prefix']])]
                );
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
        $this->tempTranslations = null;
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
                    'name'                     => $translation->name,
                    'description'              => $translation->description,
                    'listing_title'            => $translation->listing_title,
                    'listing_intro'            => $translation->listing_intro,
                    'default_meta_title'       => $translation->default_meta_title,
                    'default_meta_description' => $translation->default_meta_description,
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

        $translationModel->where('collection_id', $collectionId)->delete();

        foreach ($translations as $translation) {
            $translationModel->insert([
                'collection_id'            => $collectionId,
                'language_id'              => (int) $translation['language_id'],
                'name'                     => $translation['name'],
                'description'              => $translation['description'] ?? null,
                'listing_title'            => $translation['listing_title'] ?? null,
                'listing_intro'            => $translation['listing_intro'] ?? null,
                'default_meta_title'       => $translation['default_meta_title'] ?? null,
                'default_meta_description' => $translation['default_meta_description'] ?? null,
            ]);
        }
    }
}
