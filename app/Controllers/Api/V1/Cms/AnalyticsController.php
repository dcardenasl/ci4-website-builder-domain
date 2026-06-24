<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\AnalyticsQueryRequestDTO;
use App\Services\Cms\AnalyticsService;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class AnalyticsController extends ApiController
{
    protected function resolveDefaultService(): AnalyticsService
    {
        return service('analyticsService');
    }

    public function overview(): ResponseInterface
    {
        return $this->handleRequest(
            function (AnalyticsQueryRequestDTO $dto, SecurityContext $context): mixed {
                if (! $context->hasPermission('cms.analytics.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var AnalyticsService $svc */
                $svc = service('analyticsService');
                return $svc->getOverview($dto->period);
            },
            AnalyticsQueryRequestDTO::class
        );
    }

    public function pages(): ResponseInterface
    {
        return $this->handleRequest(
            function (AnalyticsQueryRequestDTO $dto, SecurityContext $context): mixed {
                if (! $context->hasPermission('cms.analytics.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var AnalyticsService $svc */
                $svc = service('analyticsService');
                return $svc->getTopPages($dto->period, $dto->limit);
            },
            AnalyticsQueryRequestDTO::class
        );
    }

    public function referrers(): ResponseInterface
    {
        return $this->handleRequest(
            function (AnalyticsQueryRequestDTO $dto, SecurityContext $context): mixed {
                if (! $context->hasPermission('cms.analytics.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var AnalyticsService $svc */
                $svc = service('analyticsService');
                return $svc->getTopReferrers($dto->period, $dto->limit);
            },
            AnalyticsQueryRequestDTO::class
        );
    }

    public function devices(): ResponseInterface
    {
        return $this->handleRequest(
            function (AnalyticsQueryRequestDTO $dto, SecurityContext $context): mixed {
                if (! $context->hasPermission('cms.analytics.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var AnalyticsService $svc */
                $svc = service('analyticsService');
                return $svc->getDeviceBreakdown($dto->period);
            },
            AnalyticsQueryRequestDTO::class
        );
    }

    public function timeseries(): ResponseInterface
    {
        return $this->handleRequest(
            function (AnalyticsQueryRequestDTO $dto, SecurityContext $context): mixed {
                if (! $context->hasPermission('cms.analytics.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var AnalyticsService $svc */
                $svc = service('analyticsService');
                return $svc->getTimeseries($dto->period);
            },
            AnalyticsQueryRequestDTO::class
        );
    }
}
