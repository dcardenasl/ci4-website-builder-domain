<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface BlockInstanceServiceInterface extends CrudServiceContract
{
    /**
     * Scope subsequent index() calls to a specific owner (page or entry).
     * Must be called before index(); the context is consumed on first use.
     */
    public function setOwnerContext(string $ownerType, int $ownerId): void;
}
