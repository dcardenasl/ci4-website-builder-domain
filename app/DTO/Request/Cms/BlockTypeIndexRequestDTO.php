<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'BlockTypeIndexRequest')]
readonly class BlockTypeIndexRequestDTO extends AbstractSimpleIndexRequestDTO
{
    protected static function maxPerPage(): int
    {
        return 100;
    }
}
