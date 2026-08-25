<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\PageCreateRequestDTO;
use App\DTO\Request\Cms\PageIndexRequestDTO;
use App\DTO\Request\Cms\PageUpdateRequestDTO;
use App\Interfaces\Cms\PageQualityServiceInterface;
use App\Interfaces\Cms\PageServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PageController extends ApiController
{
    protected PageServiceInterface $pageService;
    protected PageQualityServiceInterface $pageQualityService;

    protected function resolveDefaultService(): PageServiceInterface
    {
        $this->pageService = Services::pageService();
        $this->pageQualityService = Services::pageQualityService();

        return $this->pageService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (PageIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.pages.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->pageService->index($dto, $context);
            },
            PageIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (PageCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.pages.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->pageService->store($dto, $context);
            },
            PageCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (PageUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.pages.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->pageService->update($id, $dto, $context);
            },
            PageUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.pages.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->pageService->show($id, $context);
            }
        );
    }

    public function quality(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (! $context->hasPermission('cms.pages.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }

                return ['status' => 'success', 'data' => $this->pageQualityService->analyze($id)];
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.pages.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->pageService->destroy($id, $context);
            }
        );
    }

    public function checkSlug(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.pages.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                $slug       = (string) ($dto['slug'] ?? '');
                $languageId = (int) ($dto['language_id'] ?? 0);
                $currentId  = isset($dto['current_id']) && $dto['current_id'] !== '' ? (int) $dto['current_id'] : null;

                if ($slug === '' || $languageId === 0) {
                    return ['available' => false];
                }

                $available = $this->pageService->isSlugAvailable($slug, $languageId, $currentId);
                return ['available' => $available];
            }
        );
    }
}
