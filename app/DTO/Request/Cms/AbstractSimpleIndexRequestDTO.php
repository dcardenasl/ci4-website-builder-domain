<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

/**
 * Shared shape for CMS "index" (list) request DTOs that only need
 * pagination + a free-text search + a sort key — no resource-specific
 * filters. A concrete subclass only needs its own `#[OA\Schema]` attribute;
 * override maxPerPage() if a resource needs a different upper bound (see
 * BlockTypeIndexRequestDTO).
 *
 * Resources with additional filters (category/tag/collection scoping, etc.)
 * do NOT extend this — see EntryIndexRequestDTO, MenuItemIndexRequestDTO, and
 * BlockInstanceIndexRequestDTO for the genuinely different shape. Extending
 * this class for a resource that later needs its own filter is fine: just
 * stop inheriting it and copy the four members back into the concrete class.
 */
abstract readonly class AbstractSimpleIndexRequestDTO extends BaseRequestDTO
{
    public int $page;
    public int $per_page;
    public ?string $search;
    public string $sort;

    protected static function maxPerPage(): int
    {
        return 1000;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'page'     => 'permit_empty|is_natural_no_zero',
            'per_page' => 'permit_empty|is_natural_no_zero|less_than[' . (static::maxPerPage() + 1) . ']',
            'search'   => 'permit_empty|string|max_length[100]',
            'sort'     => 'permit_empty|max_length[100]',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function map(array $data): void
    {
        $this->page = isset($data['page']) ? (int) $data['page'] : 1;
        $this->per_page = isset($data['per_page']) ? (int) $data['per_page'] : 20;
        $this->search = $data['search'] ?? null;
        $this->sort = (string) ($data['sort'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->per_page,
            'search' => $this->search,
            'sort' => $this->sort,
        ];
    }
}
