<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;

readonly class PublicCategoryIndexRequestDTO extends BaseRequestDTO
{
    public string $lang;
    public string $collection_key;

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'lang'           => 'required|string|max_length[10]',
            'collection_key' => 'required|string|max_length[50]',
        ];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
        $this->lang           = (string) ($data['lang'] ?? '');
        $this->collection_key = (string) ($data['collection_key'] ?? '');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'lang'           => $this->lang,
            'collection_key' => $this->collection_key,
        ];
    }
}
