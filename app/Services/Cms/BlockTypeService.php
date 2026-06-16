<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\BlockTypeEntity;
use App\Interfaces\Cms\BlockTypeServiceInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<BlockTypeEntity>
 */
class BlockTypeService extends BaseCrudService implements BlockTypeServiceInterface
{
    /**
     * @param RepositoryInterface<BlockTypeEntity> $blockTypeRepository
     */
    public function __construct(
        RepositoryInterface $blockTypeRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($blockTypeRepository, $responseMapper);
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
        }

        return $data;
    }

    /**
     * @param mixed $schemaDefinition
     */
    private function normalizeSchemaDefinition(mixed $schemaDefinition): string
    {
        if (is_string($schemaDefinition)) {
            return $schemaDefinition;
        }

        if (is_array($schemaDefinition) || is_object($schemaDefinition)) {
            return json_encode($schemaDefinition, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        }

        return '{}';
    }
}
