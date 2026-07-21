<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface CategoryServiceInterface extends CrudServiceContract
{
    /**
     * @return array<int, array{id: int, slug: string, name: string, description: string|null}>
     */
    public function listPublic(string $lang, string $collectionKey): array;

    public function isSlugAvailable(string $slug, int $languageId, ?int $currentId = null): bool;
}
