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

    /**
     * List public+active settings keyed by setting_key, with translation and
     * file_id resolution applied, for the given raw Accept-Language header.
     *
     * @return array<string, mixed>
     */
    public function listPublic(?string $acceptLanguageHeader): array;
}
