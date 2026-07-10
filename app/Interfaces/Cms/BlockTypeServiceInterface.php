<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface BlockTypeServiceInterface extends CrudServiceContract
{
    /**
     * Reports every page/entry block instance currently using this block type.
     *
     * @return list<array{resource: string, resource_id: int, role: string, label: string|null, context: array{owner_type: string, owner_id: int}}>
     */
    public function getUsages(int $blockTypeId): array;
}
