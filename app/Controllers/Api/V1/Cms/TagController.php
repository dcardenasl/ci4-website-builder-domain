<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\TagCreateRequestDTO;
use App\DTO\Request\Cms\TagIndexRequestDTO;
use App\DTO\Request\Cms\TagUpdateRequestDTO;
use App\Interfaces\Cms\TagServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class TagController extends ApiController
{
    protected TagServiceInterface $tagService;

    protected function resolveDefaultService(): TagServiceInterface
    {
        $this->tagService = Services::tagService();

        return $this->tagService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (TagIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.tags.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->tagService->index($dto, $context);
            },
            TagIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (TagCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.tags.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->tagService->store($dto, $context);
            },
            TagCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (TagUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.tags.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->tagService->update($id, $dto, $context);
            },
            TagUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.tags.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->tagService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.tags.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->tagService->destroy($id, $context);
            }
        );
    }
}
