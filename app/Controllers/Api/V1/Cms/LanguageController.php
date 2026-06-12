<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\LanguageCreateRequestDTO;
use App\DTO\Request\Cms\LanguageIndexRequestDTO;
use App\DTO\Request\Cms\LanguageUpdateRequestDTO;
use App\Interfaces\Cms\LanguageServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class LanguageController extends ApiController
{
    protected LanguageServiceInterface $languageService;

    protected function resolveDefaultService(): object
    {
        $this->languageService = Services::languageService();

        return $this->languageService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest('index', LanguageIndexRequestDTO::class);
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest('store', LanguageCreateRequestDTO::class);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->languageService->update($id, $dto, $context),
            LanguageUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->languageService->show($id, $context));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->languageService->destroy($id, $context));
    }
}
