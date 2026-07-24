<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EntryIndexRequest')]
readonly class EntryIndexRequestDTO extends BaseRequestDTO
{
    public int $page;
    public int $per_page;
    public ?string $search;
    public string $sort;
    public ?int $collection_id;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'page'          => 'permit_empty|is_natural_no_zero',
            'per_page'      => 'permit_empty|is_natural_no_zero|less_than[1001]',
            'search'        => 'permit_empty|string|max_length[100]',
            'sort'          => 'permit_empty|max_length[100]',
            'collection_id' => 'permit_empty|integer',
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
        $collectionId = $data['collection_id'] ?? ($data['filter']['collection_id'] ?? null);
        $this->collection_id = $collectionId !== null && $collectionId !== '' ? (int) $collectionId : null;
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

        if ($this->collection_id !== null) {
            $payload['filter'] = [
                'collection_id' => $this->collection_id,
            ];
        }

        return $payload;
    }
}
