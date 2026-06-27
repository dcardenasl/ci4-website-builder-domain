<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\BlockInstanceEntity;
use App\Interfaces\Cms\BlockInstanceServiceInterface;
use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use App\Libraries\Cms\HtmlSanitizer;
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

    private ?string $filterOwnerType = null;
    private ?int $filterOwnerId = null;

    private FileUrlResolver $fileUrlResolver;

    private FileReferenceSynchronizer $fileReferenceSynchronizer;

    public function setOwnerContext(string $ownerType, int $ownerId): void
    {
        $this->filterOwnerType = $ownerType;
        $this->filterOwnerId   = $ownerId;
    }

    /**
     * @param RepositoryInterface<BlockInstanceEntity> $blockInstanceRepository
     */
    public function __construct(
        RepositoryInterface $blockInstanceRepository,
        ResponseMapperInterface $responseMapper,
        ?FileUrlResolver $fileUrlResolver = null,
        ?FileReferenceSynchronizer $fileReferenceSynchronizer = null
    ) {
        parent::__construct($blockInstanceRepository, $responseMapper);
        $this->fileUrlResolver = $fileUrlResolver ?? service('fileUrlResolver');
        $this->fileReferenceSynchronizer = $fileReferenceSynchronizer ?? service('fileReferenceSynchronizer');
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);
        $data = $this->normalizeBlockConfig($data);
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
        $this->fileReferenceSynchronizer->syncBlockInstance((int) $entity->id);
        $this->tempTranslations = null;
        service('cacheInvalidationClient')->invalidate($this->cacheScopesForEntity($entity));
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);
        $data = $this->normalizeBlockConfig($data);
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
        $this->fileReferenceSynchronizer->syncBlockInstance((int) $entity->id);
        $this->tempTranslations = null;
        service('cacheInvalidationClient')->invalidate($this->cacheScopesForEntity($entity));
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        service('cacheInvalidationClient')->invalidate($this->cacheScopesForEntity($entity));
    }

    protected function enrichEntities(array $entities): array
    {
        if (empty($entities)) {
            return $entities;
        }

        // ── Translations ───────────────────────────────────────────────────────
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

        // ── Block type meta (block_key) ─────────────────────────────────────────
        // Merges block_key into block_config so consumers can identify the block
        // type without a separate lookup, even when block_config was created before
        // block_key was stored explicitly.
        $uniqueBlockIds = array_unique(array_map(fn ($entity) => (int) $entity->block_id, $entities));

        /** @var \App\Models\BlockTypeModel $blockTypeModel */
        $blockTypeModel = model(\App\Models\BlockTypeModel::class);
        /** @var list<\App\Entities\BlockTypeEntity> $blockTypeEntities */
        $blockTypeEntities = $blockTypeModel->whereIn('id', $uniqueBlockIds)->findAll();

        /** @var array<int, string> $blockKeyById  id → block_key */
        $blockKeyById = [];
        foreach ($blockTypeEntities as $bt) {
            $blockKeyById[(int) $bt->id] = (string) $bt->block_key;
        }

        // ── Apply to entities ──────────────────────────────────────────────────
        foreach ($entities as $entity) {
            $entity->translations = $translationsGrouped[$entity->id] ?? [];

            $bid = (int) $entity->block_id;
            if (isset($blockKeyById[$bid])) {
                $existing = is_array($entity->block_config) ? $entity->block_config : [];
                // block_key from the block type is authoritative — always in position
                $entity->block_config = array_merge(['block_key' => $blockKeyById[$bid]], $existing);
            }
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
        $blockSchemaFields = $this->blockSchemaFields($instanceId);

        // Delete existing translations for this instance
        $translationModel->where('instance_id', $instanceId)->delete();

        foreach ($translations as $translation) {
            $blockData = $translation['block_data'] ?? [];
            if (! is_array($blockData)) {
                $blockData = [];
            } else {
                $blockData = $this->sanitizeBlockData($blockData);
                if ($blockSchemaFields !== []) {
                    $blockData = $this->fileUrlResolver->normalizeBlockData($blockData, $blockSchemaFields);
                }
            }

            $translationModel->insert([
                'instance_id'  => $instanceId,
                'language_id'  => (int) $translation['language_id'],
                'block_data'   => json_encode($blockData),
                'is_published' => (bool) ($translation['is_published'] ?? true),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeBlockConfig(array $data): array
    {
        if (! array_key_exists('block_config', $data)) {
            return $data;
        }

        $blockConfig = $data['block_config'];
        if (is_string($blockConfig) && trim($blockConfig) !== '') {
            $decoded = json_decode($blockConfig, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $blockConfig = $decoded;
            }
        }

        if (is_array($blockConfig)) {
            $data['block_config'] = json_encode($blockConfig);
        } elseif ($blockConfig === null || $blockConfig === '') {
            $data['block_config'] = null;
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function cacheScopesForEntity(object $entity): array
    {
        $ownerType = (string) ($entity->owner_type ?? 'page');

        return $ownerType === 'entry' ? ['entries'] : ['pages'];
    }

    /**
     * Recursively sanitize any string values in block_data that look like HTML,
     * so rich-text content is safe when rendered unescaped in public views.
     *
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function sanitizeBlockData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && str_contains($value, '<')) {
                $data[$key] = HtmlSanitizer::clean($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeBlockData($value);
            }
        }

        return $data;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function blockSchemaFields(int $instanceId): array
    {
        $row = $this->repository->find($instanceId);
        $blockId = isset($row->block_id) ? (int) $row->block_id : null;
        if ($blockId === null || $blockId <= 0) {
            return [];
        }

        $blockType = model(\App\Models\BlockTypeModel::class)->find($blockId);
        if (! $blockType instanceof \App\Entities\BlockTypeEntity) {
            return [];
        }

        $schemaDefinition = $blockType->schema_definition ?? null;
        if (! is_string($schemaDefinition) || trim($schemaDefinition) === '') {
            return [];
        }

        $decoded = json_decode($schemaDefinition, true);
        if (! is_array($decoded)) {
            return [];
        }

        $fields = $decoded['fields'] ?? [];
        return is_array($fields) ? $fields : [];
    }

    protected function applyBaseCriteria(object $builder): void
    {
        if ($this->filterOwnerType !== null && $this->filterOwnerId !== null) {
            $builder->where('owner_type', $this->filterOwnerType)
                    ->where('owner_id', $this->filterOwnerId);

            // Consume — reset so a shared service instance doesn't leak state.
            $this->filterOwnerType = null;
            $this->filterOwnerId   = null;
        }
    }
}
