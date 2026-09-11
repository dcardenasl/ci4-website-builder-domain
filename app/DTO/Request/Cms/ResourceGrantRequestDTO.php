<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ResourceGrantRequest')]
readonly class ResourceGrantRequestDTO extends BaseRequestDTO
{
    public int $user_id;
    public string $access_level;

    public function rules(): array
    {
        return [
            'user_id' => 'required|is_natural_no_zero',
            'access_level' => 'required|in_list[read,write,admin]',
        ];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
        $this->user_id = (int) ($data['user_id'] ?? 0);
        $this->access_level = (string) ($data['access_level'] ?? '');
    }

    public function toArray(): array
    {
        return ['user_id' => $this->user_id, 'access_level' => $this->access_level];
    }
}
