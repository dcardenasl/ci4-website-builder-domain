<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\TrackEventRequestDTO;
use App\Services\Cms\AnalyticsService;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

/**
 * Public endpoint — no auth required.
 * Called by the website (ci4-website-builder-web) after each page render.
 * Returns 204 to avoid exposing any internal detail.
 */
class PublicTrackingController extends ApiController
{
    protected function resolveDefaultService(): AnalyticsService
    {
        return service('analyticsService');
    }

    public function track(): ResponseInterface
    {
        return $this->handleRequest(
            function (TrackEventRequestDTO $dto, SecurityContext $context): ResponseInterface {
                /** @var AnalyticsService $svc */
                $svc = service('analyticsService');
                $svc->record($dto);
                return $this->response->setStatusCode(204)->setBody('');
            },
            TrackEventRequestDTO::class
        );
    }
}
