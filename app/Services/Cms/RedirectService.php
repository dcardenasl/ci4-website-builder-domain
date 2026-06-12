<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\RedirectEntity;
use App\Interfaces\Cms\RedirectServiceInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<RedirectEntity>
 */
class RedirectService extends BaseCrudService implements RedirectServiceInterface
{
    /**
     * @param RepositoryInterface<RedirectEntity> $redirectRepository
     */
    public function __construct(
        RepositoryInterface $redirectRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($redirectRepository, $responseMapper);
    }

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    // Custom methods declared in RedirectServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
