<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\RedirectCreateRequestDTO;
use App\DTO\Request\Cms\RedirectIndexRequestDTO;
use App\DTO\Request\Cms\RedirectUpdateRequestDTO;
use App\Interfaces\Cms\RedirectServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class RedirectController extends ApiController
{
    protected RedirectServiceInterface $redirectService;

    protected function resolveDefaultService(): RedirectServiceInterface
    {
        $this->redirectService = Services::redirectService();

        return $this->redirectService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (RedirectIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.redirects.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->redirectService->index($dto, $context);
            },
            RedirectIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (RedirectCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.redirects.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->redirectService->store($dto, $context);
            },
            RedirectCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (RedirectUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.redirects.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->redirectService->update($id, $dto, $context);
            },
            RedirectUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.redirects.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->redirectService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.redirects.admin')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->redirectService->destroy($id, $context);
            }
        );
    }
}
