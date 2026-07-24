<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\FormSubmissionIndexRequestDTO;
use App\DTO\Request\Cms\FormSubmissionUpdateStatusRequestDTO;
use App\Services\Cms\FormSubmissionService;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

/**
 * Admin-facing CRUD for form submissions.
 * All routes protected by cms.submissions.read / cms.submissions.write.
 */
class FormSubmissionController extends ApiController
{
    protected function resolveDefaultService(): FormSubmissionService
    {
        return service('formSubmissionService');
    }

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (FormSubmissionIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (! $context->hasPermission('cms.submissions.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormSubmissionService $svc */
                $svc = service('formSubmissionService');
                return $svc->list($dto);
            },
            FormSubmissionIndexRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (FormSubmissionIndexRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (! $context->hasPermission('cms.submissions.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormSubmissionService $svc */
                $svc = service('formSubmissionService');
                return $svc->get($id);
            },
            FormSubmissionIndexRequestDTO::class
        );
    }

    public function updateStatus(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (FormSubmissionUpdateStatusRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (! $context->hasPermission('cms.submissions.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormSubmissionService $svc */
                $svc = service('formSubmissionService');
                return $svc->updateStatus($id, $dto);
            },
            FormSubmissionUpdateStatusRequestDTO::class
        );
    }

    public function counts(): ResponseInterface
    {
        return $this->handleRequest(
            function (FormSubmissionIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (! $context->hasPermission('cms.submissions.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormSubmissionService $svc */
                $svc = service('formSubmissionService');
                return $svc->countByStatus();
            },
            FormSubmissionIndexRequestDTO::class
        );
    }
}
