<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use App\DTO\Request\Cms\Support\NormalizesTaxonomyIds;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EntrySetCategoriesRequest')]
readonly class EntrySetCategoriesRequestDTO extends BaseRequestDTO
{
    use NormalizesTaxonomyIds;

    /** @var list<int> */
    public array $category_ids;

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            // An empty list deliberately clears all category assignments.
            'category_ids' => 'permit_empty|is_list',
        ];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
        $this->category_ids = $this->normalizeTaxonomyIds(
            $data['category_ids'] ?? [],
            'category_ids',
            'Entries.invalid_categories',
            'Entries.some_categories_not_found',
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['category_ids' => $this->category_ids];
    }
}
