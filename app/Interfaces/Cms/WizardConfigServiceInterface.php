<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

/**
 * Aggregates data from languages, collections, pages, menus, and block types
 * for the admin creation wizard — spans multiple resources by design, so it
 * does not follow the generic CrudServiceContract shape.
 */
interface WizardConfigServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function buildConfig(): array;
}
