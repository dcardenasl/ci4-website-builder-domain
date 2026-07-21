<?php

declare(strict_types=1);

namespace App\Interfaces\Cms;

use dcardenasl\Ci4ApiCore\Services\CrudServiceContract;

interface LanguageServiceInterface extends CrudServiceContract
{
    /**
     * List active languages for public site discovery, ordered by sort_order
     * then code.
     *
     * @return list<array{code: string, name: string, native_name: string, is_default: bool}>
     */
    public function listPublic(): array;
}
