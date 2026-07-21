<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface MenuServiceInterface extends CrudServiceContract
{
    /**
     * @return array<string, mixed>
     */
    public function showPublic(string $menuKey, string $lang): array;
}
