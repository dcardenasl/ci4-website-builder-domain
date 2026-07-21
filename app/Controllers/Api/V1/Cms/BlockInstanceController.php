<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\BlockInstanceCreateRequestDTO;
use App\DTO\Request\Cms\BlockInstanceIndexRequestDTO;
use App\DTO\Request\Cms\BlockInstanceUpdateRequestDTO;
use App\Interfaces\Cms\BlockInstanceServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class BlockInstanceController extends ApiController
{
    protected BlockInstanceServiceInterface $blockInstanceService;

    protected function resolveDefaultService(): BlockInstanceServiceInterface
    {
        $this->blockInstanceService = Services::blockInstanceService();

        return $this->blockInstanceService;
    }

    private function ownerTypeFromRequest(): string
    {
        $segments = service('request')->getUri()->getSegments();

        return in_array('entries', $segments, true) ? 'entry' : 'page';
    }

    private function requiresPermission(string $action): string
    {
        $ownerType = $this->ownerTypeFromRequest();

        return $ownerType === 'entry'
            ? "cms.entries.{$action}"
            : "cms.pages.{$action}";
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function indexForPage(int $pageId): ResponseInterface
    {
        $this->blockInstanceService->setOwnerContext('page', $pageId);

        return $this->index();
    }

    public function indexForEntry(int $entryId): ResponseInterface
    {
        $this->blockInstanceService->setOwnerContext('entry', $entryId);

        return $this->index();
    }

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (BlockInstanceIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission($this->requiresPermission('read'))) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->blockInstanceService->index($dto, $context);
            },
            BlockInstanceIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (BlockInstanceCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission($this->requiresPermission('write'))) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->blockInstanceService->store($dto, $context);
            },
            BlockInstanceCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (BlockInstanceUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission($this->requiresPermission('write'))) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->blockInstanceService->update($id, $dto, $context);
            },
            BlockInstanceUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission($this->requiresPermission('read'))) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->blockInstanceService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission($this->requiresPermission('write'))) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }

                return $this->blockInstanceService->destroy($id, $context);
            }
        );
    }
}
