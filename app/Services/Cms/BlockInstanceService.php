<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\BlockInstanceEntity;
use App\Interfaces\Cms\BlockInstanceServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<BlockInstanceEntity>
 */
class BlockInstanceService extends BaseCrudService implements BlockInstanceServiceInterface
{
    /** @var array<mixed>|null */
    private ?array $tempTranslations = null;

    /**
     * @param RepositoryInterface<BlockInstanceEntity> $blockInstanceRepository
     */
    public function __construct(
        RepositoryInterface $blockInstanceRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($blockInstanceRepository, $responseMapper);
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);
        if (array_key_exists('translations', $data)) {
            $this->tempTranslations = $data['translations'];
            unset($data['translations']);
        } else {
            $this->tempTranslations = null;
        }
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

        $instanceIds = array_map(fn ($entity) => (int) $entity->id, $entities);

        /** @var \App\Models\BlockInstanceTranslationModel $translationModel */
        $translationModel = model(\App\Models\BlockInstanceTranslationModel::class);
        $translations = $translationModel->whereIn('instance_id', $instanceIds)->findAll();

        $translationsGrouped = [];
        foreach ($translations as $translation) {
            /** @var \App\Entities\BlockInstanceTranslationEntity $translation */
            $translationsGrouped[$translation->instance_id][] = [
                'language_id'  => (int) $translation->language_id,
                'block_data'   => $translation->block_data,
                'is_published' => (bool) $translation->is_published,
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
    private function saveTranslations(int $instanceId, array $translations): void
    {
        /** @var \App\Models\BlockInstanceTranslationModel $translationModel */
        $translationModel = model(\App\Models\BlockInstanceTranslationModel::class);

        // Delete existing translations for this instance
        $translationModel->where('instance_id', $instanceId)->delete();

        foreach ($translations as $translation) {
            $translationModel->insert([
                'instance_id'  => $instanceId,
                'language_id'  => (int) $translation['language_id'],
                'block_data'   => json_encode($translation['block_data']),
                'is_published' => (bool) ($translation['is_published'] ?? true),
            ]);
        }
    }

    protected function applyBaseCriteria(object $builder): void
    {
        $uri = service('request')->getUri();
        $segments = $uri->getSegments();

        $ownerType = null;
        $ownerId = null;

        foreach ($segments as $index => $segment) {
            if ($segment === 'pages' && isset($segments[$index + 1]) && is_numeric($segments[$index + 1])) {
                $ownerType = 'page';
                $ownerId = (int) $segments[$index + 1];
                break;
            }
            if ($segment === 'entries' && isset($segments[$index + 1]) && is_numeric($segments[$index + 1])) {
                $ownerType = 'entry';
                $ownerId = (int) $segments[$index + 1];
                break;
            }
        }

        if ($ownerType !== null && $ownerId !== null) {
            $builder->where('owner_type', $ownerType)
                    ->where('owner_id', $ownerId);
        }
    }
}
