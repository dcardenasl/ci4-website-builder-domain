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

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'lang'           => 'required|string|max_length[10]',
            'collection_key' => 'required|string|max_length[50]',
            'page'           => 'permit_empty|is_natural_no_zero',
            'per_page'       => 'permit_empty|is_natural_no_zero|less_than[101]',
            'category'       => 'permit_empty|string|max_length[150]',
            'tag'            => 'permit_empty|string|max_length[100]',
        ];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
        $this->lang           = (string) ($data['lang'] ?? '');
        $this->collection_key = (string) ($data['collection_key'] ?? '');
        $this->page           = isset($data['page']) && $data['page'] !== '' ? (int) $data['page'] : 1;
        $this->per_page       = isset($data['per_page']) && $data['per_page'] !== '' ? (int) $data['per_page'] : 20;
        $this->category       = isset($data['category']) && $data['category'] !== '' ? (string) $data['category'] : null;
        $this->tag            = isset($data['tag']) && $data['tag'] !== '' ? (string) $data['tag'] : null;
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
        ];
    }
}
