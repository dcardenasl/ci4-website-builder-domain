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
                if (! $context->hasPermission('cms.collections.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }

                return $this->sortOrderService->reorder($dto);
            },
            SortOrderBatchRequestDTO::class,
        );
    }
}
