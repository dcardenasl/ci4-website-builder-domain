<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Services\Cms\FormPublicDefinitionAssembler;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Http\ApiController;

/**
 * Public endpoint — validated by X-App-Key only (webappkey filter).
 * Returns the form definition (fields + translations) for the requested language.
 */
class PublicFormController extends ApiController
{
    protected function resolveDefaultService(): FormPublicDefinitionAssembler
    {
        return service('formPublicDefinitionAssembler');
    }

    public function definition(string $lang, string $formKey): ResponseInterface
    {
        return $this->handleRequest(
            function () use ($lang, $formKey): mixed {
                /** @var FormPublicDefinitionAssembler $svc */
                $svc = service('formPublicDefinitionAssembler');
                return $svc->getDefinition($lang, $formKey)->toArray();
            }
        );
    }
}
