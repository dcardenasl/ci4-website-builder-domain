<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\SettingBatchUpdateRequestDTO;
use App\DTO\Request\Cms\SettingCreateRequestDTO;
use App\DTO\Request\Cms\SettingIndexRequestDTO;
use App\DTO\Request\Cms\SettingUpdateRequestDTO;
use App\Interfaces\Cms\SettingServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class SettingController extends ApiController
{
    protected SettingServiceInterface $settingService;

    protected function resolveDefaultService(): object
    {
        $this->settingService = Services::settingService();

        return $this->settingService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest('index', SettingIndexRequestDTO::class);
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest('store', SettingCreateRequestDTO::class);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->settingService->update($id, $dto, $context),
            SettingUpdateRequestDTO::class,
            ['id' => $id]
        );
    }

    public function batch(): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->settingService->batchUpdate($dto->updates, $context),
            SettingBatchUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->settingService->show($id, $context));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->settingService->destroy($id, $context));
    }
}
