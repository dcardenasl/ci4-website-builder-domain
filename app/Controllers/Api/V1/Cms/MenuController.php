<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\MenuCreateRequestDTO;
use App\DTO\Request\Cms\MenuIndexRequestDTO;
use App\DTO\Request\Cms\MenuUpdateRequestDTO;
use App\Interfaces\Cms\MenuServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class MenuController extends ApiController
{
    protected MenuServiceInterface $menuService;

    protected function resolveDefaultService(): MenuServiceInterface
    {
        $this->menuService = Services::menuService();

        return $this->menuService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (MenuIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.menus.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                return $this->menuService->index($dto, $context);
            },
            MenuIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (MenuCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.menus.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                return $this->menuService->store($dto, $context);
            },
            MenuCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (MenuUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.menus.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                return $this->menuService->update($id, $dto, $context);
            },
            MenuUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.menus.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                return $this->menuService->show($id, $context);
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
                return $this->menuService->destroy($id, $context);
            }
        );
    }
}
