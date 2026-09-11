<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ResourceOwnershipTransferRequest')]
readonly class ResourceOwnershipTransferRequestDTO extends BaseRequestDTO
{
    public int $user_id;

    public function rules(): array
    {
        return ['user_id' => 'required|is_natural_no_zero'];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
        $this->user_id = (int) ($data['user_id'] ?? 0);
    }

    public function toArray(): array
    {
        return ['user_id' => $this->user_id];
    }
}
