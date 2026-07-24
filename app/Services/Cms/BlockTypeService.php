<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\BlockTypeEntity;
use App\Interfaces\Cms\BlockTypeServiceInterface;
use CodeIgniter\Database\BaseConnection;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ConflictException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<BlockTypeEntity>
 */
class BlockTypeService extends BaseCrudService implements BlockTypeServiceInterface
{
    private const ALLOWED_CONTENT_SOURCES = ['manual', 'page', 'collection', 'entry', 'container'];

    /** @var BaseConnection<mixed, mixed> */
    private BaseConnection $db;

    private bool $schemaChanged = false;

    /**
     * @param RepositoryInterface<BlockTypeEntity> $blockTypeRepository
     * @param BaseConnection<mixed, mixed> $db
     */
    public function __construct(
        RepositoryInterface $blockTypeRepository,
        ResponseMapperInterface $responseMapper,
        BaseConnection $db,
        private readonly \App\Libraries\Cms\FileReferenceSynchronizer $fileReferenceSynchronizer,
        private readonly \App\Libraries\Cms\OwnerUsageResolver $ownerUsageResolver,
    ) {
        parent::__construct($blockTypeRepository, $responseMapper);
        $this->db = $db;
    }

    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);

        $existing = $this->repository->findBy('block_key', $data['block_key']);
        if ($existing) {
            throw new ValidationException(
                lang('Api.validationFailed'),
                ['block_key' => lang('Cms.block_types.block_key_already_taken', [$data['block_key']])]
            );
        }

        $data['schema_definition'] = $this->normalizeSchemaDefinition($data['schema_definition'] ?? null);

        return $data;
    }

    protected function beforeDelete(int $id, ?SecurityContext $context): void
    {
        parent::beforeDelete($id, $context);

        $usages = $this->getUsages($id);
        if ($usages === []) {
            return;
        }

        $descriptions = array_map(fn (array $usage): string => $this->describeUsage($usage), $usages);

        throw new ConflictException(
            lang('Cms.block_types.in_use', [(string) count($usages), implode('; ', $descriptions)])
        );
    }

    /**
     * @return list<array{resource: string, resource_id: int, role: string, label: string|null, context: array{owner_type: string, owner_id: int}}>
     */
    public function getUsages(int $blockTypeId): array
    {
        $instancesResult = $this->db->table('cms_block_instances')
            ->select('id, owner_type, owner_id')
            ->where('block_id', $blockTypeId)
            ->get();

        /** @var list<array{id: int|string, owner_type: string, owner_id: int|string}> $instances */
        $instances = $instancesResult ? $instancesResult->getResultArray() : [];

        $owners = array_map(
            static fn (array $instance): array => [
                'owner_type' => (string) $instance['owner_type'],
                'owner_id'   => (int) $instance['owner_id'],
            ],
            $instances
        );
        $ownerTitles = $this->ownerUsageResolver->resolveTitles($owners);

        return array_map(function (array $instance) use ($ownerTitles): array {
            $ownerType = (string) $instance['owner_type'];
            $ownerId   = (int) $instance['owner_id'];

            return [
                'resource'    => 'block_instances',
                'resource_id' => (int) $instance['id'],
                'role'        => $ownerType,
                'label'       => $ownerTitles[$ownerType . ':' . $ownerId] ?? null,
                'context'     => [
                    'owner_type' => $ownerType,
                    'owner_id'   => $ownerId,
                ],
            ];
        }, $instances);
    }

    /**
     * @param array{resource: string, resource_id: int, role: string, label: string|null, context: array{owner_type: string, owner_id: int}} $usage
     */
    private function describeUsage(array $usage): string
    {
        $ownerType  = $usage['context']['owner_type'];
        $ownerId    = $usage['context']['owner_id'];
        $instanceId = $usage['resource_id'];
        $title      = $usage['label'];

        $label = $ownerType === 'page' ? lang('Cms.block_types.usage_page') : lang('Cms.block_types.usage_entry');

        return $title !== null
            ? sprintf('%s "%s" (id %d, %s #%d)', $label, $title, $ownerId, lang('Cms.block_types.usage_instance'), $instanceId)
            : sprintf('%s (id %d, %s #%d)', $label, $ownerId, lang('Cms.block_types.usage_instance'), $instanceId);
    }

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    // Custom methods declared in BlockTypeServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');

    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);
        $this->schemaChanged = false;

        if (array_key_exists('block_key', $data)) {
            $existing = $this->repository->findBy('block_key', $data['block_key']);
            if ($existing && (int) $existing->id !== $id) {
                throw new ValidationException(
                    lang('Api.validationFailed'),
                    ['block_key' => lang('Cms.block_types.block_key_already_taken', [$data['block_key']])]
                );
            }
        }

        if (array_key_exists('schema_definition', $data)) {
            $data['schema_definition'] = $this->normalizeSchemaDefinition($data['schema_definition']);
            $this->schemaChanged = true;
        }

        return $data;
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);

        if (! $this->schemaChanged) {
            return;
        }

        $result = $this->db->table('cms_block_instances')
            ->select('id')
            ->where('block_id', (int) $entity->id)
            ->get();

        foreach ($result ? $result->getResultArray() : [] as $row) {
            $instanceId = (int) ($row['id'] ?? 0);
            if ($instanceId > 0) {
                $this->fileReferenceSynchronizer->syncBlockInstance($instanceId);
            }
        }

        $this->schemaChanged = false;
    }

    /**
     * @param mixed $schemaDefinition
     */
    private function normalizeSchemaDefinition(mixed $schemaDefinition): string
    {
        if (is_string($schemaDefinition)) {
            $decoded = json_decode($schemaDefinition, true);
            if (is_array($decoded)) {
                $this->assertValidSchemaDefinition($decoded);
                return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
            }

            return $schemaDefinition;
        }

        if (is_array($schemaDefinition) || is_object($schemaDefinition)) {
            $normalized = is_array($schemaDefinition)
                ? $schemaDefinition
                : json_decode((string) json_encode($schemaDefinition), true);
            if (is_array($normalized)) {
                $this->assertValidSchemaDefinition($normalized);
                return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
            }
        }

        return '{}';
    }

    /**
     * @param array<string, mixed> $schemaDefinition
     */
    private function assertValidSchemaDefinition(array $schemaDefinition): void
    {
        $contentSource = $schemaDefinition['content_source'] ?? null;
        if ($contentSource === null) {
            return;
        }

        if (! is_array($contentSource)) {
            throw new ValidationException(
                lang('Api.validationFailed'),
                ['schema_definition' => lang('BlockTypes.invalid_content_source')]
            );
        }

        $sourceType = (string) ($contentSource['type'] ?? '');
        if ($sourceType === '' || ! in_array($sourceType, self::ALLOWED_CONTENT_SOURCES, true)) {
            throw new ValidationException(
                lang('Api.validationFailed'),
                ['schema_definition' => lang('BlockTypes.invalid_content_source')]
            );
        }
    }
}
