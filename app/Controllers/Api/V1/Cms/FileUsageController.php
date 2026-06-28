<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Models\EntryTranslationModel;
use App\Models\PageTranslationModel;
use App\Models\SettingModel;
use App\Services\Cms\FileUsageService;
use CodeIgniter\HTTP\ResponseInterface;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class FileUsageController extends ApiController
{
    protected FileUsageService $fileUsageService;

    protected function resolveDefaultService(): FileUsageService
    {
        $this->fileUsageService = new FileUsageService(
            model(EntryTranslationModel::class),
            model(PageTranslationModel::class),
            model(SettingModel::class),
            \Config\Database::connect(),
        );

        return $this->fileUsageService;
    }

    public function usages(int $hubFileId): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->fileUsageService->getUsagesByHubFileId($hubFileId)
        );
    }
}
