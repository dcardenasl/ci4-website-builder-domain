<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Interfaces\Cms\SettingServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicSettingController extends ApiController
{
    protected SettingServiceInterface $settingService;

    protected function resolveDefaultService(): SettingServiceInterface
    {
        $this->settingService = Services::settingService();

        return $this->settingService;
    }

    /**
     * Get all public settings with their translations.
     * Settings marked as is_public=1 are returned with translation values.
     */
    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context): mixed {
                $result = $this->settingService->listPublic($this->request->getHeaderLine('Accept-Language'));

                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $result,
                ])->setStatusCode(200);
            }
        );
    }
}
