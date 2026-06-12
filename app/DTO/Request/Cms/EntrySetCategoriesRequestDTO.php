<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EntrySetCategoriesRequest')]
readonly class EntrySetCategoriesRequestDTO extends BaseRequestDTO
{
    /** @var list<int> */
    public array $category_ids;

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'category_ids'   => 'required|is_list',
            'category_ids.*' => 'required|is_natural_no_zero',
        ];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
        $raw = $data['category_ids'] ?? [];
        $this->category_ids = array_values(array_map('intval', is_array($raw) ? $raw : []));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['category_ids' => $this->category_ids];
    }
}
