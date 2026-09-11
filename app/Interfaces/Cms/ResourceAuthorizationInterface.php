<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use dcardenasl\Ci4ApiCore\Dto\SecurityContext;

interface ResourceAuthorizationInterface
{
    public function assertCan(string $resourceType, int $resourceId, string $action, ?SecurityContext $context): void;

    /**
     * Add a database-backed scope to an administrative list criteria array.
     *
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function scopeCriteria(string $resourceType, array $criteria, ?SecurityContext $context): array;

    /**
     * Add the scope marker consumed by SQL projection repositories.
     *
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function projectionCriteria(string $resourceType, array $criteria, ?SecurityContext $context): array;

    public function grantOnCreate(string $resourceType, int $resourceId, ?SecurityContext $context): void;

    /**
     * @return list<array{user_id: int, access_level: string}>
     */
    public function listGrants(string $resourceType, int $resourceId, ?SecurityContext $context): array;

    public function grant(string $resourceType, int $resourceId, int $userId, string $accessLevel, SecurityContext $context): void;

    public function revoke(string $resourceType, int $resourceId, int $userId, SecurityContext $context): void;

    public function transferOwnership(string $resourceType, int $resourceId, int $userId, SecurityContext $context): void;
}
