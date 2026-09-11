<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Cms;

use App\Interfaces\Cms\TranslationAuditServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
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
            function (array $dto, SecurityContext $context): ResponseInterface {
                $this->assertAggregateAccess($context);
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
            function (array $dto, SecurityContext $context): ResponseInterface {
                $this->assertAggregateAccess($context);
                $langId = $this->request->getGet('language_id');
                $filters = [];
                if ($langId !== null) {
                    /** @var int $langId */
                    $filters['language_id'] = (int) $langId;
                }
                foreach (['resource', 'status', 'search'] as $filter) {
                    $value = $this->request->getGet($filter);
                    if (is_string($value) && trim($value) !== '') {
                        $filters[$filter] = trim($value);
                    }
                }

                $pageRaw = $this->request->getGet('page');
                $limitRaw = $this->request->getGet('limit') ?? $this->request->getGet('per_page');
                $hasPagination = $pageRaw !== null || $limitRaw !== null;
                if ($hasPagination) {
                    $filters['page'] = is_scalar($pageRaw) && (string) $pageRaw !== '' ? (int) $pageRaw : 1;
                    $filters['limit'] = is_scalar($limitRaw) && (string) $limitRaw !== '' ? (int) $limitRaw : 25;
                    $report = $this->auditService->getMissingTranslationsReportPage($filters);

                    return $this->response->setJSON([
                        'status' => 'success',
                        'data'   => $report,
                    ])->setStatusCode(200);
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
            function (array $dto, SecurityContext $context) use ($type, $id): ResponseInterface {
                if (in_array($type, ['page', 'entry', 'collection'], true)) {
                    Services::resourceAuthorization()->assertCan($type, $id, 'read', $context);
                }
                $report = $this->auditService->auditResource($type, $id);
                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $report,
                ])->setStatusCode(200);
            }
        );
    }

    /**
     * Audit every block instance belonging to a single page/entry — the
     * contextual counterpart to resource(), used by the admin's block list
     * and "Ver" views instead of the sitewide report/stats.
     */
    public function owner(string $ownerType, int $ownerId): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($ownerType, $ownerId): ResponseInterface {
                if (! in_array($ownerType, ['page', 'entry'], true)) {
                    throw new ValidationException(null, ['owner_type' => 'Must be "page" or "entry".']);
                }
                Services::resourceAuthorization()->assertCan($ownerType, $ownerId, 'read', $context);

                $report = $this->auditService->auditOwnerBlocks($ownerType, $ownerId);
                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => $report,
                ])->setStatusCode(200);
            }
        );
    }

    private function assertAggregateAccess(SecurityContext $context): void
    {
        if (! $context->hasPermission('iam.superadmin-access')) {
            throw new AuthorizationException(lang('Api.forbidden'));
        }
    }
}
