<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class PublicEntryIndexRequestDTO extends BaseRequestDTO
{
    public string $lang;
    public string $collection_key;
    public int $page;
    public int $per_page;
    public ?string $category;
    public ?string $tag;
    public ?string $q;
    public string $order_by;
    public string $order_direction;
    public bool $include_listing_content;

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'lang'           => 'required|string|max_length[10]',
            'collection_key' => 'required|string|max_length[50]',
            'page'           => 'permit_empty|is_natural_no_zero',
            'per_page'       => 'permit_empty|is_natural_no_zero|less_than[101]',
            'limit'          => 'permit_empty|is_natural_no_zero|less_than[101]',
            'category'       => 'permit_empty|string|max_length[150]',
            'tag'            => 'permit_empty|string|max_length[100]',
            'q'              => 'permit_empty|string|max_length[255]',
            'order_by'       => 'permit_empty|in_list[published_at,sort_order,created_at,title]',
            'order_direction' => 'permit_empty|in_list[asc,desc,ASC,DESC]',
            'include'         => 'permit_empty|in_list[listing_content]',
        ];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
        $this->lang           = (string) ($data['lang'] ?? '');
        $this->collection_key = (string) ($data['collection_key'] ?? '');
        $this->page           = isset($data['page']) && $data['page'] !== '' ? (int) $data['page'] : 1;
        $perPage              = $data['per_page'] ?? ($data['limit'] ?? 20);
        $this->per_page       = $perPage !== '' ? (int) $perPage : 20;
        $this->category       = isset($data['category']) && $data['category'] !== '' ? (string) $data['category'] : null;
        $this->tag            = isset($data['tag']) && $data['tag'] !== '' ? (string) $data['tag'] : null;
        $this->q              = isset($data['q']) && $data['q'] !== '' ? (string) $data['q'] : null;
        $this->order_by       = (string) ($data['order_by'] ?? 'sort_order');
        $direction            = strtoupper((string) ($data['order_direction'] ?? 'ASC'));
        $this->order_direction = $direction === 'DESC' ? 'DESC' : 'ASC';
        $this->include_listing_content = (string) ($data['include'] ?? '') === 'listing_content';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'lang'           => $this->lang,
            'collection_key' => $this->collection_key,
            'page'           => $this->page,
            'per_page'       => $this->per_page,
            'category'       => $this->category,
            'tag'            => $this->tag,
            'q'              => $this->q,
            'order_by'       => $this->order_by,
            'order_direction' => $this->order_direction,
            'include'         => $this->include_listing_content ? 'listing_content' : null,
        ];
    }
}
