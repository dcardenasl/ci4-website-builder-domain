<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

interface TranslationAuditServiceInterface
{
    /**
     * Get overall translation completeness statistics per active language.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOverallCompleteness(): array;

    /**
     * Get a report of missing or incomplete translations across resources.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getMissingTranslationsReport(array $filters = []): array;

    /**
     * Audit a single resource instance for translation completeness.
     *
     * @param string $resourceType
     * @param int $resourceId
     * @return array<string, mixed>
     */
    public function auditResource(string $resourceType, int $resourceId): array;

    /**
     * Audit every block instance belonging to a single page/entry, so the
     * admin can render contextual per-language badges without pulling the
     * sitewide report.
     *
     * @param string $ownerType 'page' | 'entry'
     * @return array{
     *   blocks: array<int, array<string, array{language_id:int,status:string,detail:string}>>,
     *   summary: array<string, array{complete:int,total:int}>,
     * }
     */
    public function auditOwnerBlocks(string $ownerType, int $ownerId): array;
}
