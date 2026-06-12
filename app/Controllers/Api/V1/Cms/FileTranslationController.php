<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\DTO\Request\Cms\FileTranslationIndexRequestDTO;
use App\DTO\Request\Cms\FileTranslationSaveRequestDTO;
use App\Interfaces\Cms\FileTranslationServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class FileTranslationController extends ApiController
{
    protected FileTranslationServiceInterface $fileTranslationService;

    protected function resolveDefaultService(): object
    {
        $this->fileTranslationService = Services::fileTranslationService();

        return $this->fileTranslationService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(int $fileId): ResponseInterface
    {
        return $this->handleRequest('index', FileTranslationIndexRequestDTO::class, ['file_id' => $fileId]);
    }

    public function create(int $fileId): ResponseInterface
    {
        return $this->handleRequest('store', FileTranslationSaveRequestDTO::class, ['file_id' => $fileId]);
    }

    public function update(int $fileId, int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->fileTranslationService->update($id, $dto, $context),
            FileTranslationSaveRequestDTO::class,
            ['file_id' => $fileId]
        );
    }

    public function show(int $fileId, int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->fileTranslationService->show($id, $context)
        );
    }

    public function delete(int $fileId, int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->fileTranslationService->destroy($id, $context)
        );
    }
}
