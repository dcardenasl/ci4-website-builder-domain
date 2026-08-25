<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Internal;

use App\Services\Cms\FileUsageService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Hub-initiated file coordination endpoints.
 *
 * These routes are machine-to-machine and are gated by HubSignatureFilter,
 * not user JWT or the public Web app key. They expose only the usage contract
 * needed by the Hub before destructive file operations and the cache invalidation
 * callback needed after a file update.
 */
class InternalFileController extends Controller
{
    public function usage(int $hubFileId): ResponseInterface
    {
        /** @var FileUsageService $service */
        $service = Services::fileUsageService();
        $usages  = $service->getUsagesByHubFileId($hubFileId);

        return Services::response()->setJSON([
            'status' => 'success',
            'data'   => [
                'in_use' => $usages !== [],
                'usages' => $usages,
            ],
        ]);
    }

    public function invalidateCache(int $hubFileId): ResponseInterface
    {
        Services::hubClient()->invalidateFileMetaCache($hubFileId);

        return Services::response()->setJSON([
            'status' => 'success',
            'data'   => ['invalidated' => true],
        ]);
    }
}
