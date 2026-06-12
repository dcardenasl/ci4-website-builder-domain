<?php

declare(strict_types=1);

namespace App\DTO\Request\Cms;

use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'EntrySetTagsRequest')]
readonly class EntrySetTagsRequestDTO extends BaseRequestDTO
{
    /** @var list<int> */
    public array $tag_ids;

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'tag_ids'   => 'required|is_list',
            'tag_ids.*' => 'required|is_natural_no_zero',
        ];
    }

    /** @param array<string, mixed> $data */
    protected function map(array $data): void
    {
        $raw = $data['tag_ids'] ?? [];
        $this->tag_ids = array_values(array_map('intval', is_array($raw) ? $raw : []));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['tag_ids' => $this->tag_ids];
    }
}
