<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\BlockInstanceEntity;
use App\Interfaces\Cms\BlockInstanceServiceInterface;
use App\Interfaces\Cms\ResourceAuthorizationInterface;
use App\Libraries\Cms\BlockDataSanitizer;
use App\Libraries\Cms\FileReferenceSynchronizer;
use App\Libraries\Cms\FileUrlResolver;
use App\Traits\Services\HasDeferredTranslations;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<BlockInstanceEntity>
 */
class BlockInstanceService extends BaseCrudService implements BlockInstanceServiceInterface
{
    use HasDeferredTranslations;

    private ?string $filterOwnerType = null;
    private ?int $filterOwnerId = null;

    private FileUrlResolver $fileUrlResolver;

    private FileReferenceSynchronizer $fileReferenceSynchronizer;

    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    private ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer;
    private ?ResourceAuthorizationInterface $resourceAuthorization;

    public function setOwnerContext(string $ownerType, int $ownerId): void
    {
        $this->filterOwnerType = $ownerType;
        $this->filterOwnerId   = $ownerId;
    }

    public function clearOwnerContext(): void
    {
        $this->filterOwnerType = null;
        $this->filterOwnerId = null;
    }

    /**
     * @param RepositoryInterface<BlockInstanceEntity> $blockInstanceRepository
     */
    public function __construct(
        RepositoryInterface $blockInstanceRepository,
        ResponseMapperInterface $responseMapper,
        FileUrlResolver $fileUrlResolver,
        FileReferenceSynchronizer $fileReferenceSynchronizer,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        ?\App\Libraries\Cms\TranslationSynchronizer $translationSynchronizer = null,
        ?ResourceAuthorizationInterface $resourceAuthorization = null
    ) {
        parent::__construct($blockInstanceRepository, $responseMapper);
        $this->fileUrlResolver = $fileUrlResolver;
        $this->fileReferenceSynchronizer = $fileReferenceSynchronizer;
        $this->cacheInvalidator = $cacheInvalidator;
        $this->translationSynchronizer = $translationSynchronizer;
        $this->resourceAuthorization = $resourceAuthorization;
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        if ($this->filterOwnerType !== null && $this->filterOwnerId !== null) {
            // Nested routes are authoritative. Never let a client move a new
            // block to another owner by changing the body fields.
            $data['owner_type'] = $this->filterOwnerType;
            $data['owner_id'] = $this->filterOwnerId;
        }
        if ($context !== null && $this->resourceAuthorization !== null) {
            $this->resourceAuthorization->assertCan(
                (string) ($data['owner_type'] ?? ''),
                (int) ($data['owner_id'] ?? 0),
                'write',
                $context,
            );
        }
        $data = parent::beforeStore($data, $context);
        $data = $this->normalizeBlockConfig($data);

        return $this->deferTranslationsFromUpdate($data);
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        $this->flushDeferredTranslations(fn (array $t) => $this->saveTranslations((int) $entity->id, $t));
        $this->fileReferenceSynchronizer->syncBlockInstance((int) $entity->id);
        $this->cacheInvalidator->invalidate($this->cacheScopesForEntity($entity));
    }

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $existing = ($context !== null || $this->filterOwnerType !== null) ? $this->repository->find($id) : null;
        $this->assertRouteOwnerMatches($existing);
        if ($context !== null && $this->resourceAuthorization !== null && $existing instanceof BlockInstanceEntity) {
            $this->resourceAuthorization->assertCan($existing->owner_type, (int) $existing->owner_id, 'write', $context);
        }
        // Ownership is a URL/domain concern, never an editable block field.
        unset($data['owner_type'], $data['owner_id']);
        $data = parent::beforeUpdate($id, $data, $context);
        $data = $this->normalizeBlockConfig($data);

