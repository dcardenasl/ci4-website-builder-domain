<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface SettingServiceInterface extends CrudServiceContract
{
    /**
     * @param list<array{id: int, payload: array<string, mixed>}> $updates
     * @return array{updated: list<int>}
     */
    public function batchUpdate(array $updates, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context = null): array;
}
