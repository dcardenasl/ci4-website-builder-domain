<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\BlockTypeCreateRequestDTO;
use App\DTO\Request\Cms\BlockTypeIndexRequestDTO;
use App\DTO\Request\Cms\BlockTypeUpdateRequestDTO;
use App\Interfaces\Cms\BlockTypeServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class BlockTypeController extends ApiController
{
    protected BlockTypeServiceInterface $blockTypeService;

    protected function resolveDefaultService(): BlockTypeServiceInterface
    {
        $this->blockTypeService = Services::blockTypeService();

        return $this->blockTypeService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (BlockTypeIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.blocks.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->blockTypeService->index($dto, $context);
            },
            BlockTypeIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (BlockTypeCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.blocks.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->blockTypeService->store($dto, $context);
            },
            BlockTypeCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (BlockTypeUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.blocks.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->blockTypeService->update($id, $dto, $context);
            },
            BlockTypeUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.blocks.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->blockTypeService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.blocks.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->blockTypeService->destroy($id, $context);
            }
        );
    }

    public function templates(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.blocks.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return Services::blockTemplateCatalog()->all();
            }
        );
    }

    public function usages(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.blocks.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->blockTypeService->getUsages($id);
            }
        );
    }
}
