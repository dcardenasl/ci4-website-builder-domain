<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\EntryEntity;
use App\Interfaces\Cms\EntryServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
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

    /**
     * @param RepositoryInterface<EntryEntity> $entryRepository
     */
    public function __construct(
        RepositoryInterface $entryRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($entryRepository, $responseMapper);
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

        foreach ($entities as $entity) {
            $entity->translations = $translationsGrouped[$entity->id] ?? [];
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

        $translationModel->where('entry_id', $entryId)->delete();

        foreach ($translations as $translation) {
            $translationModel->insert([
                'entry_id'          => $entryId,
                'language_id'      => (int) $translation['language_id'],
                'slug'             => $translation['slug'],
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
            ]);
        }
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
