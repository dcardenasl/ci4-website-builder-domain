<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Services\Cms\FileUsageService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class FileUsageController extends ApiController
{
    protected FileUsageService $fileUsageService;

    protected function resolveDefaultService(): FileUsageService
    {
        $this->fileUsageService = Services::fileUsageService();

        return $this->fileUsageService;
    }

    public function usages(int $hubFileId): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->fileUsageService->getUsagesByHubFileId($hubFileId)
        );
    }
}
