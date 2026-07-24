<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\MenuItemCreateRequestDTO;
use App\DTO\Request\Cms\MenuItemIndexRequestDTO;
use App\DTO\Request\Cms\MenuItemUpdateRequestDTO;
use App\Interfaces\Cms\MenuItemServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class MenuItemController extends ApiController
{
    protected MenuItemServiceInterface $menuItemService;

    protected function resolveDefaultService(): MenuItemServiceInterface
    {
        $this->menuItemService = Services::menuItemService();

        return $this->menuItemService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (MenuItemIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.menus.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                return $this->menuItemService->index($dto, $context);
            },
            MenuItemIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (MenuItemCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.menus.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                return $this->menuItemService->store($dto, $context);
            },
            MenuItemCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (MenuItemUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.menus.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                return $this->menuItemService->update($id, $dto, $context);
            },
            MenuItemUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.menus.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                return $this->menuItemService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.menus.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                return $this->menuItemService->destroy($id, $context);
            }
        );
    }
}
