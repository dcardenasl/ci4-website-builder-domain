<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface CollectionServiceInterface extends CrudServiceContract
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listPublic(string $lang): array;

    public function isSlugAvailable(string $slug, int $languageId, ?int $currentId = null): bool;
}
