<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Interfaces\Cms\TranslationAuditServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class TranslationAuditController extends ApiController
{
    protected TranslationAuditServiceInterface $auditService;

    protected function resolveDefaultService(): object
    {
        $this->auditService = Services::translationAuditService();
        return $this->auditService;
    }

    /**
     * Get overall translation completeness statistics per active language.
     */
    public function stats(): ResponseInterface
    {
        return $this->handleRequest(
            function (): ResponseInterface {
                $stats = $this->auditService->getOverallCompleteness();
                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $stats,
                ])->setStatusCode(200);
            }
        );
    }

    /**
     * Get a report of missing or incomplete translations across resources.
     */
    public function report(): ResponseInterface
    {
        return $this->handleRequest(
            function (): ResponseInterface {
                $langId = $this->request->getGet('language_id');
                $filters = [];
                if ($langId !== null) {
                    $filters['language_id'] = (int) $langId;
                }

                $report = $this->auditService->getMissingTranslationsReport($filters);
                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $report,
                ])->setStatusCode(200);
            }
        );
    }

    /**
     * Audit a single resource instance for translation completeness.
     */
    public function resource(string $type, int $id): ResponseInterface
    {
        return $this->handleRequest(
            function () use ($type, $id): ResponseInterface {
                $report = $this->auditService->auditResource($type, $id);
                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $report,
                ])->setStatusCode(200);
            }
        );
    }
}
