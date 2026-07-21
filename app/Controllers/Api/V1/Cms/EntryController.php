<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\EntryCreateRequestDTO;
use App\DTO\Request\Cms\EntryIndexRequestDTO;
use App\DTO\Request\Cms\EntrySetCategoriesRequestDTO;
use App\DTO\Request\Cms\EntrySetTagsRequestDTO;
use App\DTO\Request\Cms\EntrySyncTaxonomyRequestDTO;
use App\DTO\Request\Cms\EntryUpdateRequestDTO;
use App\Interfaces\Cms\EntryServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class EntryController extends ApiController
{
    protected EntryServiceInterface $entryService;

    protected function resolveDefaultService(): EntryServiceInterface
    {
        $this->entryService = Services::entryService();

        return $this->entryService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (EntryIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.entries.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->entryService->index($dto, $context);
            },
            EntryIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (EntryCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.entries.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->entryService->store($dto, $context);
            },
            EntryCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (EntryUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.entries.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->entryService->update($id, $dto, $context);
            },
            EntryUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.entries.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->entryService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.entries.admin')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->entryService->destroy($id, $context);
            }
        );
    }

    public function setCategories(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (EntrySetCategoriesRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.entries.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->entryService->syncCategories($id, $dto, $context);
            },
            EntrySetCategoriesRequestDTO::class
        );
    }

    public function setTags(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (EntrySetTagsRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.entries.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->entryService->syncTags($id, $dto, $context);
            },
            EntrySetTagsRequestDTO::class
        );
    }

    public function syncTaxonomy(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (EntrySyncTaxonomyRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (! $context->hasPermission('cms.entries.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }

                return $this->entryService->syncTaxonomy($id, $dto, $context);
            },
            EntrySyncTaxonomyRequestDTO::class
        );
    }

    public function checkSlug(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.entries.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                $slug       = (string) ($dto['slug'] ?? '');
                $languageId = (int) ($dto['language_id'] ?? 0);
                $currentId  = isset($dto['current_id']) && $dto['current_id'] !== '' ? (int) $dto['current_id'] : null;

                if ($slug === '' || $languageId === 0) {
                    return ['available' => false];
                }

                $available = $this->entryService->isSlugAvailable($slug, $languageId, $currentId);
                return ['available' => $available];
            }
        );
    }
}
