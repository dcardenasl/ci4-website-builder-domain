<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'RedirectIndexRequest')]
readonly class RedirectIndexRequestDTO extends AbstractSimpleIndexRequestDTO
{
}
