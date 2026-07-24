<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface RedirectServiceInterface extends CrudServiceContract
{
    /**
     * @param list<string> $segments
     * @return array{new_url: string, redirect_type: int}
     */
    public function resolvePublic(array $segments): array;
}
