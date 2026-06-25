<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'MenuItemIndexRequest')]
readonly class MenuItemIndexRequestDTO extends BaseRequestDTO
{
    public int $page;
    public int $per_page;
    public ?string $search;
    public string $sort;
    public ?int $menu_id;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'page'      => 'permit_empty|is_natural_no_zero',
            'per_page'  => 'permit_empty|is_natural_no_zero|less_than[1001]',
            'search'    => 'permit_empty|string|max_length[100]',
            'sort'      => 'permit_empty|max_length[100]',
            'menu_id'   => 'permit_empty|integer',
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
        $menuId = $data['menu_id'] ?? ($data['filter']['menu_id'] ?? null);
        $this->menu_id = $menuId !== null && $menuId !== '' ? (int) $menuId : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'page' => $this->page,
            'per_page' => $this->per_page,
            'search' => $this->search,
            'sort' => $this->sort,
        ];

        if ($this->menu_id !== null) {
            $payload['filter'] = [
                'menu_id' => $this->menu_id,
            ];
        }

        return $payload;
    }
}
