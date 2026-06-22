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
}
