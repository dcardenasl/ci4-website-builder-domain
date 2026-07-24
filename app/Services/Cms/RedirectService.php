<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Entities\RedirectEntity;
use App\Interfaces\Cms\RedirectServiceInterface;
use App\Libraries\Cms\PublicRedirectResolver;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<RedirectEntity>
 */
class RedirectService extends BaseCrudService implements RedirectServiceInterface
{
    private \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator;

    /**
     * @param RepositoryInterface<RedirectEntity> $redirectRepository
     */
    public function __construct(
        RepositoryInterface $redirectRepository,
        ResponseMapperInterface $responseMapper,
        \App\Libraries\Cms\CacheInvalidationClient $cacheInvalidator,
        private readonly PublicRedirectResolver $publicRedirectResolver
    ) {
        parent::__construct($redirectRepository, $responseMapper);
        $this->cacheInvalidator = $cacheInvalidator;
    }

    /**
     * @param list<string> $segments
     * @return array{new_url: string, redirect_type: int}
     */
    public function resolvePublic(array $segments): array
    {
        return $this->publicRedirectResolver->resolve($segments);
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        $this->cacheInvalidator->invalidate(['redirects']);
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->cacheInvalidator->invalidate(['redirects']);
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['redirects']);
    }
}
