<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\ResourceGrantRequestDTO;
use App\DTO\Request\Cms\ResourceOwnershipTransferRequestDTO;
use App\Interfaces\Cms\ResourceAuthorizationInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

/** Operational surface for assigning and transferring Domain-owned scope. */
final class ResourceAccessController extends ApiController
{
    private ResourceAuthorizationInterface $resourceAuthorization;

    protected function resolveDefaultService(): ResourceAuthorizationInterface
    {
        $this->resourceAuthorization = Services::resourceAuthorization();

        return $this->resourceAuthorization;
    }

    public function index(string $resourceType, int $resourceId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($resourceType, $resourceId): array {
                return [
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceId,
                    'grants' => $this->resourceAuthorization->listGrants($resourceType, $resourceId, $context),
                ];
            }
        );
    }

    public function grant(string $resourceType, int $resourceId): ResponseInterface
    {
        return $this->handleRequest(
            function (ResourceGrantRequestDTO $dto, SecurityContext $context) use ($resourceType, $resourceId): array {
                $this->resourceAuthorization->grant($resourceType, $resourceId, $dto->user_id, $dto->access_level, $context);

                return [
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceId,
                    'user_id' => $dto->user_id,
                    'access_level' => $dto->access_level,
                ];
            },
            ResourceGrantRequestDTO::class,
        );
    }

    public function revoke(string $resourceType, int $resourceId, int $userId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($resourceType, $resourceId, $userId): array {
                $this->resourceAuthorization->revoke($resourceType, $resourceId, $userId, $context);

                return ['resource_type' => $resourceType, 'resource_id' => $resourceId, 'user_id' => $userId];
            }
        );
    }

    public function transfer(string $resourceType, int $resourceId): ResponseInterface
    {
        return $this->handleRequest(
            function (ResourceOwnershipTransferRequestDTO $dto, SecurityContext $context) use ($resourceType, $resourceId): array {
                $this->resourceAuthorization->transferOwnership($resourceType, $resourceId, $dto->user_id, $context);

                return ['resource_type' => $resourceType, 'resource_id' => $resourceId, 'owner_user_id' => $dto->user_id];
            },
            ResourceOwnershipTransferRequestDTO::class,
        );
    }
}
