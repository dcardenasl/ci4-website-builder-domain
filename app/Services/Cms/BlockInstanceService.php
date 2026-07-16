<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\BlockInstanceEntity;
use App\Interfaces\Cms\BlockInstanceServiceInterface;
use App\Libraries\Cms\BlockTextPayload;
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
        /** @var array<int, array<string, mixed>> $schemaByBlockId */
        $schemaByBlockId = [];
        foreach ($blockTypeEntities as $bt) {
            $blockKeyById[(int) $bt->id] = (string) $bt->block_key;
            $schemaDefinition = $bt->schema_definition ?? null;
            if (is_string($schemaDefinition)) {
                $decoded = json_decode($schemaDefinition, true);
                $schemaByBlockId[(int) $bt->id] = is_array($decoded) ? $decoded : [];
            } elseif (is_array($schemaDefinition)) {
                $schemaByBlockId[(int) $bt->id] = $schemaDefinition;
            } else {
                $schemaByBlockId[(int) $bt->id] = [];
            }
        }

        $defaultLangId = $this->defaultLanguageId();

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

            $schemaDefinition = $schemaByBlockId[$bid] ?? [];
            if ($schemaDefinition !== []) {
                $entity->block_config = $this->hydrateLegacyMediaReferences(
                    $entity->block_config,
                    $entity->translations,
                    $schemaDefinition,
                    $defaultLangId
                );
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
                $blockData = BlockTextPayload::normalize($blockData);
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
        $schemaDefinition = $this->blockSchemaDefinitionByInstance($instanceId);
        $fields = $schemaDefinition['fields'] ?? [];

        return is_array($fields) ? $fields : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function blockSchemaDefinition(int $blockId): array
    {
        if ($blockId <= 0) {
            return [];
        }

        $blockType = (new \App\Models\BlockTypeModel())->find($blockId);
        if (! $blockType instanceof \App\Entities\BlockTypeEntity) {
            return [];
        }

        $schemaDefinition = $blockType->schema_definition ?? null;
        if (is_string($schemaDefinition) && trim($schemaDefinition) !== '') {
            $decoded = json_decode($schemaDefinition, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($schemaDefinition) ? $schemaDefinition : [];
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

    /**
     * @param array<string, mixed> $blockConfig
     * @param list<array{language_id: int, block_data?: mixed}> $translations
     * @param array<string, mixed> $schemaDefinition
     * @return array<string, mixed>
     */
    private function hydrateLegacyMediaReferences(array $blockConfig, array $translations, array $schemaDefinition, ?int $defaultLangId): array
    {
        $configFields = is_array($schemaDefinition['config_fields'] ?? null)
            ? (array) $schemaDefinition['config_fields']
            : [];

        if ($configFields === []) {
            return $blockConfig;
        }

        foreach ($configFields as $fieldKey => $fieldDef) {
            if (! is_array($fieldDef) || strtolower((string) ($fieldDef['type'] ?? 'string')) !== 'media_reference') {
                continue;
            }

            $current = $blockConfig[$fieldKey] ?? null;
            if (is_array($current) && (($current['file_id'] ?? null) !== null || trim((string) ($current['url'] ?? '')) !== '')) {
                $blockConfig[$fieldKey] = $this->fileUrlResolver->normalizeMediaReference($current);
                continue;
            }

            $legacyReference = $this->legacyMediaReferenceFromTranslations($translations, (string) $fieldKey, $defaultLangId);
            if ($legacyReference !== null) {
                $blockConfig[$fieldKey] = $legacyReference;
                continue;
            }

            if (is_array($current)) {
                $blockConfig[$fieldKey] = $this->fileUrlResolver->normalizeMediaReference($current);
            }
        }

        return $blockConfig;
    }

    /**
     * @param list<array{language_id: int, block_data?: mixed}> $translations
     * @return array{source_kind: string, file_id: int|null, url: string|null}|null
     */
    private function legacyMediaReferenceFromTranslations(array $translations, string $fieldKey, ?int $defaultLangId): ?array
    {
        $orderedTranslations = $translations;
        usort(
            $orderedTranslations,
            static function (array $a, array $b) use ($defaultLangId): int {
                $aDefault = $defaultLangId !== null && (int) ($a['language_id'] ?? 0) === $defaultLangId;
                $bDefault = $defaultLangId !== null && (int) ($b['language_id'] ?? 0) === $defaultLangId;
                return ($aDefault === $bDefault) ? 0 : ($aDefault ? -1 : 1);
            }
        );

        foreach ($orderedTranslations as $translation) {
            $blockData = $translation['block_data'] ?? null;
            if (is_string($blockData)) {
                $decoded = json_decode($blockData, true);
                $blockData = is_array($decoded) ? $decoded : [];
            }

            if (! is_array($blockData)) {
                continue;
            }

            $candidate = $this->legacyMediaReferenceFromBlockData($blockData, $fieldKey);
            if ($candidate !== null) {
                return $this->fileUrlResolver->normalizeMediaReference($candidate);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $blockData
     * @return array<string, mixed>|null
     */
    private function legacyMediaReferenceFromBlockData(array $blockData, string $fieldKey): ?array
    {
        if (is_array($blockData[$fieldKey] ?? null)) {
            return $blockData[$fieldKey];
        }

        $fileIdKey = $fieldKey . '_file_id';
        $urlKey    = $fieldKey . '_url';
        $legacyFileId = $blockData[$fileIdKey] ?? $blockData['file_id'] ?? null;
        $legacyUrl = $blockData[$urlKey] ?? $blockData['file_url'] ?? null;

        if ($legacyFileId === null && $legacyUrl === null) {
            return null;
        }

        return [
            'file_id' => $legacyFileId,
            'url' => is_string($legacyUrl) ? $legacyUrl : (is_scalar($legacyUrl) ? (string) $legacyUrl : null),
        ];
    }

    private function defaultLanguageId(): ?int
    {
        /** @var \App\Models\LanguageModel $languageModel */
        $languageModel = model(\App\Models\LanguageModel::class);
        $language = $languageModel->where('is_default', 1)->first();

        return isset($language->id) ? (int) $language->id : null;
    }

    protected function applyQueryOptions(array $criteria): array
    {
        $criteria = parent::applyQueryOptions($criteria);

        if (empty($criteria['sort'])) {
            $criteria['sort'] = 'sort_order';
        }

        return $criteria;
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
