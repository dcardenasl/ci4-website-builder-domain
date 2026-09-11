<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\SortOrderBatchRequestDTO;
use App\Services\Cms\SortOrderService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

final class SortOrderController extends ApiController
{
    private SortOrderService $sortOrderService;

    protected function resolveDefaultService(): SortOrderService
    {
        $this->sortOrderService = Services::sortOrderService();

        return $this->sortOrderService;
    }

    public function reorder(): ResponseInterface
    {
        return $this->handleRequest(
            function (SortOrderBatchRequestDTO $dto, SecurityContext $context): array {
                $permission = match ($dto->resource) {
                    'collections' => 'cms.collections.write',
                    'pages' => 'cms.pages.write',
                    'entries' => 'cms.entries.write',
                    'categories' => 'cms.categories.write',
                    'languages' => 'cms.languages.write',
                    'menu_items' => 'cms.menus.write',
                    'block_instances' => ($dto->scope['owner_type'] ?? null) === 'entry'
                        ? 'cms.entries.write'
                        : 'cms.pages.write',
                    default => null,
                };

                if ($permission === null || ! $context->hasPermission($permission)) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }

                return $this->sortOrderService->reorder($dto, $context);
            },
            SortOrderBatchRequestDTO::class,
        );
    }
}
