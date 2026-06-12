<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\EntryCreateRequestDTO;
use App\DTO\Request\Cms\EntryIndexRequestDTO;
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
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.entries.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }

                $body = $this->request->getJSON(true);
                $categoryIds = is_array($body) ? ($body['category_ids'] ?? []) : [];
                if (!is_array($categoryIds)) {
                    throw new \InvalidArgumentException(lang('Categories.invalid_array'));
                }

                // Update entry categories pivot
                $db = \Config\Database::connect();
                $db->table('cms_entry_categories')->where('entry_id', $id)->delete();
                foreach ($categoryIds as $order => $catId) {
                    $db->table('cms_entry_categories')->insert([
                        'entry_id'    => $id,
                        'category_id' => (int) $catId,
                        'sort_order'  => $order,
                    ]);
                }

                return ['status' => 'success'];
            }
        );
    }

    public function setTags(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('cms.entries.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }

                $body = $this->request->getJSON(true);
                $tagIds = is_array($body) ? ($body['tag_ids'] ?? []) : [];
                if (!is_array($tagIds)) {
                    throw new \InvalidArgumentException(lang('Tags.invalid_array'));
                }

                // Update entry tags pivot
                $db = \Config\Database::connect();
                $db->table('cms_entry_tags')->where('entry_id', $id)->delete();
                foreach ($tagIds as $tagId) {
                    $db->table('cms_entry_tags')->insert([
                        'entry_id' => $id,
                        'tag_id'   => (int) $tagId,
                    ]);
                }

                return ['status' => 'success'];
            }
        );
    }
}