        return $this->deferTranslationsFromUpdate($data);
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->flushDeferredTranslations(fn (array $t) => $this->saveTranslations((int) $entity->id, $t));
        $this->fileReferenceSynchronizer->syncBlockInstance((int) $entity->id);
        $this->cacheInvalidator->invalidate($this->cacheScopesForEntity($entity));
    }

    protected function beforeDelete(int $id, ?SecurityContext $context): void
    {
        parent::beforeDelete($id, $context);
        $existing = $this->repository->find($id);
        $this->assertRouteOwnerMatches($existing);
        if ($context !== null && $this->resourceAuthorization !== null && $existing instanceof BlockInstanceEntity) {
            $this->resourceAuthorization->assertCan($existing->owner_type, (int) $existing->owner_id, 'write', $context);
        }
        $this->assertBlockNotLocked($id);
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate($this->cacheScopesForEntity($entity));
    }

    /**
     * Throws AuthorizationException when the block instance is locked by its
     * collection's block_template. Only applies to entry-owned instances.
     * destroy() already guarantees the instance exists before this hook runs.
     *
     * @throws \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException
     */
    private function assertBlockNotLocked(int $instanceId): void
    {
        /** @var BlockInstanceEntity|null $instance */
        $instance = $this->repository->find($instanceId);
        if (!$instance instanceof BlockInstanceEntity || $instance->owner_type !== 'entry') {
            return;
        }

        /** @var \App\Models\EntryModel $entryModel */
        $entryModel = model(\App\Models\EntryModel::class);
        $entry = $entryModel->find((int) $instance->owner_id);
        if (!$entry instanceof \App\Entities\EntryEntity) {
            return;
        }

        /** @var \App\Models\CollectionModel $collectionModel */
        $collectionModel = model(\App\Models\CollectionModel::class);
        $collection = $collectionModel->find((int) $entry->collection_id);
        if (!$collection instanceof \App\Entities\CollectionEntity) {
            return;
        }

        $blockType = $this->blockTypeById((int) $instance->block_id);
        if ($blockType === null) {
            return;
        }

        if ($collection->isBlockLocked((string) $blockType->block_key)) {
            throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(
                lang('BlockInstances.locked_by_template')
            );
        }
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
            } else {
                $entity->block_config = is_array($entity->block_config) ? $entity->block_config : [];
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

        $rows = [];
        foreach ($translations as $translation) {
            $blockData = $translation['block_data'] ?? [];
            if (! is_array($blockData)) {
                $blockData = [];
            } else {
                $blockData = BlockDataSanitizer::clean($blockData);
                if ($blockSchemaFields !== []) {
                    $blockData = $this->fileUrlResolver->normalizeBlockData($blockData, $blockSchemaFields);
                }
            }

            $rows[] = [
                'language_id'  => (int) $translation['language_id'],
                'block_data'   => json_encode($blockData),
                'is_published' => (bool) ($translation['is_published'] ?? true),
            ];
        }

        ($this->translationSynchronizer ?? throw new \LogicException(lang('Api.translationSynchronizerRequired')))->replace(
            $translationModel,
            'instance_id',
            $instanceId,
            $rows,
            static fn (array $row): array => $row,
        );
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
            $blockId = isset($data['block_id']) ? (int) $data['block_id'] : 0;
            if ($blockId > 0) {
                $schemaDefinition = $this->blockSchemaDefinition($blockId);
                $configFields = is_array($schemaDefinition['config_fields'] ?? null)
                    ? (array) $schemaDefinition['config_fields']
                    : [];
                if ($configFields !== []) {
                    $blockConfig = $this->fileUrlResolver->normalizeBlockConfig($blockConfig, $configFields);
                }
            }

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
     * @return array<string, array<string, mixed>>
     */
    private function blockSchemaFields(int $instanceId): array
    {
        $schemaDefinition = $this->blockSchemaDefinitionByInstance($instanceId);
        $fields = $schemaDefinition['fields'] ?? [];

        return is_array($fields) ? $fields : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function blockSchemaDefinition(int $blockId): array
    {
        $blockType = $this->blockTypeById($blockId);
        if ($blockType === null) {
            return [];
        }

        $schemaDefinition = $blockType->schema_definition ?? null;
        if (is_string($schemaDefinition) && trim($schemaDefinition) !== '') {
            $decoded = json_decode($schemaDefinition, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($schemaDefinition) ? $schemaDefinition : [];
    }

    private function blockTypeById(int $blockId): ?\App\Entities\BlockTypeEntity
    {
        if ($blockId <= 0) {
            return null;
        }

        $blockType = (new \App\Models\BlockTypeModel())->find($blockId);

        return $blockType instanceof \App\Entities\BlockTypeEntity ? $blockType : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function blockSchemaDefinitionByInstance(int $instanceId): array
    {
        $row = $this->repository->find($instanceId);
        $blockId = isset($row->block_id) ? (int) $row->block_id : null;

        return $blockId !== null ? $this->blockSchemaDefinition($blockId) : [];
    }

    protected function applyQueryOptions(array $criteria): array
    {
        $criteria = parent::applyQueryOptions($criteria);

        if (empty($criteria['sort'])) {
            $criteria['sort'] = 'sort_order';
        }

        return $criteria;
    }

    public function store(\dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface $request, ?SecurityContext $context = null): \dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface
    {
        try {
            return parent::store($request, $context);
        } finally {
            $this->clearOwnerContext();
        }
    }

    public function update(int $id, \dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface $request, ?SecurityContext $context = null): \dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface
    {
        try {
            return parent::update($id, $request, $context);
        } finally {
            $this->clearOwnerContext();
        }
    }

    public function destroy(int $id, ?SecurityContext $context = null): bool
    {
        try {
            return parent::destroy($id, $context);
        } finally {
            $this->clearOwnerContext();
        }
    }

    public function index(\dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface $request, ?SecurityContext $context = null): \dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface
    {
        try {
            if ($context !== null && $this->resourceAuthorization !== null && $this->filterOwnerType !== null && $this->filterOwnerId !== null) {
                $this->resourceAuthorization->assertCan($this->filterOwnerType, $this->filterOwnerId, 'read', $context);
            }

            return parent::index($request, $context);
        } finally {
            $this->clearOwnerContext();
        }
    }

    public function show(int $id, ?SecurityContext $context = null): \dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface
    {
        try {
            $instance = $this->repository->find($id);
            $this->assertRouteOwnerMatches($instance);
            if ($context !== null && $this->resourceAuthorization !== null && $instance instanceof BlockInstanceEntity) {
                $this->resourceAuthorization->assertCan($instance->owner_type, (int) $instance->owner_id, 'read', $context);
            }

            return parent::show($id, $context);
        } finally {
            $this->clearOwnerContext();
        }
    }

    private function assertRouteOwnerMatches(?object $instance): void
    {
        if ($this->filterOwnerType === null || $this->filterOwnerId === null || $instance === null) {
            return;
        }

        if (! $instance instanceof BlockInstanceEntity
            || $instance->owner_type !== $this->filterOwnerType
            || (int) $instance->owner_id !== $this->filterOwnerId
        ) {
            throw new \dcardenasl\Ci4ApiCore\Exceptions\NotFoundException(lang('Api.resourceNotFound'));
        }
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
