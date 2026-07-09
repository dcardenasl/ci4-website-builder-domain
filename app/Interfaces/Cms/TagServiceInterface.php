<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface TagServiceInterface extends CrudServiceContract
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPublic(string $lang, string $collectionKey): array;
}
