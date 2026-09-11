<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

interface PageQualityServiceInterface
{
    /**
     * Build the editorial and SEO readiness report for a page.
     *
     * @return array<string, mixed>
     */
    public function analyze(int $pageId): array;
}
