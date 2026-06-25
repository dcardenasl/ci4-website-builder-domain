<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Services\Cms\FormService;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Http\ApiController;

/**
 * Public endpoint — validated by X-App-Key only (webappkey filter).
 * Returns the form definition (fields + translations) for the requested language.
 */
class PublicFormController extends ApiController
{
    protected function resolveDefaultService(): FormService
    {
        return service('formService');
    }

    public function definition(string $lang, string $formKey): ResponseInterface
    {
        return $this->handleRequest(
            function () use ($lang, $formKey): mixed {
                /** @var FormService $svc */
                $svc = service('formService');
                return $svc->getPublicDefinition($lang, $formKey)->toArray();
            }
        );
    }
}
