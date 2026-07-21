<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\CollectionCreateRequestDTO;
use App\DTO\Request\Cms\CollectionIndexRequestDTO;
use App\DTO\Request\Cms\CollectionUpdateRequestDTO;
use App\Interfaces\Cms\CollectionServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class CollectionController extends ApiController
{
    protected CollectionServiceInterface $collectionService;

    protected function resolveDefaultService(): CollectionServiceInterface
    {
        $this->collectionService = Services::collectionService();

        return $this->collectionService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (CollectionIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.collections.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->collectionService->index($dto, $context);
            },
            CollectionIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (CollectionCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.collections.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->collectionService->store($dto, $context);
            },
            CollectionCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (CollectionUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.collections.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->collectionService->update($id, $dto, $context);
            },
            CollectionUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.collections.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->collectionService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.collections.admin')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->collectionService->destroy($id, $context);
            }
        );
    }

    public function checkSlug(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.collections.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }

                $slug = (string) ($dto['slug'] ?? '');
                $languageId = (int) ($dto['language_id'] ?? 0);
                $currentId = isset($dto['current_id']) && $dto['current_id'] !== '' ? (int) $dto['current_id'] : null;

                if ($slug === '' || $languageId === 0) {
                    return ['available' => false];
                }

                $available = $this->collectionService->isSlugAvailable($slug, $languageId, $currentId);

                return ['available' => $available];
            }
        );
    }
}
