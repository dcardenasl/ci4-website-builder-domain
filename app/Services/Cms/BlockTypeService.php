<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\BlockTypeEntity;
use App\Interfaces\Cms\BlockTypeServiceInterface;
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

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    // Custom methods declared in BlockTypeServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
