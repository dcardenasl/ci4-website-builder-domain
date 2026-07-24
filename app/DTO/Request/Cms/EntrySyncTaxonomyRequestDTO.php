<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use App\DTO\Request\Cms\Support\NormalizesTaxonomyIds;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EntrySyncTaxonomyRequest')]
readonly class EntrySyncTaxonomyRequestDTO extends BaseRequestDTO
{
    use NormalizesTaxonomyIds;

    /** @var list<int> */
    public array $category_ids;

    /** @var list<int> */
    public array $tag_ids;

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'category_ids' => 'permit_empty|is_list',
            'tag_ids' => 'permit_empty|is_list',
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
        $this->tag_ids = $this->normalizeTaxonomyIds(
            $data['tag_ids'] ?? [],
            'tag_ids',
            'Entries.invalid_tags',
            'Entries.some_tags_not_found',
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'category_ids' => $this->category_ids,
            'tag_ids' => $this->tag_ids,
        ];
    }
}
