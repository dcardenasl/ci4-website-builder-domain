<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Interfaces\Cms\WizardConfigServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class WizardConfigController extends ApiController
{
    protected WizardConfigServiceInterface $wizardConfigService;

    protected function resolveDefaultService(): WizardConfigServiceInterface
    {
        $this->wizardConfigService = Services::wizardConfigService();

        return $this->wizardConfigService;
    }

    public function config(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('cms.entries.read')) {
                    throw new AuthorizationException(lang('Api.forbidden'));
                }

                return $this->wizardConfigService->buildConfig();
            }
        );
    }
}
