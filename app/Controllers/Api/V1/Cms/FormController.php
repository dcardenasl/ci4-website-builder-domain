<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\FormCreateRequestDTO;
use App\DTO\Request\Cms\FormFieldCreateRequestDTO;
use App\DTO\Request\Cms\FormFieldReorderRequestDTO;
use App\DTO\Request\Cms\FormFieldUpdateRequestDTO;
use App\DTO\Request\Cms\FormUpdateRequestDTO;
use App\Services\Cms\FormService;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class FormController extends ApiController
{
    protected function resolveDefaultService(): FormService
    {
        return service('formService');
    }

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $data, SecurityContext $context): mixed {
                if (! $context->hasPermission('cms.forms.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormService $svc */
                $svc = service('formService');
                return $svc->list();
            }
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $data, SecurityContext $context) use ($id): mixed {
                if (! $context->hasPermission('cms.forms.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormService $svc */
                $svc = service('formService');
                return $svc->get($id)->toArray();
            }
        );
    }

    public function store(): ResponseInterface
    {
        return $this->handleRequest(
            function (FormCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (! $context->hasPermission('cms.forms.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormService $svc */
                $svc = service('formService');
                return $svc->create($dto)->toArray();
            },
            FormCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (FormUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (! $context->hasPermission('cms.forms.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormService $svc */
                $svc = service('formService');
                return $svc->update($id, $dto)->toArray();
            },
            FormUpdateRequestDTO::class
        );
    }

    public function destroy(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $data, SecurityContext $context) use ($id): mixed {
                if (! $context->hasPermission('cms.forms.admin')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormService $svc */
                $svc = service('formService');
                $svc->delete($id);
                return null;
            }
        );
    }

    // ── Field sub-resource ───────────────────────────────────────────────────

    public function fields(int $formId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $data, SecurityContext $context) use ($formId): mixed {
                if (! $context->hasPermission('cms.forms.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormService $svc */
                $svc = service('formService');
                return $svc->listFields($formId);
            }
        );
    }

    public function storeField(int $formId): ResponseInterface
    {
        return $this->handleRequest(
            function (FormFieldCreateRequestDTO $dto, SecurityContext $context) use ($formId): mixed {
                if (! $context->hasPermission('cms.forms.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormService $svc */
                $svc = service('formService');
                return $svc->createField($formId, $dto)->toArray();
            },
            FormFieldCreateRequestDTO::class
        );
    }

    public function updateField(int $formId, int $fieldId): ResponseInterface
    {
        return $this->handleRequest(
            function (FormFieldUpdateRequestDTO $dto, SecurityContext $context) use ($formId, $fieldId): mixed {
                if (! $context->hasPermission('cms.forms.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormService $svc */
                $svc = service('formService');
                return $svc->updateField($formId, $fieldId, $dto)->toArray();
            },
            FormFieldUpdateRequestDTO::class
        );
    }

    public function destroyField(int $formId, int $fieldId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $data, SecurityContext $context) use ($formId, $fieldId): mixed {
                if (! $context->hasPermission('cms.forms.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormService $svc */
                $svc = service('formService');
                $svc->deleteField($formId, $fieldId);
                return null;
            }
        );
    }

    public function reorderFields(int $formId): ResponseInterface
    {
        return $this->handleRequest(
            function (FormFieldReorderRequestDTO $dto, SecurityContext $context) use ($formId): mixed {
                if (! $context->hasPermission('cms.forms.write')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }
                /** @var FormService $svc */
                $svc = service('formService');
                $svc->reorderFields($formId, $dto);
                return null;
            },
            FormFieldReorderRequestDTO::class
        );
    }
}
