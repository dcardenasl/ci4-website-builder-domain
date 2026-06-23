<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\FormSubmissionCreateRequestDTO;
use App\Services\Cms\FormSubmissionService;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

/**
 * Public endpoint — no auth required.
 * Called by the website (ci4-website-builder-web) when a user submits a contact form.
 * IP and User-Agent are injected server-side via additionalParams (not trusted from client body).
 */
class PublicFormSubmissionController extends ApiController
{
    protected function resolveDefaultService(): FormSubmissionService
    {
        return service('formSubmissionService');
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function store(): ResponseInterface
    {
        $request   = service('request');
        $ipAddress = $request->getIPAddress();
        $userAgent = substr((string) $request->getUserAgent(), 0, 500);

        return $this->handleRequest(
            function (FormSubmissionCreateRequestDTO $dto, SecurityContext $context): mixed {
                /** @var FormSubmissionService $svc */
                $svc = service('formSubmissionService');
                return $svc->create($dto);
            },
            FormSubmissionCreateRequestDTO::class,
            // Server-side values override anything in the POST body
            ['ip_address' => $ipAddress, 'user_agent' => $userAgent]
        );
    }
}
