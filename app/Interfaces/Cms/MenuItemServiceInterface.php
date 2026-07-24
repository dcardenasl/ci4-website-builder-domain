<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use App\Entities\MenuItemEntity;
use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface MenuItemServiceInterface extends CrudServiceContract
{
    /**
     * Resolve a MenuItem (type + IDs) to its public navigable URL.
     *
     * @param MenuItemEntity $item MenuItem to resolve
     * @param string $lang Language code (e.g., 'es', 'en')
     * @return string|null Relative URL (e.g., '/pages/slug', '/colecciones'), or null if unresolved
     */
    public function resolveLink(MenuItemEntity $item, string $lang): ?string;
}
