<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\SettingConnectionCreateRequestDTO;
use App\Interfaces\Cms\SettingConnectionServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class SettingConnectionController extends ApiController
{
    protected SettingConnectionServiceInterface $settingConnectionService;

    protected function resolveDefaultService(): SettingConnectionServiceInterface
    {
        $this->settingConnectionService = Services::settingConnectionService();

        return $this->settingConnectionService;
    }

    public function index(int $settingId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $data, SecurityContext $context) use ($settingId): ResponseInterface {
                $result = $this->settingConnectionService->listForSetting($settingId);

                return $this->response->setJSON(['ok' => true, 'data' => $result]);
            }
        );
    }

    public function create(int $settingId): ResponseInterface
    {
        return $this->handleRequest(
            function (SettingConnectionCreateRequestDTO $dto, SecurityContext $context) use ($settingId): ResponseInterface {
                $data = $this->settingConnectionService->create(
                    $settingId,
                    $dto->entityType,
                    $dto->entityKey,
                    $dto->usageLabel
                );

                return $this->response->setStatusCode(201)->setJSON(['ok' => true, 'data' => $data]);
            },
            SettingConnectionCreateRequestDTO::class
        );
    }

    public function delete(int $settingId, int $connectionId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $data, SecurityContext $context) use ($settingId, $connectionId): ResponseInterface {
                $this->settingConnectionService->delete($settingId, $connectionId);

                return $this->response->setJSON(['ok' => true, 'data' => null]);
            }
        );
    }
}
