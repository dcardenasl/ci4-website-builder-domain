<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\CategoryCreateRequestDTO;
use App\DTO\Request\Cms\CategoryIndexRequestDTO;
use App\DTO\Request\Cms\CategoryUpdateRequestDTO;
use App\Interfaces\Cms\CategoryServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class CategoryController extends ApiController
{
    protected CategoryServiceInterface $categoryService;

    protected function resolveDefaultService(): CategoryServiceInterface
    {
        $this->categoryService = Services::categoryService();

        return $this->categoryService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (CategoryIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.categories.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->categoryService->index($dto, $context);
            },
            CategoryIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (CategoryCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.categories.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->categoryService->store($dto, $context);
            },
            CategoryCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (CategoryUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.categories.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->categoryService->update($id, $dto, $context);
            },
            CategoryUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.categories.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->categoryService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.categories.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->categoryService->destroy($id, $context);
            }
        );
    }

    public function checkSlug(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.categories.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                $slug       = (string) ($dto['slug'] ?? '');
                $languageId = (int) ($dto['language_id'] ?? 0);
                $currentId  = isset($dto['current_id']) && $dto['current_id'] !== '' ? (int) $dto['current_id'] : null;

                if ($slug === '' || $languageId === 0) {
                    return ['available' => false];
                }

                $available = $this->categoryService->isSlugAvailable($slug, $languageId, $currentId);
                return ['available' => $available];
            }
        );
    }
}
